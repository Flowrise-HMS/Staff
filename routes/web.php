<?php

use Illuminate\Support\Facades\Route;
use Modules\Staff\Http\Controllers\StaffIdCardController;
use Modules\Staff\Http\Controllers\StaffIdCardsBulkController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('staff/id-cards', StaffIdCardsBulkController::class)
        ->name('staff.id-cards.bulk');
    Route::get('staff/{staff}/id-card', StaffIdCardController::class)
        ->name('staff.id-card');

});
