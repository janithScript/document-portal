<?php
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SignatureController;
use Illuminate\Support\Facades\Route;

// Document management
Route::get('/', [DocumentController::class, 'index'])->name('documents.index');
Route::get('/documents/upload', [DocumentController::class, 'create'])->name('documents.create');
Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
Route::get('/documents/{document}/sign', [DocumentController::class, 'sign'])->name('documents.sign');
Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

// Signature API
Route::post('/documents/{document}/signature', [SignatureController::class, 'store'])->name('signature.store');
Route::delete('/documents/{document}/signature', [SignatureController::class, 'clear'])->name('signature.clear');
