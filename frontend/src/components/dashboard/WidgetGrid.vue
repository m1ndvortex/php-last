<template>
  <div class="widget-grid" ref="gridContainer">
    <div
      v-for="widget in widgets"
      :key="widget.id"
      class="widget-item"
      :class="{
        'widget-dragging': draggedWidget === widget.id,
        'widget-resizing': resizedWidget === widget.id
      }"
      :style="getWidgetStyle(widget)"
      @mousedown="startDrag(widget.id, $event)"
    >
      <!-- Widget Header -->
      <div 
        class="widget-header"
        :class="{ 'cursor-move': !isResizing }"
      >
        <h3 class="widget-title">{{ widget.title }}</h3>
        <div class="widget-controls">
          <button
            @click="refreshWidget(widget.id)"
            class="widget-control-btn"
            :title="$t('dashboard.widgets.refresh')"
          >
            <ArrowPathIcon class="w-4 h-4" />
          </button>
          <button
            @click="removeWidget(widget.id)"
            class="widget-control-btn text-red-500 hover:text-red-700"
            :title="$t('dashboard.widgets.remove')"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
      
      <!-- Widget Content -->
      <div class="widget-content">
        <component 
          :is="getWidgetComponent(widget.type)"
          v-bind="getWidgetProps(widget)"
          @refresh="refreshWidget(widget.id)"
        />
      </div>
      
      <!-- Resize Handle -->
      <div
        v-if="widget.config?.allowResize !== false"
        class="resize-handle"
        @mousedown="startResize(widget.id, $event)"
      >
        <div class="resize-handle-icon"></div>
      </div>
    </div>
    
    <!-- Add Widget Button -->
    <div class="add-widget-btn" @click="showAddWidgetModal = true">
      <PlusIcon class="w-8 h-8 text-gray-400" />
      <span class="text-sm text-gray-500 mt-2">{{ $t('dashboard.widgets.add_widget') }}</span>
    </div>
    
    <!-- Add Widget Modal -->
    <div
      v-if="showAddWidgetModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="showAddWidgetModal = false"
    >
      <div
        class="bg-white rounded-lg p-6 max-w-md w-full mx-4"
        @click.stop
      >
        <h3 class="text-lg font-semibold mb-4">{{ $t('dashboard.widgets.add_widget') }}</h3>
        <div class="grid grid-cols-2 gap-3">
          <button
            v-for="widgetType in availableWidgetTypes"
            :key="widgetType.type"
            @click="addWidget(widgetType.type)"
            class="p-4 border border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-colors text-left"
          >
            <component :is="widgetType.icon" class="w-6 h-6 mb-2 text-gray-600" />
            <div class="text-sm font-medium">{{ widgetType.name }}</div>
            <div class="text-xs text-gray-500">{{ widgetType.description }}</div>
          </button>
        </div>
        <div class="mt-4 flex justify-end">
          <button
            @click="showAddWidgetModal = false"
            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors"
          >
            {{ $t('common.cancel') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useDashboardStore } from '@/stores/dashboard';
import type { DashboardWidget } from '@/types/dashboard';

// Widget Components
import KPIWidget from './KPIWidget.vue';
import ChartWidget from './ChartWidget.vue';
import AlertWidget from './AlertWidget.vue';
import TableWidget from './TableWidget.vue';

// Icons
import {
  ArrowPathIcon,
  XMarkIcon,
  PlusIcon,
  ChartBarIcon,
  ExclamationTriangleIcon,
  TableCellsIcon,
  CogIcon
} from '@heroicons/vue/24/outline';

interface Props {
  widgets: DashboardWidget[];
  gridCols?: number;
  cellSize?: number;
  gap?: number;
}

const props = withDefaults(defineProps<Props>(), {
  gridCols: 12,
  cellSize: 80,
  gap: 16
});

const emit = defineEmits<{
  widgetMoved: [widgetId: string, position: { x: number; y: number; w: number; h: number }];
  widgetResized: [widgetId: string, size: { w: number; h: number }];
  widgetRemoved: [widgetId: string];
  widgetAdded: [widget: DashboardWidget];
}>();

const { t } = useI18n();
const dashboardStore = useDashboardStore();

const gridContainer = ref<HTMLDivElement>();
const draggedWidget = ref<string | null>(null);
const resizedWidget = ref<string | null>(null);
const showAddWidgetModal = ref(false);
const isResizing = ref(false);

const dragState = ref({
  startX: 0,
  startY: 0,
  startPosition: { x: 0, y: 0, w: 0, h: 0 },
  currentPosition: { x: 0, y: 0, w: 0, h: 0 }
});

const resizeState = ref({
  startX: 0,
  startY: 0,
  startSize: { w: 0, h: 0 },
  currentSize: { w: 0, h: 0 }
});

const availableWidgetTypes = [
  {
    type: 'kpi',
    name: t('dashboard.widgets.types.kpi'),
    description: t('dashboard.widgets.types.kpi_desc'),
    icon: ChartBarIcon
  },
  {
    type: 'chart',
    name: t('dashboard.widgets.types.chart'),
    description: t('dashboard.widgets.types.chart_desc'),
    icon: ChartBarIcon
  },
  {
    type: 'alert',
    name: t('dashboard.widgets.types.alert'),
    description: t('dashboard.widgets.types.alert_desc'),
    icon: ExclamationTriangleIcon
  },
  {
    type: 'table',
    name: t('dashboard.widgets.types.table'),
    description: t('dashboard.widgets.types.table_desc'),
    icon: TableCellsIcon
  }
];

const getWidgetStyle = (widget: DashboardWidget) => {
  const { x, y, w, h } = widget.position;
  const cellWidth = (100 / props.gridCols);
  
  return {
    left: `${x * cellWidth}%`,
    top: `${y * props.cellSize + y * props.gap}px`,
    width: `calc(${w * cellWidth}% - ${props.gap}px)`,
    height: `${h * props.cellSize + (h - 1) * props.gap}px`,
    zIndex: draggedWidget.value === widget.id ? 1000 : 1
  };
};

const getWidgetComponent = (type: string) => {
  switch (type) {
    case 'kpi':
      return KPIWidget;
    case 'chart':
      return ChartWidget;
    case 'alert':
      return AlertWidget;
    case 'table':
      return TableWidget;
    default:
      return 'div';
  }
};

const getWidgetProps = (widget: DashboardWidget) => {
  switch (widget.type) {
    case 'kpi':
      return {
        kpis: dashboardStore.kpis
      };
    case 'chart':
      return {
        title: widget.title,
        chartType: widget.data?.chartType || 'line',
        chartData: widget.data?.chartData,
        showPeriodSelector: true
      };
    case 'alert':
      return {
        title: widget.title,
        alerts: dashboardStore.alerts
      };
    case 'table':
      return {
        title: widget.title,
        columns: getTableColumns(widget.id),
        data: getTableData(widget.id),
        showPagination: true,
        pageSize: 5
      };
    default:
      return {};
  }
};

const getTableColumns = (widgetId: string) => {
  switch (widgetId) {
    case 'recent-activities':
      return [
        { key: 'activity', label: 'Activity', type: 'text' },
        { key: 'user', label: 'User', type: 'text' },
        { key: 'time', label: 'Time', type: 'text' },
        { key: 'status', label: 'Status', type: 'status' }
      ];
    case 'top-customers':
      return [
        { key: 'name', label: 'Customer', type: 'text' },
        { key: 'orders', label: 'Orders', type: 'number', align: 'right' },
        { key: 'total', label: 'Total', type: 'currency', align: 'right' },
        { key: 'lastOrder', label: 'Last Order', type: 'date' }
      ];
    case 'pending-transactions':
      return [
        { key: 'reference', label: 'Reference', type: 'text' },
        { key: 'description', label: 'Description', type: 'text' },
        { key: 'amount', label: 'Amount', type: 'currency', align: 'right' },
        { key: 'date', label: 'Date', type: 'date' },
        { key: 'status', label: 'Status', type: 'status' }
      ];
    case 'sales-activities':
      return [
        { key: 'invoice', label: 'Invoice', type: 'text' },
        { key: 'customer', label: 'Customer', type: 'text' },
        { key: 'amount', label: 'Amount', type: 'currency', align: 'right' },
        { key: 'date', label: 'Date', type: 'date' },
        { key: 'status', label: 'Status', type: 'status' }
      ];
    default:
      return [];
  }
};

const getTableData = (widgetId: string) => {
  switch (widgetId) {
    case 'recent-activities':
      return [
        { activity: 'Invoice #INV-001 created', user: 'Admin', time: '2 minutes ago', status: 'completed' },
        { activity: 'Customer John Doe added', user: 'Admin', time: '5 minutes ago', status: 'completed' },
        { activity: 'Gold Ring inventory updated', user: 'Admin', time: '10 minutes ago', status: 'completed' },
        { activity: 'Payment received for INV-002', user: 'System', time: '15 minutes ago', status: 'completed' },
        { activity: 'Stock alert for Silver Necklace', user: 'System', time: '20 minutes ago', status: 'pending' }
      ];
    case 'top-customers':
      return [
        { name: 'John Doe', orders: 15, total: 25000, lastOrder: '2024-01-15' },
        { name: 'Jane Smith', orders: 12, total: 18500, lastOrder: '2024-01-14' },
        { name: 'Mike Johnson', orders: 8, total: 12000, lastOrder: '2024-01-13' },
        { name: 'Sarah Wilson', orders: 6, total: 9500, lastOrder: '2024-01-12' },
        { name: 'David Brown', orders: 5, total: 7800, lastOrder: '2024-01-11' }
      ];
    case 'pending-transactions':
      return [
        { reference: 'TXN-001', description: 'Gold purchase', amount: 5000, date: '2024-01-15', status: 'pending' },
        { reference: 'TXN-002', description: 'Silver sale', amount: -1200, date: '2024-01-14', status: 'pending' },
        { reference: 'TXN-003', description: 'Rent payment', amount: -2000, date: '2024-01-13', status: 'pending' },
        { reference: 'TXN-004', description: 'Customer payment', amount: 3500, date: '2024-01-12', status: 'pending' }
      ];
    case 'sales-activities':
      return [
        { invoice: 'INV-001', customer: 'John Doe', amount: 2500, date: '2024-01-15', status: 'paid' },
        { invoice: 'INV-002', customer: 'Jane Smith', amount: 1800, date: '2024-01-14', status: 'paid' },
        { invoice: 'INV-003', customer: 'Mike Johnson', amount: 3200, date: '2024-01-13', status: 'pending' },
        { invoice: 'INV-004', customer: 'Sarah Wilson', amount: 950, date: '2024-01-12', status: 'overdue' }
      ];
    default:
      return [];
  }
};

const startDrag = (widgetId: string, event: MouseEvent) => {
  if (isResizing.value) return;
  
  const widget = props.widgets.find(w => w.id === widgetId);
  if (!widget || widget.config?.allowMove === false) return;
  
  event.preventDefault();
  draggedWidget.value = widgetId;
  
  dragState.value = {
    startX: event.clientX,
    startY: event.clientY,
    startPosition: { ...widget.position },
    currentPosition: { ...widget.position }
  };
  
  document.addEventListener('mousemove', handleDrag);
  document.addEventListener('mouseup', endDrag);
};

const handleDrag = (event: MouseEvent) => {
  if (!draggedWidget.value) return;
  
  const deltaX = event.clientX - dragState.value.startX;
  const deltaY = event.clientY - dragState.value.startY;
  
  const cellWidth = gridContainer.value!.offsetWidth / props.gridCols;
  const newX = Math.max(0, Math.min(
    props.gridCols - dragState.value.startPosition.w,
    dragState.value.startPosition.x + Math.round(deltaX / cellWidth)
  ));
  const newY = Math.max(0, dragState.value.startPosition.y + Math.round(deltaY / (props.cellSize + props.gap)));
  
  dragState.value.currentPosition = { 
    x: newX, 
    y: newY,
    w: dragState.value.startPosition.w,
    h: dragState.value.startPosition.h
  };
};

const endDrag = () => {
  if (!draggedWidget.value) return;
  
  const widget = props.widgets.find(w => w.id === draggedWidget.value);
  if (widget) {
    widget.position = { ...dragState.value.currentPosition };
    emit('widgetMoved', draggedWidget.value, widget.position);
    dashboardStore.updateWidgetPosition(draggedWidget.value, widget.position);
  }
  
  draggedWidget.value = null;
  document.removeEventListener('mousemove', handleDrag);
  document.removeEventListener('mouseup', endDrag);
};

const startResize = (widgetId: string, event: MouseEvent) => {
  const widget = props.widgets.find(w => w.id === widgetId);
  if (!widget || widget.config?.allowResize === false) return;
  
  event.preventDefault();
  event.stopPropagation();
  isResizing.value = true;
  resizedWidget.value = widgetId;
  
  resizeState.value = {
    startX: event.clientX,
    startY: event.clientY,
    startSize: { w: widget.position.w, h: widget.position.h },
    currentSize: { w: widget.position.w, h: widget.position.h }
  };
  
  document.addEventListener('mousemove', handleResize);
  document.addEventListener('mouseup', endResize);
};

const handleResize = (event: MouseEvent) => {
  if (!resizedWidget.value) return;
  
  const deltaX = event.clientX - resizeState.value.startX;
  const deltaY = event.clientY - resizeState.value.startY;
  
  const cellWidth = gridContainer.value!.offsetWidth / props.gridCols;
  const newW = Math.max(1, resizeState.value.startSize.w + Math.round(deltaX / cellWidth));
  const newH = Math.max(1, resizeState.value.startSize.h + Math.round(deltaY / (props.cellSize + props.gap)));
  
  resizeState.value.currentSize = { w: newW, h: newH };
};

const endResize = () => {
  if (!resizedWidget.value) return;
  
  const widget = props.widgets.find(w => w.id === resizedWidget.value);
  if (widget) {
    widget.position.w = resizeState.value.currentSize.w;
    widget.position.h = resizeState.value.currentSize.h;
    emit('widgetResized', resizedWidget.value, resizeState.value.currentSize);
    dashboardStore.updateWidgetPosition(resizedWidget.value, widget.position);
  }
  
  resizedWidget.value = null;
  isResizing.value = false;
  document.removeEventListener('mousemove', handleResize);
  document.removeEventListener('mouseup', endResize);
};

const removeWidget = (widgetId: string) => {
  emit('widgetRemoved', widgetId);
  dashboardStore.removeWidget(widgetId);
};

const refreshWidget = (widgetId: string) => {
  // Implement widget refresh logic
  console.log('Refreshing widget:', widgetId);
};

const addWidget = (type: string) => {
  const newWidget: DashboardWidget = {
    id: `widget-${Date.now()}`,
    type: type as any,
    title: t(`dashboard.widgets.types.${type}`),
    position: { x: 0, y: 0, w: 4, h: 3 },
    config: {
      allowResize: true,
      allowMove: true,
      showHeader: true
    }
  };
  
  emit('widgetAdded', newWidget);
  dashboardStore.addWidget(newWidget);
  showAddWidgetModal.value = false;
};

onMounted(() => {
  // Initialize grid
});

onUnmounted(() => {
  document.removeEventListener('mousemove', handleDrag);
  document.removeEventListener('mouseup', endDrag);
  document.removeEventListener('mousemove', handleResize);
  document.removeEventListener('mouseup', endResize);
});
</script>

<style scoped>
.widget-grid {
  position: relative;
  min-height: 600px;
  padding: 16px;
}

.widget-item {
  position: absolute;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  transition: box-shadow 0.2s ease;
  overflow: hidden;
}

.widget-item:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.widget-dragging {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  transform: rotate(2deg);
}

.widget-resizing {
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.widget-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.widget-title {
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin: 0;
}

.widget-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}

.widget-control-btn {
  padding: 4px;
  border: none;
  background: none;
  color: #6b7280;
  cursor: pointer;
  border-radius: 4px;
  transition: color 0.2s ease;
}

.widget-control-btn:hover {
  color: #374151;
  background: #e5e7eb;
}

.widget-content {
  padding: 16px;
  height: calc(100% - 60px);
  overflow: auto;
}

.resize-handle {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 16px;
  height: 16px;
  cursor: se-resize;
  display: flex;
  align-items: center;
  justify-content: center;
}

.resize-handle-icon {
  width: 8px;
  height: 8px;
  border-right: 2px solid #d1d5db;
  border-bottom: 2px solid #d1d5db;
}

.resize-handle:hover .resize-handle-icon {
  border-color: #6b7280;
}

.add-widget-btn {
  position: absolute;
  top: 16px;
  right: 16px;
  width: 120px;
  height: 120px;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.add-widget-btn:hover {
  border-color: #6366f1;
  background: #f0f9ff;
}

.add-widget-btn:hover .text-gray-400 {
  color: #6366f1;
}

.add-widget-btn:hover .text-gray-500 {
  color: #6366f1;
}
</style>