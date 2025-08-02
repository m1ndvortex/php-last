<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTag;
use App\Models\InvoiceTemplate;
use App\Models\Customer;
use App\Services\InvoiceTemplateService;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default invoice templates
        $templateService = app(InvoiceTemplateService::class);
        
        // English template
        InvoiceTemplate::create([
            'name' => 'Default English Template',
            'description' => 'Default template for English invoices',
            'template_data' => $templateService->getDefaultTemplateStructure('en'),
            'language' => 'en',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Persian template
        InvoiceTemplate::create([
            'name' => 'قالب پیش‌فرض فارسی',
            'description' => 'قالب پیش‌فرض برای فاکتورهای فارسی',
            'template_data' => $templateService->getDefaultTemplateStructure('fa'),
            'language' => 'fa',
            'is_default' => true,
            'is_active' => true,
        ]);

        // Create sample invoices
        $customers = Customer::all();
        
        if ($customers->count() > 0) {
            // Create 20 sample invoices
            for ($i = 0; $i < 20; $i++) {
                $customer = $customers->random();
                $language = fake()->randomElement(['en', 'fa']);
                
                $invoice = Invoice::create([
                    'customer_id' => $customer->id,
                    'template_id' => InvoiceTemplate::where('language', $language)->where('is_default', true)->first()?->id,
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'issue_date' => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                    'due_date' => fake()->dateTimeBetween('now', '+2 months')->format('Y-m-d'),
                    'language' => $language,
                    'status' => fake()->randomElement(['draft', 'sent', 'paid', 'overdue']),
                    'notes' => fake()->optional(0.3)->sentence(),
                    'internal_notes' => fake()->optional(0.2)->sentence(),
                    'discount_amount' => fake()->optional(0.3)->randomFloat(2, 0, 100),
                ]);

                // Add 1-5 items to each invoice
                $itemCount = fake()->numberBetween(1, 5);
                for ($j = 0; $j < $itemCount; $j++) {
                    $quantity = fake()->randomFloat(3, 0.1, 10);
                    $unitPrice = fake()->randomFloat(2, 50, 2000);
                    $totalPrice = $quantity * $unitPrice;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'name' => fake()->randomElement([
                            'Gold Ring', 'Silver Necklace', 'Diamond Earrings', 'Pearl Bracelet',
                            'Platinum Ring', 'Ruby Pendant', 'Emerald Ring', 'Sapphire Necklace',
                            'Gold Chain', 'Silver Bracelet', 'Diamond Ring', 'Gold Earrings'
                        ]),
                        'description' => fake()->optional(0.7)->sentence(),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                        'gold_purity' => fake()->optional(0.6)->randomFloat(3, 10, 24),
                        'weight' => fake()->optional(0.8)->randomFloat(3, 0.5, 50),
                        'serial_number' => fake()->optional(0.4)->bothify('SN-####-????'),
                    ]);
                }

                // Add 0-3 tags to each invoice
                $tagCount = fake()->numberBetween(0, 3);
                $availableTags = ['jewelry', 'gold', 'silver', 'diamond', 'ring', 'necklace', 'bracelet', 'earrings', 'custom', 'wholesale'];
                $selectedTags = fake()->randomElements($availableTags, $tagCount);
                
                foreach ($selectedTags as $tag) {
                    InvoiceTag::create([
                        'invoice_id' => $invoice->id,
                        'tag' => $tag,
                    ]);
                }

                // Calculate totals
                app(\App\Services\InvoiceService::class)->calculateInvoiceTotals($invoice);
            }
        }

        $this->command->info('Created invoice templates and sample invoices');
    }
}
