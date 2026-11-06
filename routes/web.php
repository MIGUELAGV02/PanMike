<?php

use App\Http\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;



Route::get('/', [InventoryController::class, 'index'])->name('inventory.index');
Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
