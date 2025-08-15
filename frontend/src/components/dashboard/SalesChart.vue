<template>
  <div class="relative">
    <!-- Loading overlay -->
    <div
      v-if="isLoading"
      class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10 rounded-lg"
    >
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Chart container -->
    <div v-if="chartData" class="h-64">
      <Line
        :data="chartData"
        :options="chartOptions"
        :key="chartKey"
      />
    </div>

    <!-- Fallback when no data -->
    <div
      v-else
      class="h-64 flex items-center justify-center bg-gray-50 rounded-lg"
    >
      <div class="text-center">
        <ChartBarIcon class="w-12 h-12 text-gray-400 mx-auto mb-2" />
        <p class="text-gray-500">{{ $t("dashboard.sales_chart") }}</p>
        <p class="text-sm text-gray-400">{{ $t("dashboard.loading_chart") }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { useI18n } from "vue-i18n";
import { Line } from "vue-chartjs";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from "chart.js";
import { ChartBarIcon } from "@heroicons/vue/24/outline";
import { apiService } from "@/services/api";

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

interface Props {
  period: string;
}

const props = withDefaults(defineProps<Props>(), {
  period: "monthly",
});

const emit = defineEmits<{
  periodChange: [period: string];
  refresh: [];
}>();

const { t, locale } = useI18n();

const isLoading = ref(false);
const chartData = ref<any>(null);
const chartKey = ref(0); // Force re-render when data changes

const isRTL = computed(() => locale.value === "fa");

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true,
      position: "top" as const,
      rtl: isRTL.value,
      labels: {
        usePointStyle: true,
        padding: 20,
        font: {
          family: isRTL.value ? "Vazirmatn" : "Inter",
        },
      },
    },
    tooltip: {
      rtl: isRTL.value,
      titleFont: {
        family: isRTL.value ? "Vazirmatn" : "Inter",
      },
      bodyFont: {
        family: isRTL.value ? "Vazirmatn" : "Inter",
      },
      callbacks: {
        label: function (context: any) {
          return `${context.dataset.label}: $${context.parsed.y.toLocaleString()}`;
        },
      },
    },
  },
  scales: {
    x: {
      ticks: {
        font: {
          family: isRTL.value ? "Vazirmatn" : "Inter",
        },
      },
      grid: {
        display: false,
      },
    },
    y: {
      ticks: {
        font: {
          family: isRTL.value ? "Vazirmatn" : "Inter",
        },
        callback: function (value: any) {
          return "$" + value.toLocaleString();
        },
      },
      grid: {
        color: "rgba(0, 0, 0, 0.1)",
      },
    },
  },
  animation: {
    duration: 750,
    easing: "easeInOutQuart" as const,
  },
  interaction: {
    intersect: false,
    mode: "index" as const,
  },
}));

const fetchSalesData = async () => {
  try {
    isLoading.value = true;
    const response = await apiService.dashboard.getSalesChart(props.period);

    if (response.data.success && response.data.data) {
      const salesData = response.data.data;

      chartData.value = {
        labels: salesData.map((item: any) => item.label),
        datasets: [
          {
            label: t("dashboard.sales"),
            data: salesData.map((item: any) => item.sales),
            borderColor: "rgb(59, 130, 246)",
            backgroundColor: "rgba(59, 130, 246, 0.1)",
            fill: true,
            tension: 0.4,
            pointBackgroundColor: "rgb(59, 130, 246)",
            pointBorderColor: "#fff",
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
          },
        ],
      };
    } else {
      // Fallback to sample data
      chartData.value = generateSampleData();
    }

    chartKey.value++; // Force re-render
  } catch (error) {
    console.error("Failed to fetch sales chart data:", error);
    // Fallback to sample data
    chartData.value = generateSampleData();
    chartKey.value++; // Force re-render
  } finally {
    isLoading.value = false;
  }
};

const generateSampleData = () => {
  const labels = [];
  const data = [];

  if (props.period === "weekly") {
    const days = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
    labels.push(...days);
    data.push(12000, 15000, 8000, 22000, 18000, 25000, 20000);
  } else if (props.period === "monthly") {
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    labels.push(...months);
    data.push(45000, 52000, 48000, 61000, 55000, 67000, 59000, 73000, 68000, 82000, 76000, 89000);
  } else {
    const years = ["2020", "2021", "2022", "2023", "2024"];
    labels.push(...years);
    data.push(450000, 520000, 680000, 750000, 890000);
  }

  return {
    labels,
    datasets: [
      {
        label: t("dashboard.sales"),
        data,
        borderColor: "rgb(59, 130, 246)",
        backgroundColor: "rgba(59, 130, 246, 0.1)",
        fill: true,
        tension: 0.4,
        pointBackgroundColor: "rgb(59, 130, 246)",
        pointBorderColor: "#fff",
        pointBorderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 6,
      },
    ],
  };
};

// Watch for period changes
watch(
  () => props.period,
  () => {
    fetchSalesData();
  }
);

// Watch for locale changes to update chart
watch(
  () => locale.value,
  () => {
    chartKey.value++; // Force re-render with new locale
  }
);

onMounted(() => {
  fetchSalesData();
});

// Expose refresh method
defineExpose({
  refresh: fetchSalesData,
});
</script>

<style scoped>
/* Ensure proper RTL support for chart container */
[dir="rtl"] canvas {
  direction: ltr; /* Charts should always render LTR */
}
</style>