<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Staff\Models\Staff;

class StaffIdCardController extends Controller
{
    public function __invoke(Staff $staff): View
    {
        Gate::authorize('view', $staff);

        abort_unless(request()->user()?->can('print_staff_id'), 403);

        $staff->loadMissing([
            'user',
            'departments',
        ]);

        $primaryPivot = $staff->staffDepartments()
            ->where('is_primary', true)
            ->with(['department'])
            ->first();

        $credential = $staff->validCredentials()->first();

        return view('staff::print.id-card', [
            'staff' => $staff,
            'primaryStaffDepartment' => $primaryPivot,
            'credential' => $credential,
        ]);
    }
}
