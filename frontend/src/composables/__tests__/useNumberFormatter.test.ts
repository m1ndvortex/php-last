import { describe, it, expect, beforeEach, vi } from "vitest";
import { useNumberFormatter } from "../useNumberFormatter";

// Mock the useI18n composable
vi.mock("vue-i18n", async () => {
  const actual = (await vi.importActual("vue-i18n")) as Record<string, unknown>;
  return {
    ...actual,
    useI18n: () => ({
      locale: { value: "en" },
    }),
  };
});

describe("useNumberFormatter", () => {
  let formatter: ReturnType<typeof useNumberFormatter>;

  beforeEach(() => {
    formatter = useNumberFormatter();
  });

  describe("toPersianNumerals", () => {
    it("should convert English numerals to Persian", () => {
      expect(formatter.toPersianNumerals("1234567890")).toBe("۱۲۳۴۵۶۷۸۹۰");
      expect(formatter.toPersianNumerals(12345)).toBe("۱۲۳۴۵");
    });
  });

  describe("toEnglishNumerals", () => {
    it("should convert Persian numerals to English", () => {
      expect(formatter.toEnglishNumerals("۱۲۳۴۵۶۷۸۹۰")).toBe("1234567890");
    });

    it("should convert Arabic numerals to English", () => {
      expect(formatter.toEnglishNumerals("١٢٣٤٥٦٧٨٩٠")).toBe("1234567890");
    });

    it("should handle mixed numerals", () => {
      expect(formatter.toEnglishNumerals("۱۲3٤5")).toBe("12345");
    });
  });

  describe("parseNumber", () => {
    it("should parse English numbers", () => {
      expect(formatter.parseNumber("123.45")).toBe(123.45);
      expect(formatter.parseNumber("1,234.56")).toBe(1234.56);
    });

    it("should parse Persian numbers", () => {
      expect(formatter.parseNumber("۱۲۳.۴۵")).toBe(123.45);
    });

    it("should return null for invalid input", () => {
      expect(formatter.parseNumber("")).toBe(null);
      expect(formatter.parseNumber("abc")).toBe(null);
      expect(formatter.parseNumber(null as unknown as string)).toBe(null);
    });
  });

  describe("formatNumber", () => {
    it("should format numbers with default options", () => {
      const result = formatter.formatNumber(1234.56);
      expect(result).toBe("1,234.56");
    });

    it("should handle null and undefined values", () => {
      expect(formatter.formatNumber(null as unknown as number)).toBe("");
      expect(formatter.formatNumber(undefined as unknown as number)).toBe("");
      expect(formatter.formatNumber("")).toBe("");
    });

    it("should format with custom precision", () => {
      const result = formatter.formatNumber(1234.5678, {
        maximumFractionDigits: 3,
      });
      expect(result).toBe("1,234.568");
    });
  });

  describe("formatFileSize", () => {
    it("should format file sizes correctly", () => {
      expect(formatter.formatFileSize(0)).toBe("0 B");
      expect(formatter.formatFileSize(1024)).toBe("1 KB");
      expect(formatter.formatFileSize(1024 * 1024)).toBe("1 MB");
      expect(formatter.formatFileSize(1024 * 1024 * 1024)).toBe("1 GB");
    });
  });

  describe("isValidNumber", () => {
    it("should validate numeric strings", () => {
      expect(formatter.isValidNumber("123")).toBe(true);
      expect(formatter.isValidNumber("123.45")).toBe(true);
      expect(formatter.isValidNumber("۱۲۳")).toBe(true);
      expect(formatter.isValidNumber("abc")).toBe(false);
      expect(formatter.isValidNumber("")).toBe(false);
    });
  });
});
