<template>
  <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
      <!-- Header -->
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("dashboard.gold_purity_performance") }}
        </h3>
        <button
          @click="refreshData"
          :disabled="loading"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          <ArrowPathIcon :class="['h-5 w-5', loading && 'animate-spin']" />
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-48">
        <div
          class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"
        ></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-8">
        <div class="text-red-500 dark:text-red-400 mb-2">
          {{ $t("dashboard.error_loading_data") }}
        </div>
        <button
          @click="refreshData"
          class="text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400"
        >
          {{ $t("common.retry") }}
        </button>
      </div>

      <!-- Content -->
      <div v-else-if="data && data.length > 0" class="space-y-4">
        <!-- Summary Stats -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-yellow-50 dark:bg-yellow-900/20 p-3 rounded-lg">
            <div
              class="text-sm font-medium text-yellow-800 dark:text-yellow-400 mb-1"
            >
              {{ $t("dashboard.total_gold_sold") }}
            </div>
            <div class="text-lg font-bold text-yellow-600 dark:text-yellow-400">
              {{ formatWeight(totalWeightSold) }}
            </div>
          </div>

          <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
            <div
              class="text-sm font-medium text-green-800 dark:text-green-400 mb-1"
            >
              {{ $t("dashboard.avg_margin") }}
            </div>
            <div class="text-lg font-bold text-green-600 dark:text-green-400">
              {{ formatPercentage(averageMargin) }}
            </div>
          </div>
        </div>

        <!-- Purity Chart -->
        <div class="h-48">
          <canvas ref="chartCanvas"></canvas>
        </div>

        <!-- Purity List -->
        <div class="space-y-2">
          <div
            v-for="purity in data.slice(0, 6)"
            :key="purity.gold_purity"
            class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded"
          >
            <div class="flex items-center space-x-3">
              <div class="flex-shrink-0">
                <div
                  class="w-3 h-3 rounded-full"
                  :style="{
                    backgroundColor: getPurityColor(purity.gold_purity),
                  }"
                ></div>
              </div>
              <div>
                <div class="font-medium text-sm text-gray-900 dark:text-white">
                  {{ formatGoldPurity(purity.gold_purity) }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ purity.total_orders }} {{ $t("dashboard.orders") }}
                </div>
              </div>
            </div>
            <div class="text-right">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ formatCurrency(purity.total_revenue) }}
              </div>
              <div
                class="text-xs"
                :class="[
                  purity.margin_percentage >= 20
                    ? 'text-green-600 dark:text-green-400'
                    : purity.margin_percentage >= 10
                      ? 'text-yellow-600 dark:text-yellow-400'
                      : 'text-red-600 dark:text-red-400',
                ]"
              >
                {{ formatPercentage(purity.margin_percentage) }}
              </div>
            </div>
          </div>
        </div>

        <!-- View All Link -->
        <div class="text-center pt-2">
          <router-link
            to="/reports?tab=gold-purity"
            class="text-sm text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300"
          >
            {{ $t("dashboard.view_detailed_analysis") }} →
          </router-link>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="text-center py-8">
        <BeakerIcon class="mx-auto h-12 w-12 text-gray-400" />
        <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("dashboard.no_gold_purity_data") }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useApi } from "@/composables/useApi";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { ArrowPathIcon, BeakerIcon } from "@heroicons/vue/24/outline";
import Chart from "chart.js/auto";
import { apiService } from "@/services/api";

interface GoldPurityData {
  gold_purity: number;
  total_orders: number;
  total_revenue: number;
  margin_percentage: number;
  total_weight_sold: number;
}

const { t: _t } = useI18n();
const { execute, data, loading, error } = useApi<GoldPurityData[]>();
const { formatCurrency, formatPercentage } = useNumberFormatter();

// Data
const chartCanvas = ref<HTMLCanvasElement | null>(null);
const chart = ref<Chart | null>(null);

// Computed
const totalWeightSold = computed(() => {
  if (!data.value?.length) return 0;
  return data.value.reduce(
    (total, purity) => total + (purity.total_weight_sold || 0),
    0,
  );
});

const averageMargin = computed(() => {
  if (!data.value?.length) return 0;
  const totalMargin = data.value.reduce(
    (total, purity) => total + purity.margin_percentage,
    0,
  );
  return totalMargin / data.value.length;
});

// Methods
const refreshData = async () => {
  await execute(() => apiService.get("/api/dashboard/gold-purity-performance"));
};

const formatGoldPurity = (purity: number) => {
  return `${purity}K`;
};

const formatWeight = (weight: number) => {
  return `${weight.toFixed(2)}g`;
};

const getPurityColor = (purity: number) => {
  // Color gradient based on purity level
  const colors: Record<number, string> = {
    24: "#FFD700", // Gold
    22: "#FFA500", // Orange
    21: "#FF8C00", // Dark Orange
    18: "#FF6347", // Tomato
    14: "#FF4500", // Orange Red
    10: "#DC143C", // Crimson
    9: "#B22222", // Fire Brick
  };

  return colors[purity] || "#808080"; // Default gray
};

const createChart = () => {
  if (!chartCanvas.value || !data.value?.length) return;

  const ctx = chartCanvas.value.getContext("2d");
  if (!ctx) return;

  // Destroy existing chart
  if (chart.value) {
    chart.value.destroy();
  }

  const chartData = data.value.slice(0, 8); // Top 8 purities

  chart.value = new Chart(ctx, {
    type: "doughnut",
    data: {
      labels: chartData.map((purity) => formatGoldPurity(purity.gold_purity)),
      datasets: [
        {
          data: chartData.map((purity) => purity.total_revenue),
          backgroundColor: chartData.map((purity) =>
            getPurityColor(purity.gold_purity),
          ),
          borderWidth: 2,
          borderColor: "#ffffff",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: "right",
          labels: {
            usePointStyle: true,
            padding: 15,
          },
        },
        tooltip: {
          callbacks: {
            label: function (context) {
              const label = context.label || "";
              const value = formatCurrency(context.parsed);
              const percentage = (
                (context.parsed /
                  context.dataset.data.reduce((a, b) => a + b, 0)) *
                100
              ).toFixed(1);
              return `${label}: ${value} (${percentage}%)`;
            },
          },
        },
      },
    },
  });
};

// Watchers
watch(data, () => {
  nextTick(() => {
    createChart();
  });
});

// Lifecycle
onMounted(() => {
  refreshData();
});

onUnmounted(() => {
  if (chart.value) {
    chart.value.destroy();
  }
});
</script>
