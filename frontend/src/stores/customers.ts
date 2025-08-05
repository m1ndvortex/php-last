import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import type {
  Customer,
  Communication,
  CRMPipelineData,
  CustomerAgingReport,
  PaginatedResponse,
} from "@/types";

export const useCustomersStore = defineStore("customers", () => {
  // State
  const customers = ref<Customer[]>([]);
  const currentCustomer = ref<Customer | null>(null);
  const communications = ref<Communication[]>([]);
  const crmPipeline = ref<CRMPipelineData | null>(null);
  const agingReport = ref<CustomerAgingReport | null>(null);
  const loading = ref({
    customers: false,
    customer: false,
    communications: false,
    crm: false,
    aging: false,
    creating: false,
    updating: false,
    deleting: false,
  });
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
  });
  const filters = ref({
    search: "",
    customer_type: "",
    crm_stage: "",
    is_active: "",
    preferred_language: "",
    lead_source: "",
    tags: "",
    sort_by: "name",
    sort_direction: "asc",
  });

  // Getters
  const activeCustomers = computed(() =>
    customers.value.filter((customer) => customer.is_active),
  );

  const customersByStage = computed(() => {
    const stages = {
      lead: [] as Customer[],
      prospect: [] as Customer[],
      customer: [] as Customer[],
      inactive: [] as Customer[],
    };

    customers.value.forEach((customer) => {
      if (stages[customer.crm_stage]) {
        stages[customer.crm_stage].push(customer);
      }
    });

    return stages;
  });

  const totalOutstanding = computed(() =>
    customers.value.reduce(
      (total, customer) => total + (customer.outstanding_balance || 0),
      0,
    ),
  );

  // Actions
  const fetchCustomers = async (params?: Record<string, any>) => {
    loading.value.customers = true;
    try {
      const queryParams = { ...filters.value, ...params };
      const response = await apiService.customers.getCustomers(queryParams);

      if (response.data.success) {
        const data = response.data.data as PaginatedResponse<Customer>;
        customers.value = data.data;
        pagination.value = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
        };
      }
    } catch (error) {
      console.error("Failed to fetch customers:", error);
      throw error;
    } finally {
      loading.value.customers = false;
    }
  };

  const fetchCustomer = async (id: number) => {
    loading.value.customer = true;
    try {
      const response = await apiService.customers.getCustomer(id);
      if (response.data.success) {
        currentCustomer.value = response.data.data;
        // Also fetch communications for this customer
        await fetchCommunications(id);
      }
    } catch (error) {
      console.error("Failed to fetch customer:", error);
      throw error;
    } finally {
      loading.value.customer = false;
    }
  };

  const createCustomer = async (customerData: Partial<Customer>) => {
    loading.value.creating = true;
    try {
      const response = await apiService.customers.createCustomer(customerData);
      if (response.data.success) {
        const newCustomer = response.data.data;
        customers.value.unshift(newCustomer);
        return newCustomer;
      }
    } catch (error) {
      console.error("Failed to create customer:", error);
      throw error;
    } finally {
      loading.value.creating = false;
    }
  };

  const updateCustomer = async (
    id: number,
    customerData: Partial<Customer>,
  ) => {
    loading.value.updating = true;
    try {
      const response = await apiService.customers.updateCustomer(
        id,
        customerData,
      );
      if (response.data.success) {
        const updatedCustomer = response.data.data;
        const index = customers.value.findIndex((c) => c.id === id);
        if (index !== -1) {
          customers.value[index] = updatedCustomer;
        }
        if (currentCustomer.value?.id === id) {
          currentCustomer.value = updatedCustomer;
        }
        return updatedCustomer;
      }
    } catch (error) {
      console.error("Failed to update customer:", error);
      throw error;
    } finally {
      loading.value.updating = false;
    }
  };

  const deleteCustomer = async (id: number) => {
    loading.value.deleting = true;
    try {
      const response = await apiService.customers.deleteCustomer(id);
      if (response.data.success) {
        customers.value = customers.value.filter((c) => c.id !== id);
        if (currentCustomer.value?.id === id) {
          currentCustomer.value = null;
        }
      }
    } catch (error) {
      console.error("Failed to delete customer:", error);
      throw error;
    } finally {
      loading.value.deleting = false;
    }
  };

  const fetchCRMPipeline = async () => {
    loading.value.crm = true;
    try {
      const response = await apiService.customers.getCRMPipeline();
      if (response.data.success) {
        crmPipeline.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch CRM pipeline:", error);
      throw error;
    } finally {
      loading.value.crm = false;
    }
  };

  const updateCRMStage = async (id: number, stage: string, notes?: string) => {
    try {
      const response = await apiService.customers.updateCRMStage(id, {
        crm_stage: stage,
        notes,
      });
      if (response.data.success) {
        const updatedCustomer = response.data.data;
        const index = customers.value.findIndex((c) => c.id === id);
        if (index !== -1) {
          customers.value[index] = updatedCustomer;
        }
        if (currentCustomer.value?.id === id) {
          currentCustomer.value = updatedCustomer;
        }
        // Refresh CRM pipeline data
        await fetchCRMPipeline();
        return updatedCustomer;
      }
    } catch (error) {
      console.error("Failed to update CRM stage:", error);
      throw error;
    }
  };

  const fetchAgingReport = async (reportFilters?: Record<string, any>) => {
    loading.value.aging = true;
    try {
      const response = await apiService.customers.getAgingReport(reportFilters);
      if (response.data.success) {
        agingReport.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch aging report:", error);
      throw error;
    } finally {
      loading.value.aging = false;
    }
  };

  const fetchCommunications = async (customerId: number) => {
    loading.value.communications = true;
    try {
      // This would typically be a separate endpoint, but for now we'll get it from customer details
      const response = await apiService.customers.getCustomer(customerId);
      if (response.data.success && response.data.data.communications) {
        communications.value = response.data.data.communications;
      }
    } catch (error) {
      console.error("Failed to fetch communications:", error);
      throw error;
    } finally {
      loading.value.communications = false;
    }
  };

  const sendCommunication = async (
    customerId: number,
    communicationData: {
      type: string;
      subject?: string;
      message: string;
      metadata?: Record<string, any>;
    },
  ) => {
    try {
      const response = await apiService.customers.sendCommunication(
        customerId,
        communicationData,
      );
      if (response.data.success) {
        const newCommunication = response.data.data;
        communications.value.unshift(newCommunication);
        return newCommunication;
      }
    } catch (error) {
      console.error("Failed to send communication:", error);
      throw error;
    }
  };

  const exportVCard = async (customerId: number) => {
    try {
      const response = await apiService.customers.exportVCard(customerId);
      if (response.data.success) {
        const { vcard, filename } = response.data.data;

        // Create and download the vCard file
        const blob = new Blob([vcard], { type: "text/vcard" });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        return response.data.data;
      }
    } catch (error) {
      console.error("Failed to export vCard:", error);
      throw error;
    }
  };

  const updateFilters = (newFilters: Partial<typeof filters.value>) => {
    filters.value = { ...filters.value, ...newFilters };
  };

  const resetFilters = () => {
    filters.value = {
      search: "",
      customer_type: "",
      crm_stage: "",
      is_active: "",
      preferred_language: "",
      lead_source: "",
      tags: "",
      sort_by: "name",
      sort_direction: "asc",
    };
  };

  const clearCurrentCustomer = () => {
    currentCustomer.value = null;
    communications.value = [];
  };

  return {
    // State
    customers,
    currentCustomer,
    communications,
    crmPipeline,
    agingReport,
    loading,
    pagination,
    filters,

    // Getters
    activeCustomers,
    customersByStage,
    totalOutstanding,

    // Actions
    fetchCustomers,
    fetchCustomer,
    createCustomer,
    updateCustomer,
    deleteCustomer,
    fetchCRMPipeline,
    updateCRMStage,
    fetchAgingReport,
    fetchCommunications,
    sendCommunication,
    exportVCard,
    updateFilters,
    resetFilters,
    clearCurrentCustomer,
  };
});
