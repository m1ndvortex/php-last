# Gold Purity System Documentation

## Overview

The Gold Purity System is a specialized component designed for jewelry businesses to manage and display gold purity information in both English and Persian languages. The system handles karat-to-purity conversions, localized formatting, and integration with the category and inventory management systems.

## Technical Architecture

### Core Components

1. **GoldPurityService**: Main service class handling purity calculations and formatting
2. **GoldPuritySelector**: Vue.js component for purity selection in forms
3. **Database Integration**: Storage and retrieval of purity values
4. **Localization Support**: Bilingual display with Persian numerals

### Service Class Structure

```php
<?php

namespace App\Services;

class GoldPurityService
{
    // Standard gold purity values in karats
    private const STANDARD_PURITIES = [
        10.000, 14.000, 18.000, 21.000, 22.000, 24.000
    ];
    
    // Karat to per-mille conversion factor
    private const KARAT_TO_PERMILLE = 41.666667;
    
    public function getStandardPurities(): array
    public function formatPurityDisplay(float $purity, string $locale): string
    public function convertKaratToPurity(float $karat): float
    public function convertPurityToKarat(float $purity): float
    public function getPurityRanges(): array
    public function validatePurity(float $purity): bool
}
```

## Gold Purity Standards

### International Standards

| Karat | Purity (‰) | Gold Content | Common Use |
|-------|------------|--------------|------------|
| 24K   | 999‰       | 99.9%        | Investment gold, coins |
| 22K   | 916‰       | 91.6%        | High-end jewelry (Middle East) |
| 21K   | 875‰       | 87.5%        | Traditional jewelry (Middle East) |
| 18K   | 750‰       | 75.0%        | Fine jewelry (International) |
| 14K   | 585‰       | 58.5%        | Everyday jewelry (US/Europe) |
| 10K   | 417‰       | 41.7%        | Budget jewelry (US) |

### Persian Gold Standards

In Persian jewelry markets, common purities include:
- **24 عیار** (24K): Pure gold for investment
- **22 عیار** (22K): Premium jewelry
- **21 عیار** (21K): Traditional Persian jewelry
- **18 عیار** (18K): Modern jewelry
- **14 عیار** (14K): Affordable options

## Implementation Details

### Database Schema

```sql
-- Categories table enhancement
ALTER TABLE categories ADD COLUMN default_gold_purity DECIMAL(5,3) NULL;

-- Inventory items table
ALTER TABLE inventory_items ADD COLUMN gold_purity DECIMAL(5,3) NULL;

-- Index for performance
CREATE INDEX idx_inventory_items_gold_purity ON inventory_items(gold_purity);
CREATE INDEX idx_categories_default_gold_purity ON categories(default_gold_purity);
```

### Service Methods

#### 1. Get Standard Purity Options

```php
public function getStandardPurities(): array
{
    return collect(self::STANDARD_PURITIES)->map(function ($purity) {
        return [
            'value' => $purity,
            'label' => $this->formatPurityDisplay($purity, 'en'),
            'label_persian' => $this->formatPurityDisplay($purity, 'fa'),
            'permille' => $this->convertKaratToPermille($purity),
        ];
    })->toArray();
}
```

#### 2. Format Purity Display

```php
public function formatPurityDisplay(float $purity, string $locale): string
{
    $permille = $this->convertKaratToPermille($purity);
    
    if ($locale === 'fa') {
        $persianKarat = $this->convertToPersianNumerals($purity);
        return "{$persianKarat} عیار";
    }
    
    return "{$purity}K ({$permille}‰)";
}
```

#### 3. Karat to Per-mille Conversion

```php
public function convertKaratToPermille(float $karat): int
{
    return round($karat * self::KARAT_TO_PERMILLE);
}

public function convertPermilleToKarat(int $permille): float
{
    return round($permille / self::KARAT_TO_PERMILLE, 3);
}
```

#### 4. Persian Numeral Conversion

```php
private function convertToPersianNumerals(float $number): string
{
    $englishNumerals = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $persianNumerals = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    
    return str_replace($englishNumerals, $persianNumerals, (string)$number);
}
```

### Frontend Component

#### GoldPuritySelector Vue Component

```vue
<template>
  <div class="gold-purity-selector">
    <label class="form-label">
      {{ $t('inventory.gold_purity') }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    
    <div class="flex space-x-2">
      <!-- Dropdown for standard values -->
      <select 
        v-model="selectedStandard" 
        @change="onStandardChange"
        class="form-input flex-1"
      >
        <option value="">{{ $t('inventory.select_purity') }}</option>
        <option 
          v-for="option in standardOptions" 
          :key="option.value"
          :value="option.value"
        >
          {{ currentLocale === 'fa' ? option.label_persian : option.label }}
        </option>
      </select>
      
      <!-- Custom input -->
      <input
        v-model.number="customValue"
        @input="onCustomInput"
        type="number"
        step="0.001"
        min="1"
        max="24"
        :placeholder="$t('inventory.custom_purity')"
        class="form-input w-24"
      />
    </div>
    
    <!-- Display formatted value -->
    <div v-if="modelValue" class="mt-1 text-sm text-gray-600">
      {{ formatDisplayValue(modelValue) }}
    </div>
    
    <!-- Validation error -->
    <div v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useLocale } from '@/composables/useLocale'
import { useApi } from '@/composables/useApi'

interface GoldPurityOption {
  value: number
  label: string
  label_persian: string
  permille: number
}

const props = defineProps<{
  modelValue?: number
  required?: boolean
  error?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number | undefined]
}>()

const { currentLocale } = useLocale()
const { get } = useApi()

const selectedStandard = ref<number | ''>('')
const customValue = ref<number | ''>('')
const standardOptions = ref<GoldPurityOption[]>([])

// Load standard purity options
const loadStandardOptions = async () => {
  try {
    const response = await get('/api/categories/gold-purity-options')
    standardOptions.value = response.data
  } catch (error) {
    console.error('Failed to load gold purity options:', error)
  }
}

// Format display value based on locale
const formatDisplayValue = (value: number): string => {
  if (currentLocale.value === 'fa') {
    const persianValue = value.toString().replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[parseInt(d)])
    return `${persianValue} عیار`
  }
  
  const permille = Math.round(value * 41.666667)
  return `${value}K (${permille}‰)`
}

// Handle standard selection
const onStandardChange = () => {
  if (selectedStandard.value) {
    customValue.value = ''
    emit('update:modelValue', selectedStandard.value)
  }
}

// Handle custom input
const onCustomInput = () => {
  if (customValue.value) {
    selectedStandard.value = ''
    emit('update:modelValue', customValue.value)
  }
}

// Initialize component
onMounted(() => {
  loadStandardOptions()
  
  // Set initial values
  if (props.modelValue) {
    const isStandard = standardOptions.value.some(opt => opt.value === props.modelValue)
    if (isStandard) {
      selectedStandard.value = props.modelValue
    } else {
      customValue.value = props.modelValue
    }
  }
})
</script>
```

## Integration with Categories

### Default Purity Assignment

When creating categories, you can set a default gold purity:

```php
// In CategoryController
public function store(StoreCategoryRequest $request)
{
    $category = Category::create([
        'name' => $request->name,
        'default_gold_purity' => $request->default_gold_purity,
        // ... other fields
    ]);
    
    return response()->json(['data' => $category]);
}
```

### Auto-population in Item Forms

When selecting a category for an inventory item:

```javascript
// In ItemFormModal.vue
const onCategoryChange = async (categoryId) => {
  if (categoryId) {
    const category = await fetchCategory(categoryId)
    if (category.default_gold_purity) {
      form.gold_purity = category.default_gold_purity
    }
  }
}
```

## Validation and Business Rules

### Validation Rules

```php
// In validation requests
public function rules(): array
{
    return [
        'gold_purity' => [
            'nullable',
            'numeric',
            'min:1',
            'max:24',
            'regex:/^\d+(\.\d{1,3})?$/', // Up to 3 decimal places
        ],
    ];
}
```

### Business Logic

1. **Purity Ranges**: Valid range is 1K to 24K
2. **Precision**: Support up to 3 decimal places (e.g., 18.750)
3. **Standard Values**: Promote use of industry-standard purities
4. **Category Defaults**: Inherit from category when available
5. **Validation**: Ensure realistic values for jewelry

## Reporting and Analytics

### Purity-Based Reports

```php
// Generate purity distribution report
public function getPurityDistribution(): array
{
    return InventoryItem::selectRaw('
        gold_purity,
        COUNT(*) as item_count,
        SUM(quantity) as total_quantity,
        AVG(unit_price) as avg_price
    ')
    ->whereNotNull('gold_purity')
    ->groupBy('gold_purity')
    ->orderBy('gold_purity', 'desc')
    ->get()
    ->map(function ($item) {
        return [
            'purity' => $item->gold_purity,
            'formatted_purity' => app(GoldPurityService::class)
                ->formatPurityDisplay($item->gold_purity, app()->getLocale()),
            'item_count' => $item->item_count,
            'total_quantity' => $item->total_quantity,
            'avg_price' => $item->avg_price,
        ];
    })
    ->toArray();
}
```

### Dashboard Widgets

Create widgets showing:
- Purity distribution pie chart
- Average purity by category
- High-value purity items
- Purity-based profit margins

## Localization Support

### Translation Keys

```json
// en.json
{
  "inventory": {
    "gold_purity": "Gold Purity",
    "select_purity": "Select Purity",
    "custom_purity": "Custom",
    "default_from_category": "Default from category: {purity}",
    "purity_required": "Gold purity is required",
    "invalid_purity": "Invalid gold purity value"
  }
}

// fa.json
{
  "inventory": {
    "gold_purity": "عیار طلا",
    "select_purity": "انتخاب عیار",
    "custom_purity": "سفارشی",
    "default_from_category": "پیش‌فرض از دسته‌بندی: {purity}",
    "purity_required": "عیار طلا الزامی است",
    "invalid_purity": "مقدار عیار طلا نامعتبر است"
  }
}
```

### RTL Support

```css
/* RTL styles for Persian interface */
.gold-purity-selector[dir="rtl"] {
  .flex {
    flex-direction: row-reverse;
  }
  
  .space-x-2 > * + * {
    margin-right: 0.5rem;
    margin-left: 0;
  }
}
```

## Performance Considerations

### Database Optimization

1. **Indexing**: Index gold_purity columns for fast filtering
2. **Caching**: Cache standard purity options
3. **Aggregation**: Pre-calculate purity statistics

### Frontend Optimization

1. **Component Caching**: Cache purity options in component
2. **Lazy Loading**: Load options only when needed
3. **Debouncing**: Debounce custom input validation

## Testing

### Unit Tests

```php
class GoldPurityServiceTest extends TestCase
{
    public function test_converts_karat_to_permille()
    {
        $service = new GoldPurityService();
        $this->assertEquals(750, $service->convertKaratToPermille(18));
        $this->assertEquals(916, $service->convertKaratToPermille(22));
    }
    
    public function test_formats_purity_display_english()
    {
        $service = new GoldPurityService();
        $result = $service->formatPurityDisplay(18, 'en');
        $this->assertEquals('18K (750‰)', $result);
    }
    
    public function test_formats_purity_display_persian()
    {
        $service = new GoldPurityService();
        $result = $service->formatPurityDisplay(18, 'fa');
        $this->assertEquals('۱۸ عیار', $result);
    }
}
```

### Frontend Tests

```typescript
describe('GoldPuritySelector', () => {
  it('should display standard purity options', async () => {
    const wrapper = mount(GoldPuritySelector)
    await wrapper.vm.$nextTick()
    
    expect(wrapper.find('select option').length).toBeGreaterThan(1)
  })
  
  it('should format Persian numerals correctly', () => {
    const wrapper = mount(GoldPuritySelector, {
      props: { modelValue: 18 }
    })
    
    // Set locale to Persian
    wrapper.vm.currentLocale = 'fa'
    
    expect(wrapper.text()).toContain('۱۸ عیار')
  })
})
```

## Troubleshooting

### Common Issues

1. **Persian numerals not displaying**: Check font support and UTF-8 encoding
2. **Conversion errors**: Verify calculation precision and rounding
3. **Validation failures**: Ensure proper decimal handling
4. **Performance issues**: Check database indexes and caching

### Debug Tools

```php
// Add to GoldPurityService for debugging
public function debugPurityCalculation(float $input): array
{
    return [
        'input' => $input,
        'permille' => $this->convertKaratToPermille($input),
        'formatted_en' => $this->formatPurityDisplay($input, 'en'),
        'formatted_fa' => $this->formatPurityDisplay($input, 'fa'),
        'is_standard' => in_array($input, self::STANDARD_PURITIES),
    ];
}
```

## Future Enhancements

### Planned Features

1. **Alloy Composition**: Track other metals in gold alloys
2. **Certification Integration**: Link to gold certification systems
3. **Price Integration**: Connect to live gold prices
4. **Custom Standards**: Allow businesses to define custom purity standards
5. **Hallmarking**: Integration with hallmarking requirements

### API Extensions

1. **Bulk Purity Updates**: Update multiple items at once
2. **Purity History**: Track purity changes over time
3. **Market Integration**: Real-time gold price feeds
4. **Compliance Reporting**: Generate regulatory compliance reports