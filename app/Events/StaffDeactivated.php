<?php

namespace Modules\Staff\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Staff\Models\Staff;

class StaffDeactivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Staff $staff,
        public ?string $reason = null
    ) {}
}
