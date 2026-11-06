<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

/**
 * Category model for product categorization, active status, and product relationships.
 *
 * @property int|string|null $created_by
 */
class Category extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            // Record the acting admin, only when someone is actually authenticated.
            $category->created_by = $category->created_by ?? (filled($authId = Auth::id()) ? (int) $authId : null);
        });
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
