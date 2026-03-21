<?php

namespace App\Events;

use App\Models\MaintenanceRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MaintenanceRequest $maintenanceRequest;

    /**
     * Create a new event instance.
     */
    public function __construct(MaintenanceRequest $maintenanceRequest)
    {
        $this->maintenanceRequest = $maintenanceRequest;
    }
}
