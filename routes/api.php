<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Services\ApiRouteRegistrar;
use Modules\Staff\Http\Controllers\Api\StaffController;
use Modules\Staff\Http\Controllers\Api\StaffCredentialController;

ApiRouteRegistrar::register(routes: function () {
    Route::apiResource('staff', StaffController::class)->only(['index', 'show', 'store', 'update']);

    /*
     | Credentials are nested because StaffCredential has no policy of its own and
     | no branch column. Both authorization and branch isolation are inherited from
     | the parent staff record.
     */
    Route::get('staff/{staff}/credentials', [StaffCredentialController::class, 'index'])
        ->name('staff.credentials.index');
    Route::post('staff/{staff}/credentials', [StaffCredentialController::class, 'store'])
        ->name('staff.credentials.store');
    Route::put('staff/{staff}/credentials/{credential}', [StaffCredentialController::class, 'update'])
        ->name('staff.credentials.update');
});
