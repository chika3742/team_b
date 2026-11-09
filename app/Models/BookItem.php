<?php

namespace App\Models;

use App\Enums\BookItemStatus;
use Database\Factories\BookItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $book_id
 * @property int $owner_facility_id
 * @property int $current_facility_id
 * @property string $item_no
 * @property BookItemStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['book_id', 'owner_facility_id', 'current_facility_id', 'item_no', 'status'])]
class BookItem extends Model
{
    /** @use HasFactory<BookItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BookItemStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * @return BelongsTo<Facility, $this>
     */
    public function ownerFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * @return BelongsTo<Facility, $this>
     */
    public function currentFacility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
