<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\ClientBocController;
use App\Http\Controllers\ClientFinancialController;
use App\Http\Controllers\AdminPerformanceController;

/*
|--------------------------------------------------------------------------
| Page d’accueil
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/welcome');

Route::get('/welcome', function () {
    return view('welcome');
})->name('landing');


/*
|--------------------------------------------------------------------------
| Uploads (analyses & états financiers)
|--------------------------------------------------------------------------
*/

Route::get('/uploads', [UploadController::class, 'index'])
    ->name('uploads.index');

Route::post('/uploads/analysis', [UploadController::class, 'storeAnalysis'])
    ->name('uploads.analysis.store');

Route::post('/uploads/statement', [UploadController::class, 'storeStatement'])
    ->name('uploads.statement.store');


/*
|--------------------------------------------------------------------------
| Résumés quotidiens (summaries)
|--------------------------------------------------------------------------
*/

Route::get('/summaries/today', [SummaryController::class, 'showToday'])
    ->name('summaries.today');

Route::get('/summaries', [SummaryController::class, 'index'])
    ->name('summaries.index');

Route::get('/summaries/generate', [SummaryController::class, 'generateForm'])
    ->name('summaries.generate.form');

Route::post('/summaries/generate', [SummaryController::class, 'generateForDate'])
    ->name('summaries.generate');

// Audio d’un résumé précis
Route::get('/summaries/{summary}/audio', [SummaryController::class, 'audio'])
    ->name('summaries.audio');

// Résumé pour une date précise (doit rester APRÈS les routes ci-dessus)
Route::get('/summaries/{date}', [SummaryController::class, 'showDate'])
    ->name('summaries.show');


/*
|--------------------------------------------------------------------------
| BOC clients (client-bocs)
|--------------------------------------------------------------------------
*/

Route::prefix('client-bocs')->name('client-bocs.')->group(function () {

    // Liste + formulaire + enregistrement
    Route::get('/', [ClientBocController::class, 'index'])
        ->name('index');

    Route::get('/create', [ClientBocController::class, 'create'])
        ->name('create');

    Route::post('/', [ClientBocController::class, 'store'])
        ->name('store');

    // Retour CinetPay (GET + POST)
    Route::match(['GET', 'POST'], '/payment/return/{clientBoc}', [
        ClientBocController::class,
        'paymentReturn',
    ])->name('payment.return');

    // Notification serveur à serveur CinetPay
    Route::post('/payment/notify', [
        ClientBocController::class,
        'paymentNotify',
    ])->name('payment.notify');

    // Page de transition "Paiement confirmé / traitement en cours"
    Route::get('/{clientBoc}/processing', [ClientBocController::class, 'processing'])
        ->name('processing');

    // API de statut pour le polling JS
    Route::get('/{clientBoc}/status', [ClientBocController::class, 'status'])
        ->name('status');

    // API bulles BRVM
    Route::get('/{clientBoc}/bubbles', [ClientBocController::class, 'bubbles'])
        ->name('bubbles');

    // Page de résultat final (doit être la DERNIÈRE route du groupe)
    Route::get('/{clientBoc}', [ClientBocController::class, 'show'])
        ->name('show');
});


/*
|--------------------------------------------------------------------------
| États financiers (client-financials)
|--------------------------------------------------------------------------
*/



Route::prefix('client-financials')->name('client-financials.')->group(function () {

    // Liste des derniers états financiers
    Route::get('/', [ClientFinancialController::class, 'index'])
        ->name('index');

    // Formulaire d’upload
    Route::get('/create', [ClientFinancialController::class, 'create'])
        ->name('create');

    // Enregistrement + appel CinetPay
    Route::post('/', [ClientFinancialController::class, 'store'])
        ->name('store');

    // 🔁 Retour CinetPay (GET + POST) – même pattern que BOC
    Route::match(['GET', 'POST'], '/payment/return/{clientFinancial}', [
        ClientFinancialController::class,
        'paymentReturn',
    ])->name('payment.return');

    // Notification CinetPay
    Route::post('/payment/notify', [
        ClientFinancialController::class,
        'paymentNotify',
    ])->name('payment.notify');

    // Page "processing"
    Route::get('/{clientFinancial}/processing', [
        ClientFinancialController::class,
        'processing',
    ])->name('processing');

    // API de statut
    Route::get('/{clientFinancial}/status', [
        ClientFinancialController::class,
        'status',
    ])->name('status');

    // Page de résultat final
    Route::get('/{clientFinancial}', [
        ClientFinancialController::class,
        'show',
    ])->name('show');
});


/*
|--------------------------------------------------------------------------
| Formations BRVM
|--------------------------------------------------------------------------
*/

Route::get('/formations-brvm', function () {
    return view('sections.formations-brvm');
})->name('formations.brvm');


// routes admin

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login', [AdminController::class, 'showLoginForm'])
        ->name('login.form');

    Route::post('/login', [AdminController::class, 'login'])
        ->name('login');

    Route::middleware('admin.code')->group(function () {

        Route::post('/logout', [AdminController::class, 'logout'])
            ->name('logout');

        Route::get('/dashboard', [AdminController::class, 'dashboard'])
            ->name('dashboard');

             Route::get('/performances', [AdminPerformanceController::class, 'index'])
        ->name('performances.index');

    Route::get('/performances/data', [AdminPerformanceController::class, 'data'])
        ->name('performances.data');


        // 👉 Nouveau : gestion des BOC journalières
        Route::get('/bocs', [AdminController::class, 'dailyBocsIndex'])
            ->name('bocs.index');
        Route::post('/bocs', [AdminController::class, 'dailyBocsStore'])
            ->name('bocs.store');
    });
});


// Export PDF du résultat BOC
Route::post('/{clientBoc}/pdf', [
    ClientBocController::class,
    'downloadPdf',
])->name('client-bocs.pdf');

// route contact

Route::view('/contact', 'sections.contact')->name('contact');

// sections autres

Route::view('/conditions', 'sections.conditions')->name('conditions');
Route::view('/confidentialite', 'sections.confidentialite')->name('confidentialite');
Route::view('/notre-histoire', 'sections.notre-histoire')->name('notre.histoire');

