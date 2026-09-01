<?php

namespace Modules\Staff\Http\Requests\Api;

use Modules\Core\Http\Requests\Concerns\InjectsCurrentBranch;
use Modules\Staff\Http\Requests\StaffRequest;

/**
 * API flavour of {@see StaffRequest}.
 *
 * Identical validation, except `branch_id` is taken from the authenticated
 * caller's branch context rather than the request body.
 */
class StaffApiRequest extends StaffRequest
{
    use InjectsCurrentBranch;
}
