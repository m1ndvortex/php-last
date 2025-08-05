<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $t("customers.title") }}
        </h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("customers.description") }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button
          @click="showCreateModal = true"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ $t("customers.add_customer") }}
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          :class="[
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm',
            activeTab === tab.key
              ? 'border-primary-500 text-primary-600 dark:text-primary-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
          ]"
        >
          {{ $t(tab.label) }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- Customer List Tab -->
      <div v-if="activeTab === 'list'">
        <CustomerList
          @edit-customer="editCustomer"
          @view-customer="viewCustomer"
          @delete-customer="deleteCustomer"
        />
      </div>

      <!-- CRM Pipeline Tab -->
      <div v-if="activeTab === 'crm'">
        <CRMPipeline />
      </div>

      <!-- Communication Center Tab -->
      <div v-if="activeTab === 'communication'">
        <CommunicationCenter />
      </div>

      <!-- Aging Report Tab -->
      <div v-if="activeTab === 'aging'">
        <CustomerAgingReport />
      </div>
    </div>

    <!-- Create/Edit Customer Modal -->
    <CustomerFormModal
      v-if="showCreateModal || showEditModal"
      :customer="selectedCustomer"
      :is-edit="showEditModal"
      @close="closeModals"
      @saved="handleCustomerSaved"
    />

    <!-- Customer Details Modal -->
    <CustomerDetailsModal
      v-if="showDetailsModal"
      :customer="selectedCustomer"
      @close="closeModals"
      @edit="editCustomer"
      @delete="deleteCustomer"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      :title="$t('customers.delete_customer')"
      :message="
        $t('customers.delete_confirmation', { name: selectedCustomer?.name })
      "
      :loading="customersStore.loading.deleting"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
// import { useI18n } from "vue-i18n";
import { PlusIcon } from "@heroicons/vue/24/outline";
import { useCustomersStore } from "@/stores/customers";
import type { Customer } from "@/types";

// Components
import CustomerList from "@/components/customers/CustomerList.vue";
import CRMPipeline from "@/components/customers/CRMPipeline.vue";
import CommunicationCenter from "@/components/customers/CommunicationCenter.vue";
import CustomerAgingReport from "@/components/customers/CustomerAgingReport.vue";
import CustomerFormModal from "@/components/customers/CustomerFormModal.vue";
import CustomerDetailsModal from "@/components/customers/CustomerDetailsModal.vue";
import ConfirmationModal from "@/components/ui/ConfirmationModal.vue";

// const {} = useI18n();
const customersStore = useCustomersStore();

// State
const activeTab = ref("list");
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailsModal = ref(false);
const showDeleteModal = ref(false);
const selectedCustomer = ref<Customer | null>(null);

// Tabs configuration
const tabs = [
  { key: "list", label: "customers.tabs.list" },
  { key: "crm", label: "customers.tabs.crm_pipeline" },
  { key: "communication", label: "customers.tabs.communication" },
  { key: "aging", label: "customers.tabs.aging_report" },
];

// Methods
const editCustomer = (customer: Customer) => {
  selectedCustomer.value = customer;
  showEditModal.value = true;
  showDetailsModal.value = false;
};

const viewCustomer = (customer: Customer) => {
  selectedCustomer.value = customer;
  showDetailsModal.value = true;
};

const deleteCustomer = (customer: Customer) => {
  selectedCustomer.value = customer;
  showDeleteModal.value = true;
  showDetailsModal.value = false;
};

const confirmDelete = async () => {
  if (selectedCustomer.value) {
    try {
      await customersStore.deleteCustomer(selectedCustomer.value.id);
      showDeleteModal.value = false;
      selectedCustomer.value = null;
    } catch (error) {
      console.error("Failed to delete customer:", error);
    }
  }
};

const handleCustomerSaved = () => {
  closeModals();
  // Refresh the customer list
  customersStore.fetchCustomers();
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showDetailsModal.value = false;
  selectedCustomer.value = null;
};

// Lifecycle
onMounted(() => {
  customersStore.fetchCustomers();
});
</script>
