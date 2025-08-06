<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          {{ $t("invoices.title") }}
        </h1>
        <p class="mt-2 text-sm text-gray-700 dark:text-gray-300">
          {{ $t("invoices.description") }}
        </p>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none space-x-3">
        <button
          @click="openTemplateDesigner"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
        >
          <PaintBrushIcon class="h-4 w-4 mr-2" />
          {{ $t("invoices.template_designer") }}
        </button>
        <button
          @click="showCreateModal = true"
          type="button"
          class="inline-flex items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
        >
          <PlusIcon class="h-4 w-4 mr-2" />
          {{ $t("invoices.create_invoice") }}
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
          <span
            v-if="tab.count !== undefined"
            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
          >
            {{ tab.count }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- Invoice List Tab -->
      <div v-if="activeTab === 'list'">
        <InvoiceList
          @edit-invoice="editInvoice"
          @view-invoice="viewInvoice"
          @delete-invoice="deleteInvoice"
          @duplicate-invoice="duplicateInvoice"
          @generate-pdf="generatePDF"
          @send-invoice="sendInvoice"
        />
      </div>

      <!-- Templates Tab -->
      <div v-if="activeTab === 'templates'">
        <InvoiceTemplateList
          @edit-template="editTemplate"
          @delete-template="deleteTemplate"
          @create-template="showTemplateDesigner = true"
        />
      </div>

      <!-- Batch Operations Tab -->
      <div v-if="activeTab === 'batch'">
        <BatchInvoiceInterface />
      </div>

      <!-- Recurring Invoices Tab -->
      <div v-if="activeTab === 'recurring'">
        <RecurringInvoiceList
          @edit-recurring="editRecurringInvoice"
          @delete-recurring="deleteRecurringInvoice"
          @create-recurring="showRecurringModal = true"
        />
      </div>
    </div>

    <!-- Create/Edit Invoice Modal -->
    <InvoiceFormModal
      v-if="showCreateModal || showEditModal"
      :invoice="selectedInvoice"
      :is-edit="showEditModal"
      @close="closeModals"
      @saved="handleInvoiceSaved"
    />

    <!-- Invoice Details Modal -->
    <InvoiceDetailsModal
      v-if="showDetailsModal"
      :invoice="selectedInvoice"
      @close="closeModals"
      @edit="editInvoice"
      @delete="deleteInvoice"
      @duplicate="duplicateInvoice"
      @generate-pdf="generatePDF"
      @send="sendInvoice"
    />

    <!-- Template Designer Modal -->
    <InvoiceTemplateDesigner
      v-if="showTemplateDesigner"
      :template="selectedTemplate"
      @close="closeTemplateDesigner"
      @saved="handleTemplateSaved"
    />

    <!-- Recurring Invoice Setup Modal -->
    <RecurringInvoiceModal
      v-if="showRecurringModal"
      :recurring-invoice="selectedRecurring"
      @close="closeRecurringModal"
      @saved="handleRecurringSaved"
    />

    <!-- PDF Preview Modal -->
    <PDFPreviewModal
      v-if="showPDFPreview"
      :invoice="selectedInvoice"
      @close="showPDFPreview = false"
      @download="generatePDF"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      :title="$t('invoices.delete_invoice')"
      :message="
        $t('invoices.delete_confirmation', {
          number: selectedInvoice?.invoice_number,
        })
      "
      :loading="invoicesStore.loading.deleting"
      @confirm="confirmDelete"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { PlusIcon, PaintBrushIcon } from "@heroicons/vue/24/outline";
import { useInvoicesStore } from "@/stores/invoices";
import type { Invoice, InvoiceTemplate } from "@/types";

// Components
import InvoiceList from "@/components/invoices/InvoiceList.vue";
import InvoiceTemplateList from "@/components/invoices/InvoiceTemplateList.vue";
import BatchInvoiceInterface from "@/components/invoices/BatchInvoiceInterface.vue";
import RecurringInvoiceList from "@/components/invoices/RecurringInvoiceList.vue";
import InvoiceFormModal from "@/components/invoices/InvoiceFormModal.vue";
import InvoiceDetailsModal from "@/components/invoices/InvoiceDetailsModal.vue";
import InvoiceTemplateDesigner from "@/components/invoices/InvoiceTemplateDesigner.vue";
import RecurringInvoiceModal from "@/components/invoices/RecurringInvoiceModal.vue";
import PDFPreviewModal from "@/components/invoices/PDFPreviewModal.vue";
import ConfirmationModal from "@/components/ui/ConfirmationModal.vue";

const invoicesStore = useInvoicesStore();

// State
const activeTab = ref("list");
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailsModal = ref(false);
const showDeleteModal = ref(false);
const showTemplateDesigner = ref(false);
const showRecurringModal = ref(false);
const showPDFPreview = ref(false);
const selectedInvoice = ref<Invoice | null>(null);
const selectedTemplate = ref<InvoiceTemplate | null>(null);
const selectedRecurring = ref<any | null>(null);

// Computed
const tabs = computed(() => [
  {
    key: "list",
    label: "invoices.tabs.all_invoices",
    count: invoicesStore.invoices.length,
  },
  {
    key: "templates",
    label: "invoices.tabs.templates",
    count: invoicesStore.templates.length,
  },
  {
    key: "batch",
    label: "invoices.tabs.batch_operations",
  },
  {
    key: "recurring",
    label: "invoices.tabs.recurring",
    count: invoicesStore.recurringInvoices.length,
  },
]);

// Methods
const editInvoice = (invoice: Invoice) => {
  selectedInvoice.value = invoice;
  showEditModal.value = true;
  showDetailsModal.value = false;
};

const viewInvoice = (invoice: Invoice) => {
  selectedInvoice.value = invoice;
  showDetailsModal.value = true;
};

const deleteInvoice = (invoice: Invoice) => {
  selectedInvoice.value = invoice;
  showDeleteModal.value = true;
  showDetailsModal.value = false;
};

const duplicateInvoice = async (invoice: Invoice) => {
  try {
    await invoicesStore.duplicateInvoice(invoice.id);
    // Refresh the invoice list
    await invoicesStore.fetchInvoices();
  } catch (error) {
    console.error("Failed to duplicate invoice:", error);
  }
};

const generatePDF = async (invoice: Invoice) => {
  try {
    await invoicesStore.generatePDF(invoice.id);
  } catch (error) {
    console.error("Failed to generate PDF:", error);
  }
};

const sendInvoice = async (
  invoice: Invoice,
  method: "email" | "whatsapp" | "sms",
) => {
  try {
    await invoicesStore.sendInvoice(invoice.id, method);
  } catch (error) {
    console.error("Failed to send invoice:", error);
  }
};

const editTemplate = (template: InvoiceTemplate) => {
  selectedTemplate.value = template;
  showTemplateDesigner.value = true;
};

const deleteTemplate = async (template: InvoiceTemplate) => {
  try {
    await invoicesStore.deleteTemplate(template.id);
  } catch (error) {
    console.error("Failed to delete template:", error);
  }
};

const openTemplateDesigner = () => {
  selectedTemplate.value = null;
  showTemplateDesigner.value = true;
};

const editRecurringInvoice = (recurring: any) => {
  selectedRecurring.value = recurring;
  showRecurringModal.value = true;
};

const deleteRecurringInvoice = async (recurring: any) => {
  // Implementation for deleting recurring invoice
  console.log("Delete recurring invoice:", recurring);
};

const confirmDelete = async () => {
  if (selectedInvoice.value) {
    try {
      await invoicesStore.deleteInvoice(selectedInvoice.value.id);
      showDeleteModal.value = false;
      selectedInvoice.value = null;
    } catch (error) {
      console.error("Failed to delete invoice:", error);
    }
  }
};

const handleInvoiceSaved = () => {
  closeModals();
  invoicesStore.fetchInvoices();
};

const handleTemplateSaved = () => {
  closeTemplateDesigner();
  invoicesStore.fetchTemplates();
};

const handleRecurringSaved = () => {
  closeRecurringModal();
  invoicesStore.fetchRecurringInvoices();
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showDetailsModal.value = false;
  selectedInvoice.value = null;
};

const closeTemplateDesigner = () => {
  showTemplateDesigner.value = false;
  selectedTemplate.value = null;
};

const closeRecurringModal = () => {
  showRecurringModal.value = false;
  selectedRecurring.value = null;
};

// Lifecycle
onMounted(async () => {
  // Load data with error handling to prevent blocking the UI
  try {
    await Promise.all([
      invoicesStore.fetchInvoices().catch((error) => {
        console.warn("Failed to load invoices:", error);
      }),
      invoicesStore.fetchTemplates().catch((error) => {
        console.warn("Failed to load templates:", error);
      }),
      invoicesStore.fetchRecurringInvoices().catch((error) => {
        console.warn("Failed to load recurring invoices:", error);
      }),
    ]);
  } catch (error) {
    console.warn("Some invoice data failed to load, but continuing:", error);
  }
});
</script>
