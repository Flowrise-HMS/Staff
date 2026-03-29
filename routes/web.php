<?php

use Illuminate\Support\Facades\Route;
use Modules\Staff\Http\Controllers\StaffController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('staff', StaffController::class)->names('staff');
});
