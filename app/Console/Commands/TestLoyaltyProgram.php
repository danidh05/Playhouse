<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Child;
use App\Models\PlaySession;

class TestLoyaltyProgram extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:loyalty {child_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test loyalty program logic for a specific child or all children';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $childId = $this->argument('child_id');
        
        if ($childId) {
            $children = [Child::find($childId)];
            if (!$children[0]) {
                $this->error("Child with ID {$childId} not found.");
                return;
            }
        } else {
            $children = Child::all();
        }
        
        $this->info("Testing Loyalty Program Logic");
        $this->line("==============================");
        
        foreach ($children as $child) {
            if (!$child) continue;
            
            $this->line("\nChild: {$child->name} (ID: {$child->id})");
            $this->line("----------------------------------------");
            
            // Get all sessions for this child
            $allSessions = PlaySession::where('child_id', $child->id)
                ->orderBy('created_at')
                ->get(['id', 'ended_at', 'discount_pct', 'created_at']);
                
            $totalSessions = $allSessions->count();
            $completedSessions = $allSessions->where('ended_at', '!=', null);
            $paidSessions = $completedSessions->where('discount_pct', '<', 100);
            $freeSessions = $completedSessions->where('discount_pct', '=', 100);
            $incompleteSessions = $allSessions->where('ended_at', '=', null);
            
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Sessions', $totalSessions],
                    ['Completed Sessions', $completedSessions->count()],
                    ['Paid Sessions (discount < 100%)', $paidSessions->count()],
                    ['Free Sessions (discount = 100%)', $freeSessions->count()],
                    ['Incomplete Sessions', $incompleteSessions->count()],
                ]
            );
            
            // Test loyalty logic (matching controller logic)
            $paidSessionsCount = $paidSessions->count();
            $pendingFreeSession = $allSessions->where('ended_at', '=', null)->where('discount_pct', '=', 100)->count() > 0;
            $isFreeSession = ($paidSessionsCount > 0) && ($paidSessionsCount % 5 === 0) && !$pendingFreeSession;
            $nextSessionNumber = $paidSessionsCount + 1;
            
            $this->line("Loyalty Program Analysis:");
            $this->line("- Paid Sessions Count: {$paidSessionsCount}");
            $this->line("- Next Session Number: {$nextSessionNumber}");
            $this->line("- Modulo Result: " . ($paidSessionsCount % 5));
            $this->line("- Has Pending Free Session: " . ($pendingFreeSession ? 'YES' : 'NO'));
            $this->line("- Should Next Session Be Free: " . ($isFreeSession ? 'YES' : 'NO'));
            
            if ($isFreeSession) {
                $this->info("🎉 The next session for {$child->name} should be FREE!");
            } else {
                $remainingForFree = 5 - ($paidSessionsCount % 5);
                $this->line("📊 {$remainingForFree} more paid sessions needed for a free session.");
            }
            
            // Show recent sessions
            if ($allSessions->count() > 0) {
                $this->line("\nRecent Sessions:");
                $recentSessions = $allSessions->take(-5); // Last 5 sessions
                $sessionData = [];
                foreach ($recentSessions as $session) {
                    $sessionData[] = [
                        $session->id,
                        $session->ended_at ? $session->ended_at->format('M d, H:i') : 'Incomplete',
                        $session->discount_pct . '%',
                        $session->discount_pct == 100 ? 'FREE' : 'PAID'
                    ];
                }
                $this->table(['Session ID', 'Ended At', 'Discount', 'Type'], $sessionData);
            }
        }
    }
}
