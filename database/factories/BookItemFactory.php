<?php

namespace Database\Factories;

use App\Enums\BookItemStatus;
use App\Models\Book;
use App\Models\BookItem;
use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookItem>
 */
class BookItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'owner_facility_id' => Facility::factory(),
            'current_facility_id' => fn (array $attributes) => $attributes['owner_facility_id'],
            'item_no' => fake()->unique()->numerify('########'),
            'status' => BookItemStatus::Available,
        ];
    }
}
