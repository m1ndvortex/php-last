<?php

namespace Database\Factories;

use App\Models\BatchOperationItem;
use App\Models\BatchOperation;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BatchOperationItem>
 */
class BatchOperationItemFactory extends Factory
{
    protected $model = BatchOperationItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $referenceTypes = ['invoice', 'customer'];
        $statuses = ['pending', 'processing', 'completed', 'failed'];
        
        $referenceType = $this->faker->randomElement($referenceTypes);
        $status = $this->faker->randomElement($statuses);

        return [
            'batch_operation_id' => BatchOperation::factory(),
            'reference_type' => $referenceType,
            'reference_id' => $referenceType === 'invoice' ? Invoice::factory() : Customer::factory(),
            'customer_id' => Customer::factory(),
            'status' => $status,
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'data' => $this->generateDataForType($referenceType, $status),
            'processed_at' => in_array($status, ['completed', 'failed']) ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
        ];
    }

    /**
     * Generate appropriate data based on reference type and status.
     */
    protected function generateDataForType(string $referenceType, string $status): array
    {
        $data = [];

        if ($referenceType === 'invoice') {
            $data['invoice_number'] = 'INV-' . $this->faker->unique()->numberBetween(1000, 9999);
            $data['total_amount'] = $this->faker->randomFloat(2, 10, 1000);

            if ($status === 'completed') {
                // Add PDF generation data if applicable
                if ($this->faker->boolean(70)) {
                    $data['pdf_path'] = 'invoices/invoice_' . $data['invoice_number'] . '.pdf';
                    $data['pdf_generated_at'] = $this->faker->dateTimeBetween('-1 week', 'now')->format('c');
                    $data['file_size'] = $this->faker->numberBetween(50000, 500000);
                }

                // Add communication data if applicable
                if ($this->faker->boolean(60)) {
                    $data['communication_sent'] = true;
                    $data['communication_method'] = $this->faker->randomElement(['email', 'sms', 'whatsapp']);
                    $data['recipient'] = $this->faker->email();
                    $data['sent_at'] = $this->faker->dateTimeBetween('-1 week', 'now')->format('c');
                }
            }
        }

        return $data;
    }

    /**
     * Indicate that the item is pending.
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
                'error_message' => null,
                'processed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the item is processing.
     */
    public function processing(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
                'error_message' => null,
                'processed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the item is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'error_message' => null,
                'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'data' => $this->generateCompletedData($attributes['reference_type']),
            ];
        });
    }

    /**
     * Indicate that the item failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'error_message' => $this->faker->sentence(),
                'processed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
                'data' => $this->generateFailedData($attributes['reference_type']),
            ];
        });
    }

    /**
     * Indicate that the item is for an invoice.
     */
    public function forInvoice(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'reference_type' => 'invoice',
                'reference_id' => Invoice::factory(),
                'data' => $this->generateDataForType('invoice', $attributes['status']),
            ];
        });
    }

    /**
     * Indicate that the item is for a customer.
     */
    public function forCustomer(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'reference_type' => 'customer',
                'reference_id' => Customer::factory(),
                'data' => $this->generateDataForType('customer', $attributes['status']),
            ];
        });
    }

    /**
     * Generate data for completed items.
     */
    protected function generateCompletedData(string $referenceType): array
    {
        $data = [];

        if ($referenceType === 'invoice') {
            $data['invoice_number'] = 'INV-' . $this->faker->unique()->numberBetween(1000, 9999);
            $data['total_amount'] = $this->faker->randomFloat(2, 10, 1000);
            $data['pdf_path'] = 'invoices/invoice_' . $data['invoice_number'] . '.pdf';
            $data['pdf_generated_at'] = $this->faker->dateTimeBetween('-1 week', 'now')->format('c');
            $data['communication_sent'] = $this->faker->boolean(80);
            
            if ($data['communication_sent']) {
                $data['communication_method'] = $this->faker->randomElement(['email', 'sms', 'whatsapp']);
                $data['recipient'] = $this->faker->email();
                $data['sent_at'] = $this->faker->dateTimeBetween('-1 week', 'now')->format('c');
            }
        }

        return $data;
    }

    /**
     * Generate data for failed items.
     */
    protected function generateFailedData(string $referenceType): array
    {
        $data = [];

        if ($referenceType === 'invoice') {
            $data['invoice_id'] = $this->faker->numberBetween(1, 1000);
            $data['pdf_error'] = $this->faker->sentence();
            $data['communication_error'] = $this->faker->sentence();
        }

        return $data;
    }

    /**
     * Indicate that the item has PDF generation data.
     */
    public function withPDF(): static
    {
        return $this->state(function (array $attributes) {
            $data = $attributes['data'] ?? [];
            $data['pdf_path'] = 'invoices/invoice_' . $this->faker->unique()->numberBetween(1000, 9999) . '.pdf';
            $data['pdf_generated_at'] = $this->faker->dateTimeBetween('-1 week', 'now')->toISOString();
            $data['file_size'] = $this->faker->numberBetween(50000, 500000);

            return ['data' => $data];
        });
    }

    /**
     * Indicate that the item has communication data.
     */
    public function withCommunication(): static
    {
        return $this->state(function (array $attributes) {
            $data = $attributes['data'] ?? [];
            $data['communication_sent'] = true;
            $data['communication_method'] = $this->faker->randomElement(['email', 'sms', 'whatsapp']);
            $data['recipient'] = $this->faker->email();
            $data['sent_at'] = $this->faker->dateTimeBetween('-1 week', 'now')->format('c');

            return ['data' => $data];
        });
    }
}