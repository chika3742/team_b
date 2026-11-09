<?php

namespace Database\Factories;

use App\Enums\MaterialType;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'title' => fake()->sentence(3),
            'image_url' => null,
            'material_type' => MaterialType::Book,
            'author' => fake()->name(),
            'publisher' => fake()->company(),
            'isbn' => fake()->isbn13(),
        ];
    }
}
