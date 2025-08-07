<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("settings.templates.title") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("settings.templates.description") }}
        </p>
      </div>
      <button
        @click="showCreateTemplateModal = true"
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        {{ $t("settings.templates.create_template") }}
      </button>
    </div>

    <!-- Template Type Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button
          v-for="type in templateTypes"
          :key="type"
          @click="activeTemplateType = type"
          :class="[
            activeTemplateType === type
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm capitalize'
          ]"
        >
          {{ $t(`settings.templates.types.${type}`) }}
        </button>
      </nav>
    </div>

    <!-- Templates List -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="template in filteredTemplates"
        :key="template.id"
        class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg"
      >
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <component
                :is="getTemplateIcon(template.type)"
                class="h-8 w-8 text-gray-400"
              />
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                  {{ template.name }}
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                  {{ template.subject || $t("settings.templates.no_subject") }}
                </dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 px-5 py-3">
          <div class="text-sm">
            <div class="flex justify-between items-center mb-2">
              <span class="text-gray-500 dark:text-gray-400">
                {{ $t("settings.templates.variables") }}:
              </span>
              <span
                :class="[
                  template.is_active
                    ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
                    : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'
                ]"
              >
                {{ template.is_active ? $t("common.active") : $t("common.inactive") }}
              </span>
            </div>
            <div class="flex flex-wrap gap-1 mb-3">
              <span
                v-for="variable in template.variables"
                :key="variable"
                class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100"
              >
                {{ variable }}
              </span>
            </div>
            <div class="flex justify-end space-x-2">
              <button
                @click="editTemplate(template)"
                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 text-sm font-medium"
              >
                {{ $t("common.edit") }}
              </button>
              <button
                @click="deleteTemplate(template)"
                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium"
              >
                {{ $t("common.delete") }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Template Modal -->
    <div
      v-if="showCreateTemplateModal || showEditTemplateModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="closeModals"
    >
      <div
        class="relative top-10 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 xl:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800 max-h-screen overflow-y-auto"
        @click.stop
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ showCreateTemplateModal ? $t("settings.templates.create_template") : $t("settings.templates.edit_template") }}
          </h3>
          
          <form @submit.prevent="saveTemplate" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label for="template_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t("settings.templates.template_name") }}
                </label>
                <input
                  type="text"
                  id="template_name"
                  v-model="templateForm.name"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                  :placeholder="$t('settings.templates.template_name_placeholder')"
                />
              </div>

              <div>
                <label for="template_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                  {{ $t("settings.templates.template_type") }}
                </label>
                <select
                  id="template_type"
                  v-model="templateForm.type"
                  required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                >
                  <option value="email">{{ $t("settings.templates.types.email") }}</option>
                  <option value="sms">{{ $t("settings.templates.types.sms") }}</option>
                  <option value="whatsapp">{{ $t("settings.templates.types.whatsapp") }}</option>
                </select>
              </div>
            </div>

            <div v-if="templateForm.type === 'email'">
              <label for="template_subject" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.templates.subject") }}
              </label>
              <input
                type="text"
                id="template_subject"
                v-model="templateForm.subject"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.templates.subject_placeholder')"
              />
            </div>

            <!-- Language Tabs for Content -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $t("settings.templates.content") }}
              </label>
              <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                  <button
                    type="button"
                    @click="activeContentLanguage = 'en'"
                    :class="[
                      activeContentLanguage === 'en'
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                      'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                    ]"
                  >
                    English
                  </button>
                  <button
                    type="button"
                    @click="activeContentLanguage = 'fa'"
                    :class="[
                      activeContentLanguage === 'fa'
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
                      'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
                    ]"
                  >
                    فارسی
                  </button>
                </nav>
              </div>

              <div v-if="activeContentLanguage === 'en'">
                <textarea
                  v-model="templateForm.content"
                  rows="8"
                  required
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                  :placeholder="$t('settings.templates.content_placeholder')"
                ></textarea>
              </div>

              <div v-if="activeContentLanguage === 'fa'">
                <textarea
                  v-model="templateForm.content_persian"
                  rows="8"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                  :placeholder="$t('settings.templates.content_persian_placeholder')"
                  dir="rtl"
                ></textarea>
              </div>
            </div>

            <!-- Available Variables -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
              <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                {{ $t("settings.templates.available_variables") }}
              </h4>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="variable in availableVariables"
                  :key="variable"
                  type="button"
                  @click="insertVariable(variable)"
                  class="inline-flex items-center px-2.5 py-1.5 border border-gray-300 dark:border-gray-600 shadow-sm text-xs font-medium rounded text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  {{ variable }}
                </button>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ $t("settings.templates.variables_help") }}
              </p>
            </div>

            <div class="flex items-center">
              <input
                id="template_active"
                type="checkbox"
                v-model="templateForm.is_active"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <label for="template_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                {{ $t("settings.templates.is_active") }}
              </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="closeModals"
                class="bg-white dark:bg-gray-700 py-2 px-4 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                {{ $t("common.cancel") }}
              </button>
              <button
                type="submit"
                :disabled="isLoading"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ isLoading ? $t("common.saving") : $t("common.save") }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      v-if="showDeleteModal"
      :title="$t('settings.templates.delete_template')"
      :message="$t('settings.templates.delete_confirmation', { template: templateToDelete?.name })"
      :confirm-text="$t('common.delete')"
      :cancel-text="$t('common.cancel')"
      @confirm="confirmDeleteTemplate"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useSettingsStore } from "@/stores/settings";
import { useNotifications } from "@/composables/useNotifications";
import {
  EnvelopeIcon,
  ChatBubbleLeftRightIcon,
  DevicePhoneMobileIcon,
} from "@heroicons/vue/24/outline";
import ConfirmationModal from "@/components/ui/ConfirmationModal.vue";
import type { MessageTemplate } from "@/types/settings";

const settingsStore = useSettingsStore();
const { showNotification } = useNotifications();

// State
const isLoading = ref(false);
const showCreateTemplateModal = ref(false);
const showEditTemplateModal = ref(false);
const showDeleteModal = ref(false);
const templateToDelete = ref<MessageTemplate | null>(null);
const editingTemplate = ref<MessageTemplate | null>(null);
const activeTemplateType = ref<"email" | "sms" | "whatsapp">("email");
const activeContentLanguage = ref<"en" | "fa">("en");

const templateTypes = ["email", "sms", "whatsapp"] as const;

const templateForm = reactive({
  name: "",
  type: "email" as "email" | "sms" | "whatsapp",
  subject: "",
  content: "",
  content_persian: "",
  is_active: true,
});

const availableVariables = [
  "{{customer_name}}",
  "{{customer_email}}",
  "{{customer_phone}}",
  "{{business_name}}",
  "{{invoice_number}}",
  "{{invoice_total}}",
  "{{due_date}}",
  "{{item_name}}",
  "{{quantity}}",
  "{{price}}",
  "{{birthday}}",
  "{{anniversary}}",
];

// Computed
const filteredTemplates = computed(() => {
  return settingsStore.messageTemplates.filter(
    template => template.type === activeTemplateType.value
  );
});

// Methods
const getTemplateIcon = (type: string) => {
  switch (type) {
    case "email":
      return EnvelopeIcon;
    case "sms":
      return DevicePhoneMobileIcon;
    case "whatsapp":
      return ChatBubbleLeftRightIcon;
    default:
      return EnvelopeIcon;
  }
};

const editTemplate = (template: MessageTemplate) => {
  editingTemplate.value = template;
  templateForm.name = template.name;
  templateForm.type = template.type;
  templateForm.subject = template.subject || "";
  templateForm.content = template.content;
  templateForm.content_persian = template.content_persian || "";
  templateForm.is_active = template.is_active;
  showEditTemplateModal.value = true;
};

const deleteTemplate = (template: MessageTemplate) => {
  templateToDelete.value = template;
  showDeleteModal.value = true;
};

const confirmDeleteTemplate = async () => {
  if (!templateToDelete.value) return;

  try {
    isLoading.value = true;
    const result = await settingsStore.deleteMessageTemplate(templateToDelete.value.id);
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Template deleted",
        message: "Message template has been deleted successfully",
      });
    } else {
      showNotification({
        type: "error",
        title: "Delete failed",
        message: result.error || "Failed to delete template",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Delete failed",
      message: "An unexpected error occurred while deleting the template",
    });
  } finally {
    isLoading.value = false;
    showDeleteModal.value = false;
    templateToDelete.value = null;
  }
};

const insertVariable = (variable: string) => {
  const textarea = document.querySelector(
    activeContentLanguage.value === 'en' 
      ? 'textarea[v-model="templateForm.content"]' 
      : 'textarea[v-model="templateForm.content_persian"]'
  ) as HTMLTextAreaElement;
  
  if (textarea) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const currentContent = activeContentLanguage.value === 'en' 
      ? templateForm.content 
      : templateForm.content_persian;
    
    const newContent = currentContent.substring(0, start) + variable + currentContent.substring(end);
    
    if (activeContentLanguage.value === 'en') {
      templateForm.content = newContent;
    } else {
      templateForm.content_persian = newContent;
    }
    
    // Set cursor position after the inserted variable
    setTimeout(() => {
      textarea.focus();
      textarea.setSelectionRange(start + variable.length, start + variable.length);
    }, 0);
  }
};

const saveTemplate = async () => {
  try {
    isLoading.value = true;
    
    const templateData = {
      name: templateForm.name,
      type: templateForm.type,
      subject: templateForm.subject || undefined,
      content: templateForm.content,
      content_persian: templateForm.content_persian || undefined,
      is_active: templateForm.is_active,
      variables: availableVariables.filter(variable => 
        templateForm.content.includes(variable) || 
        (templateForm.content_persian && templateForm.content_persian.includes(variable))
      ),
    };

    let result;
    if (showEditTemplateModal.value && editingTemplate.value) {
      result = await settingsStore.updateMessageTemplate(editingTemplate.value.id, templateData);
    } else {
      result = await settingsStore.createMessageTemplate(templateData);
    }
    
    if (result.success) {
      showNotification({
        type: "success",
        title: showEditTemplateModal.value ? "Template updated" : "Template created",
        message: `Message template has been ${showEditTemplateModal.value ? "updated" : "created"} successfully`,
      });
      closeModals();
    } else {
      showNotification({
        type: "error",
        title: "Save failed",
        message: result.error || "Failed to save template",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Save failed",
      message: "An unexpected error occurred while saving the template",
    });
  } finally {
    isLoading.value = false;
  }
};

const closeModals = () => {
  showCreateTemplateModal.value = false;
  showEditTemplateModal.value = false;
  editingTemplate.value = null;
  activeContentLanguage.value = "en";
  
  // Reset form
  templateForm.name = "";
  templateForm.type = "email";
  templateForm.subject = "";
  templateForm.content = "";
  templateForm.content_persian = "";
  templateForm.is_active = true;
};

onMounted(async () => {
  await settingsStore.fetchMessageTemplates();
});
</script>