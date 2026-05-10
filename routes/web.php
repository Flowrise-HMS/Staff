<?php

use Illuminate\Support\Facades\Route;
use Modules\Staff\Http\Controllers\StaffController;
use Modules\Staff\Http\Controllers\StaffIdCardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('staff/{staff}/id-card', StaffIdCardController::class)
        ->name('staff.id-card');

    Route::resource('staff', StaffController::class)->names('staff');
});
