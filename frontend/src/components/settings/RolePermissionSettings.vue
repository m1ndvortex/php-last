<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
          {{ $t("settings.roles.title") }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          {{ $t("settings.roles.description") }}
        </p>
      </div>
      <button
        @click="showCreateRoleModal = true"
        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        {{ $t("settings.roles.create_role") }}
      </button>
    </div>

    <!-- Roles List -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-md">
      <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        <li v-for="role in settingsStore.roles" :key="role.id">
          <div class="px-4 py-4 flex items-center justify-between">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <UserGroupIcon class="h-8 w-8 text-gray-400" />
              </div>
              <div class="ml-4">
                <div class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ role.display_name }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                  {{ role.description }}
                </div>
                <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                  {{ role.permissions.length }} {{ $t("settings.roles.permissions") }}
                </div>
              </div>
            </div>
            <div class="flex items-center space-x-2">
              <span
                v-if="role.is_system"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
              >
                {{ $t("settings.roles.system_role") }}
              </span>
              <button
                @click="editRole(role)"
                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
              >
                {{ $t("common.edit") }}
              </button>
              <button
                v-if="!role.is_system"
                @click="deleteRole(role)"
                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
              >
                {{ $t("common.delete") }}
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>

    <!-- Create/Edit Role Modal -->
    <div
      v-if="showCreateRoleModal || showEditRoleModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="closeModals"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white dark:bg-gray-800"
        @click.stop
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            {{ showCreateRoleModal ? $t("settings.roles.create_role") : $t("settings.roles.edit_role") }}
          </h3>
          
          <form @submit.prevent="saveRole" class="space-y-4">
            <div>
              <label for="role_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.roles.role_name") }}
              </label>
              <input
                type="text"
                id="role_name"
                v-model="roleForm.name"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.roles.role_name_placeholder')"
              />
            </div>

            <div>
              <label for="role_display_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.roles.display_name") }}
              </label>
              <input
                type="text"
                id="role_display_name"
                v-model="roleForm.display_name"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.roles.display_name_placeholder')"
              />
            </div>

            <div>
              <label for="role_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $t("settings.roles.description") }}
              </label>
              <textarea
                id="role_description"
                v-model="roleForm.description"
                rows="3"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
                :placeholder="$t('settings.roles.description_placeholder')"
              ></textarea>
            </div>

            <!-- Permissions -->
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ $t("settings.roles.permissions") }}
              </label>
              <div class="space-y-4">
                <div v-for="module in permissionModules" :key="module" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white capitalize">
                      {{ module }}
                    </h4>
                    <button
                      type="button"
                      @click="toggleModulePermissions(module)"
                      class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400"
                    >
                      {{ isModuleFullySelected(module) ? $t("common.deselect_all") : $t("common.select_all") }}
                    </button>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <label
                      v-for="permission in getModulePermissions(module)"
                      :key="permission.id"
                      class="flex items-center"
                    >
                      <input
                        type="checkbox"
                        :value="permission.id"
                        v-model="roleForm.permission_ids"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                      />
                      <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                        {{ permission.display_name }}
                      </span>
                    </label>
                  </div>
                </div>
              </div>
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
      :title="$t('settings.roles.delete_role')"
      :message="$t('settings.roles.delete_confirmation', { role: roleToDelete?.display_name })"
      :confirm-text="$t('common.delete')"
      :cancel-text="$t('common.cancel')"
      @confirm="confirmDeleteRole"
      @cancel="showDeleteModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from "vue";
import { useSettingsStore } from "@/stores/settings";
import { useNotifications } from "@/composables/useNotifications";
import { UserGroupIcon } from "@heroicons/vue/24/outline";
import ConfirmationModal from "@/components/ui/ConfirmationModal.vue";
import type { Role, Permission } from "@/types/settings";

const settingsStore = useSettingsStore();
const { showNotification } = useNotifications();

// State
const isLoading = ref(false);
const showCreateRoleModal = ref(false);
const showEditRoleModal = ref(false);
const showDeleteModal = ref(false);
const roleToDelete = ref<Role | null>(null);
const editingRole = ref<Role | null>(null);

const roleForm = reactive({
  name: "",
  display_name: "",
  description: "",
  permission_ids: [] as number[],
});

// Computed
const permissionModules = computed(() => {
  const modules = new Set(settingsStore.permissions.map(p => p.module));
  return Array.from(modules);
});

const getModulePermissions = (module: string) => {
  return settingsStore.permissions.filter(p => p.module === module);
};

const isModuleFullySelected = (module: string) => {
  const modulePermissions = getModulePermissions(module);
  return modulePermissions.every(p => roleForm.permission_ids.includes(p.id));
};

// Methods
const editRole = (role: Role) => {
  editingRole.value = role;
  roleForm.name = role.name;
  roleForm.display_name = role.display_name;
  roleForm.description = role.description || "";
  roleForm.permission_ids = role.permissions.map(p => p.id);
  showEditRoleModal.value = true;
};

const deleteRole = (role: Role) => {
  roleToDelete.value = role;
  showDeleteModal.value = true;
};

const confirmDeleteRole = async () => {
  if (!roleToDelete.value) return;

  try {
    isLoading.value = true;
    const result = await settingsStore.deleteRole(roleToDelete.value.id);
    
    if (result.success) {
      showNotification({
        type: "success",
        title: "Role deleted",
        message: "Role has been deleted successfully",
      });
    } else {
      showNotification({
        type: "error",
        title: "Delete failed",
        message: result.error || "Failed to delete role",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Delete failed",
      message: "An unexpected error occurred while deleting the role",
    });
  } finally {
    isLoading.value = false;
    showDeleteModal.value = false;
    roleToDelete.value = null;
  }
};

const toggleModulePermissions = (module: string) => {
  const modulePermissions = getModulePermissions(module);
  const isFullySelected = isModuleFullySelected(module);
  
  if (isFullySelected) {
    // Remove all module permissions
    roleForm.permission_ids = roleForm.permission_ids.filter(
      id => !modulePermissions.some(p => p.id === id)
    );
  } else {
    // Add all module permissions
    const newPermissions = modulePermissions
      .filter(p => !roleForm.permission_ids.includes(p.id))
      .map(p => p.id);
    roleForm.permission_ids.push(...newPermissions);
  }
};

const saveRole = async () => {
  try {
    isLoading.value = true;
    
    const roleData = {
      name: roleForm.name,
      display_name: roleForm.display_name,
      description: roleForm.description,
      permission_ids: roleForm.permission_ids,
    };

    let result;
    if (showEditRoleModal.value && editingRole.value) {
      result = await settingsStore.updateRole(editingRole.value.id, roleData);
    } else {
      result = await settingsStore.createRole(roleData);
    }
    
    if (result.success) {
      showNotification({
        type: "success",
        title: showEditRoleModal.value ? "Role updated" : "Role created",
        message: `Role has been ${showEditRoleModal.value ? "updated" : "created"} successfully`,
      });
      closeModals();
    } else {
      showNotification({
        type: "error",
        title: "Save failed",
        message: result.error || "Failed to save role",
      });
    }
  } catch (error) {
    showNotification({
      type: "error",
      title: "Save failed",
      message: "An unexpected error occurred while saving the role",
    });
  } finally {
    isLoading.value = false;
  }
};

const closeModals = () => {
  showCreateRoleModal.value = false;
  showEditRoleModal.value = false;
  editingRole.value = null;
  
  // Reset form
  roleForm.name = "";
  roleForm.display_name = "";
  roleForm.description = "";
  roleForm.permission_ids = [];
};

onMounted(async () => {
  await Promise.all([
    settingsStore.fetchRoles(),
    settingsStore.fetchPermissions(),
  ]);
});
</script>