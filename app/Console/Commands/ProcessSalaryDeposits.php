<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\SalarySetting;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessSalaryDeposits extends Command
{
    protected $signature = 'bayan:process-salaries';

    protected $description = 'Process automatic salary deposits for users';

    public function handle(): void
    {
        $today = Carbon::today();
        $actualDay = $today->day;

        $settings = SalarySetting::with('user')
            ->where('is_active', true)
            ->where('day_of_month', $actualDay)
            ->get();

        if ($settings->isEmpty()) {
            $this->info('No salary deposits to process today.');
            return;
        }

        $salaryCategory = Category::where('type', 'income')
            ->where('name', 'راتب')
            ->whereNull('user_id')
            ->first();

        foreach ($settings as $setting) {
            $depositDate = $this->adjustForWeekend($today, $actualDay);

            Transaction::create([
                'user_id' => $setting->user_id,
                'category_id' => $salaryCategory?->id,
                'type' => 'income',
                'amount' => $setting->amount,
                'description' => 'راتب شهري',
                'transaction_date' => $depositDate,
            ]);

            $this->info("Salary deposited for user {$setting->user_id}: {$setting->amount} SAR on {$depositDate->format('Y-m-d')}");
        }
    }

    private function adjustForWeekend(Carbon $date, int $expectedDay): Carbon
    {
        $dayOfWeek = $date->dayOfWeek;

        if ($dayOfWeek === Carbon::FRIDAY) {
            return $date->copy()->subDay();
        }

        if ($dayOfWeek === Carbon::SATURDAY) {
            return $date->copy()->addDay();
        }

        return $date;
    }
}
