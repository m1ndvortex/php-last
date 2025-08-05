<template>
  <div class="preset-switcher">
    <div class="flex items-center space-x-4">
      <label class="text-sm font-medium text-gray-700">
        {{ $t("dashboard.presets.label") }}:
      </label>

      <div class="flex items-center space-x-2">
        <button
          v-for="preset in availablePresets"
          :key="preset.id"
          @click="switchPreset(preset.id)"
          class="preset-btn"
          :class="{
            'preset-btn-active': activePreset === preset.id,
            'preset-btn-inactive': activePreset !== preset.id,
          }"
        >
          <component :is="getPresetIcon(preset.id)" class="w-4 h-4 mr-2" />
          {{ preset.name }}
        </button>
      </div>

      <div class="flex items-center space-x-2 ml-auto">
        <button
          @click="showCustomizeModal = true"
          class="text-sm text-gray-500 hover:text-gray-700 transition-colors flex items-center space-x-1"
        >
          <CogIcon class="w-4 h-4" />
          <span>{{ $t("dashboard.presets.customize") }}</span>
        </button>

        <button
          @click="resetLayout"
          class="text-sm text-gray-500 hover:text-gray-700 transition-colors flex items-center space-x-1"
        >
          <ArrowPathIcon class="w-4 h-4" />
          <span>{{ $t("dashboard.presets.reset") }}</span>
        </button>
      </div>
    </div>

    <!-- Customize Modal -->
    <div
      v-if="showCustomizeModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="showCustomizeModal = false"
    >
      <div
        class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto"
        @click.stop
      >
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-lg font-semibold">
            {{ $t("dashboard.presets.customize_title") }}
          </h3>
          <button
            @click="showCustomizeModal = false"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <XMarkIcon class="w-6 h-6" />
          </button>
        </div>

        <div class="space-y-6">
          <!-- Preset Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              {{ $t("dashboard.presets.select_base") }}
            </label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
              <div
                v-for="preset in availablePresets"
                :key="preset.id"
                class="preset-card"
                :class="{
                  'preset-card-selected': customizePreset === preset.id,
                  'preset-card-unselected': customizePreset !== preset.id,
                }"
                @click="customizePreset = preset.id"
              >
                <component
                  :is="getPresetIcon(preset.id)"
                  class="w-6 h-6 mb-2"
                />
                <div class="text-sm font-medium">{{ preset.name }}</div>
                <div class="text-xs text-gray-500 mt-1">
                  {{ preset.description }}
                </div>
              </div>
            </div>
          </div>

          <!-- Widget Configuration -->
          <div v-if="customizePreset">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              {{ $t("dashboard.presets.configure_widgets") }}
            </label>
            <div class="space-y-3">
              <div
                v-for="widget in getPresetWidgets(customizePreset)"
                :key="widget.id"
                class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
              >
                <div class="flex items-center space-x-3">
                  <component
                    :is="getWidgetIcon(widget.type)"
                    class="w-5 h-5 text-gray-500"
                  />
                  <div>
                    <div class="text-sm font-medium">{{ widget.title }}</div>
                    <div class="text-xs text-gray-500">
                      {{ $t(`dashboard.widgets.types.${widget.type}_desc`) }}
                    </div>
                  </div>
                </div>
                <div class="flex items-center space-x-2">
                  <label class="flex items-center">
                    <input
                      type="checkbox"
                      :checked="isWidgetEnabled(widget.id)"
                      @change="toggleWidget(widget.id)"
                      class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <span class="ml-2 text-sm text-gray-600">
                      {{ $t("dashboard.presets.enabled") }}
                    </span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Layout Options -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              {{ $t("dashboard.presets.layout_options") }}
            </label>
            <div class="grid grid-cols-2 gap-4">
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="layoutOptions.compactMode"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="ml-2 text-sm text-gray-600">
                  {{ $t("dashboard.presets.compact_mode") }}
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="layoutOptions.showHeaders"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="ml-2 text-sm text-gray-600">
                  {{ $t("dashboard.presets.show_headers") }}
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="layoutOptions.allowDragging"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="ml-2 text-sm text-gray-600">
                  {{ $t("dashboard.presets.allow_dragging") }}
                </span>
              </label>
              <label class="flex items-center">
                <input
                  type="checkbox"
                  v-model="layoutOptions.autoRefresh"
                  class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                />
                <span class="ml-2 text-sm text-gray-600">
                  {{ $t("dashboard.presets.auto_refresh") }}
                </span>
              </label>
            </div>
          </div>
        </div>

        <div
          class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200"
        >
          <button
            @click="showCustomizeModal = false"
            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
          >
            {{ $t("common.cancel") }}
          </button>
          <button
            @click="saveCustomization"
            class="px-4 py-2 bg-primary-600 text-white text-sm rounded-md hover:bg-primary-700 transition-colors"
          >
            {{ $t("common.save") }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useDashboardStore } from "@/stores/dashboard";

import {
  CogIcon,
  ArrowPathIcon,
  XMarkIcon,
  HomeIcon,
  CalculatorIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  TableCellsIcon,
  PresentationChartLineIcon,
} from "@heroicons/vue/24/outline";

const { t } = useI18n();
const dashboardStore = useDashboardStore();

const showCustomizeModal = ref(false);
const customizePreset = ref<string>("");
const enabledWidgets = ref<Set<string>>(new Set());

const layoutOptions = ref({
  compactMode: false,
  showHeaders: true,
  allowDragging: true,
  autoRefresh: false,
});

const availablePresets = computed(() => dashboardStore.availablePresets);
const activePreset = computed(() => dashboardStore.activePreset);

const getPresetIcon = (presetId: string) => {
  switch (presetId) {
    case "default":
      return HomeIcon;
    case "accountant":
      return CalculatorIcon;
    case "sales":
      return PresentationChartLineIcon;
    default:
      return HomeIcon;
  }
};

const getWidgetIcon = (widgetType: string) => {
  switch (widgetType) {
    case "kpi":
      return ChartBarIcon;
    case "chart":
      return PresentationChartLineIcon;
    case "alert":
      return ExclamationTriangleIcon;
    case "table":
      return TableCellsIcon;
    default:
      return HomeIcon;
  }
};

const getPresetWidgets = (presetId: string) => {
  const preset = availablePresets.value.find((p) => p.id === presetId);
  return preset?.layout.widgets || [];
};

const isWidgetEnabled = (widgetId: string) => {
  return enabledWidgets.value.has(widgetId);
};

const toggleWidget = (widgetId: string) => {
  if (enabledWidgets.value.has(widgetId)) {
    enabledWidgets.value.delete(widgetId);
  } else {
    enabledWidgets.value.add(widgetId);
  }
};

const switchPreset = async (presetId: string) => {
  try {
    await dashboardStore.switchPreset(presetId);
  } catch (error) {
    console.error("Failed to switch preset:", error);
  }
};

const resetLayout = async () => {
  if (confirm(t("dashboard.presets.reset_confirm"))) {
    try {
      await dashboardStore.switchPreset("default");
    } catch (error) {
      console.error("Failed to reset layout:", error);
    }
  }
};

const saveCustomization = async () => {
  try {
    // Here you would implement the logic to save the customized preset
    // This could involve creating a new preset or updating an existing one
    console.log("Saving customization:", {
      preset: customizePreset.value,
      enabledWidgets: Array.from(enabledWidgets.value),
      layoutOptions: layoutOptions.value,
    });

    showCustomizeModal.value = false;
  } catch (error) {
    console.error("Failed to save customization:", error);
  }
};

// Initialize enabled widgets when modal opens
// const initializeCustomization = (presetId: string) => {
//   customizePreset.value = presetId;
//   const widgets = getPresetWidgets(presetId);
//   enabledWidgets.value = new Set(widgets.map((w) => w.id));
// };
</script>

<style scoped>
.preset-switcher {
  background: white;
  border-radius: 8px;
  padding: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 24px;
}

.preset-btn {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  border: 1px solid transparent;
}

.preset-btn-active {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.preset-btn-inactive {
  background: #f9fafb;
  color: #6b7280;
  border-color: #e5e7eb;
}

.preset-btn-inactive:hover {
  background: #f3f4f6;
  color: #374151;
  border-color: #d1d5db;
}

.preset-card {
  padding: 16px;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  text-align: center;
}

.preset-card-selected {
  border-color: #3b82f6;
  background: #eff6ff;
}

.preset-card-unselected:hover {
  border-color: #d1d5db;
  background: #f9fafb;
}
</style>
