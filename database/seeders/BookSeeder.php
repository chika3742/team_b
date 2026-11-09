<?php

namespace Database\Seeders;

use App\Enums\BookItemStatus;
use App\Enums\MaterialType;
use App\Models\Book;
use App\Models\BookItem;
use App\Models\Category;
use App\Models\Facility;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Seed categories, books, and their physical copies.
     *
     * Requires FacilitySeeder to run first, because each copy belongs to a facility.
     */
    public function run(): void
    {
        $facilities = Facility::all();

        $literature = Category::factory()->create(['parent_id' => null, 'name' => '文学']);
        $novel = Category::factory()->create(['parent_id' => $literature->id, 'name' => '小説']);
        $poetry = Category::factory()->create(['parent_id' => $literature->id, 'name' => '詩歌']);
        $science = Category::factory()->create(['parent_id' => null, 'name' => '自然科学']);
        $astronomy = Category::factory()->create(['parent_id' => $science->id, 'name' => '天文学']);
        $biology = Category::factory()->create(['parent_id' => $science->id, 'name' => '生物学']);
        $magazine = Category::factory()->create(['parent_id' => null, 'name' => '雑誌']);

        $books = [
            [$novel, '吾輩は猫である', '夏目漱石', MaterialType::Book],
            [$novel, '羅生門', '芥川龍之介', MaterialType::Book],
            [$novel, '銀河鉄道の夜', '宮沢賢治', MaterialType::Book],
            [$novel, '走れメロス', '太宰治', MaterialType::Book],
            [$poetry, '一握の砂', '石川啄木', MaterialType::Book],
            [$poetry, '春と修羅', '宮沢賢治', MaterialType::Book],
            [$astronomy, 'はじめての星空観察', '星野光', MaterialType::Book],
            [$biology, '身近な昆虫図鑑', '森葉子', MaterialType::Book],
            [$magazine, '月刊星空ガイド 2026年11月号', '星空ガイド編集部', MaterialType::Magazine],
        ];

        $itemCount = 0;

        foreach ($books as [$category, $title, $author, $materialType]) {
            $book = Book::factory()->create([
                'category_id' => $category->id,
                'title' => $title,
                'image_url' => null,
                'material_type' => $materialType,
                'author' => $author,
                'publisher' => fake()->randomElement(['架空書房', 'サンプル出版', '見本社']),
                'isbn' => $materialType === MaterialType::Book ? fake()->unique()->isbn13() : null,
            ]);

            foreach ($facilities->random(fake()->numberBetween(1, $facilities->count())) as $facility) {
                BookItem::factory()->create([
                    'book_id' => $book->id,
                    'owner_facility_id' => $facility->id,
                    'current_facility_id' => $facility->id,
                    'item_no' => sprintf('%08d', ++$itemCount),
                    'status' => BookItemStatus::Available,
                ]);
            }
        }
    }
}
