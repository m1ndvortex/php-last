import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { apiService } from "@/services/api";
import type {
  InventoryItem,
  InventoryMovement,
  StockAudit,
  BillOfMaterial,
  Category,
  Location,
  PaginatedResponse,
} from "@/types";

export const useInventoryStore = defineStore("inventory", () => {
  // State
  const items = ref<InventoryItem[]>([]);
  const currentItem = ref<InventoryItem | null>(null);
  const movements = ref<InventoryMovement[]>([]);
  const audits = ref<StockAudit[]>([]);
  const currentAudit = ref<StockAudit | null>(null);
  const boms = ref<BillOfMaterial[]>([]);
  const categories = ref<Category[]>([]);
  const locations = ref<Location[]>([]);

  const loading = ref({
    items: false,
    item: false,
    movements: false,
    audits: false,
    audit: false,
    boms: false,
    categories: false,
    locations: false,
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
    category_id: "",
    location_id: "",
    is_active: "",
    is_low_stock: "",
    is_expiring: "",
    sort_by: "name",
    sort_direction: "asc",
  });

  // Getters
  const activeItems = computed(() =>
    items.value?.filter((item) => item.is_active) || [],
  );

  const lowStockItems = computed(() =>
    items.value?.filter((item) => item.is_low_stock) || [],
  );

  const expiringItems = computed(() =>
    items.value?.filter((item) => item.is_expiring) || [],
  );

  const totalInventoryValue = computed(() =>
    items.value?.reduce((total, item) => total + (item.total_value || 0), 0) || 0,
  );

  const totalInventoryCost = computed(() =>
    items.value?.reduce((total, item) => total + (item.total_cost || 0), 0) || 0,
  );

  const itemsByCategory = computed(() => {
    const grouped: Record<string, InventoryItem[]> = {};
    items.value?.forEach((item) => {
      const categoryName = item.category?.name || "Uncategorized";
      if (!grouped[categoryName]) {
        grouped[categoryName] = [];
      }
      grouped[categoryName].push(item);
    });
    return grouped;
  });

  const itemsByLocation = computed(() => {
    const grouped: Record<string, InventoryItem[]> = {};
    items.value?.forEach((item) => {
      const locationName = item.location?.name || "No Location";
      if (!grouped[locationName]) {
        grouped[locationName] = [];
      }
      grouped[locationName].push(item);
    });
    return grouped;
  });

  // Actions
  const fetchItems = async (params?: Record<string, any>) => {
    loading.value.items = true;
    try {
      const queryParams = { ...filters.value, ...params };
      const response = await apiService.inventory.getItems(queryParams);

      if (response.data.success) {
        const data = response.data.data as PaginatedResponse<InventoryItem>;
        items.value = data.data;
        pagination.value = {
          current_page: data.current_page,
          last_page: data.last_page,
          per_page: data.per_page,
          total: data.total,
        };
      }
    } catch (error) {
      console.error("Failed to fetch inventory items:", error);
      throw error;
    } finally {
      loading.value.items = false;
    }
  };

  const fetchItem = async (id: number) => {
    loading.value.item = true;
    try {
      const response = await apiService.inventory.getItem(id);
      if (response.data.success) {
        currentItem.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch inventory item:", error);
      throw error;
    } finally {
      loading.value.item = false;
    }
  };

  const createItem = async (itemData: FormData) => {
    loading.value.creating = true;
    try {
      const response = await apiService.inventory.createItem(itemData);
      if (response.data.success) {
        const newItem = response.data.data;
        // Ensure items array is initialized before adding new item
        if (!items.value) {
          items.value = [];
        }
        items.value.unshift(newItem);
        return newItem;
      }
    } catch (error) {
      console.error("Failed to create inventory item:", error);
      throw error;
    } finally {
      loading.value.creating = false;
    }
  };

  const updateItem = async (id: number, itemData: FormData) => {
    loading.value.updating = true;
    try {
      const response = await apiService.inventory.updateItem(id, itemData);
      if (response.data.success) {
        const updatedItem = response.data.data;
        const index = items.value.findIndex((item) => item.id === id);
        if (index !== -1) {
          items.value[index] = updatedItem;
        }
        if (currentItem.value?.id === id) {
          currentItem.value = updatedItem;
        }
        return updatedItem;
      }
    } catch (error) {
      console.error("Failed to update inventory item:", error);
      throw error;
    } finally {
      loading.value.updating = false;
    }
  };

  const deleteItem = async (id: number) => {
    loading.value.deleting = true;
    try {
      const response = await apiService.inventory.deleteItem(id);
      if (response.data.success) {
        items.value = items.value.filter((item) => item.id !== id);
        if (currentItem.value?.id === id) {
          currentItem.value = null;
        }
      }
    } catch (error) {
      console.error("Failed to delete inventory item:", error);
      throw error;
    } finally {
      loading.value.deleting = false;
    }
  };

  const fetchMovements = async (params?: Record<string, any>) => {
    loading.value.movements = true;
    try {
      const response = await apiService.inventory.getMovements(params);
      if (response.data.success) {
        movements.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch inventory movements:", error);
      throw error;
    } finally {
      loading.value.movements = false;
    }
  };

  const createMovement = async (movementData: any) => {
    try {
      const response = await apiService.inventory.createMovement(movementData);
      if (response.data.success) {
        const newMovement = response.data.data;
        movements.value.unshift(newMovement);
        // Refresh items to update quantities
        await fetchItems();
        return newMovement;
      }
    } catch (error) {
      console.error("Failed to create inventory movement:", error);
      throw error;
    }
  };

  const fetchCategories = async () => {
    loading.value.categories = true;
    try {
      const response = await apiService.inventory.getCategories();
      if (response.data.success) {
        categories.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch categories:", error);
      throw error;
    } finally {
      loading.value.categories = false;
    }
  };

  const createCategory = async (categoryData: any) => {
    loading.value.creating = true;
    try {
      const response = await apiService.inventory.createCategory(categoryData);
      if (response.data.success) {
        const newCategory = response.data.data;
        categories.value.push(newCategory);
        return newCategory;
      }
    } catch (error) {
      console.error("Failed to create category:", error);
      throw error;
    } finally {
      loading.value.creating = false;
    }
  };

  const updateCategory = async (id: number, categoryData: any) => {
    loading.value.updating = true;
    try {
      const response = await apiService.inventory.updateCategory(
        id,
        categoryData,
      );
      if (response.data.success) {
        const updatedCategory = response.data.data;
        const index = categories.value.findIndex(
          (category) => category.id === id,
        );
        if (index !== -1) {
          categories.value[index] = updatedCategory;
        }
        return updatedCategory;
      }
    } catch (error) {
      console.error("Failed to update category:", error);
      throw error;
    } finally {
      loading.value.updating = false;
    }
  };

  const deleteCategory = async (id: number) => {
    loading.value.deleting = true;
    try {
      const response = await apiService.inventory.deleteCategory(id);
      if (response.data.success) {
        categories.value = categories.value.filter(
          (category) => category.id !== id,
        );
      }
    } catch (error) {
      console.error("Failed to delete category:", error);
      throw error;
    } finally {
      loading.value.deleting = false;
    }
  };

  const reorderCategories = async (orderData: any) => {
    try {
      const response = await apiService.inventory.reorderCategories(orderData);
      if (response.data.success) {
        // Refresh categories to get updated order
        await fetchCategories();
        return response.data.data;
      }
    } catch (error) {
      console.error("Failed to reorder categories:", error);
      throw error;
    }
  };

  const fetchLocations = async () => {
    loading.value.locations = true;
    try {
      const response = await apiService.inventory.getLocations();
      if (response.data.success) {
        locations.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch locations:", error);
      throw error;
    } finally {
      loading.value.locations = false;
    }
  };

  const createLocation = async (locationData: any) => {
    try {
      const response = await apiService.inventory.createLocation(locationData);
      if (response.data.success) {
        const newLocation = response.data.data;
        locations.value.push(newLocation);
        return newLocation;
      }
    } catch (error) {
      console.error("Failed to create location:", error);
      throw error;
    }
  };

  const fetchAudits = async (params?: Record<string, any>) => {
    loading.value.audits = true;
    try {
      const response = await apiService.inventory.getAudits(params);
      if (response.data.success) {
        audits.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch stock audits:", error);
      throw error;
    } finally {
      loading.value.audits = false;
    }
  };

  const fetchAudit = async (id: number) => {
    loading.value.audit = true;
    try {
      const response = await apiService.inventory.getAudit(id);
      if (response.data.success) {
        currentAudit.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch stock audit:", error);
      throw error;
    } finally {
      loading.value.audit = false;
    }
  };

  const createAudit = async (auditData: any) => {
    try {
      const response = await apiService.inventory.createAudit(auditData);
      if (response.data.success) {
        const newAudit = response.data.data;
        audits.value.unshift(newAudit);
        return newAudit;
      }
    } catch (error) {
      console.error("Failed to create stock audit:", error);
      throw error;
    }
  };

  const startAudit = async (id: number) => {
    try {
      const response = await apiService.inventory.startAudit(id);
      if (response.data.success) {
        const updatedAudit = response.data.data;
        const index = audits.value.findIndex((audit) => audit.id === id);
        if (index !== -1) {
          audits.value[index] = updatedAudit;
        }
        if (currentAudit.value?.id === id) {
          currentAudit.value = updatedAudit;
        }
        return updatedAudit;
      }
    } catch (error) {
      console.error("Failed to start audit:", error);
      throw error;
    }
  };

  const completeAudit = async (id: number) => {
    try {
      const response = await apiService.inventory.completeAudit(id);
      if (response.data.success) {
        const updatedAudit = response.data.data;
        const index = audits.value.findIndex((audit) => audit.id === id);
        if (index !== -1) {
          audits.value[index] = updatedAudit;
        }
        if (currentAudit.value?.id === id) {
          currentAudit.value = updatedAudit;
        }
        return updatedAudit;
      }
    } catch (error) {
      console.error("Failed to complete audit:", error);
      throw error;
    }
  };

  const updateAuditItem = async (
    auditId: number,
    itemId: number,
    data: any,
  ) => {
    try {
      const response = await apiService.inventory.updateAuditItem(
        auditId,
        itemId,
        data,
      );
      if (response.data.success) {
        // Refresh current audit to get updated data
        if (currentAudit.value?.id === auditId) {
          await fetchAudit(auditId);
        }
        return response.data.data;
      }
    } catch (error) {
      console.error("Failed to update audit item:", error);
      throw error;
    }
  };

  const fetchBOMs = async (params?: Record<string, any>) => {
    loading.value.boms = true;
    try {
      const response = await apiService.inventory.getBOMs(params);
      if (response.data.success) {
        boms.value = response.data.data;
      }
    } catch (error) {
      console.error("Failed to fetch BOMs:", error);
      throw error;
    } finally {
      loading.value.boms = false;
    }
  };

  const createBOM = async (bomData: any) => {
    try {
      const response = await apiService.inventory.createBOM(bomData);
      if (response.data.success) {
        const newBOM = response.data.data;
        boms.value.push(newBOM);
        return newBOM;
      }
    } catch (error) {
      console.error("Failed to create BOM:", error);
      throw error;
    }
  };

  const updateFilters = (newFilters: Partial<typeof filters.value>) => {
    filters.value = { ...filters.value, ...newFilters };
  };

  const resetFilters = () => {
    filters.value = {
      search: "",
      category_id: "",
      location_id: "",
      is_active: "",
      is_low_stock: "",
      is_expiring: "",
      sort_by: "name",
      sort_direction: "asc",
    };
  };

  const clearCurrentItem = () => {
    currentItem.value = null;
  };

  const clearCurrentAudit = () => {
    currentAudit.value = null;
  };

  return {
    // State
    items,
    currentItem,
    movements,
    audits,
    currentAudit,
    boms,
    categories,
    locations,
    loading,
    pagination,
    filters,

    // Getters
    activeItems,
    lowStockItems,
    expiringItems,
    totalInventoryValue,
    totalInventoryCost,
    itemsByCategory,
    itemsByLocation,

    // Actions
    fetchItems,
    fetchItem,
    createItem,
    updateItem,
    deleteItem,
    fetchMovements,
    createMovement,
    fetchCategories,
    createCategory,
    updateCategory,
    deleteCategory,
    reorderCategories,
    fetchLocations,
    createLocation,
    fetchAudits,
    fetchAudit,
    createAudit,
    startAudit,
    completeAudit,
    updateAuditItem,
    fetchBOMs,
    createBOM,
    updateFilters,
    resetFilters,
    clearCurrentItem,
    clearCurrentAudit,
  };
});
