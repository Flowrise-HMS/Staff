<?php

namespace Modules\Staff\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Core\Settings\FeatureSettings;
use Modules\Staff\Models\Staff;

/**
 * Prints several staff ID cards on one sheet. Records the user may not view are skipped.
 */
class StaffIdCardsBulkController extends Controller
{
    public const MAX_CARDS = 100;

    /**
     * @param  array<int, int|string>  $ids
     */
    public static function urlFor(array $ids): string
    {
        return route('staff.id-cards.bulk', ['ids' => array_values($ids)]);
    }

    public function __invoke(Request $request): View
    {
        abort_unless(app(FeatureSettings::class)->staff_id_card_enabled, 404);
        abort_unless($request->user()?->can('print_staff_id'), 403);

        $ids = array_values(array_unique(array_filter((array) $request->query('ids', []), 'is_scalar')));

        abort_if($ids === [], 404);
        abort_if(count($ids) > self::MAX_CARDS, 422, __('Print at most :max cards at a time.', ['max' => self::MAX_CARDS]));

        $order = array_flip(array_map('strval', $ids));

        $cards = Staff::query()
            ->whereKey($ids)
            ->with([
                'user',
                // Department::$branch is an accessor (via its primary location), not a relation.
                'staffDepartments' => fn ($query) => $query->where('is_primary', true)->with('department'),
                'validCredentials',
            ])
            ->get()
            ->filter(fn (Staff $staff): bool => Gate::allows('view', $staff))
            ->sortBy(fn (Staff $staff): int => $order[(string) $staff->getKey()] ?? PHP_INT_MAX)
            ->map(fn (Staff $staff): array => [
                'staff' => $staff,
                'primaryStaffDepartment' => $staff->staffDepartments->first(),
                'credential' => $staff->validCredentials->first(),
            ])
            ->values()
            ->all();

        abort_if($cards === [], 403);

        return view('staff::print.id-cards-bulk', ['cards' => $cards]);
    }
}
