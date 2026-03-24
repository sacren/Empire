<?php

namespace App\Observers;

use App\Enums\ActivityAction;
use App\Enums\ActivityType;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Notifications\ProspectAssigned;

class ProspectObserver
{
    /**
     * Handle the Prospect "updated" event.
     */
    public function updated(Prospect $prospect): void
    {
        if ($prospect->wasChanged('status')) {
            ProspectActivity::create([
                'prospect_id' => $prospect->id,
                'type' => ActivityType::Automatic,
                'action' => ActivityAction::StatusChange,
                'from_value' => $prospect->getOriginal('status'),
                'to_value' => $prospect->status->value,
                'performed_by' => auth()->id(),
            ]);
        }

        if ($prospect->wasChanged('assigned_to')) {
            ProspectActivity::create([
                'prospect_id' => $prospect->id,
                'type' => ActivityType::Automatic,
                'action' => ActivityAction::AssignmentChange,
                'from_value' => $prospect->getOriginal('assigned_to'),
                'to_value' => $prospect->assigned_to,
                'performed_by' => auth()->id(),
            ]);

            if ($prospect->assigned_to) {
                $prospect->assignedTo->notify(new ProspectAssigned($prospect));
            }
        }
    }
}
