<template>
  <div class="space-y-6">
    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <button
          @click="activeTab = 'cost-centers'"
          :class="[
            activeTab === 'cost-centers'
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          <BuildingOfficeIcon class="w-5 h-5 inline-block mr-2" />
          {{ $t('accounting.cost_centers') }}
        </button>
        <button
          @click="activeTab = 'assets'"
          :class="[
            activeTab === 'assets'
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          <CubeIcon class="w-5 h-5 inline-block mr-2" />
          {{ $t('accounting.assets') }}
        </button>
      </nav>
    </div>

    <!-- Cost Centers Tab -->
    <div v-if="activeTab === 'cost-centers'">
      <!-- Cost Centers Header -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ $t('accounting.cost_centers') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ $t('accounting.cost_centers_description') }}
            </p>
          </div>
          <div class="flex space-x-3">
            <button
              @click="showCostCenterModal = true"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <PlusIcon class="w-4 h-4 mr-2" />
              {{ $t('accounting.create_cost_center') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Cost Centers List -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('accounting.code') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('common.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('common.description') }}
                </th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('common.status') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="costCenter in accountingStore.costCenters" :key="costCenter.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
                  {{ costCenter.code }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ getLocalizedName(costCenter) }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                  {{ costCenter.description || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      costCenter.is_active
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                        : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                    ]"
                  >
                    {{ costCenter.is_active ? $t('common.active') : $t('common.inactive') }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end space-x-2">
                    <button
                      @click="editCostCenter(costCenter)"
                      class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                      {{ $t('common.edit') }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Assets Tab -->
    <div v-if="activeTab === 'assets'">
      <!-- Assets Header -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-4 sm:space-y-0">
          <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              {{ $t('accounting.assets') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {{ $t('accounting.assets_description') }}
            </p>
          </div>
          <div class="flex space-x-3">
            <button
              @click="showAssetModal = true"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <PlusIcon class="w-4 h-4 mr-2" />
              {{ $t('accounting.create_asset') }}
            </button>
          </div>
        </div>

        <!-- Asset Filters -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('accounting.asset_category') }}
            </label>
            <select
              v-model="assetFilter.category"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="">{{ $t('common.all') }}</option>
              <option value="equipment">{{ $t('accounting.equipment') }}</option>
              <option value="furniture">{{ $t('accounting.furniture') }}</option>
              <option value="vehicle">{{ $t('accounting.vehicle') }}</option>
              <option value="building">{{ $t('accounting.building') }}</option>
              <option value="software">{{ $t('accounting.software') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('common.status') }}
            </label>
            <select
              v-model="assetFilter.status"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            >
              <option value="">{{ $t('common.all') }}</option>
              <option value="active">{{ $t('common.active') }}</option>
              <option value="disposed">{{ $t('accounting.disposed') }}</option>
              <option value="under_maintenance">{{ $t('accounting.under_maintenance') }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
              {{ $t('common.search') }}
            </label>
            <input
              v-model="assetFilter.search"
              type="text"
              :placeholder="$t('accounting.search_assets')"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white sm:text-sm"
            />
          </div>
        </div>
      </div>

      <!-- Assets List -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('accounting.asset_number') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('common.name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('accounting.category') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('accounting.purchase_cost') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('accounting.current_value') }}
                </th>
                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('common.status') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ $t('common.actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="asset in filteredAssets" :key="asset.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 dark:text-white">
                  {{ asset.asset_number }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                  {{ getLocalizedAssetName(asset) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white capitalize">
                  {{ $t(`accounting.${asset.category}`) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                  {{ formatCurrency(asset.purchase_cost) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-white">
                  {{ formatCurrency(asset.current_value) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                  <span
                    :class="[
                      'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                      asset.status === 'active'
                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                        : asset.status === 'disposed'
                        ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
                    ]"
                  >
                    {{ $t(`accounting.${asset.status}`) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end space-x-2">
                    <button
                      @click="viewAsset(asset)"
                      class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                      {{ $t('common.view') }}
                    </button>
                    <button
                      @click="editAsset(asset)"
                      class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                    >
                      {{ $t('common.edit') }}
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <CostCenterFormModal
      v-if="showCostCenterModal"
      :cost-center="editingCostCenter"
      @close="closeCostCenterModal"
      @saved="handleCostCenterSaved"
    />

    <AssetFormModal
      v-if="showAssetModal"
      :asset="editingAsset"
      @close="closeAssetModal"
      @saved="handleAssetSaved"
    />

    <AssetDetailsModal
      v-if="showAssetDetailsModal"
      :asset="viewingAsset"
      @close="showAssetDetailsModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { PlusIcon, BuildingOfficeIcon, CubeIcon } from '@heroicons/vue/24/outline'
import { useAccountingStore, type CostCenter, type Asset } from '@/stores/accounting'
import { useNumberFormatter } from '@/composables/useNumberFormatter'
import CostCenterFormModal from './CostCenterFormModal.vue'
import AssetFormModal from './AssetFormModal.vue'
import AssetDetailsModal from './AssetDetailsModal.vue'

const accountingStore = useAccountingStore()
const { formatCurrency } = useNumberFormatter()

const activeTab = ref('cost-centers')
const showCostCenterModal = ref(false)
const showAssetModal = ref(false)
const showAssetDetailsModal = ref(false)
const editingCostCenter = ref<CostCenter | null>(null)
const editingAsset = ref<Asset | null>(null)
const viewingAsset = ref<Asset | null>(null)

const assetFilter = ref({
  category: '',
  status: '',
  search: ''
})

const filteredAssets = computed(() => {
  return accountingStore.assets.filter(asset => {
    const matchesCategory = !assetFilter.value.category || asset.category === assetFilter.value.category
    const matchesStatus = !assetFilter.value.status || asset.status === assetFilter.value.status
    const matchesSearch = !assetFilter.value.search || 
      asset.name.toLowerCase().includes(assetFilter.value.search.toLowerCase()) ||
      asset.asset_number.toLowerCase().includes(assetFilter.value.search.toLowerCase())
    
    return matchesCategory && matchesStatus && matchesSearch
  })
})

const getLocalizedName = (costCenter: CostCenter) => {
  const locale = document.documentElement.lang || 'en'
  return locale === 'fa' && costCenter.name_persian ? costCenter.name_persian : costCenter.name
}

const getLocalizedAssetName = (asset: Asset) => {
  const locale = document.documentElement.lang || 'en'
  return locale === 'fa' && asset.name_persian ? asset.name_persian : asset.name
}

const editCostCenter = (costCenter: CostCenter) => {
  editingCostCenter.value = costCenter
  showCostCenterModal.value = true
}

const closeCostCenterModal = () => {
  showCostCenterModal.value = false
  editingCostCenter.value = null
}

const handleCostCenterSaved = () => {
  closeCostCenterModal()
  accountingStore.fetchCostCenters()
}

const viewAsset = (asset: Asset) => {
  viewingAsset.value = asset
  showAssetDetailsModal.value = true
}

const editAsset = (asset: Asset) => {
  editingAsset.value = asset
  showAssetModal.value = true
}

const closeAssetModal = () => {
  showAssetModal.value = false
  editingAsset.value = null
}

const handleAssetSaved = () => {
  closeAssetModal()
  accountingStore.fetchAssets()
}

onMounted(() => {
  accountingStore.fetchCostCenters()
  accountingStore.fetchAssets()
})
</script>