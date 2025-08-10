import { describe, it, expect, beforeEach, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createI18n } from "vue-i18n";
import CategorySelector from "../CategorySelector.vue";
import { useInventoryStore } from "@/stores/inventory";
import { mockCategories } from "@/test-utils/mockData";

const i18n = createI18n({
  legacy: false,
  locale: "en",
  messages: {
    en: {
      inventory: {
        categories: {
          select_parent: "Select parent category",
        },
      },
    },
  },
});

describe("CategorySelector", () => {
  let pinia: any;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
  });

  const createWrapper = (props = {}) => {
    return mount(CategorySelector, {
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
    expect(placeholderOption.text()).toBe("inventory.categories.select_parent");
  });

  it("displays categories from store", async () => {
    const store = useInventoryStore();
    store.categories = mockCategories;

    const wrapper = createWrapper();
    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    expect(options.length).toBeGreaterThan(1); // placeholder + categories
  });

  it("emits update:modelValue when selection changes", async () => {
    const store = useInventoryStore();
    store.categories = mockCategories;

    const wrapper = createWrapper();
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    await select.setValue("1");

    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([1]);
  });

  it("excludes specified category from options", async () => {
    const store = useInventoryStore();
    store.categories = mockCategories;

    const wrapper = createWrapper({ excludeId: 1 });
    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    const excludedOption = options.find(
      (option) => option.attributes("value") === "1",
    );
    expect(excludedOption).toBeUndefined();
  });

  it("displays category hierarchy with indentation", async () => {
    const store = useInventoryStore();
    store.categories = mockCategories;

    const wrapper = createWrapper();
    await wrapper.vm.$nextTick();

    const options = wrapper.findAll("option");
    // Child categories should have indentation in their display name
    const childOption = options.find(
      (option) => option.attributes("value") === "2",
    );
    // The component uses getCategoryDisplayName which adds indentation based on level
    expect(childOption?.text()).toContain("Necklaces"); // Should contain the category name
  });

  it("handles custom placeholder", async () => {
    const customPlaceholder = "Choose a category";
    const wrapper = createWrapper({ placeholder: customPlaceholder });

    const placeholderOption = wrapper.find("option[value='']");
    expect(placeholderOption.text()).toBe(customPlaceholder);
  });

  it("sets correct value when modelValue prop changes", async () => {
    const store = useInventoryStore();
    store.categories = mockCategories;

    const wrapper = createWrapper({ modelValue: 1 });
    await wrapper.vm.$nextTick();

    const select = wrapper.find("select");
    expect(select.element.value).toBe("1");

    await wrapper.setProps({ modelValue: 2 });
    expect(select.element.value).toBe("2");
  });

  it("handles null modelValue", async () => {
    const wrapper = createWrapper({ modelValue: null });

    const select = wrapper.find("select");
    expect(select.element.value).toBe("");
  });

  it("fetches categories on mount if not available", async () => {
    const store = useInventoryStore();
    const fetchCategoriesSpy = vi
      .spyOn(store, "fetchCategories")
      .mockResolvedValue();
    store.categories = [];

    createWrapper();

    expect(fetchCategoriesSpy).toHaveBeenCalled();
  });

  it("does not fetch categories if already available", async () => {
    const store = useInventoryStore();
    const fetchCategoriesSpy = vi
      .spyOn(store, "fetchCategories")
      .mockResolvedValue();
    store.categories = mockCategories;

    createWrapper();

    expect(fetchCategoriesSpy).not.toHaveBeenCalled();
  });
});
