<?php

namespace Modules\Staff\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\Api\ApiController;
use Modules\Core\Http\Responses\ApiResponse;
use Modules\Staff\Classes\Services\StaffCredentialService;
use Modules\Staff\Http\Requests\Api\StaffCredentialApiRequest;
use Modules\Staff\Http\Resources\StaffCredentialTransformer;
use Modules\Staff\Models\Staff;
use Modules\Staff\Models\StaffCredential;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group Staff
 *
 * Credentials nested under a staff member.
 *
 * StaffCredential has no policy class of its own. Authorising against it directly
 * would hand every non-super-admin a 403, because Gate::authorize() throws when no
 * policy is registered. Every action here therefore authorises against the parent
 * Staff record instead.
 *
 * StaffCredential is also a plain Model with no `branch_id` column, so it carries no
 * branch global scope. Branch isolation comes entirely from resolving the parent
 * Staff first — that binding is branch-scoped — and constraining the child to it.
 */
class StaffCredentialController extends ApiController
{
    public function __construct(protected StaffCredentialService $credentialService) {}

    public function index(Request $request, Staff $staff): JsonResponse
    {
        $this->authorizeApi('view', $staff);

        return ApiResponse::paginated(
            $staff->credentials()->getQuery()->orderByDesc('expiry_date'),
            StaffCredentialTransformer::class,
            (int) $request->integer('per_page', 20),
        );
    }

    public function store(StaffCredentialApiRequest $request, Staff $staff): JsonResponse
    {
        $this->authorizeApi('update', $staff);

        $credential = $this->credentialService->create($staff, $request->attributesForStaff());

        return ApiResponse::created(new StaffCredentialTransformer($credential));
    }

    public function update(StaffCredentialApiRequest $request, Staff $staff, string $credential): JsonResponse
    {
        $this->authorizeApi('update', $staff);

        $model = $this->resolveCredential($staff, $credential);

        $updated = $this->credentialService->update($model, $request->attributesForStaff());

        return ApiResponse::ok(new StaffCredentialTransformer($updated));
    }

    /**
     * Resolve the credential through the parent relation rather than by id alone.
     *
     * A bare `StaffCredential::findOrFail($id)` would return credentials belonging
     * to any staff member in any branch.
     */
    private function resolveCredential(Staff $staff, string $credentialId): StaffCredential
    {
        $credential = $staff->credentials()->whereKey($credentialId)->first();

        if (! $credential) {
            throw new NotFoundHttpException;
        }

        return $credential;
    }
}
