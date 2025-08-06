import { computed } from "vue";
import { useI18n } from "vue-i18n";

export function useLocale() {
  const { locale, t } = useI18n();

  const isRTL = computed(() => locale.value === "fa");
  const currentLanguage = computed(() => locale.value);

  const formatNumber = (num: number): string => {
    if (locale.value === "fa") {
      // Convert to Persian numerals
      return num.toLocaleString("fa-IR");
    }
    return num.toLocaleString("en-US");
  };

  const formatCurrency = (amount: number, currency = "USD"): string => {
    if (locale.value === "fa") {
      return new Intl.NumberFormat("fa-IR", {
        style: "currency",
        currency: currency === "USD" ? "IRR" : currency,
      }).format(amount);
    }
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency,
    }).format(amount);
  };

  const formatDate = (date: Date | string): string => {
    const dateObj = typeof date === "string" ? new Date(date) : date;

    if (locale.value === "fa") {
      return new Intl.DateTimeFormat("fa-IR-u-ca-persian").format(dateObj);
    }
    return new Intl.DateTimeFormat("en-US").format(dateObj);
  };

  const getDirection = () => (isRTL.value ? "rtl" : "ltr");

  const switchLanguage = (newLocale: string) => {
    locale.value = newLocale;
    document.documentElement.dir = newLocale === "fa" ? "rtl" : "ltr";
    document.documentElement.lang = newLocale;
    localStorage.setItem("preferred-language", newLocale);
  };

  // Category-specific localization methods
  const getLocalizedCategoryName = (category: any): string => {
    if (!category) return "";
    
    if (locale.value === "fa" && category.name_persian) {
      return category.name_persian;
    }
    return category.name || "";
  };

  const getLocalizedCategoryDescription = (category: any): string => {
    if (!category) return "";
    
    if (locale.value === "fa" && category.description_persian) {
      return category.description_persian;
    }
    return category.description || "";
  };

  const formatGoldPurity = (purity: number): string => {
    if (!purity) return "";
    
    if (locale.value === "fa") {
      // Format with Persian numerals and terminology
      const persianPurity = formatNumber(purity);
      return `${persianPurity} ${t("inventory.categories.gold_purity_karat")}`;
    }
    return `${purity}K`;
  };

  const formatGoldPurityDisplay = (purity: number): string => {
    if (!purity) return "";
    
    const formattedPurity = formatGoldPurity(purity);
    const terminology = t("inventory.categories.gold_purity_terminology");
    
    if (locale.value === "fa") {
      return `${terminology}: ${formattedPurity}`;
    }
    return `${terminology}: ${formattedPurity}`;
  };

  const getCategoryPath = (category: any, categories: any[] = []): string => {
    if (!category) return "";
    
    const path: string[] = [];
    let current = category;
    
    while (current) {
      path.unshift(getLocalizedCategoryName(current));
      current = categories.find(c => c.id === current.parent_id);
    }
    
    const separator = locale.value === "fa" ? " ← " : " → ";
    return path.join(separator);
  };

  const formatPersianNumerals = (text: string): string => {
    if (locale.value !== "fa") return text;
    
    const persianDigits = ["۰", "۱", "۲", "۳", "۴", "۵", "۶", "۷", "۸", "۹"];
    return text.replace(/\d/g, (digit) => persianDigits[parseInt(digit)]);
  };

  return {
    locale,
    isRTL,
    currentLanguage,
    formatNumber,
    formatCurrency,
    formatDate,
    getDirection,
    switchLanguage,
    getLocalizedCategoryName,
    getLocalizedCategoryDescription,
    formatGoldPurity,
    formatGoldPurityDisplay,
    getCategoryPath,
    formatPersianNumerals,
    t,
  };
}
