import { describe, it, expect, beforeEach, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createI18n } from "vue-i18n";
import GoldPuritySelector from "../GoldPuritySelector.vue";
import { mockGoldPurityOptions } from "@/test-utils/mockData";

const i18n = createI18n({
  legacy: false,
  locale: "en",
  messages: {
    en: {
      inventory: {
        categories: {
          gold_purity_placeholder: "Select gold purity",
        },
        gold_purity_help: "Select the gold purity for this item",
      },
      common: {
        custom: "Custom",
      },
    },
  },
});

describe("GoldPuritySelector", () => {
  let pinia: any;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
  });

  const createWrapper = (props = {}) => {
    return mount(GoldPuritySelector, {
      props,
      global: {
        plugins: [pinia, i18n],
      },
    });
  };

  it("renders select element with placeholder", async () => {
    const wrapper = createWrapper();

    const select = wrapper.find("select");
    expect(select.exists()).toBe(true);

    const placeholderOption = wrapper.find("option[value='']");
    expect(placeholderOption.exists()).toBe(true);
    expect(placeholderOption.text()).toBe(
      "inventory.categories.gold_purity_placeholder",
    );
  });

  it("displays standard gold purity options", async () => {
    const wrapper = createWrapper();
    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    expect(options.length).toBeGreaterThan(2); // placeholder + options + custom

    // Should have custom option
    const customOption = options.find(
      (option) => option.attributes("value") === "custom",
    );
    expect(customOption?.text()).toBe("common.custom");
  });

  it("emits update:modelValue when standard option is selected", async () => {
    const wrapper = createWrapper();
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    await select.setValue("18");

    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([18]);
  });

  it("shows custom input when custom option is selected", async () => {
    const wrapper = createWrapper();
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    await select.setValue("custom");

    await wrapper.vm.$nextTick();

    const customInput = wrapper.find('input[type="number"]');
    expect(customInput.exists()).toBe(true);
  });

  it("emits update:modelValue when custom value is entered", async () => {
    const wrapper = createWrapper();
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    await select.setValue("custom");
    await wrapper.vm.$nextTick();

    const customInput = wrapper.find('input[type="number"]');
    await customInput.setValue(20.5);

    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    // Should emit the custom value
    const emissions = wrapper.emitted("update:modelValue") as any[];
    expect(emissions[emissions.length - 1]).toEqual([20.5]);
  });

  it("initializes with correct value when modelValue is provided", async () => {
    const wrapper = createWrapper({ modelValue: 18 });
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    expect(select.element.value).toBe("18");
  });

  it("initializes with custom input for non-standard values", async () => {
    const wrapper = createWrapper({ modelValue: 20.5 });
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    expect(select.element.value).toBe("custom");

    const customInput = wrapper.find('input[type="number"]');
    expect(customInput.exists()).toBe(true);
    expect(customInput.element.value).toBe("20.5");
  });

  it("handles null modelValue", async () => {
    const wrapper = createWrapper({ modelValue: null });
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    expect(select.element.value).toBe("");
  });

  it("shows help text", async () => {
    const wrapper = createWrapper();

    const helpText = wrapper.find("p.text-xs");
    expect(helpText.exists()).toBe(true);
    expect(helpText.text()).toBe("inventory.gold_purity_help");
  });

  it("has correct input attributes for custom input", async () => {
    const wrapper = createWrapper();
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    await select.setValue("custom");
    await wrapper.vm.$nextTick();

    const customInput = wrapper.find('input[type="number"]');
    expect(customInput.attributes("step")).toBe("0.001");
    expect(customInput.attributes("min")).toBe("0");
    expect(customInput.attributes("max")).toBe("24");
  });

  it("clears value when empty option is selected", async () => {
    const wrapper = createWrapper({ modelValue: 18 });
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    await select.setValue("");

    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    const emissions = wrapper.emitted("update:modelValue") as any[];
    expect(emissions[emissions.length - 1]).toEqual([null]);
  });
});
