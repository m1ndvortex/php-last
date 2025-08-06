import { computed } from "vue";
import { useI18n } from "vue-i18n";

export function useNumberFormatter() {
  const { locale } = useI18n();

  // Persian numerals mapping
  const persianNumerals = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
  const englishNumerals = ["0", "1", "2", "3", "4", "5", "6", "7", "8", "9"];

  // Arabic numerals mapping (sometimes used in Persian contexts)
  const arabicNumerals = ["٠", "١", "٢", "٣", "٤", "٥", "٦", "٧", "٨", "٩"];

  /**
   * Convert English numerals to Persian numerals
   */
  const toPersianNumerals = (input: string | number): string => {
    const str = input.toString();
    return str.replace(/[0-9]/g, (match) => {
      const index = parseInt(match);
      return persianNumerals[index];
    });
  };

  /**
   * Convert Persian numerals to English numerals
   */
  const toEnglishNumerals = (input: string): string => {
    let result = input;

    // Convert Persian numerals
    persianNumerals.forEach((persian, index) => {
      const regex = new RegExp(persian, "g");
      result = result.replace(regex, englishNumerals[index]);
    });

    // Convert Arabic numerals
    arabicNumerals.forEach((arabic, index) => {
      const regex = new RegExp(arabic, "g");
      result = result.replace(regex, englishNumerals[index]);
    });

    return result;
  };

  /**
   * Format number based on current locale
   */
  const formatNumber = (
    value: number | string,
    options?: {
      minimumFractionDigits?: number;
      maximumFractionDigits?: number;
      useGrouping?: boolean;
      style?: "decimal" | "currency" | "percent";
      currency?: string;
    },
  ): string => {
    if (value === null || value === undefined || value === "") {
      return "";
    }

    const numValue =
      typeof value === "string" ? parseFloat(toEnglishNumerals(value)) : value;

    if (isNaN(numValue)) {
      return value.toString();
    }

    const defaultOptions = {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
      useGrouping: true,
      style: "decimal" as const,
      currency: "IRR",
    };

    const formatOptions = { ...defaultOptions, ...options };

    try {
      let formatted: string;

      if (locale.value === "fa") {
        // For Persian locale, use custom formatting
        if (formatOptions.style === "currency") {
          // Format currency in Persian style
          const currencyFormatted = new Intl.NumberFormat("en-US", {
            minimumFractionDigits: formatOptions.minimumFractionDigits,
            maximumFractionDigits: formatOptions.maximumFractionDigits,
            useGrouping: formatOptions.useGrouping,
          }).format(numValue);

          const persianFormatted = toPersianNumerals(currencyFormatted);

          // Add currency symbol based on currency code
          switch (formatOptions.currency) {
            case "IRR":
              return `${persianFormatted} ریال`;
            case "USD":
              return `${persianFormatted} دلار`;
            case "EUR":
              return `${persianFormatted} یورو`;
            default:
              return `${persianFormatted} ${formatOptions.currency}`;
          }
        } else if (formatOptions.style === "percent") {
          const percentFormatted = new Intl.NumberFormat("en-US", {
            style: "percent",
            minimumFractionDigits: formatOptions.minimumFractionDigits,
            maximumFractionDigits: formatOptions.maximumFractionDigits,
          }).format(numValue / 100);

          return toPersianNumerals(percentFormatted);
        } else {
          // Decimal formatting
          formatted = new Intl.NumberFormat("en-US", {
            minimumFractionDigits: formatOptions.minimumFractionDigits,
            maximumFractionDigits: formatOptions.maximumFractionDigits,
            useGrouping: formatOptions.useGrouping,
          }).format(numValue);

          return toPersianNumerals(formatted);
        }
      } else {
        // For English locale, use standard Intl formatting
        formatted = new Intl.NumberFormat("en-US", formatOptions).format(
          numValue,
        );
        return formatted;
      }
    } catch (error) {
      console.error("Error formatting number:", error);
      return value.toString();
    }
  };

  /**
   * Format currency based on current locale
   */
  const formatCurrency = (
    value: number | string,
    currency: string = "IRR",
    options?: {
      minimumFractionDigits?: number;
      maximumFractionDigits?: number;
    },
  ): string => {
    return formatNumber(value, {
      style: "currency",
      currency,
      ...options,
    });
  };

  /**
   * Format percentage based on current locale
   */
  const formatPercentage = (
    value: number | string,
    options?: {
      minimumFractionDigits?: number;
      maximumFractionDigits?: number;
    },
  ): string => {
    return formatNumber(value, {
      style: "percent",
      ...options,
    });
  };

  /**
   * Parse number from localized string
   */
  const parseNumber = (value: string): number | null => {
    if (!value || typeof value !== "string") {
      return null;
    }

    try {
      // Convert to English numerals first
      const englishValue = toEnglishNumerals(value);

      // Remove currency symbols and other non-numeric characters except decimal point and minus
      const cleanValue = englishValue.replace(/[^\d.-]/g, "");

      const parsed = parseFloat(cleanValue);
      return isNaN(parsed) ? null : parsed;
    } catch (error) {
      console.error("Error parsing number:", error);
      return null;
    }
  };

  /**
   * Format file size in appropriate units
   */
  const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return formatNumber(0) + " B";

    const k = 1024;
    const sizes =
      locale.value === "fa"
        ? ["بایت", "کیلوبایت", "مگابایت", "گیگابایت", "ترابایت"]
        : ["B", "KB", "MB", "GB", "TB"];

    const i = Math.floor(Math.log(bytes) / Math.log(k));
    const size = bytes / Math.pow(k, i);

    return `${formatNumber(size, { maximumFractionDigits: 1 })} ${sizes[i]}`;
  };

  /**
   * Format duration in human readable format
   */
  const formatDuration = (seconds: number): string => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    if (locale.value === "fa") {
      if (hours > 0) {
        return `${toPersianNumerals(hours.toString())} ساعت و ${toPersianNumerals(minutes.toString())} دقیقه`;
      } else if (minutes > 0) {
        return `${toPersianNumerals(minutes.toString())} دقیقه و ${toPersianNumerals(secs.toString())} ثانیه`;
      } else {
        return `${toPersianNumerals(secs.toString())} ثانیه`;
      }
    } else {
      if (hours > 0) {
        return `${hours}h ${minutes}m`;
      } else if (minutes > 0) {
        return `${minutes}m ${secs}s`;
      } else {
        return `${secs}s`;
      }
    }
  };

  /**
   * Check if current locale uses Persian numerals
   */
  const usesPersianNumerals = computed(() => locale.value === "fa");

  /**
   * Get decimal separator for current locale
   */
  const getDecimalSeparator = (): string => {
    return locale.value === "fa" ? "/" : ".";
  };

  /**
   * Get thousands separator for current locale
   */
  const getThousandsSeparator = (): string => {
    return locale.value === "fa" ? "،" : ",";
  };

  /**
   * Validate numeric input
   */
  const isValidNumber = (value: string): boolean => {
    if (!value) return false;

    const englishValue = toEnglishNumerals(value);
    const cleanValue = englishValue.replace(/[^\d.-]/g, "");

    return !isNaN(parseFloat(cleanValue));
  };

  /**
   * Format number for input fields (removes formatting for editing)
   */
  const formatForInput = (value: number | string): string => {
    if (value === null || value === undefined || value === "") {
      return "";
    }

    const numValue =
      typeof value === "string" ? parseFloat(toEnglishNumerals(value)) : value;

    if (isNaN(numValue)) {
      return "";
    }

    if (locale.value === "fa") {
      return toPersianNumerals(numValue.toString());
    } else {
      return numValue.toString();
    }
  };

  /**
   * Format gold purity for display
   */
  const formatGoldPurity = (purity: number | string): string => {
    if (!purity) return "";
    
    const numValue = typeof purity === "string" ? parseFloat(toEnglishNumerals(purity)) : purity;
    if (isNaN(numValue)) return "";

    if (locale.value === "fa") {
      const persianPurity = toPersianNumerals(numValue.toFixed(3));
      return `${persianPurity} عیار`;
    } else {
      return `${numValue.toFixed(3)}K`;
    }
  };

  /**
   * Format gold purity with terminology
   */
  const formatGoldPurityWithLabel = (purity: number | string, label?: string): string => {
    if (!purity) return "";
    
    const formattedPurity = formatGoldPurity(purity);
    const terminology = label || (locale.value === "fa" ? "عیار طلا" : "Gold Purity");
    
    if (locale.value === "fa") {
      return `${terminology}: ${formattedPurity}`;
    }
    return `${terminology}: ${formattedPurity}`;
  };

  /**
   * Get standard gold purity options with localized labels
   */
  const getGoldPurityOptions = () => {
    const options = [
      { value: 10, label: locale.value === "fa" ? "طلای ۱۰ عیار" : "10K Gold" },
      { value: 14, label: locale.value === "fa" ? "طلای ۱۴ عیار" : "14K Gold" },
      { value: 18, label: locale.value === "fa" ? "طلای ۱۸ عیار" : "18K Gold" },
      { value: 21, label: locale.value === "fa" ? "طلای ۲۱ عیار" : "21K Gold" },
      { value: 22, label: locale.value === "fa" ? "طلای ۲۲ عیار" : "22K Gold" },
      { value: 24, label: locale.value === "fa" ? "طلای ۲۴ عیار" : "24K Gold" },
    ];

    return options.map(option => ({
      ...option,
      displayValue: locale.value === "fa" ? toPersianNumerals(option.value.toString()) : option.value.toString()
    }));
  };

  /**
   * Format category data with Persian numerals if needed
   */
  const formatCategoryData = (data: any): any => {
    if (!data || locale.value !== "fa") return data;

    const formatted = { ...data };

    // Format numeric fields
    if (formatted.sort_order) {
      formatted.sort_order_display = toPersianNumerals(formatted.sort_order.toString());
    }

    if (formatted.item_count) {
      formatted.item_count_display = toPersianNumerals(formatted.item_count.toString());
    }

    if (formatted.subcategory_count) {
      formatted.subcategory_count_display = toPersianNumerals(formatted.subcategory_count.toString());
    }

    if (formatted.default_gold_purity) {
      formatted.gold_purity_display = formatGoldPurity(formatted.default_gold_purity);
    }

    return formatted;
  };

  return {
    formatNumber,
    formatCurrency,
    formatPercentage,
    formatFileSize,
    formatDuration,
    formatForInput,
    formatGoldPurity,
    formatGoldPurityWithLabel,
    formatCategoryData,
    parseNumber,
    toPersianNumerals,
    toEnglishNumerals,
    isValidNumber,
    usesPersianNumerals,
    getDecimalSeparator,
    getThousandsSeparator,
    getGoldPurityOptions,
  };
}
