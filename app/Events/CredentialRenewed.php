<?php

namespace Modules\Staff\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Staff\Models\StaffCredential;

class CredentialRenewed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public StaffCredential $credential,
        public \DateTimeInterface $previousExpiryDate,
        public \DateTimeInterface $newExpiryDate
    ) {}
}
