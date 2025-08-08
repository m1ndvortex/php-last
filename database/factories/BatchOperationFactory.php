<?php

namespace Database\Factories;

use App\Models\BatchOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BatchOperation>
 */
class BatchOperationFactory extends Factory
{
    protected $model = BatchOperation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['invoice_generation', 'pdf_generation', 'communication_sending'];
        $statuses = ['processing', 'completed', 'completed_with_errors', 'failed'];
        
        $type = $this->faker->randomElement($types);
        $status = $this->faker->randomElement($statuses);
        $totalCount = $this->faker->numberBetween(1, 50);
        $processedCount = $status === 'processing' ? $this->faker->numberBetween(0, $totalCount - 1) : $totalCount;
        $progress = $totalCount > 0 ? round(($processedCount / $totalCount) * 100, 2) : 0;

        return [
            'type' => $type,
            'status' => $status,
            'progress' => $progress,
            'processed_count' => $processedCount,
            'total_count' => $totalCount,
            'metadata' => [
                'customer_count' => $totalCount,
                'options' => [
                    'language' => $this->faker->randomElement(['en', 'fa']),
                    'due_days' => $this->faker->numberBetween(15, 60),
                    'generate_pdf' => $this->faker->boolean(),
                    'send_immediately' => $this->faker->boolean()
                ]
            ],
            'summary' => $status !== 'processing' ? [
                'total_processed' => $totalCount,
                'successful' => $this->faker->numberBetween(0, $totalCount),
                'failed' => $this->faker->numberBetween(0, $totalCount),
                'success_rate' => $this->faker->randomFloat(2, 0, 100)
            ] : null,
            'error_message' => $status === 'failed' ? $this->faker->sentence() : null,
            'combined_file_path' => $type === 'pdf_generation' && $this->faker->boolean() 
                ? 'batch_pdfs/combined_' . $this->faker->uuid() . '.pdf' 
                : null,
            'created_by' => User::factory(),
            'started_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'completed_at' => $status !== 'processing' ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
        ];
    }

    /**
     * Indicate that the batch operation is processing.
     */
    public function processing(): static
    {
        return $this->state(function (array $attributes) {
            $processedCount = $this->faker->numberBetween(0, $attributes['total_count'] - 1);
            $progress = $attributes['total_count'] > 0 ? round(($processedCount / $attributes['total_count']) * 100, 2) : 0;

            return [
                'status' => 'processing',
                'progress' => $progress,
                'processed_count' => $processedCount,
                'summary' => null,
                'error_message' => null,
                'completed_at' => null,
            ];
        });
    }

    /**
     * Indicate that the batch operation is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $totalCount = $attributes['total_count'];
            $successful = $this->faker->numberBetween(floor($totalCount * 0.8), $totalCount);
            $failed = $totalCount - $successful;

            return [
                'status' => 'completed',
                'progress' => 100.00,
                'processed_count' => $totalCount,
                'summary' => [
                    'total_processed' => $totalCount,
                    'successful' => $successful,
                    'failed' => $failed,
                    'success_rate' => round(($successful / $totalCount) * 100, 2)
                ],
                'error_message' => null,
                'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Indicate that the batch operation completed with errors.
     */
    public function completedWithErrors(): static
    {
        return $this->state(function (array $attributes) {
            $totalCount = $attributes['total_count'];
            $successful = $this->faker->numberBetween(1, floor($totalCount * 0.8));
            $failed = $totalCount - $successful;

            return [
                'status' => 'completed_with_errors',
                'progress' => 100.00,
                'processed_count' => $totalCount,
                'summary' => [
                    'total_processed' => $totalCount,
                    'successful' => $successful,
                    'failed' => $failed,
                    'success_rate' => round(($successful / $totalCount) * 100, 2)
                ],
                'error_message' => null,
                'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Indicate that the batch operation failed.
     */
    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'progress' => $this->faker->randomFloat(2, 0, 99),
                'processed_count' => $this->faker->numberBetween(0, $attributes['total_count'] - 1),
                'summary' => null,
                'error_message' => $this->faker->sentence(),
                'completed_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            ];
        });
    }

    /**
     * Indicate that the batch operation is for invoice generation.
     */
    public function invoiceGeneration(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'invoice_generation',
                'metadata' => [
                    'customer_count' => $attributes['total_count'],
                    'options' => [
                        'language' => $this->faker->randomElement(['en', 'fa']),
                        'due_days' => $this->faker->numberBetween(15, 60),
                        'generate_pdf' => $this->faker->boolean(),
                        'send_immediately' => $this->faker->boolean(),
                        'communication_method' => $this->faker->randomElement(['email', 'sms', 'whatsapp'])
                    ]
                ]
            ];
        });
    }

    /**
     * Indicate that the batch operation is for PDF generation.
     */
    public function pdfGeneration(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'pdf_generation',
                'metadata' => [
                    'invoice_count' => $attributes['total_count'],
                    'options' => [
                        'language' => $this->faker->randomElement(['en', 'fa']),
                        'create_combined_pdf' => $this->faker->boolean()
                    ]
                ],
                'combined_file_path' => $this->faker->boolean() 
                    ? 'batch_pdfs/combined_' . $this->faker->uuid() . '.pdf' 
                    : null
            ];
        });
    }

    /**
     * Indicate that the batch operation is for communication sending.
     */
    public function communicationSending(): static
    {
        return $this->state(function (array $attributes) {
            $method = $this->faker->randomElement(['email', 'sms', 'whatsapp']);
            
            return [
                'type' => 'communication_sending',
                'metadata' => [
                    'invoice_count' => $attributes['total_count'],
                    'method' => $method,
                    'options' => [
                        'subject' => $method === 'email' ? $this->faker->sentence() : null,
                        'include_pdf' => $this->faker->boolean(),
                        'language' => $this->faker->randomElement(['en', 'fa'])
                    ]
                ]
            ];
        });
    }
}