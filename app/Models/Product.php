<?php

namespace App\Models;

use App\Traits\ManagesStorage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Product model with image storage, category relation, and soft deletes.
 *
 * @property int|string|null $created_by
 * @property string $description_text
 */
class Product extends Model
{
    use ManagesStorage, SoftDeletes;

    protected $fillable = ['name', 'price', 'is_active', 'image', 'description', 'category_id', 'stock_quantity',
        'low_stock_threshold', ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'stock_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            // Auth ids are integers in this application, per the users table.
            // Record the acting admin, only when someone is actually authenticated.
            $product->created_by = $product->created_by ?? (filled($authId = Auth::id()) ? (int) $authId : null);
        });
    }

    /** @return Attribute<string|null, never> */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->getImageUrl($this->image, asset('images/no_images.jpeg')),
        );
    }

    /** @return Attribute<string, never> */
    protected function descriptionText(): Attribute
    {
        return Attribute::make(get: fn (): string => html_entity_decode(strip_tags((string) $this->description)));
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Normalize imported image paths for storage.
    public static function normalizeImagePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        // Extract the storage-relative path from supported absolute URLs.
        if (Str::startsWith($path, ['http://', 'https://']) && str_contains($path, '/storage/')) {
            $path = Str::after($path, '/storage/');
        }

        $path = trim(str_replace('\\', '/', $path), " \t\n\r\0\x0B/");

        // Reject path traversal and absolute paths.
        if (str_contains($path, '..') || str_contains($path, '://')) {
            return null;
        }

        // Only strip storage/ prefix (public/ also stripped for consistency)
        if (Str::startsWith($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (Str::startsWith($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        if ($path === '') {
            return null;
        }

        return str_contains($path, '/') ? $path : 'products/'.$path;
    }
}
