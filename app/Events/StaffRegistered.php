<?php

namespace Modules\Staff\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Staff\Models\Staff;

class StaffRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Staff $staff,
        public ?int $registeredBy = null
    ) {}
}
