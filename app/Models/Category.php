<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'name_en', 'icon', 'color', 'type', 'user_id'];

    /**
     * Scope to the user's own categories, falling back to the shared defaults
     * when the user has not customized their categories yet.
     */
    public function scopeAvailableFor(Builder $query, User $user): Builder
    {
        if (static::where('user_id', $user->id)->exists()) {
            return $query->where('user_id', $user->id);
        }

        return $query->whereNull('user_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
