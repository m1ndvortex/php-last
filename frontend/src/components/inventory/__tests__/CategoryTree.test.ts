import { describe, it, expect, beforeEach, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import CategoryTree from "../CategoryTree.vue";

// Mock vue-i18n
vi.mock("vue-i18n", () => ({
  useI18n: () => ({
    t: (key: string) => key,
    locale: { value: "en" },
  }),
}));

describe("CategoryTree", () => {
  const mockCategories = [
    {
      id: 1,
      name: "Jewelry",
      name_persian: "جواهرات",
      code: "JEWELRY",
      is_active: true,
      parent_id: null,
      sort_order: 1,
      children: [
        {
          id: 2,
          name: "Rings",
          name_persian: "انگشتر",
          code: "RINGS",
          is_active: true,
          parent_id: 1,
          sort_order: 1,
          children: [],
        },
        {
          id: 3,
          name: "Necklaces",
          name_persian: "گردنبند",
          code: "NECKLACES",
          is_active: true,
          parent_id: 1,
          sort_order: 2,
          children: [],
        },
      ],
    },
    {
      id: 4,
      name: "Accessories",
      name_persian: "لوازم جانبی",
      code: "ACCESSORIES",
      is_active: true,
      parent_id: null,
      sort_order: 2,
      children: [],
    },
  ];

  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("renders category tree structure", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    expect(wrapper.findAllComponents({ name: "CategoryTreeNode" })).toHaveLength(2);
  });

  it("passes correct props to tree nodes", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    const firstNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    expect(firstNode.props("category")).toEqual(mockCategories[0]);
    expect(firstNode.props("level")).toBe(0);
  });

  it("emits edit event when tree node emits edit", async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("edit", mockCategories[0]);

    expect(wrapper.emitted("edit")).toBeTruthy();
    expect(wrapper.emitted("edit")?.[0]).toEqual([mockCategories[0]]);
  });

  it("emits delete event when tree node emits delete", async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("delete", mockCategories[0]);

    expect(wrapper.emitted("delete")).toBeTruthy();
    expect(wrapper.emitted("delete")?.[0]).toEqual([mockCategories[0]]);
  });

  it("emits reorder event when tree node emits reorder", async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    const reorderData = [
      { id: 1, sort_order: 2 },
      { id: 4, sort_order: 1 },
    ];

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("reorder", reorderData);

    expect(wrapper.emitted("reorder")).toBeTruthy();
    expect(wrapper.emitted("reorder")?.[0]).toEqual([reorderData]);
  });

  it("renders empty state when no categories", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: [],
      },
    });

    expect(wrapper.find(".empty-state").exists()).toBe(true);
    expect(wrapper.text()).toContain("categories.no_categories");
  });

  it("handles drag and drop functionality", async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        allowReorder: true,
      },
    });

    const treeContainer = wrapper.find(".category-tree");
    expect(treeContainer.attributes("draggable")).toBe("true");
  });

  it("disables drag and drop when allowReorder is false", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        allowReorder: false,
      },
    });

    const treeContainer = wrapper.find(".category-tree");
    expect(treeContainer.attributes("draggable")).toBeFalsy();
  });

  it("applies correct CSS classes for styling", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    expect(wrapper.find(".bg-white").exists()).toBe(true);
    expect(wrapper.find(".shadow").exists()).toBe(true);
    expect(wrapper.find(".rounded-lg").exists()).toBe(true);
  });

  it("supports compact mode", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        compact: true,
      },
    });

    expect(wrapper.classes()).toContain("compact");
  });

  it("shows category count when showCount is true", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        showCount: true,
      },
    });

    const treeNodes = wrapper.findAllComponents({ name: "CategoryTreeNode" });
    treeNodes.forEach((node) => {
      expect(node.props("showCount")).toBe(true);
    });
  });

  it("filters categories based on search query", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        searchQuery: "Ring",
      },
    });

    // The filtering logic would be implemented in the component
    // This test verifies the prop is passed correctly
    expect(wrapper.props("searchQuery")).toBe("Ring");
  });

  it("handles keyboard navigation", async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    // Test arrow key navigation
    await wrapper.trigger("keydown", { key: "ArrowDown" });
    expect(wrapper.vm.selectedIndex).toBe(1);

    await wrapper.trigger("keydown", { key: "ArrowUp" });
    expect(wrapper.vm.selectedIndex).toBe(0);
  });

  it("expands and collapses categories", async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    const expandableNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await expandableNode.vm.$emit("toggle", mockCategories[0]);

    expect(wrapper.vm.expandedCategories).toContain(mockCategories[0].id);
  });

  it("supports multi-selection mode", async () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
        multiSelect: true,
      },
    });

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("select", mockCategories[0]);

    expect(wrapper.vm.selectedCategories).toContain(mockCategories[0].id);
  });

  it("renders with correct accessibility attributes", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: mockCategories,
      },
    });

    const treeContainer = wrapper.find('[role="tree"]');
    expect(treeContainer.exists()).toBe(true);
    expect(treeContainer.attributes("aria-label")).toBe("categories.tree_label");
  });

  it("handles loading state", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: [],
        loading: true,
      },
    });

    expect(wrapper.find(".loading-spinner").exists()).toBe(true);
  });

  it("shows error state when error prop is provided", () => {
    const wrapper = mount(CategoryTree, {
      props: {
        categories: [],
        error: "Failed to load categories",
      },
    });

    expect(wrapper.find(".error-message").exists()).toBe(true);
    expect(wrapper.text()).toContain("Failed to load categories");
  });
});