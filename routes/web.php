<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;
use App\Events\FileProcessed;
use App\Events\FileFailed;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/chatbot', [ChatbotController::class, 'index']);
Route::get('/chatbot/ask', [ChatbotController::class, 'ask']);
Route::post('/chatbot/ask', [ChatbotController::class, 'ask']);

// File upload for chatbot
Route::post('/chatbot/upload', [App\Http\Controllers\ChatbotController::class, 'upload']);

// Stream LLM output as tokens arrive
Route::post('/chatbot/stream', [App\Http\Controllers\ChatbotController::class, 'streamLLM']);

// Endpoint for frontend to check if streaming is enabled
Route::get('/chatbot/streaming-enabled', [App\Http\Controllers\ChatbotController::class, 'streamingEnabled']);

// Endpoint for checking file processing status (polling)
Route::post('/chatbot/processing-status', [App\Http\Controllers\ChatbotController::class, 'processingStatus']);

// Endpoint for getting RAG information
Route::get('/chatbot/rag-info', [App\Http\Controllers\ChatbotController::class, 'getRagInfo']);

// Endpoint for getting available files for selection
Route::get('/chatbot/available-files', [App\Http\Controllers\ChatbotController::class, 'getAvailableFiles']);

// File gallery routes
Route::get('/chatbot/files', [App\Http\Controllers\ChatbotController::class, 'fileGallery']);
Route::post('/chatbot/file-chunks', [App\Http\Controllers\ChatbotController::class, 'getFileChunks']);

// Debug RAG search
Route::post('/chatbot/debug-rag', [App\Http\Controllers\ChatbotController::class, 'debugRagSearch']);

Route::get('/test-broadcast', function () {
    event(new FileProcessed('test.txt'));
    return 'Sent';
});
Route::get('/test-broadcast-fail', function () {
    event(new FileFailed('test.txt', 'This is a test error'));
    return 'Sent';
});