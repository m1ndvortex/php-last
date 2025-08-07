import { describe, it, expect, beforeEach, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { createPinia, setActivePinia } from "pinia";
import CategoryFormModal from "../CategoryFormModal.vue";

// Mock vue-i18n
vi.mock("vue-i18n", () => ({
  useI18n: () => ({
    t: (key: string) => key,
    locale: { value: "en" },
  }),
}));

// Mock vee-validate
vi.mock("vee-validate", () => ({
  useForm: () => ({
    handleSubmit: vi.fn((fn) => fn),
    values: {},
    errors: {},
    isSubmitting: false,
    resetForm: vi.fn(),
  }),
  useField: (name: string) => ({
    value: "",
    errorMessage: "",
    handleChange: vi.fn(),
  }),
}));

// Mock the API service
vi.mock("../../../services/api", () => ({
  default: {
    post: vi.fn(),
    put: vi.fn(),
  },
}));

// Mock notifications
vi.mock("../../../composables/useNotifications", () => ({
  useNotifications: () => ({
    showSuccess: vi.fn(),
    showError: vi.fn(),
  }),
}));

describe("CategoryFormModal", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it("renders create mode correctly", () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    expect(wrapper.find("h3").text()).toBe("categories.add_category");
    expect(wrapper.find('button[type="submit"]').text()).toBe("common.create");
  });

  it("renders edit mode correctly", () => {
    const mockCategory = {
      id: 1,
      name: "Test Category",
      name_persian: "دسته تست",
      code: "TEST",
      description: "Test description",
      default_gold_purity: 18.0,
    };

    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: true,
        category: mockCategory,
      },
    });

    expect(wrapper.find("h3").text()).toBe("categories.edit_category");
    expect(wrapper.find('button[type="submit"]').text()).toBe("common.update");
  });

  it("displays all form fields", () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    // Check for required form fields
    expect(wrapper.find('input[name="name"]').exists()).toBe(true);
    expect(wrapper.find('input[name="name_persian"]').exists()).toBe(true);
    expect(wrapper.find('input[name="code"]').exists()).toBe(true);
    expect(wrapper.find('textarea[name="description"]').exists()).toBe(true);
    expect(wrapper.find('textarea[name="description_persian"]').exists()).toBe(true);
    expect(wrapper.findComponent({ name: "CategorySelector" }).exists()).toBe(true);
    expect(wrapper.findComponent({ name: "GoldPuritySelector" }).exists()).toBe(true);
    expect(wrapper.findComponent({ name: "CategoryImageUpload" }).exists()).toBe(true);
  });

  it("validates required fields", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    const form = wrapper.find("form");
    await form.trigger("submit");

    // Validation errors should be displayed
    expect(wrapper.find(".error-message").exists()).toBe(true);
  });

  it("populates form with category data in edit mode", () => {
    const mockCategory = {
      id: 1,
      name: "Test Category",
      name_persian: "دسته تست",
      code: "TEST",
      description: "Test description",
      description_persian: "توضیحات تست",
      default_gold_purity: 18.0,
      parent_id: 2,
      image_path: "categories/test.webp",
    };

    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: true,
        category: mockCategory,
      },
    });

    expect(wrapper.find('input[name="name"]').element.value).toBe("Test Category");
    expect(wrapper.find('input[name="name_persian"]').element.value).toBe("دسته تست");
    expect(wrapper.find('input[name="code"]').element.value).toBe("TEST");
  });

  it("handles form submission for create", async () => {
    const mockApiPost = vi.fn().mockResolvedValue({ data: { id: 1 } });
    vi.mocked(require("../../../services/api").default.post).mockImplementation(mockApiPost);

    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    // Fill form
    await wrapper.find('input[name="name"]').setValue("New Category");
    await wrapper.find('input[name="code"]').setValue("NEW");

    // Submit form
    await wrapper.find("form").trigger("submit");

    expect(mockApiPost).toHaveBeenCalledWith("/api/categories", expect.any(Object));
    expect(wrapper.emitted("saved")).toBeTruthy();
  });

  it("handles form submission for update", async () => {
    const mockApiPut = vi.fn().mockResolvedValue({ data: { id: 1 } });
    vi.mocked(require("../../../services/api").default.put).mockImplementation(mockApiPut);

    const mockCategory = {
      id: 1,
      name: "Test Category",
      code: "TEST",
    };

    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: true,
        category: mockCategory,
      },
    });

    await wrapper.find("form").trigger("submit");

    expect(mockApiPut).toHaveBeenCalledWith("/api/categories/1", expect.any(Object));
    expect(wrapper.emitted("saved")).toBeTruthy();
  });

  it("handles API errors gracefully", async () => {
    const mockApiPost = vi.fn().mockRejectedValue(new Error("API Error"));
    vi.mocked(require("../../../services/api").default.post).mockImplementation(mockApiPost);

    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    await wrapper.find("form").trigger("submit");

    expect(wrapper.find(".error-message").exists()).toBe(true);
  });

  it("closes modal when cancel button is clicked", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    const cancelButton = wrapper.find('button[type="button"]');
    await cancelButton.trigger("click");

    expect(wrapper.emitted("close")).toBeTruthy();
  });

  it("closes modal when overlay is clicked", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    const overlay = wrapper.find(".modal-overlay");
    await overlay.trigger("click");

    expect(wrapper.emitted("close")).toBeTruthy();
  });

  it("prevents modal close when clicking inside modal content", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    const modalContent = wrapper.find(".modal-content");
    await modalContent.trigger("click");

    expect(wrapper.emitted("close")).toBeFalsy();
  });

  it("handles image upload", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    const imageUpload = wrapper.findComponent({ name: "CategoryImageUpload" });
    await imageUpload.vm.$emit("uploaded", "categories/new-image.webp");

    expect(wrapper.vm.form.image_path).toBe("categories/new-image.webp");
  });

  it("validates gold purity range", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    const goldPuritySelector = wrapper.findComponent({ name: "GoldPuritySelector" });
    await goldPuritySelector.vm.$emit("update:modelValue", 25); // Invalid value

    expect(wrapper.find(".error-message").text()).toContain("gold_purity");
  });

  it("supports RTL layout for Persian locale", () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
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

    expect(wrapper.find('input[name="name_persian"]').attributes("dir")).toBe("rtl");
    expect(wrapper.find('textarea[name="description_persian"]').attributes("dir")).toBe("rtl");
  });

  it("handles keyboard shortcuts", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    // Test Escape key to close modal
    await wrapper.trigger("keydown", { key: "Escape" });
    expect(wrapper.emitted("close")).toBeTruthy();

    // Test Ctrl+S to save
    await wrapper.trigger("keydown", { key: "s", ctrlKey: true });
    // Should trigger form submission
  });

  it("shows loading state during submission", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    // Mock loading state
    wrapper.vm.loading = true;
    await wrapper.vm.$nextTick();

    const submitButton = wrapper.find('button[type="submit"]');
    expect(submitButton.attributes("disabled")).toBeDefined();
    expect(wrapper.find(".loading-spinner").exists()).toBe(true);
  });

  it("resets form when switching between create and edit modes", async () => {
    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    // Fill form
    await wrapper.find('input[name="name"]').setValue("Test");

    // Switch to edit mode
    await wrapper.setProps({
      isEdit: true,
      category: { id: 1, name: "Edit Category", code: "EDIT" },
    });

    expect(wrapper.find('input[name="name"]').element.value).toBe("Edit Category");
  });

  it("validates category code uniqueness", async () => {
    const mockApiPost = vi.fn().mockRejectedValue({
      response: {
        status: 422,
        data: {
          errors: {
            code: ["The code has already been taken."],
          },
        },
      },
    });
    vi.mocked(require("../../../services/api").default.post).mockImplementation(mockApiPost);

    const wrapper = mount(CategoryFormModal, {
      props: {
        isEdit: false,
        category: null,
      },
    });

    await wrapper.find('input[name="code"]').setValue("EXISTING");
    await wrapper.find("form").trigger("submit");

    expect(wrapper.find(".error-message").text()).toContain("already been taken");
  });
});