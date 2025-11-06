<?php

namespace App\Observers;

use App\Models\Penalty;
use App\Models\PenaltyHistory;
use Illuminate\Support\Facades\Auth;

class PenaltyObserver
{
    public function created(Penalty $penalty)
    {
        PenaltyHistory::create([
            'penalty_id' => $penalty->id,
            'data' => $penalty->toArray(),
            'modified_by' => $penalty->created_by,
            'action' => 'created',
        ]);
    }

    public function updated(Penalty $penalty)
    {
        PenaltyHistory::create([
            'penalty_id' => $penalty->id,
            'data' => $penalty->getChanges(),
            'modified_by' => Auth::id(),
            'action' => 'updated',
        ]);
    }

    public function deleted(Penalty $penalty)
    {
        PenaltyHistory::create([
            'penalty_id' => $penalty->id,
            'data' => $penalty->toArray(),
            'modified_by' => Auth::id(),
            'action' => 'deleted',
        ]);
    }
}
