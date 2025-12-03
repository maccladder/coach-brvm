<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\ClientBocController;
use App\Http\Controllers\ClientFinancialController;

Route::get('/', fn() => redirect('/welcome'));

Route::get('/uploads', [UploadController::class, 'index'])->name('uploads.index');
Route::post('/uploads/analysis', [UploadController::class, 'storeAnalysis'])->name('uploads.analysis.store');
Route::post('/uploads/statement', [UploadController::class, 'storeStatement'])->name('uploads.statement.store');

Route::get('/summaries/today', [SummaryController::class,'showToday'])->name('summaries.today');

Route::get('/summaries', [SummaryController::class, 'index'])->name('summaries.index');

Route::get('/summaries/generate', [SummaryController::class, 'generateForm'])->name('summaries.generate.form');
Route::post('/summaries/generate', [SummaryController::class, 'generateForDate'])->name('summaries.generate');

/* 👇 NOUVELLE ROUTE : afficher un résumé pour une date précise
   (la placer APRÈS /summaries et /summaries/generate pour éviter les collisions) */
Route::get('/summaries/{date}', [SummaryController::class, 'showDate'])->name('summaries.show');

Route::get('/summaries/{summary}/audio', [SummaryController::class, 'audio'])
    ->name('summaries.audio');



Route::get('/client-bocs',        [ClientBocController::class, 'index'])->name('client-bocs.index');
Route::get('/client-bocs/create', [ClientBocController::class, 'create'])->name('client-bocs.create');
Route::post('/client-bocs',       [ClientBocController::class, 'store'])->name('client-bocs.store');
Route::get('/client-bocs/{clientBoc}', [ClientBocController::class, 'show'])->name('client-bocs.show');

Route::match(['GET', 'POST'], 'client-bocs/payment/return/{clientBoc}', [
    ClientBocController::class,
    'paymentReturn',
])->name('client-bocs.payment.return');

Route::post('client-bocs/payment/notify', [
    ClientBocController::class,
    'paymentNotify',
])->name('client-bocs.payment.notify');

Route::get('/client-bocs/{clientBoc}', [ClientBocController::class, 'show'])->name('client-bocs.show');

Route::get('/welcome', function () {
    return view('welcome');
})->name('landing');

Route::get('/client-bocs/{clientBoc}/processing', [ClientBocController::class, 'processing'])
    ->name('client-bocs.processing');

Route::get('/client-bocs/{clientBoc}/status', [ClientBocController::class, 'status'])
    ->name('client-bocs.status');


    // Pour les etats financiers

    Route::prefix('client-financials')->name('client-financials.')->group(function () {

    // Formulaire d’upload
    Route::get('/create', [ClientFinancialController::class, 'create'])
        ->name('create');

    // Enregistrement + appel CinetPay
    Route::post('/', [ClientFinancialController::class, 'store'])
        ->name('store');

    // 🔁 Retour CinetPay (GET **et** POST)
    Route::match(['GET', 'POST'], '/{clientFinancial}/payment-return', [ClientFinancialController::class, 'paymentReturn'])
        ->name('payment.return');

    // 🔔 Notification serveur à serveur (toujours POST)
    Route::post('/payment/notify', [ClientFinancialController::class, 'paymentNotify'])
        ->name('payment.notify');

    // Page “processing”
    Route::get('/{clientFinancial}/processing', [ClientFinancialController::class, 'processing'])
        ->name('processing');

    // API de statut pour le polling JS
    Route::get('/{clientFinancial}/status', [ClientFinancialController::class, 'status'])
        ->name('status');

    // Page de résultat final
    Route::get('/{clientFinancial}', [ClientFinancialController::class, 'show'])
        ->name('show');
});


// route des bubbles

    Route::get('/client-bocs/{clientBoc}/bubbles', [ClientBocController::class, 'bubbles'])
    ->name('client-bocs.bubbles');

