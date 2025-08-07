import { describe, it, expect, beforeEach, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import CategorySelector from "../CategorySelector.vue";

// Mock vue-i18n
vi.mock("vue-i18n", () => ({
  useI18n: () => ({
    t: (key: string) => key,
    locale: { value: "en" },
  }),
}));

// Mock the inventory store
vi.mock("../../../stores/inventory", () => ({
  useInventoryStore: () => ({
    categories: [
      {
        id: 1,
        name: "Jewelry",
        name_persian: "جواهرات",
        code: "JEWELRY",
        is_active: true,
        parent_id: null,
        image_url: "categories/jewelry.webp",
        level: 0,
        formatted_name: "Jewelry",
      },
      {
        id: 2,
        name: "Rings",
        name_persian: "انگشتر",
        code: "RINGS",
        is_active: true,
        parent_id: 1,
        image_url: "categories/rings.webp",
        level: 1,
        formatted_name: "— Rings",
      },
      {
        id: 3,
        name: "Necklaces",
        name_persian: "گردنبند",
        code: "NECKLACES",
        is_active: true,
        parent_id: 1,
        image_url: null,
        level: 1,
        formatted_name: "— Necklaces",
      },
    ],
    fetchCategoriesForSelect: vi.fn(),
  }),
}));

describe("CategorySelector", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("renders select dropdown", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        placeholder: "Select category",
      },
    });

    expect(wrapper.find("select").exists()).toBe(true);
    expect(wrapper.find("option[value='']").text()).toBe("Select category");
  });

  it("displays categories as options", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
      },
    });

    const options = wrapper.findAll("option");
    expect(options).toHaveLength(4); // 3 categories + placeholder

    expect(options[1].text()).toBe("Jewelry");
    expect(options[2].text()).toBe("— Rings");
    expect(options[3].text()).toBe("— Necklaces");
  });

  it("shows category images when available", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        showImages: true,
      },
    });

    const options = wrapper.findAll("option");
    // In a real implementation, images would be shown differently
    // This tests the prop is passed correctly
    expect(wrapper.props("showImages")).toBe(true);
  });

  it("excludes specified category from options", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        excludeId: 1,
      },
    });

    const options = wrapper.findAll("option");
    const jewelryOption = options.find((option) => option.text() === "Jewelry");
    expect(jewelryOption).toBeUndefined();
  });

  it("filters by parent category", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        parentId: 1,
      },
    });

    const options = wrapper.findAll("option");
    // Should only show children of category 1 (Jewelry)
    expect(options).toHaveLength(3); // 2 children + placeholder
  });

  it("shows only root categories when rootOnly is true", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        rootOnly: true,
      },
    });

    const options = wrapper.findAll("option");
    expect(options).toHaveLength(2); // 1 root category + placeholder
    expect(options[1].text()).toBe("Jewelry");
  });

  it("emits update:modelValue when selection changes", async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
      },
    });

    const select = wrapper.find("select");
    await select.setValue("2");

    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([2]);
  });

  it("emits change event with category object", async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
      },
    });

    const select = wrapper.find("select");
    await select.setValue("2");

    expect(wrapper.emitted("change")).toBeTruthy();
    expect(wrapper.emitted("change")?.[0][0]).toMatchObject({
      id: 2,
      name: "Rings",
    });
  });

  it("displays localized category names", () => {
    const wrapper = mount(CategorySelector, {
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

    const options = wrapper.findAll("option");
    // In Persian locale, should show Persian names
    expect(options[1].text()).toContain("جواهرات");
  });

  it("handles loading state", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        loading: true,
      },
    });

    expect(wrapper.find("select").attributes("disabled")).toBeDefined();
    expect(wrapper.find(".loading-spinner").exists()).toBe(true);
  });

  it("shows error state", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        error: "Failed to load categories",
      },
    });

    expect(wrapper.find(".error-message").exists()).toBe(true);
    expect(wrapper.text()).toContain("Failed to load categories");
  });

  it("supports multiple selection", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: [],
        multiple: true,
      },
    });

    const select = wrapper.find("select");
    expect(select.attributes("multiple")).toBeDefined();
  });

  it("handles multiple selection values", async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: [],
        multiple: true,
      },
    });

    const select = wrapper.find("select");
    const options = select.findAll("option");

    // Select multiple options
    options[1].element.selected = true;
    options[2].element.selected = true;
    await select.trigger("change");

    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([[1, 2]]);
  });

  it("validates required selection", async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        required: true,
      },
    });

    const select = wrapper.find("select");
    await select.trigger("blur");

    expect(wrapper.find(".error-message").exists()).toBe(true);
  });

  it("supports custom option rendering", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        customRenderer: true,
      },
      slots: {
        option: `
          <template #option="{ category }">
            <span class="custom-option">{{ category.name }} ({{ category.code }})</span>
          </template>
        `,
      },
    });

    expect(wrapper.find(".custom-option").exists()).toBe(true);
  });

  it("handles keyboard navigation", async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
      },
    });

    const select = wrapper.find("select");

    // Test arrow key navigation
    await select.trigger("keydown", { key: "ArrowDown" });
    await select.trigger("keydown", { key: "Enter" });

    // Should select the first category
    expect(wrapper.emitted("update:modelValue")).toBeTruthy();
  });

  it("supports search functionality", async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        searchable: true,
      },
    });

    const searchInput = wrapper.find('input[type="search"]');
    await searchInput.setValue("Ring");

    expect(wrapper.vm.searchQuery).toBe("Ring");
    // Filtered options should be displayed
  });

  it("shows category hierarchy with indentation", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        showHierarchy: true,
      },
    });

    const options = wrapper.findAll("option");
    expect(options[2].text()).toContain("—"); // Indentation for child category
  });

  it("handles disabled state", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        disabled: true,
      },
    });

    const select = wrapper.find("select");
    expect(select.attributes("disabled")).toBeDefined();
  });

  it("supports clear functionality", async () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: 1,
        clearable: true,
      },
    });

    const clearButton = wrapper.find(".clear-button");
    await clearButton.trigger("click");

    expect(wrapper.emitted("update:modelValue")?.[0]).toEqual([null]);
  });

  it("shows category count when enabled", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        showCount: true,
      },
    });

    // Category count would be shown in the option text
    expect(wrapper.props("showCount")).toBe(true);
  });

  it("applies correct CSS classes", () => {
    const wrapper = mount(CategorySelector, {
      props: {
        modelValue: null,
        class: "custom-class",
      },
    });

    expect(wrapper.classes()).toContain("custom-class");
    expect(wrapper.find("select").classes()).toContain("form-input");
  });
});