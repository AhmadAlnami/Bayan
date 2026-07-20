<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsGoal extends Model
{
    protected $fillable = [
        'user_id', 'name', 'name_en', 'target_amount', 'current_amount',
        'category', 'category_en', 'deadline', 'auto_save_amount',
        'auto_save_day', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'auto_save_amount' => 'decimal:2',
            'deadline' => 'date',
            'is_active' => 'boolean',
            'auto_save_day' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(SavingsDeposit::class);
    }

    public function getProgressAttribute(): float
    {
        return $this->target_amount > 0
            ? min(round(($this->current_amount / $this->target_amount) * 100, 1), 100)
            : 0;
    }

    public function getRemainingAttribute(): float
    {
        return max($this->target_amount - $this->current_amount, 0);
    }
}
