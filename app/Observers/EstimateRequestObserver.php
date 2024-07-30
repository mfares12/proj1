<?php

namespace App\Observers;

use App\Events\EstimateRequestRejectedEvent;
use App\Models\EstimateRequest;

class EstimateRequestObserver
{

    /**
     * Handle the EstimateRequest "created" event.
     */
    public function created(EstimateRequest $estimateRequest): void
    {
        //
    }

    /**
     * Handle the EstimateRequest "updated" event.
     */
    public function updated(EstimateRequest $estimateRequest): void
    {
        if (!isRunningInConsoleOrSeeding()) {
            if ($estimateRequest->status == 'rejected') {
                info('rejected');
                event(new EstimateRequestRejectedEvent($estimateRequest));
            }
        }
    }

    /**
     * Handle the EstimateRequest "deleted" event.
     */
    public function deleted(EstimateRequest $estimateRequest): void
    {
        //
    }

    /**
     * Handle the EstimateRequest "restored" event.
     */
    public function restored(EstimateRequest $estimateRequest): void
    {
        //
    }

    /**
     * Handle the EstimateRequest "force deleted" event.
     */
    public function forceDeleted(EstimateRequest $estimateRequest): void
    {
        //
    }

}
