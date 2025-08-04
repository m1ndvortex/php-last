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

  return {
    locale,
    isRTL,
    currentLanguage,
    formatNumber,
    formatCurrency,
    formatDate,
    getDirection,
    switchLanguage,
    t,
  };
}
