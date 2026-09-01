<?php

namespace Modules\Staff\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\ApiController;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Staff\Classes\Services\StaffService;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;
use Modules\Staff\Http\Requests\Api\StaffApiRequest;
use Modules\Staff\Http\Resources\StaffTransformer;
use Modules\Staff\Models\Staff;

/**
 * @group Staff
 *
 * The staff directory.
 *
 * Staff extends BaseModel and its branch global scope is active, so route-model
 * binding already resolves only records in the caller's branch — a staff id from
 * another branch 404s without an explicit check.
 */
class StaffController extends ApiController
{
    public function __construct(protected StaffService $staffService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Staff::class);

        return ApiResponse::paginated(
            Staff::query()
                ->when($request->filled('staff_type'), function ($query) use ($request) {
                    $type = enum_try_from(StaffType::class, $request->string('staff_type')->toString());

                    return $type ? $query->where('staff_type', $type) : $query;
                })
                ->when($request->filled('employment_status'), function ($query) use ($request) {
                    $status = enum_try_from(EmploymentStatus::class, $request->string('employment_status')->toString());

                    return $status ? $query->where('employment_status', $status) : $query;
                })
                ->orderBy('last_name')
                ->orderBy('first_name'),
            StaffTransformer::class,
            (int) $request->integer('per_page', 20),
        );
    }

    public function show(Staff $staff): JsonResponse
    {
        $this->authorizeApi('view', $staff);

        return ApiResponse::ok(new StaffTransformer($staff));
    }

    public function store(StaffApiRequest $request): JsonResponse
    {
        $this->authorizeApi('create', Staff::class);

        $staff = $this->staffService->create($this->attributes($request));

        return ApiResponse::created(new StaffTransformer($staff));
    }

    public function update(StaffApiRequest $request, Staff $staff): JsonResponse
    {
        $this->authorizeApi('update', $staff);

        $updated = $this->staffService->update($staff, $this->attributes($request));

        return ApiResponse::ok(new StaffTransformer($updated));
    }

    /**
     * `department_ids` and `specialty_ids` are validated by the shared FormRequest
     * because the panel uses them to sync pivots, but StaffService::create/update
     * pass straight to mass assignment and neither key is fillable. Dropping them
     * here keeps the discard explicit rather than silent.
     *
     * @return array<string, mixed>
     */
    private function attributes(StaffApiRequest $request): array
    {
        return collect($request->validated())
            ->except(['department_ids', 'specialty_ids'])
            ->all();
    }
}
