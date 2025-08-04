import { useI18n } from "vue-i18n";
import * as yup from "yup";
import { useNumberFormatter } from "./useNumberFormatter";
import { useCalendarConversion } from "./useCalendarConversion";

export function useLocaleValidation() {
  const { t, locale } = useI18n();
  const { parseNumber, toEnglishNumerals } = useNumberFormatter();
  const { parseDate } = useCalendarConversion();

  /**
   * Create localized validation messages
   */
  const createValidationMessages = () => {
    return {
      mixed: {
        required: t("validation.required"),
        notType: t("validation.invalid_type"),
      },
      string: {
        min: t("validation.string.min"),
        max: t("validation.string.max"),
        email: t("validation.string.email"),
        url: t("validation.string.url"),
        matches: t("validation.string.matches"),
      },
      number: {
        min: t("validation.number.min"),
        max: t("validation.number.max"),
        positive: t("validation.number.positive"),
        negative: t("validation.number.negative"),
        integer: t("validation.number.integer"),
      },
      date: {
        min: t("validation.date.min"),
        max: t("validation.date.max"),
      },
      array: {
        min: t("validation.array.min"),
        max: t("validation.array.max"),
      },
      object: {
        noUnknown: t("validation.object.no_unknown"),
      },
    };
  };

  /**
   * Configure yup with localized messages
   */
  const configureYup = () => {
    yup.setLocale(createValidationMessages());
  };

  /**
   * Create a localized string schema
   */
  const string = () => {
    return yup.string().transform((value) => {
      if (typeof value === "string" && locale.value === "fa") {
        // Convert Persian/Arabic numerals to English for validation
        return toEnglishNumerals(value);
      }
      return value;
    });
  };

  /**
   * Create a localized number schema
   */
  const number = () => {
    return yup.number().transform((value, originalValue) => {
      if (typeof originalValue === "string") {
        const parsed = parseNumber(originalValue);
        return parsed !== null ? parsed : NaN;
      }
      return value;
    });
  };

  /**
   * Create a localized date schema
   */
  const date = () => {
    return yup.date().transform((value, originalValue) => {
      if (typeof originalValue === "string") {
        const parsed = parseDate(originalValue);
        return parsed || new Date("invalid");
      }
      return value;
    });
  };

  /**
   * Email validation with Persian support
   */
  const email = () => {
    return string()
      .email()
      .matches(
        /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
        t("validation.string.email"),
      );
  };

  /**
   * Phone number validation (supports Iranian phone numbers)
   */
  const phone = () => {
    const iranianPhoneRegex = /^(\+98|0)?9\d{9}$/;
    const internationalPhoneRegex = /^\+?[1-9]\d{1,14}$/;

    return string().test("phone", t("validation.phone"), (value) => {
      if (!value) return true; // Let required() handle empty values

      const cleanValue = toEnglishNumerals(value).replace(/[\s-()]/g, "");

      if (locale.value === "fa") {
        return iranianPhoneRegex.test(cleanValue);
      } else {
        return internationalPhoneRegex.test(cleanValue);
      }
    });
  };

  /**
   * National ID validation (Iranian Melli Code)
   */
  const nationalId = () => {
    return string().test(
      "national-id",
      t("validation.national_id"),
      (value) => {
        if (!value) return true; // Let required() handle empty values

        const cleanValue = toEnglishNumerals(value).replace(/\D/g, "");

        if (locale.value === "fa") {
          // Iranian national ID validation
          if (cleanValue.length !== 10) return false;

          const check = parseInt(cleanValue[9]);
          let sum = 0;

          for (let i = 0; i < 9; i++) {
            sum += parseInt(cleanValue[i]) * (10 - i);
          }

          const remainder = sum % 11;

          return (
            (remainder < 2 && check === remainder) ||
            (remainder >= 2 && check === 11 - remainder)
          );
        }

        return true; // For other locales, just check if it's numeric
      },
    );
  };

  /**
   * Postal code validation
   */
  const postalCode = () => {
    return string().test(
      "postal-code",
      t("validation.postal_code"),
      (value) => {
        if (!value) return true; // Let required() handle empty values

        const cleanValue = toEnglishNumerals(value).replace(/\D/g, "");

        if (locale.value === "fa") {
          // Iranian postal code: 10 digits
          return /^\d{10}$/.test(cleanValue);
        } else {
          // International postal codes: varies by country
          return /^[A-Za-z0-9\s-]{3,10}$/.test(value);
        }
      },
    );
  };

  /**
   * Currency validation
   */
  const currency = (min?: number, max?: number) => {
    let schema = number();

    if (min !== undefined) {
      schema = schema.min(min, t("validation.currency.min", { min }));
    }

    if (max !== undefined) {
      schema = schema.max(max, t("validation.currency.max", { max }));
    }

    return schema.positive(t("validation.currency.positive"));
  };

  /**
   * Percentage validation
   */
  const percentage = (min: number = 0, max: number = 100) => {
    return number()
      .min(min, t("validation.percentage.min", { min }))
      .max(max, t("validation.percentage.max", { max }));
  };

  /**
   * Weight validation (for jewelry)
   */
  const weight = (min?: number, max?: number) => {
    let schema = number().positive(t("validation.weight.positive"));

    if (min !== undefined) {
      schema = schema.min(min, t("validation.weight.min", { min }));
    }

    if (max !== undefined) {
      schema = schema.max(max, t("validation.weight.max", { max }));
    }

    return schema;
  };

  /**
   * Gold purity validation (karat or percentage)
   */
  const goldPurity = () => {
    return number()
      .min(0, t("validation.gold_purity.min"))
      .max(24, t("validation.gold_purity.max"))
      .test("valid-purity", t("validation.gold_purity.invalid"), (value) => {
        if (value === undefined || value === null) return true;

        // Common gold purities: 8, 9, 10, 14, 18, 21, 22, 24 karat
        const validPurities = [8, 9, 10, 14, 18, 21, 22, 24];
        return validPurities.includes(Math.round(value));
      });
  };

  /**
   * SKU validation
   */
  const sku = () => {
    return string()
      .matches(/^[A-Za-z0-9-_]+$/, t("validation.sku.format"))
      .min(3, t("validation.sku.min"))
      .max(50, t("validation.sku.max"));
  };

  /**
   * Password validation with strength requirements
   */
  const password = (minLength: number = 8) => {
    return string()
      .min(minLength, t("validation.password.min", { min: minLength }))
      .matches(/[a-z]/, t("validation.password.lowercase"))
      .matches(/[A-Z]/, t("validation.password.uppercase"))
      .matches(/\d/, t("validation.password.number"))
      .matches(/[!@#$%^&*(),.?":{}|<>]/, t("validation.password.special"));
  };

  /**
   * Confirm password validation
   */
  const confirmPassword = (passwordField: string = "password") => {
    return string()
      .required(t("validation.confirm_password.required"))
      .test(
        "passwords-match",
        t("validation.confirm_password.match"),
        function (value) {
          return this.parent[passwordField] === value;
        },
      );
  };

  /**
   * File validation
   */
  const file = (options?: {
    maxSize?: number; // in bytes
    allowedTypes?: string[];
    required?: boolean;
  }) => {
    const { maxSize, allowedTypes, required = false } = options || {};

    return yup.mixed().test("file-validation", "", function (value) {
      if (!value && !required) return true;
      if (!value && required) {
        return this.createError({ message: t("validation.file.required") });
      }

      const file = value as File;

      if (maxSize && file.size > maxSize) {
        return this.createError({
          message: t("validation.file.max_size", {
            size: Math.round(maxSize / 1024 / 1024),
          }),
        });
      }

      if (allowedTypes && !allowedTypes.includes(file.type)) {
        return this.createError({
          message: t("validation.file.allowed_types", {
            types: allowedTypes.join(", "),
          }),
        });
      }

      return true;
    });
  };

  /**
   * Image file validation
   */
  const image = (maxSize: number = 5 * 1024 * 1024) => {
    // 5MB default
    return file({
      maxSize,
      allowedTypes: ["image/jpeg", "image/png", "image/gif", "image/webp"],
    });
  };

  /**
   * Create validation schema for common forms
   */
  const createCommonSchemas = () => {
    return {
      // User registration/profile
      userProfile: yup.object({
        name: string().required().min(2).max(100),
        email: email().required(),
        phone: phone(),
        password: password(),
        confirmPassword: confirmPassword(),
      }),

      // Customer form
      customer: yup.object({
        name: string().required().min(2).max(100),
        email: email(),
        phone: phone(),
        address: string().max(500),
        nationalId: nationalId(),
        postalCode: postalCode(),
      }),

      // Inventory item
      inventoryItem: yup.object({
        name: string().required().min(2).max(200),
        sku: sku().required(),
        weight: weight().required(),
        goldPurity: goldPurity(),
        unitPrice: currency().required(),
        costPrice: currency().required(),
        quantity: number().required().min(0),
      }),

      // Invoice
      invoice: yup.object({
        customerId: number().required(),
        issueDate: date().required(),
        dueDate: date().required().min(yup.ref("issueDate")),
        items: yup.array().min(1, t("validation.invoice.min_items")),
      }),

      // Login
      login: yup.object({
        email: email().required(),
        password: string().required(),
      }),

      // Change password
      changePassword: yup.object({
        currentPassword: string().required(),
        newPassword: password(),
        confirmPassword: confirmPassword("newPassword"),
      }),
    };
  };

  // Initialize yup with localized messages
  configureYup();

  return {
    // Schema creators
    string,
    number,
    date,
    email,
    phone,
    nationalId,
    postalCode,
    currency,
    percentage,
    weight,
    goldPurity,
    sku,
    password,
    confirmPassword,
    file,
    image,

    // Common schemas
    createCommonSchemas,

    // Utilities
    configureYup,
    createValidationMessages,
  };
}
