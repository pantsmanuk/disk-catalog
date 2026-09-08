<?php

use App\Http\Controllers\DiskController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/disks');
Route::get('/inventory/export', [DiskController::class, 'export'])->name('inventory.export');
Route::resource('disks', DiskController::class);
