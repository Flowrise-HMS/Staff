<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Staff\Models\Staff;

class StaffIdCardController extends Controller
{
    public function __invoke(Staff $staff): View
    {
        $this->authorize('view', $staff);

        abort_unless(request()->user()?->can('print_staff_id'), 403);

        $staff->loadMissing([
            'user',
            'departments.branch',
        ]);

        $primaryPivot = $staff->staffDepartments()
            ->where('is_primary', true)
            ->with(['department.branch'])
            ->first();

        $credential = $staff->validCredentials()->first();

        return view('staff::print.id-card', [
            'staff' => $staff,
            'primaryStaffDepartment' => $primaryPivot,
            'credential' => $credential,
        ]);
    }
}
