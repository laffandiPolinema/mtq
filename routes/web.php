<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminParticipantController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationDocumentController;
use App\Http\Controllers\RegistrationPaymentController;
use App\Http\Middleware\EnsureParticipantAccess;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegistrationController::class, 'create'])->name('home');
Route::post('/pendaftaran', [RegistrationController::class, 'store'])->middleware('throttle:10,1')->name('registration.store');
Route::get('/status', [RegistrationController::class, 'statusForm'])->name('status.form');
Route::post('/status', [RegistrationController::class, 'lookupStatus'])->middleware('throttle:10,1')->name('status.lookup');

Route::middleware(EnsureParticipantAccess::class)->group(function () {
    Route::get('/pendaftaran/berhasil/{code}', [RegistrationController::class, 'success'])->name('registration.success');
    Route::get('/status/{code}', [RegistrationController::class, 'status'])->name('status.show');
    Route::get('/status/{code}/bukti-pendaftaran', [RegistrationController::class, 'receipt'])->name('registration.receipt');
    Route::post('/status/{code}/pembayaran', [RegistrationPaymentController::class, 'store'])->middleware('throttle:10,1')->name('registration.payment.store');
    Route::post('/status/{code}/dokumen', [RegistrationDocumentController::class, 'update'])->middleware('throttle:10,1')->name('registration.documents.update');
});

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('/admin/login', [AdminAuthController::class, 'store'])->middleware('throttle:5,1')->name('admin.login');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
    Route::get('/peserta', [AdminParticipantController::class, 'index'])->name('participants.index');
    Route::get('/peserta/laporan', [AdminParticipantController::class, 'export'])->name('participants.export');
    Route::get('/peserta/{participant}', [AdminParticipantController::class, 'show'])->name('participants.show');
    Route::delete('/peserta/{participant}', [AdminParticipantController::class, 'destroy'])->name('participants.destroy');
    Route::patch('/peserta/{participant}/status', [AdminParticipantController::class, 'updateStatus'])->name('participants.status');
    Route::patch('/peserta/{participant}/pembayaran', [AdminParticipantController::class, 'updatePayment'])->name('participants.payment');
    Route::get('/peserta/{participant}/bukti-pendaftaran', [AdminParticipantController::class, 'receipt'])->name('participants.receipt');
    Route::get('/peserta/{participant}/dokumen/{document}', [AdminParticipantController::class, 'document'])
        ->whereIn('document', ['identitas', 'pasfoto', 'bukti-pembayaran'])
        ->name('participants.document');
});
