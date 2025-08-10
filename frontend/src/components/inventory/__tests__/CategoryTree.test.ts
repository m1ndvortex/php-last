import { describe, it, expect, beforeEach, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import { createI18n } from "vue-i18n";
import CategoryTree from "../CategoryTree.vue";
import { mockCategories } from "@/test-utils/mockData";

// Mock CategoryTreeNode component
vi.mock("../CategoryTreeNode.vue", () => ({
  default: {
    name: "CategoryTreeNode",
    template: '<div class="mock-tree-node">{{ category.name }}</div>',
    props: ["category", "level", "expanded", "allCategories"],
    emits: ["edit", "delete", "toggle-expand", "create-subcategory", "reorder"],
  },
}));

const i18n = createI18n({
  legacy: false,
  locale: "en",
  messages: {
    en: {
      inventory: {
        categories: {
          no_categories_found: "No categories found",
          description: "Get started by creating your first category",
        },
      },
    },
  },
});

describe("CategoryTree", () => {
  let pinia: any;

  beforeEach(() => {
    pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
  });

  const createWrapper = (props = {}) => {
    return mount(CategoryTree, {
      props: {
        categories: mockCategories,
        expandedNodes: new Set([1]),
        ...props,
      },
      global: {
        plugins: [pinia, i18n],
      },
    });
  };

  it("renders loading state", async () => {
    const wrapper = createWrapper({ loading: true });

    const loadingSpinner = wrapper.find(".animate-spin");
    expect(loadingSpinner.exists()).toBe(true);
  });

  it("renders empty state when no categories", async () => {
    const wrapper = createWrapper({ categories: [] });

    const emptyState = wrapper.find(".text-center");
    expect(emptyState.exists()).toBe(true);
    expect(emptyState.text()).toContain(
      "inventory.categories.no_categories_found",
    );
  });

  it("renders category tree when categories are provided", async () => {
    const wrapper = createWrapper();

    const treeNodes = wrapper.findAll(".mock-tree-node");
    expect(treeNodes.length).toBeGreaterThan(0);
  });

  it("only renders root categories at top level", async () => {
    const wrapper = createWrapper();

    // Should only render categories without parent_id
    const rootCategories = mockCategories.filter((cat) => !cat.parent_id);
    const treeNodes = wrapper.findAll(".mock-tree-node");
    expect(treeNodes.length).toBe(rootCategories.length);
  });

  it("emits edit event when tree node emits edit", async () => {
    const wrapper = createWrapper();

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("edit", mockCategories[0]);

    expect(wrapper.emitted("edit")).toBeTruthy();
    expect(wrapper.emitted("edit")?.[0]).toEqual([mockCategories[0]]);
  });

  it("emits delete event when tree node emits delete", async () => {
    const wrapper = createWrapper();

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("delete", mockCategories[0]);

    expect(wrapper.emitted("delete")).toBeTruthy();
    expect(wrapper.emitted("delete")?.[0]).toEqual([mockCategories[0]]);
  });

  it("emits toggle-expand event when tree node emits toggle-expand", async () => {
    const wrapper = createWrapper();

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("toggle-expand", 1);

    expect(wrapper.emitted("toggle-expand")).toBeTruthy();
    expect(wrapper.emitted("toggle-expand")?.[0]).toEqual([1]);
  });

  it("emits create-subcategory event when tree node emits create-subcategory", async () => {
    const wrapper = createWrapper();

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("create-subcategory", mockCategories[0]);

    expect(wrapper.emitted("create-subcategory")).toBeTruthy();
    expect(wrapper.emitted("create-subcategory")?.[0]).toEqual([
      mockCategories[0],
    ]);
  });

  it("emits reorder event when tree node emits reorder", async () => {
    const wrapper = createWrapper();

    const orderData = { categoryId: 1, newOrder: 2 };
    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    await treeNode.vm.$emit("reorder", orderData);

    expect(wrapper.emitted("reorder")).toBeTruthy();
    expect(wrapper.emitted("reorder")?.[0]).toEqual([orderData]);
  });

  it("passes correct props to tree nodes", async () => {
    const expandedNodes = new Set([1, 2]);
    const wrapper = createWrapper({ expandedNodes });

    const treeNode = wrapper.findComponent({ name: "CategoryTreeNode" });
    expect(treeNode.props("level")).toBe(0);
    expect(treeNode.props("expanded")).toBe(true); // Category 1 is expanded
    expect(treeNode.props("allCategories")).toEqual(mockCategories);
  });

  it("handles undefined categories gracefully", async () => {
    const wrapper = createWrapper({ categories: undefined });

    const emptyState = wrapper.find(".text-center");
    expect(emptyState.exists()).toBe(true);
  });

  it("handles null expandedNodes gracefully", async () => {
    const wrapper = createWrapper({ expandedNodes: undefined });

    // Should not crash and should render tree nodes
    const treeNodes = wrapper.findAll(".mock-tree-node");
    expect(treeNodes.length).toBeGreaterThan(0);
  });
});
