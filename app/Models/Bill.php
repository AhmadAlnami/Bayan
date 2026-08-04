<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bill extends Model
{
    protected $fillable = [
        'user_id', 'name', 'name_en', 'amount', 'category', 'category_en',
        'due_day', 'due_month', 'reminder_days', 'recurrence', 'is_active', 'last_paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_day' => 'integer',
            'due_month' => 'integer',
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
        $today = now();
        $reminder = $this->reminder_days;

        if ($this->recurrence === 'yearly') {
            if (! $this->due_month) {
                return false;
            }
            $dueDate = Carbon::create($today->year, $this->due_month, min($this->due_day, 28));
            if ($today->gt($dueDate)) {
                $dueDate = Carbon::create($today->year + 1, $this->due_month, min($this->due_day, 28));
            }
            $diff = (int) $today->diffInDays($dueDate, false);

            return $diff >= 0 && $diff <= $reminder;
        }

        if ($this->recurrence === 'weekly') {
            $todayDow = (int) $today->dayOfWeek;

            return $todayDow === $this->due_day
                || $todayDow >= ($this->due_day - $reminder + 7) % 7
                || $this->due_day >= 7;
        }

        $todayDay = $today->day;
        $due = $this->due_day;

        if ($due >= $reminder) {
            return $todayDay >= ($due - $reminder) && $todayDay <= $due;
        }

        $daysInMonth = $today->daysInMonth;

        return ($todayDay >= ($daysInMonth + $due - $reminder) && $todayDay <= $daysInMonth)
            || $todayDay <= $due;
    }
}
