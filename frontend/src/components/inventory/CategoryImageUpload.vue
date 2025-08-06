<template>
  <div class="space-y-4">
    <!-- Current Image Display -->
    <div v-if="currentImage || previewUrl" class="flex items-center space-x-4">
      <div class="flex-shrink-0">
        <img
          :src="previewUrl || currentImage || ''"
          :alt="$t('inventory.categories.image')"
          class="h-20 w-20 rounded-lg object-cover border border-gray-300 dark:border-gray-600"
        />
      </div>
      <div class="flex-1">
        <p class="text-sm text-gray-900 dark:text-white">
          {{ $t("inventory.categories.image") }}
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ selectedFile?.name || $t("common.current_image") }}
        </p>
        <button
          @click="removeImage"
          type="button"
          class="mt-2 text-sm text-red-600 hover:text-red-500"
        >
          {{ $t("inventory.categories.remove_image") }}
        </button>
      </div>
    </div>

    <!-- Upload Area -->
    <div
      @drop="handleDrop"
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
      :class="[
        'relative border-2 border-dashed rounded-lg p-6 text-center hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500',
        dragOver
          ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
          : 'border-gray-300 dark:border-gray-600',
      ]"
    >
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        @change="handleFileSelect"
        class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
      />

      <div class="space-y-2">
        <PhotoIcon class="mx-auto h-12 w-12 text-gray-400" />
        <div class="text-sm text-gray-600 dark:text-gray-400">
          <span class="font-medium text-primary-600 hover:text-primary-500">
            {{ $t("inventory.categories.upload_image") }}
          </span>
          {{ $t("inventory.or_drag_drop") }}
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ $t("inventory.image_formats") }}
        </p>
      </div>
    </div>

    <!-- Upload Progress -->
    <div v-if="uploading" class="w-full bg-gray-200 rounded-full h-2">
      <div
        class="bg-primary-600 h-2 rounded-full transition-all duration-300"
        :style="{ width: `${uploadProgress}%` }"
      ></div>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="text-sm text-red-600">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onUnmounted } from "vue";
import { PhotoIcon } from "@heroicons/vue/24/outline";
import { useI18n } from "vue-i18n";

interface Props {
  modelValue?: File | null;
  currentImage?: string | null;
  maxSize?: number; // in MB
  acceptedTypes?: string[];
}

interface Emits {
  (e: "update:modelValue", file: File | null): void;
  (e: "uploaded", file: File): void;
  (e: "removed"): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  currentImage: null,
  maxSize: 2, // 2MB default
  acceptedTypes: () => ["image/jpeg", "image/png", "image/jpg", "image/webp"],
});

const emit = defineEmits<Emits>();

const { t: $t } = useI18n();

// State
const fileInput = ref<HTMLInputElement>();
const selectedFile = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const dragOver = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const error = ref<string | null>(null);

// Methods
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];

  if (file) {
    processFile(file);
  }
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  dragOver.value = false;

  const files = event.dataTransfer?.files;
  if (files && files.length > 0) {
    processFile(files[0]);
  }
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  dragOver.value = true;
};

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault();
  event.stopPropagation();
  dragOver.value = false;
};

const processFile = (file: File) => {
  error.value = null;

  // Validate file type
  if (!props.acceptedTypes.includes(file.type)) {
    error.value = $t("inventory.categories.invalid_file_type", {
      types: props.acceptedTypes.join(", "),
    });
    return;
  }

  // Validate file size
  const maxSizeBytes = props.maxSize * 1024 * 1024;
  if (file.size > maxSizeBytes) {
    error.value = $t("inventory.categories.file_too_large", {
      maxSize: props.maxSize,
    });
    return;
  }

  // Validate image dimensions (optional)
  const img = new Image();
  img.onload = () => {
    // Check minimum dimensions
    if (img.width < 100 || img.height < 100) {
      error.value = $t("inventory.categories.image_too_small");
      URL.revokeObjectURL(img.src);
      return;
    }

    // Check maximum dimensions
    if (img.width > 2000 || img.height > 2000) {
      error.value = $t("inventory.categories.image_too_large");
      URL.revokeObjectURL(img.src);
      return;
    }

    // All validations passed
    selectedFile.value = file;

    // Create preview URL
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = URL.createObjectURL(file);

    // Emit events
    emit("update:modelValue", file);
    emit("uploaded", file);

    // Simulate upload progress
    simulateUpload();

    URL.revokeObjectURL(img.src);
  };

  img.onerror = () => {
    error.value = $t("inventory.categories.invalid_image_file");
    URL.revokeObjectURL(img.src);
  };

  img.src = URL.createObjectURL(file);
};

const removeImage = () => {
  selectedFile.value = null;

  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
    previewUrl.value = null;
  }

  if (fileInput.value) {
    fileInput.value.value = "";
  }

  emit("update:modelValue", null);
  emit("removed");
};

const simulateUpload = () => {
  uploading.value = true;
  uploadProgress.value = 0;

  const interval = setInterval(() => {
    uploadProgress.value += 10;

    if (uploadProgress.value >= 100) {
      clearInterval(interval);
      uploading.value = false;
      uploadProgress.value = 0;
    }
  }, 100);
};

// Watchers
watch(
  () => props.modelValue,
  (newValue) => {
    if (!newValue && selectedFile.value) {
      removeImage();
    }
  },
);

// Cleanup on unmount
onUnmounted(() => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
});
</script>
