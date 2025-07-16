<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatbotController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/debug/rag-sources', [ChatbotController::class, 'debugRagSources']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Chatbot routes (require authentication)
    Route::get('/chatbot', [ChatbotController::class, 'index'])->name('chatbot');
    Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask');
    Route::post('/chatbot/upload', [ChatbotController::class, 'upload'])->name('chatbot.upload');
    Route::post('/chatbot/stream', [ChatbotController::class, 'streamLLM'])->name('chatbot.stream');
    Route::get('/chatbot/streaming-enabled', [ChatbotController::class, 'streamingEnabled'])->name('chatbot.streaming-enabled');
    Route::match(['get', 'post'], '/chatbot/processing-status', [ChatbotController::class, 'processingStatus']);
    Route::get('/chatbot/rag-info', [ChatbotController::class, 'getRagInfo'])->name('chatbot.rag-info');
    Route::get('/chatbot/available-files', [ChatbotController::class, 'getAvailableFiles'])->name('chatbot.available-files');
    Route::get('/chatbot/files', [ChatbotController::class, 'fileGallery'])->name('chatbot.files');
    Route::post('/chatbot/file-chunks', [ChatbotController::class, 'getFileChunks'])->name('chatbot.file-chunks');
    Route::post('/chatbot/debug-rag', [ChatbotController::class, 'debugRagSearch'])->name('chatbot.debug-rag');
    
    // File management routes (now handled by ChatbotController)
    Route::post('/chatbot/files/update-sharing', [ChatbotController::class, 'updateFileSharing'])->name('chatbot.files.update-sharing');
    Route::delete('/chatbot/files/delete', [ChatbotController::class, 'deleteFile'])->name('chatbot.files.delete');
    Route::get('/chatbot/files/details', [ChatbotController::class, 'getFileDetails'])->name('chatbot.files.details');
    Route::get('/chatbot/files/users', [ChatbotController::class, 'getUsers'])->name('chatbot.files.users');
});

require __DIR__.'/auth.php';
