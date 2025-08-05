<template>
  <div class="space-y-6">
    <!-- Pipeline Overview -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("customers.crm_pipeline") }}
        </h3>
        <button
          @click="refreshPipeline"
          :disabled="customersStore.loading.crm"
          class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
        >
          <ArrowPathIcon
            :class="[
              'h-4 w-4 mr-2',
              customersStore.loading.crm && 'animate-spin',
            ]"
          />
          {{ $t("common.refresh") }}
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="customersStore.loading.crm" class="text-center py-8">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600 mx-auto"
        ></div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("common.loading") }}
        </p>
      </div>

      <!-- Pipeline Stats -->
      <div v-else-if="pipelineData" class="space-y-6">
        <!-- Conversion Rates -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{
                formatPercentage(pipelineData.conversion_rates.lead_to_prospect)
              }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("customers.lead_to_prospect") }}
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{
                formatPercentage(
                  pipelineData.conversion_rates.prospect_to_customer,
                )
              }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("customers.prospect_to_customer") }}
            </div>
          </div>
          <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">
              {{ formatPercentage(pipelineData.conversion_rates.overall) }}
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400">
              {{ $t("customers.overall_conversion") }}
            </div>
          </div>
        </div>

        <!-- Pipeline Stages -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
          <div
            v-for="stage in pipelineData.stages"
            :key="stage.stage"
            class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4"
          >
            <!-- Stage Header -->
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                {{ $t(`customers.stages.${stage.stage}`) }}
              </h4>
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  getStageColor(stage.stage),
                ]"
              >
                {{ stage.count }}
              </span>
            </div>

            <!-- Customers in Stage -->
            <div class="space-y-2 max-h-96 overflow-y-auto">
              <div
                v-for="customer in stage.customers"
                :key="customer.id"
                class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow-sm cursor-pointer hover:shadow-md transition-shadow"
                @click="viewCustomer(customer)"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1 min-w-0">
                    <p
                      class="text-sm font-medium text-gray-900 dark:text-white truncate"
                    >
                      {{ customer.name }}
                    </p>
                    <p
                      class="text-xs text-gray-500 dark:text-gray-400 truncate"
                    >
                      {{ customer.email || customer.phone || "-" }}
                    </p>
                  </div>
                  <div class="flex items-center space-x-1">
                    <!-- Customer Type Badge -->
                    <span
                      :class="[
                        'inline-flex px-1.5 py-0.5 text-xs font-medium rounded',
                        getCustomerTypeColor(customer.customer_type),
                      ]"
                    >
                      {{ customer.customer_type.charAt(0).toUpperCase() }}
                    </span>
                    <!-- Actions -->
                    <div class="flex items-center space-x-1">
                      <button
                        @click.stop="moveCustomer(customer, 'left')"
                        :disabled="!canMoveLeft(stage.stage)"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <ChevronLeftIcon class="h-3 w-3" />
                      </button>
                      <button
                        @click.stop="moveCustomer(customer, 'right')"
                        :disabled="!canMoveRight(stage.stage)"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <ChevronRightIcon class="h-3 w-3" />
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Outstanding Balance -->
                <div
                  v-if="
                    customer.outstanding_balance &&
                    customer.outstanding_balance > 0
                  "
                  class="mt-2"
                >
                  <span class="text-xs text-red-600 dark:text-red-400">
                    {{ $t("customers.outstanding") }}:
                    {{ formatCurrency(customer.outstanding_balance) }}
                  </span>
                </div>
              </div>

              <!-- Empty State -->
              <div v-if="stage.customers.length === 0" class="text-center py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ $t("customers.no_customers_in_stage") }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <UsersIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          {{ $t("customers.no_pipeline_data") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("customers.no_pipeline_description") }}
        </p>
      </div>
    </div>

    <!-- Stage Movement Modal -->
    <div v-if="showMoveModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div
        class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
      >
        <!-- Background overlay -->
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="showMoveModal = false"
        ></div>

        <!-- Modal panel -->
        <div
          class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
        >
          <div class="mb-4">
            <h3
              class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
            >
              {{ $t("customers.move_customer") }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{
                $t("customers.move_customer_description", {
                  name: selectedCustomer?.name,
                  stage: $t(`customers.stages.${targetStage}`),
                })
              }}
            </p>
          </div>

          <!-- Notes -->
          <div class="mb-4">
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
            >
              {{ $t("customers.notes") }}
            </label>
            <textarea
              v-model="moveNotes"
              rows="3"
              :placeholder="$t('customers.move_notes_placeholder')"
              class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex items-center justify-end space-x-3">
            <button
              type="button"
              @click="showMoveModal = false"
              class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600"
            >
              {{ $t("common.cancel") }}
            </button>
            <button
              type="button"
              @click="confirmMove"
              :disabled="movingCustomer"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 disabled:opacity-50"
            >
              <div
                v-if="movingCustomer"
                class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
              ></div>
              {{ $t("common.confirm") }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";

import {
  ArrowPathIcon,
  UsersIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
} from "@heroicons/vue/24/outline";
import { useCustomersStore } from "@/stores/customers";
import type { Customer } from "@/types";

const customersStore = useCustomersStore();

// State
const showMoveModal = ref(false);
const selectedCustomer = ref<Customer | null>(null);
const targetStage = ref("");
const moveNotes = ref("");
const movingCustomer = ref(false);

// Computed
const pipelineData = computed(() => customersStore.crmPipeline);

// Stage order for movement logic
const stageOrder = ["lead", "prospect", "customer", "inactive"];

// Methods
const refreshPipeline = async () => {
  await customersStore.fetchCRMPipeline();
};

const viewCustomer = (customer: Customer) => {
  // Emit event to parent or navigate to customer details
  console.log("View customer:", customer);
};

const moveCustomer = (customer: Customer, direction: "left" | "right") => {
  const currentStageIndex = stageOrder.indexOf(customer.crm_stage);
  let newStageIndex;

  if (direction === "left") {
    newStageIndex = currentStageIndex - 1;
  } else {
    newStageIndex = currentStageIndex + 1;
  }

  if (newStageIndex >= 0 && newStageIndex < stageOrder.length) {
    selectedCustomer.value = customer;
    targetStage.value = stageOrder[newStageIndex];
    showMoveModal.value = true;
  }
};

const canMoveLeft = (stage: string) => {
  const index = stageOrder.indexOf(stage);
  return index > 0;
};

const canMoveRight = (stage: string) => {
  const index = stageOrder.indexOf(stage);
  return index < stageOrder.length - 1;
};

const confirmMove = async () => {
  if (!selectedCustomer.value) return;

  movingCustomer.value = true;
  try {
    await customersStore.updateCRMStage(
      selectedCustomer.value.id,
      targetStage.value,
      moveNotes.value,
    );

    showMoveModal.value = false;
    moveNotes.value = "";
    selectedCustomer.value = null;
    targetStage.value = "";
  } catch (error) {
    console.error("Failed to move customer:", error);
  } finally {
    movingCustomer.value = false;
  }
};

const getStageColor = (stage: string) => {
  const colors = {
    lead: "bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200",
    prospect: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    customer:
      "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
    inactive: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200",
  };
  return colors[stage as keyof typeof colors] || colors.lead;
};

const getCustomerTypeColor = (type: string) => {
  const colors = {
    retail: "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200",
    wholesale:
      "bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200",
    vip: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200",
  };
  return colors[type as keyof typeof colors] || colors.retail;
};

const formatPercentage = (value: number) => {
  return `${Math.round(value)}%`;
};

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
};

// Lifecycle
onMounted(() => {
  if (!pipelineData.value) {
    customersStore.fetchCRMPipeline();
  }
});
</script>
