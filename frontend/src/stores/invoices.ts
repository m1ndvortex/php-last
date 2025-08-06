import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import type {
  Invoice,
  InvoiceItem,
  InvoiceTemplate,
  Customer,
  InventoryItem,
  PaginatedResponse,
} from "@/types";

export const useInvoicesStore = defineStore("invoices", () => {
  // State
  const invoices = ref<Invoice[]>([]);
  const currentInvoice = ref<Invoice | null>(null);
  const templates = ref<InvoiceTemplate[]>([]);
  const currentTemplate = ref<InvoiceTemplate | null>(null);
  const recurringInvoices = ref<any[]>([]);
  const loading = ref({
    invoices: false,
    invoice: false,
    templates: false,
    template: false,
    recurring: false,
    creating: false,
    updating: false,
    deleting: false,
    generating: false,
    pdf: false,
    batch: false,
  });
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0,
  });
  const filters = ref({
    search: "",
    customer_id: "",
    status: "",
    language: "",
    date_from: "",
    date_to: "",
    template_id: "",
    tags: "",
    sort_by: "created_at",
    sort_direction: "desc",
  });

  // Getters
  const draftInvoices = computed(() =>
    invoices.value.filter((invoice) => invoice.status === "draft"),
  );

  const sentInvoices = computed(() =>
    invoices.value.filter((invoice) => invoice.status === "sent"),
  );

  const paidInvoices = computed(() =>
    invoices.value.filter((invoice) => invoice.status === "paid"),
  );

  const overdueInvoices = computed(() =>
    invoices.value.filter((invoice) => invoice.status === "overdue"),
  );

  const totalInvoiceAmount = computed(() =>
    invoices.value.reduce((total, invoice) => total + invoice.total_amount, 0),
  );

  const totalOutstanding = computed(() =>
    invoices.value
      .filter((invoice) => ["sent", "overdue"].includes(invoice.status))
      .reduce((total, invoice) => total + invoice.total_amount, 0),
  );

  // Actions
  const fetchInvoices = async (params?: Record<string, any>) => {
    loading.value.invoices = true;
    try {
      const queryParams = { ...filters.value, ...params };
      const response = await apiService.invoices.getInvoices(queryParams);

      if (response.data.success) {
        const data = response.data.data as PaginatedResponse<Invoice>;
        invoices.value = data.data;
        pagination.value = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
          from: data.from || 0,
          to: data.to || 0,
        };
      }
    } catch (error) {
      console.error("Failed to fetch invoices:", error);
      throw error;
    } finally {
      loading.value.invoices = false;
    }
  };

  const fetchInvoice = async (id: number) => {
    loading.value.invoice = true;
    try {
      const response = await apiService.invoices.getInvoice(id);
      if (response.data.success) {
        currentInvoice.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch invoice:", error);
      throw error;
    } finally {
      loading.value.invoice = false;
    }
  };

  const createInvoice = async (invoiceData: Partial<Invoice>) => {
    loading.value.creating = true;
    try {
      const response = await apiService.invoices.createInvoice(invoiceData);
      if (response.data.success) {
        const newInvoice = response.data.data;
        invoices.value.unshift(newInvoice);
        return newInvoice;
      }
    } catch (error) {
      console.error("Failed to create invoice:", error);
      throw error;
    } finally {
      loading.value.creating = false;
    }
  };

  const updateInvoice = async (id: number, invoiceData: Partial<Invoice>) => {
    loading.value.updating = true;
    try {
      const response = await apiService.invoices.updateInvoice(id, invoiceData);
      if (response.data.success) {
        const updatedInvoice = response.data.data;
        const index = invoices.value.findIndex((i) => i.id === id);
        if (index !== -1) {
          invoices.value[index] = updatedInvoice;
        }
        if (currentInvoice.value?.id === id) {
          currentInvoice.value = updatedInvoice;
        }
        return updatedInvoice;
      }
    } catch (error) {
      console.error("Failed to update invoice:", error);
      throw error;
    } finally {
      loading.value.updating = false;
    }
  };

  const deleteInvoice = async (id: number) => {
    loading.value.deleting = true;
    try {
      const response = await apiService.invoices.deleteInvoice(id);
      if (response.data.success) {
        invoices.value = invoices.value.filter((i) => i.id !== id);
        if (currentInvoice.value?.id === id) {
          currentInvoice.value = null;
        }
      }
    } catch (error) {
      console.error("Failed to delete invoice:", error);
      throw error;
    } finally {
      loading.value.deleting = false;
    }
  };

  const generatePDF = async (id: number) => {
    loading.value.pdf = true;
    try {
      const response = await apiService.invoices.generatePDF(id);
      if (response.data.success) {
        const { pdf_url, filename } = response.data.data;

        // Create and download the PDF file
        const link = document.createElement("a");
        link.href = pdf_url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        return response.data.data;
      }
    } catch (error) {
      console.error("Failed to generate PDF:", error);
      throw error;
    } finally {
      loading.value.pdf = false;
    }
  };

  const previewPDF = async (id: number) => {
    loading.value.pdf = true;
    try {
      const response = await apiService.invoices.previewPDF(id);
      if (response.data.success) {
        return response.data.data;
      }
    } catch (error) {
      console.error("Failed to preview PDF:", error);
      throw error;
    } finally {
      loading.value.pdf = false;
    }
  };

  const sendInvoice = async (
    id: number,
    method: "email" | "whatsapp" | "sms",
  ) => {
    try {
      const response = await apiService.invoices.sendInvoice(id, { method });
      if (response.data.success) {
        // Update invoice status
        const index = invoices.value.findIndex((i) => i.id === id);
        if (index !== -1) {
          invoices.value[index].status = "sent";
        }
        return response.data.data;
      }
    } catch (error) {
      console.error("Failed to send invoice:", error);
      throw error;
    }
  };

  const duplicateInvoice = async (id: number) => {
    loading.value.creating = true;
    try {
      const response = await apiService.invoices.duplicateInvoice(id);
      if (response.data.success) {
        const duplicatedInvoice = response.data.data;
        invoices.value.unshift(duplicatedInvoice);
        return duplicatedInvoice;
      }
    } catch (error) {
      console.error("Failed to duplicate invoice:", error);
      throw error;
    } finally {
      loading.value.creating = false;
    }
  };

  // Template management
  const fetchTemplates = async () => {
    loading.value.templates = true;
    try {
      const response = await apiService.invoices.getTemplates();
      if (response.data.success) {
        templates.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch templates:", error);
      throw error;
    } finally {
      loading.value.templates = false;
    }
  };

  const fetchTemplate = async (id: number) => {
    loading.value.template = true;
    try {
      const response = await apiService.invoices.getTemplate(id);
      if (response.data.success) {
        currentTemplate.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch template:", error);
      throw error;
    } finally {
      loading.value.template = false;
    }
  };

  const createTemplate = async (templateData: Partial<InvoiceTemplate>) => {
    loading.value.creating = true;
    try {
      const response = await apiService.invoices.createTemplate(templateData);
      if (response.data.success) {
        const newTemplate = response.data.data;
        templates.value.unshift(newTemplate);
        return newTemplate;
      }
    } catch (error) {
      console.error("Failed to create template:", error);
      throw error;
    } finally {
      loading.value.creating = false;
    }
  };

  const updateTemplate = async (
    id: number,
    templateData: Partial<InvoiceTemplate>,
  ) => {
    loading.value.updating = true;
    try {
      const response = await apiService.invoices.updateTemplate(
        id,
        templateData,
      );
      if (response.data.success) {
        const updatedTemplate = response.data.data;
        const index = templates.value.findIndex((t) => t.id === id);
        if (index !== -1) {
          templates.value[index] = updatedTemplate;
        }
        if (currentTemplate.value?.id === id) {
          currentTemplate.value = updatedTemplate;
        }
        return updatedTemplate;
      }
    } catch (error) {
      console.error("Failed to update template:", error);
      throw error;
    } finally {
      loading.value.updating = false;
    }
  };

  const deleteTemplate = async (id: number) => {
    loading.value.deleting = true;
    try {
      const response = await apiService.invoices.deleteTemplate(id);
      if (response.data.success) {
        templates.value = templates.value.filter((t) => t.id !== id);
        if (currentTemplate.value?.id === id) {
          currentTemplate.value = null;
        }
      }
    } catch (error) {
      console.error("Failed to delete template:", error);
      throw error;
    } finally {
      loading.value.deleting = false;
    }
  };

  // Batch operations
  const generateBatchInvoices = async (invoiceIds: number[]) => {
    loading.value.batch = true;
    try {
      const response = await apiService.invoices.generateBatch(invoiceIds);
      if (response.data.success) {
        return response.data.data;
      }
    } catch (error) {
      console.error("Failed to generate batch invoices:", error);
      throw error;
    } finally {
      loading.value.batch = false;
    }
  };

  const sendBatchInvoices = async (
    invoiceIds: number[],
    method: "email" | "whatsapp" | "sms",
  ) => {
    loading.value.batch = true;
    try {
      const response = await apiService.invoices.sendBatch(invoiceIds, {
        method,
      });
      if (response.data.success) {
        // Update invoice statuses
        invoiceIds.forEach((id) => {
          const index = invoices.value.findIndex((i) => i.id === id);
          if (index !== -1) {
            invoices.value[index].status = "sent";
          }
        });
        return response.data.data;
      }
    } catch (error) {
      console.error("Failed to send batch invoices:", error);
      throw error;
    } finally {
      loading.value.batch = false;
    }
  };

  // Recurring invoices
  const fetchRecurringInvoices = async () => {
    loading.value.recurring = true;
    try {
      const response = await apiService.invoices.getRecurringInvoices();
      if (response.data.success) {
        recurringInvoices.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch recurring invoices:", error);
      throw error;
    } finally {
      loading.value.recurring = false;
    }
  };

  const createRecurringInvoice = async (recurringData: any) => {
    loading.value.creating = true;
    try {
      const response =
        await apiService.invoices.createRecurringInvoice(recurringData);
      if (response.data.success) {
        const newRecurring = response.data.data;
        recurringInvoices.value.unshift(newRecurring);
        return newRecurring;
      }
    } catch (error) {
      console.error("Failed to create recurring invoice:", error);
      throw error;
    } finally {
      loading.value.creating = false;
    }
  };

  const updateRecurringInvoice = async (id: number, recurringData: any) => {
    loading.value.updating = true;
    try {
      const response = await apiService.invoices.updateRecurringInvoice(
        id,
        recurringData,
      );
      if (response.data.success) {
        const updatedRecurring = response.data.data;
        const index = recurringInvoices.value.findIndex((r) => r.id === id);
        if (index !== -1) {
          recurringInvoices.value[index] = updatedRecurring;
        }
        return updatedRecurring;
      }
    } catch (error) {
      console.error("Failed to update recurring invoice:", error);
      throw error;
    } finally {
      loading.value.updating = false;
    }
  };

  // Utility functions
  const updateFilters = (newFilters: Partial<typeof filters.value>) => {
    filters.value = { ...filters.value, ...newFilters };
  };

  const resetFilters = () => {
    filters.value = {
      search: "",
      customer_id: "",
      status: "",
      language: "",
      date_from: "",
      date_to: "",
      template_id: "",
      tags: "",
      sort_by: "created_at",
      sort_direction: "desc",
    };
  };

  const clearCurrentInvoice = () => {
    currentInvoice.value = null;
  };

  const clearCurrentTemplate = () => {
    currentTemplate.value = null;
  };

  return {
    // State
    invoices,
    currentInvoice,
    templates,
    currentTemplate,
    recurringInvoices,
    loading,
    pagination,
    filters,

    // Getters
    draftInvoices,
    sentInvoices,
    paidInvoices,
    overdueInvoices,
    totalInvoiceAmount,
    totalOutstanding,

    // Actions
    fetchInvoices,
    fetchInvoice,
    createInvoice,
    updateInvoice,
    deleteInvoice,
    generatePDF,
    previewPDF,
    sendInvoice,
    duplicateInvoice,
    fetchTemplates,
    fetchTemplate,
    createTemplate,
    updateTemplate,
    deleteTemplate,
    generateBatchInvoices,
    sendBatchInvoices,
    fetchRecurringInvoices,
    createRecurringInvoice,
    updateRecurringInvoice,
    updateFilters,
    resetFilters,
    clearCurrentInvoice,
    clearCurrentTemplate,
  };
});
