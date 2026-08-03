<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    protected $fillable = [
        'user_id', 'name', 'name_en', 'amount', 'category', 'category_en',
        'due_day', 'reminder_days', 'recurrence', 'is_active', 'last_paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_day' => 'integer',
            'reminder_days' => 'integer',
            'is_active' => 'boolean',
            'last_paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDueSoon(): bool
    {
        $today = now()->day;
        $due = $this->due_day;
        $reminder = $this->reminder_days;

        if ($due >= $reminder) {
            return $today >= ($due - $reminder) && $today <= $due;
        }

        $daysInMonth = now()->daysInMonth;

        return $today >= ($daysInMonth + $due - $reminder) && $today <= $daysInMonth
            || $today <= $due;
    }
}
