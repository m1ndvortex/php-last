import { ref, computed } from "vue";
import { useSettingsStore } from "@/stores/settings";
import { apiService } from "@/services/api";

export interface GoldPricingParams {
  weight: number;
  goldPricePerGram: number;
  laborPercentage: number;
  profitPercentage: number;
  taxPercentage: number;
  quantity: number;
}

export interface PriceBreakdown {
  baseGoldCost: number;
  laborCost: number;
  profit: number;
  tax: number;
  unitPrice: number;
  totalPrice: number;
}

export interface DefaultPricingSettings {
  defaultLaborPercentage: number;
  defaultProfitPercentage: number;
  defaultTaxPercentage: number;
}

export function useGoldPricing() {
  const settingsStore = useSettingsStore();
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Default settings state
  const defaultSettings = ref<DefaultPricingSettings>({
    defaultLaborPercentage: 10,
    defaultProfitPercentage: 15,
    defaultTaxPercentage: 9,
  });

  // Calculate item price using Persian jewelry formula
  const calculateItemPrice = (params: GoldPricingParams): PriceBreakdown => {
    const {
      weight,
      goldPricePerGram,
      laborPercentage,
      profitPercentage,
      taxPercentage,
      quantity,
    } = params;

    // Validate inputs
    if (weight <= 0 || goldPricePerGram <= 0 || quantity <= 0) {
      throw new Error(
        "Weight, gold price per gram, and quantity must be greater than zero",
      );
    }

    // Persian jewelry pricing formula implementation

    // Step 1: Base gold cost (Weight × Gold Price per gram)
    const baseGoldCost = weight * goldPricePerGram;

    // Step 2: Labor cost (percentage of base gold cost)
    const laborCost = baseGoldCost * (laborPercentage / 100);

    // Step 3: Subtotal before profit and tax
    const subtotal = baseGoldCost + laborCost;

    // Step 4: Profit (percentage of subtotal)
    const profit = subtotal * (profitPercentage / 100);

    // Step 5: Subtotal with profit
    const subtotalWithProfit = subtotal + profit;

    // Step 6: Tax (percentage of subtotal with profit)
    const tax = subtotalWithProfit * (taxPercentage / 100);

    // Step 7: Final price per unit
    const unitPrice = subtotalWithProfit + tax;

    // Step 8: Total price for quantity
    const totalPrice = unitPrice * quantity;

    return {
      baseGoldCost: Math.round(baseGoldCost * quantity * 100) / 100,
      laborCost: Math.round(laborCost * quantity * 100) / 100,
      profit: Math.round(profit * quantity * 100) / 100,
      tax: Math.round(tax * quantity * 100) / 100,
      unitPrice: Math.round(unitPrice * 100) / 100,
      totalPrice: Math.round(totalPrice * 100) / 100,
    };
  };

  // Load default pricing settings from API
  const loadDefaultSettings = async (): Promise<DefaultPricingSettings> => {
    try {
      loading.value = true;
      error.value = null;

      // Fetch pricing percentages from the dedicated endpoint
      const result = await settingsStore.getDefaultPricingPercentages();

      if (result.success) {
        defaultSettings.value = {
          defaultLaborPercentage: result.data.labor_percentage || 10,
          defaultProfitPercentage: result.data.profit_percentage || 15,
          defaultTaxPercentage: result.data.tax_percentage || 9,
        };
      }

      return defaultSettings.value;
    } catch (err: any) {
      error.value = err.message || "Failed to load default pricing settings";

      // Return hardcoded defaults as fallback
      return {
        defaultLaborPercentage: 10,
        defaultProfitPercentage: 15,
        defaultTaxPercentage: 9,
      };
    } finally {
      loading.value = false;
    }
  };

  // Validate pricing parameters
  const validatePricingParams = (
    params: Partial<GoldPricingParams>,
  ): Record<string, string> => {
    const errors: Record<string, string> = {};

    if (!params.weight || params.weight <= 0) {
      errors.weight = "Weight must be greater than zero";
    }

    if (!params.goldPricePerGram || params.goldPricePerGram <= 0) {
      errors.goldPricePerGram = "Gold price per gram must be greater than zero";
    }

    if (!params.quantity || params.quantity <= 0) {
      errors.quantity = "Quantity must be greater than zero";
    }

    if (params.laborPercentage !== undefined && params.laborPercentage < 0) {
      errors.laborPercentage = "Labor percentage cannot be negative";
    }

    if (params.profitPercentage !== undefined && params.profitPercentage < 0) {
      errors.profitPercentage = "Profit percentage cannot be negative";
    }

    if (params.taxPercentage !== undefined && params.taxPercentage < 0) {
      errors.taxPercentage = "Tax percentage cannot be negative";
    }

    return errors;
  };

  // Format currency for display
  const formatCurrency = (amount: number): string => {
    const locale =
      settingsStore.businessConfig?.default_language === "fa"
        ? "fa-IR"
        : "en-US";
    const currency = settingsStore.businessConfig?.default_currency || "USD";

    return new Intl.NumberFormat(locale, {
      style: "currency",
      currency: currency,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(amount);
  };

  return {
    loading,
    error,
    defaultSettings,
    calculateItemPrice,
    loadDefaultSettings,
    validatePricingParams,
    formatCurrency,
  };
}
