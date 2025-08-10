<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div
      class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
    >
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="$emit('close')"
      ></div>

      <div
        class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
      >
        <form @submit.prevent="handleSubmit">
          <!-- Header -->
          <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <div class="flex items-center justify-between mb-4">
              <h3
                class="text-lg leading-6 font-medium text-gray-900 dark:text-white"
              >
                {{
                  isEdit
                    ? $t("invoices.edit_invoice")
                    : $t("invoices.create_invoice")
                }}
              </h3>
              <button
                type="button"
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>

            <!-- Invoice Header Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <!-- Customer Selection -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                >
                  {{ $t("invoices.customer") }} *
                </label>
                <select
                  v-model="form.customer_id"
                  required
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  :class="{ 'border-red-500': errors.customer_id }"
                >
                  <option value="">{{ $t("invoices.select_customer") }}</option>
                  <option
                    v-for="customer in customers"
                    :key="customer?.id || 'unknown'"
                    :value="customer?.id"
                  >
                    {{ customer?.name || "Unknown Customer" }}
                  </option>
                  <!-- Fallback option if no customers are loaded -->
                  <option v-if="customers.length === 0" value="" disabled>
                    {{
                      $t("invoices.loading_customers") || "Loading customers..."
                    }}
                  </option>
                </select>
                <p v-if="errors.customer_id" class="mt-1 text-sm text-red-600">
                  {{ errors.customer_id[0] }}
                </p>
              </div>

              <!-- Invoice Number -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                >
                  {{ $t("invoices.invoice_number") }} *
                </label>
                <input
                  v-model="form.invoice_number"
                  type="text"
                  required
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  :class="{ 'border-red-500': errors.invoice_number }"
                />
                <p
                  v-if="errors.invoice_number"
                  class="mt-1 text-sm text-red-600"
                >
                  {{ errors.invoice_number[0] }}
                </p>
              </div>

              <!-- Issue Date -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                >
                  {{ $t("invoices.issue_date") }} *
                </label>
                <DatePicker
                  v-model="form.issue_date"
                  required
                  :class="{ 'border-red-500': errors.issue_date }"
                />
                <p v-if="errors.issue_date" class="mt-1 text-sm text-red-600">
                  {{ errors.issue_date[0] }}
                </p>
              </div>

              <!-- Due Date -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                >
                  {{ $t("invoices.due_date") }} *
                </label>
                <DatePicker
                  v-model="form.due_date"
                  required
                  :class="{ 'border-red-500': errors.due_date }"
                />
                <p v-if="errors.due_date" class="mt-1 text-sm text-red-600">
                  {{ errors.due_date[0] }}
                </p>
              </div>

              <!-- Language -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                >
                  {{ $t("common.language") }} *
                </label>
                <select
                  v-model="form.language"
                  required
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  :class="{ 'border-red-500': errors.language }"
                >
                  <option value="en">{{ $t("common.english") }}</option>
                  <option value="fa">{{ $t("common.persian") }}</option>
                </select>
                <p v-if="errors.language" class="mt-1 text-sm text-red-600">
                  {{ errors.language[0] }}
                </p>
              </div>

              <!-- Template -->
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                >
                  {{ $t("invoices.template") }}
                </label>
                <select
                  v-model="form.template_id"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                  <option value="">
                    {{ $t("invoices.default_template") }}
                  </option>
                  <option
                    v-for="template in templates"
                    :key="template?.id || 'unknown'"
                    :value="template?.id"
                  >
                    {{ template?.name || "Unknown Template" }}
                  </option>
                  <!-- Fallback option if no templates are loaded -->
                  <option v-if="templates.length === 0" value="" disabled>
                    {{
                      $t("invoices.loading_templates") || "Loading templates..."
                    }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Gold Pricing Section -->
            <div
              class="mb-6 border-t border-gray-200 dark:border-gray-700 pt-6"
            >
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-md font-medium text-gray-900 dark:text-white">
                  {{ $t("invoices.gold_pricing") }}
                </h4>
                <button
                  type="button"
                  @click="resetToDefaults"
                  class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                >
                  {{ $t("invoices.reset_to_defaults") }}
                </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Current Gold Price -->
                <div>
                  <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                  >
                    {{ $t("invoices.current_gold_price_per_gram") }} *
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="goldPricing.pricePerGram"
                      type="number"
                      step="0.01"
                      min="0"
                      required
                      @input="recalculateAllPrices"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-12"
                      :class="{ 'border-red-500': errors.gold_price_per_gram }"
                      :placeholder="$t('invoices.enter_gold_price')"
                    />
                    <div
                      class="absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <span class="text-gray-500 text-sm">{{
                        $t("common.currency_per_gram")
                      }}</span>
                    </div>
                  </div>
                  <p
                    v-if="errors.gold_price_per_gram"
                    class="mt-1 text-sm text-red-600"
                  >
                    {{ errors.gold_price_per_gram[0] }}
                  </p>
                </div>

                <!-- Labor Percentage -->
                <div>
                  <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                  >
                    {{ $t("invoices.labor_cost_percentage") }}
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="goldPricing.laborPercentage"
                      type="number"
                      step="0.1"
                      min="0"
                      @input="recalculateAllPrices"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-8"
                      :placeholder="
                        defaultSettings.defaultLaborPercentage.toString()
                      "
                    />
                    <div
                      class="absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <span class="text-gray-500 text-sm">%</span>
                    </div>
                  </div>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $t("invoices.default") }}:
                    {{ defaultSettings.defaultLaborPercentage }}%
                  </p>
                </div>

                <!-- Profit Percentage -->
                <div>
                  <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                  >
                    {{ $t("invoices.profit_percentage") }}
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="goldPricing.profitPercentage"
                      type="number"
                      step="0.1"
                      min="0"
                      @input="recalculateAllPrices"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-8"
                      :placeholder="
                        defaultSettings.defaultProfitPercentage.toString()
                      "
                    />
                    <div
                      class="absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <span class="text-gray-500 text-sm">%</span>
                    </div>
                  </div>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $t("invoices.default") }}:
                    {{ defaultSettings.defaultProfitPercentage }}%
                  </p>
                </div>

                <!-- Tax Percentage -->
                <div>
                  <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
                  >
                    {{ $t("invoices.tax_percentage") }}
                  </label>
                  <div class="relative">
                    <input
                      v-model.number="goldPricing.taxPercentage"
                      type="number"
                      step="0.1"
                      min="0"
                      @input="recalculateAllPrices"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white pr-8"
                      :placeholder="
                        defaultSettings.defaultTaxPercentage.toString()
                      "
                    />
                    <div
                      class="absolute inset-y-0 right-0 flex items-center pr-3"
                    >
                      <span class="text-gray-500 text-sm">%</span>
                    </div>
                  </div>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $t("invoices.default") }}:
                    {{ defaultSettings.defaultTaxPercentage }}%
                  </p>
                </div>
              </div>

              <!-- Pricing Formula Display -->
              <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                  <strong>{{ $t("invoices.pricing_formula") }}:</strong>
                  {{ $t("invoices.formula_explanation") }}
                </p>
              </div>
            </div>

            <!-- Invoice Items -->
            <div class="mb-6">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-md font-medium text-gray-900 dark:text-white">
                  {{ $t("invoices.items") }}
                </h4>
                <button
                  type="button"
                  @click="addItem"
                  class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-300"
                >
                  <PlusIcon class="h-4 w-4 mr-1" />
                  {{ $t("invoices.add_item") }}
                </button>
              </div>

              <!-- Items Table -->
              <div class="overflow-x-auto">
                <table
                  class="min-w-full divide-y divide-gray-200 dark:divide-gray-700"
                >
                  <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                      >
                        {{ $t("invoices.item_description") }}
                      </th>
                      <th
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                      >
                        {{ $t("invoices.weight_purity") }}
                      </th>
                      <th
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                      >
                        {{ $t("invoices.quantity") }}
                      </th>
                      <th
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                      >
                        {{ $t("invoices.price_breakdown") }}
                      </th>
                      <th
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                      >
                        {{ $t("invoices.total") }}
                      </th>
                      <th
                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase"
                      >
                        {{ $t("common.actions") }}
                      </th>
                    </tr>
                  </thead>
                  <tbody
                    class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700"
                  >
                    <tr v-for="(item, index) in form.items" :key="index">
                      <td class="px-4 py-2">
                        <div class="space-y-2">
                          <select
                            v-model="item.inventory_item_id"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                            @change="
                              selectInventoryItem(index, item.inventory_item_id)
                            "
                          >
                            <option value="">
                              {{ $t("invoices.select_item") }}
                            </option>
                            <option
                              v-for="inventoryItem in inventoryItems"
                              :key="inventoryItem.id"
                              :value="inventoryItem.id"
                            >
                              {{ inventoryItem.name }} ({{ inventoryItem.sku }})
                            </option>
                          </select>
                          <input
                            v-model="item.name"
                            type="text"
                            :placeholder="$t('invoices.item_name')"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                          />
                          <input
                            v-model="item.description"
                            type="text"
                            :placeholder="$t('invoices.custom_description')"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                          />
                        </div>
                      </td>
                      <td class="px-4 py-2">
                        <div class="space-y-2">
                          <div class="flex items-center space-x-2">
                            <input
                              v-model.number="item.weight"
                              type="number"
                              step="0.001"
                              min="0"
                              :placeholder="$t('invoices.weight_grams')"
                              class="w-20 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                              @input="recalculateItemPrice(index)"
                            />
                            <span class="text-xs text-gray-500">g</span>
                          </div>
                          <div class="flex items-center space-x-2">
                            <input
                              v-model.number="item.gold_purity"
                              type="number"
                              step="0.1"
                              min="0"
                              max="24"
                              :placeholder="
                                $t('invoices.gold_purity_placeholder')
                              "
                              class="w-20 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                              @input="recalculateItemPrice(index)"
                            />
                            <span class="text-xs text-gray-500">K</span>
                          </div>
                        </div>
                      </td>
                      <td class="px-4 py-2">
                        <input
                          v-model.number="item.quantity"
                          type="number"
                          step="1"
                          min="1"
                          class="w-20 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
                          @input="recalculateItemPrice(index)"
                        />
                      </td>
                      <td class="px-4 py-2">
                        <div
                          v-if="item.priceBreakdown"
                          class="text-xs space-y-1"
                        >
                          <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                              >{{ $t("invoices.base_gold") }}:</span
                            >
                            <span class="font-medium">{{
                              formatCurrency(item.priceBreakdown.baseGoldCost)
                            }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                              >{{ $t("invoices.labor") }}:</span
                            >
                            <span class="font-medium">{{
                              formatCurrency(item.priceBreakdown.laborCost)
                            }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                              >{{ $t("invoices.profit") }}:</span
                            >
                            <span class="font-medium">{{
                              formatCurrency(item.priceBreakdown.profit)
                            }}</span>
                          </div>
                          <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400"
                              >{{ $t("invoices.tax") }}:</span
                            >
                            <span class="font-medium">{{
                              formatCurrency(item.priceBreakdown.tax)
                            }}</span>
                          </div>
                          <div
                            class="flex justify-between border-t border-gray-200 dark:border-gray-600 pt-1"
                          >
                            <span class="text-gray-600 dark:text-gray-400"
                              >{{ $t("invoices.unit_price") }}:</span
                            >
                            <span class="font-bold">{{
                              formatCurrency(item.priceBreakdown.unitPrice)
                            }}</span>
                          </div>
                        </div>
                        <div v-else class="text-xs text-gray-400">
                          {{ $t("invoices.enter_weight_for_pricing") }}
                        </div>
                      </td>
                      <td class="px-4 py-2">
                        <span
                          class="text-sm font-bold text-gray-900 dark:text-white"
                        >
                          {{ formatCurrency(item.total_price || 0) }}
                        </span>
                      </td>
                      <td class="px-4 py-2">
                        <button
                          type="button"
                          @click="removeItem(index)"
                          class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                        >
                          <TrashIcon class="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Add item if no items -->
              <div v-if="form.items.length === 0" class="text-center py-8">
                <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                  {{ $t("invoices.no_items_added") }}
                </p>
                <button
                  type="button"
                  @click="addItem"
                  class="mt-2 inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 dark:bg-primary-900 dark:text-primary-300"
                >
                  <PlusIcon class="h-4 w-4 mr-1" />
                  {{ $t("invoices.add_first_item") }}
                </button>
              </div>
            </div>

            <!-- Invoice Totals -->
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
              <div class="space-y-2">
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $t("invoices.subtotal") }}:
                  </span>
                  <span
                    class="text-sm font-medium text-gray-900 dark:text-white"
                  >
                    {{ formatCurrency(subtotal) }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $t("invoices.tax") }}:
                  </span>
                  <span
                    class="text-sm font-medium text-gray-900 dark:text-white"
                  >
                    {{ formatCurrency(form.tax_amount || 0) }}
                  </span>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-600 pt-2">
                  <div class="flex justify-between">
                    <span
                      class="text-base font-medium text-gray-900 dark:text-white"
                    >
                      {{ $t("invoices.total") }}:
                    </span>
                    <span
                      class="text-base font-bold text-gray-900 dark:text-white"
                    >
                      {{ formatCurrency(total) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                {{ $t("invoices.notes") }}
              </label>
              <textarea
                v-model="form.notes"
                rows="3"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                :placeholder="$t('invoices.notes_placeholder')"
              ></textarea>
            </div>
          </div>

          <!-- Footer -->
          <div
            class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"
          >
            <button
              type="submit"
              :disabled="loading || form.items.length === 0"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg
                v-if="loading"
                class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
              {{ isEdit ? $t("common.update") : $t("common.create") }}
            </button>
            <button
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
            >
              {{ $t("common.cancel") }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import {
  XMarkIcon,
  PlusIcon,
  TrashIcon,
  DocumentTextIcon,
} from "@heroicons/vue/24/outline";
import { useInvoicesStore } from "@/stores/invoices";
import { useCustomersStore } from "@/stores/customers";
// import { useInventoryStore } from "@/stores/inventory";
import { useNumberFormatter } from "@/composables/useNumberFormatter";
import { useGoldPricing } from "@/composables/useGoldPricing";
import DatePicker from "@/components/localization/DatePicker.vue";
import type {
  Invoice,
  InvoiceItem,
  Customer,
  InventoryItem,
  InvoiceTemplate,
} from "@/types";
import type { PriceBreakdown } from "@/composables/useGoldPricing";

// Props
interface Props {
  invoice?: Invoice | null;
  isEdit?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  invoice: null,
  isEdit: false,
});

// Emits
const emit = defineEmits<{
  close: [];
  saved: [invoice: Invoice];
}>();

const invoicesStore = useInvoicesStore();
const customersStore = useCustomersStore();
// const inventoryStore = useInventoryStore();
const { formatCurrency } = useNumberFormatter();
const {
  defaultSettings,
  calculateItemPrice,
  loadDefaultSettings,
  formatCurrency: formatGoldCurrency,
} = useGoldPricing();

// State
const loading = ref(false);
const errors = ref<Record<string, string[]>>({});

const form = ref({
  customer_id: "",
  invoice_number: "",
  issue_date: new Date().toISOString().split("T")[0],
  due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
    .toISOString()
    .split("T")[0], // 30 days from now
  language: "en" as "en" | "fa",
  template_id: "",
  subtotal: 0,
  tax_amount: 0,
  total_amount: 0,
  status: "draft" as "draft" | "sent" | "paid" | "overdue" | "cancelled",
  notes: "",
  items: [] as (InvoiceItem & {
    weight?: number;
    priceBreakdown?: PriceBreakdown;
  })[],
  // Gold pricing fields
  gold_price_per_gram: 0,
  labor_percentage: 0,
  profit_percentage: 0,
  tax_percentage: 0,
});

// Gold pricing reactive data
const goldPricing = ref({
  pricePerGram: 0,
  laborPercentage: 0,
  profitPercentage: 0,
  taxPercentage: 0,
});

// Computed
const customers = computed(() => {
  // Ensure we always return an array, even if customersStore.customers is null/undefined
  return Array.isArray(customersStore.customers)
    ? customersStore.customers
    : [];
});
const inventoryItems = computed(() => [] as InventoryItem[]);
const templates = computed(() => {
  // Ensure we always return an array, even if invoicesStore.templates is null/undefined
  return Array.isArray(invoicesStore.templates) ? invoicesStore.templates : [];
});

const subtotal = computed(() => {
  return form.value.items.reduce(
    (sum, item) => sum + (item.total_price || 0),
    0,
  );
});

const total = computed(() => {
  return subtotal.value + (form.value.tax_amount || 0);
});

// Methods
const addItem = () => {
  form.value.items.push({
    id: 0,
    invoice_id: 0,
    inventory_item_id: undefined,
    name: "",
    description: "",
    quantity: 1,
    unit_price: 0,
    total_price: 0,
    gold_purity: undefined,
    weight: undefined,
    serial_number: undefined,
    category_id: undefined,
    main_category_id: undefined,
    category_path: undefined,
    main_category_name: undefined,
    category_name: undefined,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    priceBreakdown: undefined,
  });
};

const removeItem = (index: number) => {
  form.value.items.splice(index, 1);
  updateTotals();
};

const selectInventoryItem = (
  index: number,
  inventoryItemId: number | undefined,
) => {
  if (
    inventoryItemId &&
    inventoryItems.value &&
    inventoryItems.value.length > 0
  ) {
    const inventoryItem = inventoryItems.value.find(
      (item) => item && item.id === inventoryItemId,
    );
    if (inventoryItem) {
      form.value.items[index].name = inventoryItem.name || "";
      form.value.items[index].description = inventoryItem.description || "";
      form.value.items[index].unit_price = inventoryItem.unit_price || 0;
      form.value.items[index].gold_purity =
        inventoryItem.gold_purity || undefined;
      form.value.items[index].weight = inventoryItem.weight || undefined;
      form.value.items[index].serial_number =
        inventoryItem.serial_number || undefined;
      form.value.items[index].category_id =
        inventoryItem.category_id || undefined;
      form.value.items[index].main_category_id =
        inventoryItem.main_category_id || undefined;
      form.value.items[index].category_path =
        inventoryItem.category_path || undefined;
      form.value.items[index].main_category_name =
        inventoryItem.main_category?.name || undefined;
      form.value.items[index].category_name =
        inventoryItem.category?.name || undefined;

      // Use dynamic pricing if weight is available, otherwise use static price
      if (inventoryItem.weight && goldPricing.value.pricePerGram) {
        recalculateItemPrice(index);
      } else {
        calculateItemTotal(index);
      }
    }
  }
};

const calculateItemTotal = (index: number) => {
  const item = form.value.items[index];
  item.total_price = (item.quantity || 0) * (item.unit_price || 0);
  updateTotals();
};

const updateTotals = () => {
  form.value.subtotal = subtotal.value;
  form.value.total_amount = total.value;
};

// Gold pricing methods
const resetToDefaults = () => {
  goldPricing.value.laborPercentage =
    defaultSettings.value.defaultLaborPercentage;
  goldPricing.value.profitPercentage =
    defaultSettings.value.defaultProfitPercentage;
  goldPricing.value.taxPercentage = defaultSettings.value.defaultTaxPercentage;
  recalculateAllPrices();
};

const recalculateItemPrice = (index: number) => {
  const item = form.value.items[index];

  // Only calculate if we have weight and gold price
  if (!item.weight || !goldPricing.value.pricePerGram) {
    item.priceBreakdown = undefined;
    item.unit_price = 0;
    item.total_price = 0;
    updateTotals();
    return;
  }

  try {
    const pricing = calculateItemPrice({
      weight: item.weight,
      goldPricePerGram: goldPricing.value.pricePerGram,
      laborPercentage:
        goldPricing.value.laborPercentage ||
        defaultSettings.value.defaultLaborPercentage,
      profitPercentage:
        goldPricing.value.profitPercentage ||
        defaultSettings.value.defaultProfitPercentage,
      taxPercentage:
        goldPricing.value.taxPercentage ||
        defaultSettings.value.defaultTaxPercentage,
      quantity: item.quantity || 1,
    });

    item.priceBreakdown = pricing;
    item.unit_price = pricing.unitPrice;
    item.total_price = pricing.totalPrice;
  } catch (error) {
    console.error("Error calculating item price:", error);
    item.priceBreakdown = undefined;
    item.unit_price = 0;
    item.total_price = 0;
  }

  updateTotals();
};

const recalculateAllPrices = () => {
  form.value.items.forEach((_, index) => {
    recalculateItemPrice(index);
  });
};

const formatGoldPurity = (purity: number | undefined): string => {
  if (!purity) return "";
  return `${purity.toFixed(1)}K`;
};

const handleSubmit = async () => {
  loading.value = true;
  errors.value = {};

  try {
    const invoiceData = {
      ...form.value,
      customer_id: Number(form.value.customer_id),
      template_id: form.value.template_id
        ? Number(form.value.template_id)
        : undefined,
      // Include gold pricing parameters
      gold_price_per_gram: goldPricing.value.pricePerGram,
      labor_percentage:
        goldPricing.value.laborPercentage ||
        defaultSettings.value.defaultLaborPercentage,
      profit_percentage:
        goldPricing.value.profitPercentage ||
        defaultSettings.value.defaultProfitPercentage,
      tax_percentage:
        goldPricing.value.taxPercentage ||
        defaultSettings.value.defaultTaxPercentage,
    };

    let savedInvoice;
    if (props.isEdit && props.invoice) {
      savedInvoice = await invoicesStore.updateInvoice(
        props.invoice.id,
        invoiceData,
      );
    } else {
      savedInvoice = await invoicesStore.createInvoice(invoiceData);
    }

    if (savedInvoice) {
      emit("saved", savedInvoice);
    }
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    }
    console.error("Failed to save invoice:", error);
  } finally {
    loading.value = false;
  }
};

// Initialize form with existing invoice data
const initializeForm = async () => {
  // Load default settings first
  await loadDefaultSettings();

  if (props.isEdit && props.invoice) {
    form.value = {
      customer_id: props.invoice.customer_id?.toString() || "",
      invoice_number: props.invoice.invoice_number || "",
      issue_date:
        props.invoice.issue_date || new Date().toISOString().split("T")[0],
      due_date:
        props.invoice.due_date ||
        new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
          .toISOString()
          .split("T")[0],
      language: props.invoice.language || "en",
      template_id: props.invoice.template_id?.toString() || "",
      subtotal: props.invoice.subtotal || 0,
      tax_amount: props.invoice.tax_amount || 0,
      total_amount: props.invoice.total_amount || 0,
      status: props.invoice.status || "draft",
      notes: props.invoice.notes || "",
      items: props.invoice.items || [],
      // Load gold pricing data
      gold_price_per_gram: props.invoice.gold_price_per_gram || 0,
      labor_percentage: props.invoice.labor_percentage || 0,
      profit_percentage: props.invoice.profit_percentage || 0,
      tax_percentage: props.invoice.tax_percentage || 0,
    };

    // Set gold pricing reactive data
    goldPricing.value = {
      pricePerGram: props.invoice.gold_price_per_gram || 0,
      laborPercentage:
        props.invoice.labor_percentage ||
        defaultSettings.value.defaultLaborPercentage,
      profitPercentage:
        props.invoice.profit_percentage ||
        defaultSettings.value.defaultProfitPercentage,
      taxPercentage:
        props.invoice.tax_percentage ||
        defaultSettings.value.defaultTaxPercentage,
    };
  } else {
    // Generate next invoice number
    generateInvoiceNumber();

    // Set default gold pricing values
    goldPricing.value = {
      pricePerGram: 0,
      laborPercentage: defaultSettings.value.defaultLaborPercentage,
      profitPercentage: defaultSettings.value.defaultProfitPercentage,
      taxPercentage: defaultSettings.value.defaultTaxPercentage,
    };
  }
};

const generateInvoiceNumber = () => {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const random = Math.floor(Math.random() * 1000)
    .toString()
    .padStart(3, "0");
  form.value.invoice_number = `INV-${year}${month}-${random}`;
};

// Watch for total changes
watch([subtotal, () => form.value.tax_amount], () => {
  updateTotals();
});

// Lifecycle
onMounted(async () => {
  // Load required data with error handling
  try {
    await Promise.all([
      customersStore.fetchCustomers().catch((error) => {
        console.warn("Failed to load customers, using fallback data:", error);
      }),
      // inventoryStore.fetchItems(),
      invoicesStore.fetchTemplates().catch((error) => {
        console.warn(
          "Failed to load templates, continuing without templates:",
          error,
        );
      }),
    ]);
  } catch (error) {
    console.warn(
      "Some data failed to load, but continuing with available data:",
      error,
    );
  }

  await initializeForm();
});
</script>
