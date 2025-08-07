import { describe, it, expect, beforeEach, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import CategoryManagement from "../CategoryManagement.vue";

// Mock the API service
vi.mock("../../../services/api", () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

// Mock the inventory store
vi.mock("../../../stores/inventory", () => ({
  useInventoryStore: () => ({
    categories: [
      {
        id: 1,
        name: "Rings",
        name_persian: "انگشتر",
        code: "RING",
        is_active: true,
        parent_id: null,
        children: [
          {
            id: 2,
            name: "Wedding Rings",
            name_persian: "انگشتر عروسی",
            code: "WEDDING_RING",
            is_active: true,
            parent_id: 1,
            children: [],
          },
        ],
      },
    ],
    loading: false,
    fetchCategories: vi.fn(),
    createCategory: vi.fn(),
    updateCategory: vi.fn(),
    deleteCategory: vi.fn(),
    reorderCategories: vi.fn(),
  }),
}));

// Mock vue-i18n
vi.mock("vue-i18n", () => ({
  useI18n: () => ({
    t: (key: string) => key,
    locale: { value: "en" },
  }),
}));

// Mock notifications composable
vi.mock("../../../composables/useNotifications", () => ({
  useNotifications: () => ({
    showSuccess: vi.fn(),
    showError: vi.fn(),
  }),
}));

describe("CategoryManagement", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("renders category management interface", () => {
    const wrapper = mount(CategoryManagement);

    expect(wrapper.find("h2").text()).toBe("categories.title");
    expect(wrapper.find("button").text()).toContain("categories.add_category");
  });

  it("displays categories in tree structure", () => {
    const wrapper = mount(CategoryManagement);

    // Should render CategoryTree component
    expect(wrapper.findComponent({ name: "CategoryTree" }).exists()).toBe(true);
  });

  it("opens create modal when add button is clicked", async () => {
    const wrapper = mount(CategoryManagement);

    const addButton = wrapper.find("button");
    await addButton.trigger("click");

    expect(wrapper.vm.showCreateModal).toBe(true);
  });

  it("handles category edit action", async () => {
    const wrapper = mount(CategoryManagement);

    const mockCategory = {
      id: 1,
      name: "Test Category",
      code: "TEST",
    };

    await wrapper.vm.editCategory(mockCategory);

    expect(wrapper.vm.selectedCategory).toEqual(mockCategory);
    expect(wrapper.vm.showEditModal).toBe(true);
  });

  it("handles category delete action", async () => {
    const wrapper = mount(CategoryManagement);
    const mockDeleteFn = vi.fn().mockResolvedValue(true);
    wrapper.vm.$store = {
      deleteCategory: mockDeleteFn,
    };

    const mockCategory = { id: 1, name: "Test Category" };

    await wrapper.vm.deleteCategory(mockCategory);

    expect(mockDeleteFn).toHaveBeenCalledWith(1);
  });

  it("handles category reordering", async () => {
    const wrapper = mount(CategoryManagement);
    const mockReorderFn = vi.fn().mockResolvedValue(true);
    wrapper.vm.$store = {
      reorderCategories: mockReorderFn,
    };

    const orderData = [
      { id: 1, sort_order: 2 },
      { id: 2, sort_order: 1 },
    ];

    await wrapper.vm.reorderCategories(orderData);

    expect(mockReorderFn).toHaveBeenCalledWith(orderData);
  });

  it("closes modals when requested", async () => {
    const wrapper = mount(CategoryManagement);

    wrapper.vm.showCreateModal = true;
    wrapper.vm.showEditModal = true;

    await wrapper.vm.closeModals();

    expect(wrapper.vm.showCreateModal).toBe(false);
    expect(wrapper.vm.showEditModal).toBe(false);
    expect(wrapper.vm.selectedCategory).toBe(null);
  });

  it("handles category save event", async () => {
    const wrapper = mount(CategoryManagement);
    const mockFetchFn = vi.fn();
    wrapper.vm.$store = {
      fetchCategories: mockFetchFn,
    };

    await wrapper.vm.handleCategorySaved();

    expect(mockFetchFn).toHaveBeenCalled();
    expect(wrapper.vm.showCreateModal).toBe(false);
    expect(wrapper.vm.showEditModal).toBe(false);
  });

  it("shows loading state", () => {
    const wrapper = mount(CategoryManagement, {
      global: {
        mocks: {
          $store: {
            loading: true,
            categories: [],
          },
        },
      },
    });

    expect(wrapper.find(".loading").exists()).toBe(true);
  });

  it("shows empty state when no categories", () => {
    const wrapper = mount(CategoryManagement, {
      global: {
        mocks: {
          $store: {
            loading: false,
            categories: [],
          },
        },
      },
    });

    expect(wrapper.find(".empty-state").exists()).toBe(true);
  });

  it("filters categories by search query", async () => {
    const wrapper = mount(CategoryManagement);

    const searchInput = wrapper.find('input[type="search"]');
    await searchInput.setValue("Ring");

    expect(wrapper.vm.searchQuery).toBe("Ring");
    // The actual filtering logic would be tested in the computed property
  });

  it("handles keyboard shortcuts", async () => {
    const wrapper = mount(CategoryManagement);

    // Test Ctrl+N for new category
    await wrapper.trigger("keydown", {
      key: "n",
      ctrlKey: true,
    });

    expect(wrapper.vm.showCreateModal).toBe(true);
  });

  it("supports RTL layout for Persian locale", () => {
    const wrapper = mount(CategoryManagement, {
      global: {
        mocks: {
          $i18n: {
            locale: { value: "fa" },
            t: (key: string) => key,
          },
        },
      },
    });

    expect(wrapper.classes()).toContain("rtl");
  });
});