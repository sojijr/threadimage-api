<?php

use App\Http\Controllers\ThreadImageController;
use App\Http\Controllers\ThreadProfileController;
use Illuminate\Support\Facades\Route;

Route::post('/threads-post', [ThreadImageController::class, 'index'])->name('threads.process');
Route::get('/media/{type}/{shortId}', [ThreadImageController::class, 'serveMedia'])->name('media.serve');

Route::post('/threads-profile', [ThreadProfileController::class, 'index'])->name('threads.profile');
