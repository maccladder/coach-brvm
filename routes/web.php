<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\SocieteController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\ClientBocController;
use App\Http\Controllers\DividendeController;

use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\ClientFinancialController;
use App\Http\Controllers\AdminPerformanceController;
use App\Http\Controllers\AdminAnnouncementController;

/*
|--------------------------------------------------------------------------
| Page d’accueil
|--------------------------------------------------------------------------
*/

Route::redirect('/', '/welcome');

// ✅ Landing avec controller (pour passer $latestAnnouncements au welcome)
Route::get('/welcome', [LandingController::class, 'index'])->name('landing');


/*
|--------------------------------------------------------------------------
| Uploads (analyses & états financiers)
|--------------------------------------------------------------------------
*/

Route::get('/uploads', [UploadController::class, 'index'])->name('uploads.index');

Route::post('/uploads/analysis', [UploadController::class, 'storeAnalysis'])->name('uploads.analysis.store');

Route::post('/uploads/statement', [UploadController::class, 'storeStatement'])->name('uploads.statement.store');


/*
|--------------------------------------------------------------------------
| Résumés quotidiens (summaries)
|--------------------------------------------------------------------------
*/

Route::get('/summaries/today', [SummaryController::class, 'showToday'])->name('summaries.today');
Route::get('/summaries', [SummaryController::class, 'index'])->name('summaries.index');
Route::get('/summaries/generate', [SummaryController::class, 'generateForm'])->name('summaries.generate.form');
Route::post('/summaries/generate', [SummaryController::class, 'generateForDate'])->name('summaries.generate');

Route::get('/summaries/{summary}/audio', [SummaryController::class, 'audio'])->name('summaries.audio');

Route::get('/summaries/{date}', [SummaryController::class, 'showDate'])->name('summaries.show');


/*
|--------------------------------------------------------------------------
| BOC clients (client-bocs)
|--------------------------------------------------------------------------
*/

Route::prefix('client-bocs')->name('client-bocs.')->group(function () {

    Route::get('/', [ClientBocController::class, 'index'])->name('index');
    Route::get('/create', [ClientBocController::class, 'create'])->name('create');
    Route::post('/', [ClientBocController::class, 'store'])->name('store');

    Route::match(['GET', 'POST'], '/payment/return/{clientBoc}', [ClientBocController::class, 'paymentReturn'])
        ->name('payment.return');

    Route::post('/payment/notify', [ClientBocController::class, 'paymentNotify'])
        ->name('payment.notify');

    Route::get('/{clientBoc}/processing', [ClientBocController::class, 'processing'])->name('processing');
    Route::get('/{clientBoc}/status', [ClientBocController::class, 'status'])->name('status');
    Route::get('/{clientBoc}/bubbles', [ClientBocController::class, 'bubbles'])->name('bubbles');

    Route::get('/{clientBoc}', [ClientBocController::class, 'show'])->name('show');
});


/*
|--------------------------------------------------------------------------
| États financiers (client-financials)
|--------------------------------------------------------------------------
*/

Route::prefix('client-financials')->name('client-financials.')->group(function () {

    Route::get('/', [ClientFinancialController::class, 'index'])->name('index');
    Route::get('/create', [ClientFinancialController::class, 'create'])->name('create');
    Route::post('/', [ClientFinancialController::class, 'store'])->name('store');

    Route::match(['GET', 'POST'], '/payment/return/{clientFinancial}', [ClientFinancialController::class, 'paymentReturn'])
        ->name('payment.return');

    Route::post('/payment/notify', [ClientFinancialController::class, 'paymentNotify'])
        ->name('payment.notify');

    Route::get('/{clientFinancial}/processing', [ClientFinancialController::class, 'processing'])->name('processing');
    Route::get('/{clientFinancial}/status', [ClientFinancialController::class, 'status'])->name('status');

    Route::get('/{clientFinancial}', [ClientFinancialController::class, 'show'])->name('show');
});


/*
|--------------------------------------------------------------------------
| Formations BRVM
|--------------------------------------------------------------------------
*/

Route::get('/formations-brvm', function () {
    return view('sections.formations-brvm');
})->name('formations.brvm');


/*
|--------------------------------------------------------------------------
| Annonces (PUBLIC)
|--------------------------------------------------------------------------
*/

Route::get('/annonces', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/annonces/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');


/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login.form');
    Route::post('/login', [AdminController::class, 'login'])->name('login');

    Route::middleware('admin.code')->group(function () {

        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/performances', [AdminPerformanceController::class, 'index'])->name('performances.index');
        Route::get('/performances/data', [AdminPerformanceController::class, 'data'])->name('performances.data');

        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/data', [AdminAnalyticsController::class, 'data'])->name('analytics.data');

        Route::get('/bocs', [AdminController::class, 'dailyBocsIndex'])->name('bocs.index');
        Route::post('/bocs', [AdminController::class, 'dailyBocsStore'])->name('bocs.store');

        // ✅ Annonces ADMIN (CRUD)
        Route::resource('announcements', AdminAnnouncementController::class)->except(['show']);
    });
});


/*
|--------------------------------------------------------------------------
| PDF export BOC
|--------------------------------------------------------------------------
*/

Route::post('/{clientBoc}/pdf', [ClientBocController::class, 'downloadPdf'])->name('client-bocs.pdf');


/*
|--------------------------------------------------------------------------
| Pages statiques
|--------------------------------------------------------------------------
*/

Route::view('/contact', 'sections.contact')->name('contact');
Route::view('/conditions', 'sections.conditions')->name('conditions');
Route::view('/confidentialite', 'sections.confidentialite')->name('confidentialite');
Route::view('/notre-histoire', 'sections.notre-histoire')->name('notre.histoire');


/*
|--------------------------------------------------------------------------
| GA test
|--------------------------------------------------------------------------
*/

Route::get('/ga-test', function (\App\Services\GoogleAnalyticsService $ga) {
    return [
        'todayUsers' => $ga->todayUsers(),
        'realtimeUsers' => $ga->realtimeUsers(),
        'topCountries' => $ga->topCountries(5),
    ];
});


// Route::get('/radar-marche', [PerformanceController::class, 'index'])->name('radar.index');
// Route::get('/radar-marche/data', [PerformanceController::class, 'data'])->name('radar.data');
Route::get('/radar-marche', [PerformanceController::class, 'index'])->name('radar.index');
Route::get('/radar-marche/data', [PerformanceController::class, 'data'])->name('radar.data');


// route de société

Route::get('/societes', [SocieteController::class, 'index'])->name('societes.index');
Route::get('/societes/{slug}', [SocieteController::class, 'show'])->name('societes.show');

// route de dividende

Route::get('/dividendes', [DividendeController::class, 'index'])
    ->name('dividendes.index');
