<?php

namespace App\Console\Commands;

use App\Models\SavingsGoal;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('bayan:process-auto-saves')]
#[Description('Process monthly auto-save deposits for savings goals')]
class ProcessAutoSaves extends Command
{
    public function handle(): void
    {
        $today = Carbon::today();
        $day = (int) $today->day;

        $goals = SavingsGoal::where('is_active', true)
            ->whereNotNull('auto_save_amount')
            ->where('auto_save_amount', '>', 0)
            ->where('auto_save_day', $day)
            ->get();

        foreach ($goals as $goal) {
            $goal->deposits()->create([
                'user_id' => $goal->user_id,
                'amount' => $goal->auto_save_amount,
                'source' => 'auto',
                'note' => 'ادخار تلقائي شهري',
            ]);

            $goal->increment('current_amount', $goal->auto_save_amount);
        }

        $this->info('Processed '.$goals->count().' auto-save goals.');
    }
}
