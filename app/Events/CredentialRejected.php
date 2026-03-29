<?php

namespace Modules\Staff\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Staff\Models\StaffCredential;

class CredentialRejected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public StaffCredential $credential,
        public int $rejectedBy,
        public string $reason
    ) {}
}
