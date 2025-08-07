<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CategoryImage>
 */
class CategoryImageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CategoryImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'image_path' => 'categories/' . $this->faker->uuid() . '.webp',
            'alt_text' => $this->faker->sentence(3),
            'alt_text_persian' => 'متن جایگزین تصویر',
            'is_primary' => false,
            'sort_order' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Indicate that the image is primary.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the image is not primary.
     */
    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => false,
        ]);
    }

    /**
     * Set a specific sort order.
     */
    public function sortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }

    /**
     * Set specific alt text.
     */
    public function altText(string $english, string $persian = null): static
    {
        return $this->state(fn (array $attributes) => [
            'alt_text' => $english,
            'alt_text_persian' => $persian,
        ]);
    }

    /**
     * Set a specific image path.
     */
    public function imagePath(string $path): static
    {
        return $this->state(fn (array $attributes) => [
            'image_path' => $path,
        ]);
    }
}