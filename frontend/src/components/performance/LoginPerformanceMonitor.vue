<template>
  <div v-if="showMonitor" class="fixed bottom-4 right-4 z-50">
    <div class="bg-white rounded-lg shadow-lg border p-4 max-w-sm">
      <div class="flex items-center justify-between mb-3">
        <h3 class="text-sm font-semibold text-gray-900">Login Performance</h3>
        <button
          @click="toggleMonitor"
          class="text-gray-400 hover:text-gray-600 text-xs"
        >
          {{ isExpanded ? 'Collapse' : 'Expand' }}
        </button>
      </div>

      <!-- Performance Grade -->
      <div class="flex items-center mb-3">
        <div
          class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-bold"
          :class="gradeColorClass"
        >
          {{ currentGrade }}
        </div>
        <div class="ml-3">
          <div class="text-sm font-medium text-gray-900">
            {{ currentLoadTime.toFixed(0) }}ms
          </div>
          <div class="text-xs text-gray-500">
            Target: &lt; 2000ms
          </div>
        </div>
      </div>

      <!-- Expanded Details -->
      <div v-if="isExpanded" class="space-y-2">
        <!-- Performance Metrics -->
        <div class="text-xs space-y-1">
          <div class="flex justify-between">
            <span class="text-gray-600">Page Load:</span>
            <span class="font-mono">{{ metrics.pageLoadTime.toFixed(0) }}ms</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Assets:</span>
            <span class="font-mono">{{ metrics.assetLoadTime.toFixed(0) }}ms</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Auth:</span>
            <span class="font-mono">{{ metrics.authenticationTime.toFixed(0) }}ms</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Redirect:</span>
            <span class="font-mono">{{ metrics.redirectTime.toFixed(0) }}ms</span>
          </div>
        </div>

        <!-- Asset Performance -->
        <div class="border-t pt-2">
          <div class="text-xs font-medium text-gray-700 mb-1">Assets</div>
          <div class="text-xs space-y-1">
            <div class="flex justify-between">
              <span class="text-gray-600">CSS:</span>
              <span class="font-mono">{{ assetMetrics.cssLoadTime.toFixed(0) }}ms</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">JS:</span>
              <span class="font-mono">{{ assetMetrics.jsLoadTime.toFixed(0) }}ms</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Fonts:</span>
              <span class="font-mono">{{ assetMetrics.fontLoadTime.toFixed(0) }}ms</span>
            </div>
          </div>
        </div>

        <!-- Cache Performance -->
        <div class="border-t pt-2">
          <div class="text-xs font-medium text-gray-700 mb-1">Cache</div>
          <div class="flex justify-between text-xs">
            <span class="text-gray-600">Hit Rate:</span>
            <span class="font-mono">{{ cacheHitRate.toFixed(1) }}%</span>
          </div>
        </div>

        <!-- Optimization Status -->
        <div class="border-t pt-2">
          <div class="text-xs font-medium text-gray-700 mb-1">Optimizations</div>
          <div class="flex flex-wrap gap-1">
            <span
              v-for="optimization in activeOptimizations"
              :key="optimization"
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
            >
              {{ optimization }}
            </span>
          </div>
        </div>

        <!-- Suggestions -->
        <div v-if="suggestions.length > 0" class="border-t pt-2">
          <div class="text-xs font-medium text-gray-700 mb-1">Suggestions</div>
          <ul class="text-xs text-gray-600 space-y-1">
            <li v-for="suggestion in suggestions.slice(0, 2)" :key="suggestion">
              • {{ suggestion }}
            </li>
          </ul>
        </div>

        <!-- Actions -->
        <div class="border-t pt-2 flex gap-2">
          <button
            @click="exportMetrics"
            class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200"
          >
            Export
          </button>
          <button
            @click="clearMetrics"
            class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
          >
            Clear
          </button>
          <button
            @click="reoptimize"
            class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200"
          >
            Reoptimize
          </button>
        </div>
      </div>

      <!-- Quick Actions (Collapsed) -->
      <div v-else class="flex gap-2">
        <button
          @click="reoptimize"
          class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200"
        >
          Optimize
        </button>
        <button
          @click="hideMonitor"
          class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200"
        >
          Hide
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { loginPerformanceService } from '@/services/loginPerformanceService';
import { loginAssetOptimizer } from '@/services/loginAssetOptimizer';

// Component state
const showMonitor = ref(false);
const isExpanded = ref(false);
const updateInterval = ref<number | null>(null);

// Performance data
const metrics = ref({
  pageLoadTime: 0,
  assetLoadTime: 0,
  authenticationTime: 0,
  redirectTime: 0,
  totalLoginTime: 0,
});

const assetMetrics = ref({
  cssLoadTime: 0,
  jsLoadTime: 0,
  fontLoadTime: 0,
  imageLoadTime: 0,
  totalAssetSize: 0,
  compressedSize: 0,
  cacheHits: 0,
  cacheMisses: 0,
});

const suggestions = ref<string[]>([]);
const activeOptimizations = ref<string[]>([]);

// Computed properties
const currentLoadTime = computed(() => metrics.value.totalLoginTime || metrics.value.pageLoadTime);

const currentGrade = computed(() => {
  const time = currentLoadTime.value;
  if (time <= 1000) return 'A';
  if (time <= 2000) return 'B';
  if (time <= 3000) return 'C';
  if (time <= 5000) return 'D';
  return 'F';
});

const gradeColorClass = computed(() => {
  switch (currentGrade.value) {
    case 'A': return 'bg-green-500';
    case 'B': return 'bg-blue-500';
    case 'C': return 'bg-yellow-500';
    case 'D': return 'bg-orange-500';
    case 'F': return 'bg-red-500';
    default: return 'bg-gray-500';
  }
});

const cacheHitRate = computed(() => {
  const total = assetMetrics.value.cacheHits + assetMetrics.value.cacheMisses;
  return total > 0 ? (assetMetrics.value.cacheHits / total) * 100 : 0;
});

// Methods
const toggleMonitor = () => {
  isExpanded.value = !isExpanded.value;
};

const hideMonitor = () => {
  showMonitor.value = false;
  localStorage.setItem('login-performance-monitor-hidden', 'true');
};

const updateMetrics = () => {
  try {
    // Get latest metrics from performance service
    const latestMetrics = loginPerformanceService.getMetrics();
    if (latestMetrics.length > 0) {
      const latest = latestMetrics[latestMetrics.length - 1];
      metrics.value = {
        pageLoadTime: latest.pageLoadTime,
        assetLoadTime: latest.assetLoadTime,
        authenticationTime: latest.authenticationTime,
        redirectTime: latest.redirectTime,
        totalLoginTime: latest.totalLoginTime,
      };
    }

    // Get asset metrics
    const exportedMetrics = loginPerformanceService.exportMetrics();
    if (exportedMetrics.assetMetrics) {
      assetMetrics.value = exportedMetrics.assetMetrics;
    }

    // Get optimization report
    const report = loginPerformanceService.generateOptimizationReport();
    suggestions.value = report.optimizationSuggestions;

    // Get active optimizations
    const optimizationStatus = loginAssetOptimizer.getOptimizationStatus();
    activeOptimizations.value = [];
    
    if (optimizationStatus.criticalCSSInlined) {
      activeOptimizations.value.push('Critical CSS');
    }
    if (optimizationStatus.config.enableResourceHints) {
      activeOptimizations.value.push('Resource Hints');
    }
    if (optimizationStatus.config.enableFontOptimization) {
      activeOptimizations.value.push('Font Opt');
    }
    if (optimizationStatus.config.enableImageOptimization) {
      activeOptimizations.value.push('Image Opt');
    }
    if (optimizationStatus.config.enableServiceWorkerCaching) {
      activeOptimizations.value.push('SW Cache');
    }
  } catch (error) {
    console.error('[LoginPerformanceMonitor] Failed to update metrics:', error);
  }
};

const exportMetrics = () => {
  try {
    const data = {
      metrics: metrics.value,
      assetMetrics: assetMetrics.value,
      suggestions: suggestions.value,
      activeOptimizations: activeOptimizations.value,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
    };

    const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `login-performance-${Date.now()}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);

    console.log('[LoginPerformanceMonitor] Metrics exported');
  } catch (error) {
    console.error('[LoginPerformanceMonitor] Export failed:', error);
  }
};

const clearMetrics = () => {
  loginPerformanceService.clearMetrics();
  metrics.value = {
    pageLoadTime: 0,
    assetLoadTime: 0,
    authenticationTime: 0,
    redirectTime: 0,
    totalLoginTime: 0,
  };
  assetMetrics.value = {
    cssLoadTime: 0,
    jsLoadTime: 0,
    fontLoadTime: 0,
    imageLoadTime: 0,
    totalAssetSize: 0,
    compressedSize: 0,
    cacheHits: 0,
    cacheMisses: 0,
  };
  suggestions.value = [];
  console.log('[LoginPerformanceMonitor] Metrics cleared');
};

const reoptimize = async () => {
  try {
    await loginAssetOptimizer.reoptimize();
    updateMetrics();
    console.log('[LoginPerformanceMonitor] Reoptimization completed');
  } catch (error) {
    console.error('[LoginPerformanceMonitor] Reoptimization failed:', error);
  }
};

// Lifecycle
onMounted(() => {
  // Show monitor in development or if explicitly enabled
  const isDev = import.meta.env.DEV;
  const isEnabled = localStorage.getItem('login-performance-monitor-enabled') === 'true';
  const isHidden = localStorage.getItem('login-performance-monitor-hidden') === 'true';
  
  showMonitor.value = (isDev || isEnabled) && !isHidden;

  if (showMonitor.value) {
    // Initial metrics update
    updateMetrics();
    
    // Set up periodic updates
    updateInterval.value = window.setInterval(updateMetrics, 1000);
  }

  // Listen for performance events
  window.addEventListener('login-performance-update', updateMetrics);
});

onUnmounted(() => {
  if (updateInterval.value) {
    clearInterval(updateInterval.value);
  }
  window.removeEventListener('login-performance-update', updateMetrics);
});

// Expose methods for external control
defineExpose({
  show: () => { showMonitor.value = true; },
  hide: () => { showMonitor.value = false; },
  toggle: () => { showMonitor.value = !showMonitor.value; },
  updateMetrics,
  exportMetrics,
  clearMetrics,
  reoptimize,
});
</script>

<style scoped>
/* Additional styles for better performance monitor appearance */
.font-mono {
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
}

/* Smooth transitions */
.transition-all {
  transition: all 0.2s ease-in-out;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .fixed.bottom-4.right-4 {
    bottom: 1rem;
    right: 1rem;
    left: 1rem;
    right: 1rem;
  }
  
  .max-w-sm {
    max-width: none;
  }
}
</style>