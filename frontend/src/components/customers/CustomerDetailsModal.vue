<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div
      class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
    >
      <!-- Background overlay -->
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="$emit('close')"
      ></div>

      <!-- Modal panel -->
      <div
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6"
      >
        <div v-if="customer">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
              <!-- Customer Avatar -->
              <div class="flex-shrink-0 h-16 w-16">
                <div
                  class="h-16 w-16 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center"
                >
                  <span
                    class="text-xl font-medium text-primary-800 dark:text-primary-200"
                  >
                    {{ getInitials(customer.name) }}
                  </span>
                </div>
              </div>

              <!-- Customer Info -->
              <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                  {{ customer.name }}
                </h3>
                <div class="flex items-center space-x-2 mt-1">
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      getCustomerTypeClass(customer.customer_type),
                    ]"
                  >
                    {{ $t(`customers.types.${customer.customer_type}`) }}
                  </span>
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      getCRMStageClass(customer.crm_stage),
                    ]"
                  >
                    {{ $t(`customers.stages.${customer.crm_stage}`) }}
                  </span>
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      customer.is_active
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                    ]"
                  >
                    {{
                      customer.is_active
                        ? $t("common.active")
                        : $t("common.inactive")
                    }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2">
              <button
                @click="exportVCard"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                {{ $t("customers.export_vcard") }}
              </button>
              <button
                @click="$emit('edit', customer)"
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
              >
                <PencilIcon class="h-4 w-4 mr-2" />
                {{ $t("common.edit") }}
              </button>
              <button
                @click="$emit('delete', customer)"
                class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50"
              >
                <TrashIcon class="h-4 w-4 mr-2" />
                {{ $t("common.delete") }}
              </button>
              <button
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>
          </div>

          <!-- Content Tabs -->
          <div class="border-b border-gray-200 dark:border-gray-700 mb-6">
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
          <div class="space-y-6">
            <!-- Details Tab -->
            <div v-if="activeTab === 'details'">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Contact Information -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                  <h4
                    class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                  >
                    {{ $t("customers.contact_information") }}
                  </h4>
                  <dl class="space-y-2">
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.email") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ customer.email || "-" }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.phone") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ customer.phone || "-" }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.address") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ customer.address || "-" }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.preferred_language") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ $t(`languages.${customer.preferred_language}`) }}
                      </dd>
                    </div>
                  </dl>
                </div>

                <!-- Financial Information -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                  <h4
                    class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                  >
                    {{ $t("customers.financial_information") }}
                  </h4>
                  <dl class="space-y-2">
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.credit_limit") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ formatCurrency(customer.credit_limit || 0) }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.payment_terms") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ customer.payment_terms || 0 }}
                        {{ $t("common.days") }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.total_invoiced") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ formatCurrency(customer.total_invoice_amount || 0) }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.outstanding_balance") }}
                      </dt>
                      <dd
                        class="text-sm font-semibold"
                        :class="
                          customer.outstanding_balance &&
                          customer.outstanding_balance > 0
                            ? 'text-red-600 dark:text-red-400'
                            : 'text-gray-900 dark:text-white'
                        "
                      >
                        {{ formatCurrency(customer.outstanding_balance || 0) }}
                      </dd>
                    </div>
                  </dl>
                </div>

                <!-- Personal Information -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                  <h4
                    class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                  >
                    {{ $t("customers.personal_information") }}
                  </h4>
                  <dl class="space-y-2">
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.birthday") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ formatDate(customer.birthday) }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.anniversary") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ formatDate(customer.anniversary) }}
                      </dd>
                    </div>
                    <div v-if="customer.age">
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.age") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ customer.age }} {{ $t("common.years") }}
                      </dd>
                    </div>
                  </dl>
                </div>

                <!-- CRM Information -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                  <h4
                    class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                  >
                    {{ $t("customers.crm_information") }}
                  </h4>
                  <dl class="space-y-2">
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.lead_source") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{
                          customer.lead_source
                            ? $t(
                                `customers.lead_sources.${customer.lead_source}`,
                              )
                            : "-"
                        }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.preferred_communication") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{
                          customer.preferred_communication_method
                            ? $t(
                                `communication.${customer.preferred_communication_method}`,
                              )
                            : "-"
                        }}
                      </dd>
                    </div>
                    <div>
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.last_invoice") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        {{ formatDate(customer.last_invoice_date) }}
                      </dd>
                    </div>
                    <div v-if="customer.tags && customer.tags.length > 0">
                      <dt
                        class="text-xs font-medium text-gray-500 dark:text-gray-400"
                      >
                        {{ $t("customers.tags") }}
                      </dt>
                      <dd class="text-sm text-gray-900 dark:text-white">
                        <div class="flex flex-wrap gap-1 mt-1">
                          <span
                            v-for="tag in customer.tags"
                            :key="tag"
                            class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                          >
                            {{ tag }}
                          </span>
                        </div>
                      </dd>
                    </div>
                  </dl>
                </div>
              </div>

              <!-- Notes -->
              <div
                v-if="customer.notes"
                class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4"
              >
                <h4
                  class="text-sm font-medium text-gray-900 dark:text-white mb-3"
                >
                  {{ $t("customers.notes") }}
                </h4>
                <p
                  class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap"
                >
                  {{ customer.notes }}
                </p>
              </div>
            </div>

            <!-- Communications Tab -->
            <div v-if="activeTab === 'communications'">
              <CustomerCommunications :customer="customer" />
            </div>

            <!-- Invoices Tab -->
            <div v-if="activeTab === 'invoices'">
              <CustomerInvoices :customer="customer" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

import {
  XMarkIcon,
  PencilIcon,
  TrashIcon,
  ArrowDownTrayIcon,
} from "@heroicons/vue/24/outline";
import { useCustomersStore } from "@/stores/customers";
import type { Customer } from "@/types";

// Components (these would need to be created)
import CustomerCommunications from "./CustomerCommunications.vue";
import CustomerInvoices from "./CustomerInvoices.vue";

// Props & Emits
interface Props {
  customer: Customer | null;
}

const props = defineProps<Props>();

defineEmits<{
  close: [];
  edit: [customer: Customer];
  delete: [customer: Customer];
}>();

const customersStore = useCustomersStore();

// State
const activeTab = ref("details");

// Tabs configuration
const tabs = [
  { key: "details", label: "customers.tabs.details" },
  { key: "communications", label: "customers.tabs.communications" },
  { key: "invoices", label: "customers.tabs.invoices" },
];

// Methods
const exportVCard = async () => {
  if (!props.customer) return;

  try {
    await customersStore.exportVCard(props.customer.id);
  } catch (error) {
    console.error("Failed to export vCard:", error);
  }
};

const getInitials = (name: string) => {
  return name
    .split(" ")
    .map((word) => word.charAt(0))
    .join("")
    .toUpperCase()
    .slice(0, 2);
};

const getCustomerTypeClass = (type: string) => {
  const classes = {
    retail: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    wholesale:
      "bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200",
    vip: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
  };
  return (
    classes[type as keyof typeof classes] ||
    "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200"
  );
};

const getCRMStageClass = (stage: string) => {
  const classes = {
    lead: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
    prospect: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    customer:
      "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    inactive: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
  };
  return (
    classes[stage as keyof typeof classes] ||
    "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200"
  );
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
};

const formatDate = (dateString?: string) => {
  if (!dateString) return "-";
  return new Date(dateString).toLocaleDateString();
};
</script>
