<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/tasks/report',        [TaskController::class, 'report']);
Route::post('/tasks',              [TaskController::class, 'store']);
Route::get('/tasks',               [TaskController::class, 'index']);
Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
Route::delete('/tasks/{id}',       [TaskController::class, 'destroy']);
