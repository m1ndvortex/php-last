<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ title }}
      </h3>
      <div class="flex items-center space-x-2 rtl:space-x-reverse">
        <button
          @click="refreshTable"
          class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
          :disabled="isLoading"
        >
          <ArrowPathIcon
            class="w-4 h-4"
            :class="{ 'animate-spin': isLoading }"
          />
        </button>
      </div>
    </div>

    <div class="relative">
      <div
        v-if="isLoading"
        class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10"
      >
        <div
          class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary-600"
        ></div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                v-for="column in columns"
                :key="column.key"
                class="px-6 py-3 text-right rtl:text-right ltr:text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                :class="{ 'text-right': column.align === 'right' }"
              >
                {{ column.label }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-if="data.length === 0">
              <td
                :colspan="columns.length"
                class="px-6 py-4 text-center text-gray-500"
              >
                {{ $t("common.no_data") }}
              </td>
            </tr>
            <tr
              v-for="(row, index) in displayData"
              :key="index"
              class="hover:bg-gray-50 transition-colors"
            >
              <td
                v-for="column in columns"
                :key="column.key"
                class="px-6 py-4 whitespace-nowrap text-sm text-right rtl:text-right ltr:text-left"
                :class="[
                  column.align === 'right'
                    ? 'text-right'
                    : column.align === 'left'
                      ? 'ltr:text-left rtl:text-right'
                      : 'text-right rtl:text-right ltr:text-left',
                  getCellClass(column, row[column.key]),
                ]"
              >
                <component
                  v-if="column.component"
                  :is="column.component"
                  :value="row[column.key]"
                  :row="row"
                />
                <span v-else>
                  {{ formatCellValue(column, row[column.key]) }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="showPagination && totalPages > 1"
        class="mt-4 flex items-center justify-between"
      >
        <div class="text-sm text-gray-700">
          {{ $t("table.showing") }} {{ startIndex + 1 }} {{ $t("table.to") }}
          {{ endIndex }} {{ $t("table.of") }} {{ data.length }}
          {{ $t("table.results") }}
        </div>
        <div class="flex items-center space-x-2 rtl:space-x-reverse">
          <button
            @click="previousPage"
            :disabled="currentPage === 1"
            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ $t("table.previous") }}
          </button>
          <span class="text-sm text-gray-700">
            {{ currentPage }} / {{ totalPages }}
          </span>
          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ $t("table.next") }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { ArrowPathIcon } from "@heroicons/vue/24/outline";

interface TableColumn {
  key: string;
  label: string;
  type?: "text" | "number" | "currency" | "date" | "status" | "custom";
  align?: "left" | "right" | "center";
  component?: any;
  formatter?: (value: any) => string;
}

interface Props {
  title: string;
  columns: TableColumn[];
  data: any[];
  showPagination?: boolean;
  pageSize?: number;
}

const props = withDefaults(defineProps<Props>(), {
  showPagination: true,
  pageSize: 5,
});

const emit = defineEmits<{
  refresh: [];
}>();

const { t } = useI18n();
const { formatNumber, formatCurrency } = useNumberFormatter();

const isLoading = ref(false);
const currentPage = ref(1);

const totalPages = computed(() =>
  Math.ceil(props.data.length / props.pageSize),
);

const startIndex = computed(() => (currentPage.value - 1) * props.pageSize);

const endIndex = computed(() =>
  Math.min(startIndex.value + props.pageSize, props.data.length),
);

const displayData = computed(() => {
  if (!props.showPagination) return props.data;
  return props.data.slice(startIndex.value, endIndex.value);
});

const formatCellValue = (column: TableColumn, value: any) => {
  if (value === null || value === undefined) return "-";

  if (column.formatter) {
    return column.formatter(value);
  }

  switch (column.type) {
    case "currency":
      return formatCurrency(Number(value));
    case "number":
      return formatNumber(Number(value));
    case "date":
      return new Date(value).toLocaleDateString();
    case "status":
      return t(`status.${value}`);
    default:
      return String(value);
  }
};

const getCellClass = (column: TableColumn, value: any) => {
  const classes = [];

  switch (column.type) {
    case "currency":
    case "number":
      classes.push("font-mono");
      break;
    case "status":
      classes.push("font-medium");
      if (value === "active" || value === "paid") {
        classes.push("text-green-600");
      } else if (value === "pending") {
        classes.push("text-yellow-600");
      } else if (value === "inactive" || value === "overdue") {
        classes.push("text-red-600");
      }
      break;
  }

  return classes.join(" ");
};

const refreshTable = () => {
  isLoading.value = true;
  emit("refresh");

  // Simulate loading delay
  setTimeout(() => {
    isLoading.value = false;
  }, 1000);
};

const previousPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
  }
};

// Set sample data if no data provided
if (props.data.length === 0) {
  // This would normally come from the parent component or store
  // For demo purposes, we'll generate sample data
}
</script>

<style scoped>
/* RTL Support for Table Widget */
[dir="rtl"] .space-x-2 > * + * {
  margin-left: 0 !important;
  margin-right: 0.5rem !important;
}

[dir="rtl"] .text-left {
  text-align: right !important;
}

[dir="rtl"] th {
  text-align: right !important;
}

[dir="rtl"] td {
  text-align: right !important;
}

/* Ensure table content is right-aligned in RTL */
[dir="rtl"] table th,
[dir="rtl"] table td {
  text-align: right !important;
}

/* Ensure proper RTL text alignment for all Persian text */
[dir="rtl"] p,
[dir="rtl"] span,
[dir="rtl"] div {
  text-align: right !important;
}
</style>
