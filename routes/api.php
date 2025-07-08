<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentVectorController;

// Document Vector API Routes
Route::prefix('documents')->group(function () {
    Route::get('/', [DocumentVectorController::class, 'index']);
    Route::post('/', [DocumentVectorController::class, 'store']);
    Route::get('/stats', [DocumentVectorController::class, 'stats']);
    Route::post('/search', [DocumentVectorController::class, 'search']);
    Route::post('/search-mongo', [DocumentVectorController::class, 'searchMongo']);
    Route::post('/embedding', [DocumentVectorController::class, 'generateEmbedding']);
    Route::get('/{documentId}', [DocumentVectorController::class, 'show']);
    Route::put('/{documentId}', [DocumentVectorController::class, 'update']);
    Route::delete('/{documentId}', [DocumentVectorController::class, 'destroy']);
});
