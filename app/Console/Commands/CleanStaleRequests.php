<?php

namespace App\Console\Commands;

use App\Models\DetailRequest;
use App\Models\RequestPurchase;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('procurement:clean-stale')]
#[Description('Hapus item detail request berumur lebih dari 30 hari yang belum selesai, beserta request purchase kosong.')]
class CleanStaleRequests extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cutoffDate = now()->subDays(30);

        // 1. Delete detail requests where status is NOT 2 (done) and created_at is older than 30 days
        $deletedDetailsCount = DetailRequest::where('status', '!=', '2')
            ->where(function ($q) use ($cutoffDate) {
                $q->where('created_at', '<', $cutoffDate)
                  ->orWhereNull('created_at');
            })
            ->delete();

        // 2. Delete parent RequestPurchases that are older than 30 days and have no detail requests left
        $staleRequests = RequestPurchase::where('date', '<', $cutoffDate->toDateString())
            ->whereDoesntHave('detailRequests')
            ->get();

        $deletedRequestsCount = 0;
        foreach ($staleRequests as $rp) {
            $rp->delete();
            $deletedRequestsCount++;
        }

        $this->info("Stale requests cleanup completed successfully.");
        $this->info("Deleted {$deletedDetailsCount} detail request items.");
        $this->info("Deleted {$deletedRequestsCount} empty parent request purchases.");
    }
}
