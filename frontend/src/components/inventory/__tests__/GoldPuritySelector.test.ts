import { describe, it, expect, beforeEach, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import GoldPuritySelector from "../GoldPuritySelector.vue";

// Mock vue-i18n
vi.mock("vue-i18n", () => ({
  useI18n: () => ({
    t: (key: string) => key,
    locale: { value: "en" },
  }),
}));

// Mock the API service
vi.mock("../../../services/api", () => ({
  default: {
    get: vi.fn().mockResolvedValue({
      data: {
        data: [
          { karat: 24, purity: 24.0, percentage: 99.9, display: "24.0K", label: "24K (99.9% gold)" },
          { karat: 22, purity: 22.0, percentage: 91.7, display: "22.0K", label: "22K (91.7% gold)" },
          { karat: 21, purity: 21.0, percentage: 87.5, display: "21.0K", label: "21K (87.5% gold)" },
          { karat: 18, purity: 18.0, percentage: 75.0, display: "18.0K", label: "18K (75.0% gold)" },
          { karat: 14, purity: 14.0, percentage: 58.3, display: "14.0K", label: "14K (58.3% gold)" },
        ],
      },
    }),
  },
}));

describe("GoldPuritySelector", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("renders select dropdown", () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
      },
    });

    expect(wrapper.find("select").exists()).toBe(true);
  });

  it("loads and displays gold purity options", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
      },
    });

    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    expect(options.length).toBeGreaterThan(1); // Should have placeholder + purity options
    expect(options[1].text()).toContain("24K");
    expect(options[2].text()).toContain("22K");
  });

  it("displays purity options with labels", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        showLabels: true,
      },
    });

    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    expect(options[1].text()).toContain("99.9% gold");
  });

  it("emits update:modelValue when selection changes", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
      },
    });

    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    await select.setValue("18");

    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([18]);
  });

  it("emits change event with purity object", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
      },
    });

    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    await select.setValue("18");

    expect(wrapper.emitted("change")).toBeTruthy();
    expect(wrapper.emitted("change")?.[0][0]).toMatchObject({
      karat: 18,
      purity: 18.0,
      percentage: 75.0,
    });
  });

  it("supports custom purity input", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        allowCustom: true,
      },
    });

    expect(wrapper.find('input[type="number"]').exists()).toBe(true);

    const customInput = wrapper.find('input[type="number"]');
    await customInput.setValue("19.5");

    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([19.5]);
  });

  it("validates custom purity range", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        allowCustom: true,
      },
    });

    const customInput = wrapper.find('input[type="number"]');
    await customInput.setValue("25"); // Invalid: > 24

    expect(wrapper.find(".error-message").exists()).toBe(true);
    expect(wrapper.find(".error-message").text()).toContain("gold_purity.invalid_range");
  });

  it("displays Persian numerals in Persian locale", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
      },
      global: {
        mocks: {
          $i18n: {
            locale: { value: "fa" },
            t: (key: string) => key,
          },
        },
      },
    });

    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    // Should contain Persian numerals
    expect(options[1].text()).toMatch(/[۰-۹]/);
  });

  it("shows purity with عیار in Persian locale", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
      },
      global: {
        mocks: {
          $i18n: {
            locale: { value: "fa" },
            t: (key: string) => key,
          },
        },
      },
    });

    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    expect(options[1].text()).toContain("عیار");
  });

  it("handles loading state", () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        loading: true,
      },
    });

    expect(wrapper.find("select").attributes("disabled")).toBeDefined();
    expect(wrapper.find(".loading-spinner").exists()).toBe(true);
  });

  it("shows error state when API fails", async () => {
    const mockApiGet = vi.fn().mockRejectedValue(new Error("API Error"));
    vi.mocked(require("../../../services/api").default.get).mockImplementation(mockApiGet);

    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
      },
    });

    await wrapper.vm.$nextTick();

    expect(wrapper.find(".error-message").exists()).toBe(true);
  });

  it("supports disabled state", () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        disabled: true,
      },
    });

    const select = wrapper.find("select");
    expect(select.attributes("disabled")).toBeDefined();
  });

  it("shows placeholder text", () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        placeholder: "Select gold purity",
      },
    });

    const placeholderOption = wrapper.find("option[value='']");
    expect(placeholderOption.text()).toBe("Select gold purity");
  });

  it("validates required selection", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        required: true,
      },
    });

    const select = wrapper.find("select");
    await select.trigger("blur");

    expect(wrapper.find(".error-message").exists()).toBe(true);
  });

  it("supports step input for custom purity", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        allowCustom: true,
        step: 0.1,
      },
    });

    const customInput = wrapper.find('input[type="number"]');
    expect(customInput.attributes("step")).toBe("0.1");
  });

  it("shows purity percentage when enabled", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        showPercentage: true,
      },
    });

    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    expect(options[1].text()).toContain("%");
  });

  it("filters purity options by range", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        minPurity: 18,
        maxPurity: 24,
      },
    });

    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    // Should only show purities between 18 and 24
    const purityValues = options
      .slice(1) // Skip placeholder
      .map((option) => parseFloat(option.element.value));

    purityValues.forEach((purity) => {
      expect(purity).toBeGreaterThanOrEqual(18);
      expect(purity).toBeLessThanOrEqual(24);
    });
  });

  it("supports clear functionality", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: 18,
        clearable: true,
      },
    });

    const clearButton = wrapper.find(".clear-button");
    await clearButton.trigger("click");

    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([null]);
  });

  it("shows purity recommendation", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        showRecommendation: true,
        categoryType: "rings",
      },
    });

    expect(wrapper.find(".purity-recommendation").exists()).toBe(true);
    expect(wrapper.text()).toContain("gold_purity.recommended");
  });

  it("handles keyboard input for custom purity", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        allowCustom: true,
      },
    });

    const customInput = wrapper.find('input[type="number"]');
    await customInput.trigger("keydown", { key: "ArrowUp" });

    // Should increment the value
    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
  });

  it("applies correct CSS classes", () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        class: "custom-class",
      },
    });

    expect(wrapper.classes()).toContain("custom-class");
    expect(wrapper.find("select").classes()).toContain("form-input");
  });

  it("supports tooltip with purity information", async () => {
    const wrapper = mount(GoldPuritySelector, {
      props: {
        modelValue: null,
        showTooltip: true,
      },
    });

    const select = wrapper.find("select");
    await select.trigger("mouseenter");

    expect(wrapper.find(".tooltip").exists()).toBe(true);
  });
});