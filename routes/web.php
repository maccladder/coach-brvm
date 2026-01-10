<?php

use App\Services\CloudflareStream;
use Illuminate\Support\Facades\Http;

use App\Services\BrvmMarketAiService;

use Illuminate\Support\Facades\Route;
use App\Services\BrvmActionsAiService;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\SGIController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CourseController;

use App\Http\Controllers\UploadController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SocieteController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ClientBocController;
use App\Http\Controllers\DividendeController;
use App\Http\Controllers\GlossaireController;
use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\AdminMarketController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CoursePaymentController;
use App\Http\Controllers\VirtualWalletController;
use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\ClientFinancialController;
use App\Http\Controllers\AdminPerformanceController;
use App\Http\Controllers\AdminAnnouncementController;
use App\Http\Controllers\AdminVirtualWalletController;
use App\Http\Controllers\AdminFinancialReportController;

/*
|--------------------------------------------------------------------------
| Page d’accueil
|--------------------------------------------------------------------------
*/

Route::get('/test/brvm-actions-ai', function (BrvmActionsAiService $svc) {
    $stocks = $svc->fetchCloseAndChangeFromSite();

    return response()->json([
        'count' => count($stocks),
        'stocks' => array_slice($stocks, 0, 10),
    ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

Route::redirect('/', '/welcome');

//LES ROUTES DES LIVRES
Route::get('/livres', [BookController::class, 'index'])->name('books.index');
Route::get('/livres/{book:slug}', [BookController::class, 'show'])->name('books.show');

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

Route::get('/formations', [CourseController::class, 'index'])->name('courses.index');

Route::get('/mon-espace/cours', [CourseController::class, 'myCourses'])
    ->middleware('auth')
    ->name('courses.my');

Route::get('/mon-espace/cours/{slug}', [CourseController::class, 'show'])
    ->middleware('auth')
    ->name('courses.show');

// ✅ ACHAT : auth obligatoire
Route::post('/formations/{course}/acheter', [CoursePaymentController::class, 'buy'])
    ->middleware('auth')
    ->name('courses.buy');

// ✅ RETOUR utilisateur : PAS auth (CinetPay peut faire GET/POST)
Route::match(['GET','POST'], '/payment/cinetpay/return', [CoursePaymentController::class, 'return'])
    ->name('cinetpay.return.course');

// ✅ IPN serveur : PAS auth (CinetPay POST)
Route::post('/paiement/cinetpay/ipn', [CoursePaymentController::class, 'ipn'])
    ->name('cinetpay.ipn.course');



/*
|--------------------------------------------------------------------------
| Annonces (PUBLIC)
|--------------------------------------------------------------------------
*/

Route::get('/annonces', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/annonces/{announcement}', [AnnouncementController::class, 'show'])->name('announcements.show');

/*
|--------------------------------------------------------------------------
| Admin (ton système admin actuel)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/login', [AdminController::class, 'showLoginForm'])->name('login.form');
    Route::post('/login', [AdminController::class, 'login'])->name('login');

    // ✅ Utilisateurs (ADMIN)
Route::get('/users', [AdminUserController::class, 'index'])
    ->name('users.index');

    Route::middleware('admin.code')->group(function () {

        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/performances', [AdminPerformanceController::class, 'index'])->name('performances.index');
        Route::get('/performances/data', [AdminPerformanceController::class, 'data'])->name('performances.data');

        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/data', [AdminAnalyticsController::class, 'data'])->name('analytics.data');

        Route::get('/bocs', [AdminController::class, 'dailyBocsIndex'])->name('bocs.index');
        Route::post('/bocs', [AdminController::class, 'dailyBocsStore'])->name('bocs.store');

        // ✅ États financiers (ADMIN)
        Route::get('/financial-reports/{year}', [AdminFinancialReportController::class, 'index'])
            ->name('financial_reports.index');

        Route::get('/financial-reports/{year}/societes/{societe}', [AdminFinancialReportController::class, 'showSociete'])
            ->name('financial_reports.societe');

        Route::post('/financial-reports/{year}/societes/{societe}/{period}/upload', [AdminFinancialReportController::class, 'upload'])
            ->name('financial_reports.upload');

        Route::post('/financial-reports/{year}/societes/{societe}/{period}/not-published', [AdminFinancialReportController::class, 'markNotPublished'])
            ->name('financial_reports.not_published');

        // ✅ Wallet (ADMIN)
        Route::get('/wallet', [AdminVirtualWalletController::class, 'index'])->name('wallet.index');
        Route::post('/wallet/buy', [AdminVirtualWalletController::class, 'buy'])->name('wallet.buy');
        Route::post('/wallet/sell', [AdminVirtualWalletController::class, 'sell'])->name('wallet.sell');

        // ✅ Marché
        Route::get('/market', [AdminMarketController::class, 'index'])->name('market.index');
        Route::get('/market/api', [AdminMarketController::class, 'api'])->name('market.api');

        // 🎓 FORMATIONS ADMIN ✅ (corrigé)
        Route::prefix('courses')->name('courses.')->group(function () {
            Route::get('/dashboard', [AdminCourseController::class, 'dashboard'])->name('dashboard'); // /admin/courses/dashboard
            Route::get('/purchases', [AdminCourseController::class, 'purchases'])->name('purchases'); // /admin/courses/purchases
            Route::get('/buyers', [AdminCourseController::class, 'buyers'])->name('buyers');         // /admin/courses/buyers
            Route::get('/', [AdminCourseController::class, 'courses'])->name('index');              // /admin/courses
        });

        // ✅ Annonces ADMIN (CRUD)
        Route::resource('announcements', AdminAnnouncementController::class)->except(['show']);
    });
});

/*
|--------------------------------------------------------------------------
| Portfolio virtuel (Breeze auth)
|--------------------------------------------------------------------------
| Ici c’est le portefeuille des utilisateurs "normaux" (user_id)
| On protège avec auth (Breeze).
|--------------------------------------------------------------------------
|
| ⚠️ Si tu n'as pas encore ces controllers/routes, tu peux laisser ce bloc vide
| pour le moment. Mais c’est ici qu’on va travailler pour le wallet user.
*/

Route::middleware(['auth'])->group(function () {
    // Exemple:
    // Route::get('/portfolio', [\App\Http\Controllers\VirtualPortfolioController::class, 'index'])->name('portfolio.index');
});

/*
|--------------------------------------------------------------------------
| PDF export BOC
|--------------------------------------------------------------------------
*/

Route::post('/{clientBoc}/pdf', [ClientBocController::class, 'downloadPdf'])->name('client-bocs.pdf');

/*
|--------------------------------------------------------------------------
| SSL / debug
|--------------------------------------------------------------------------
*/

Route::get('/ssl-http-test', function () {
    $ca = 'C:\\wamp64\\bin\\php\\cacert.pem';

    $urls = [
        'https://brvm.org/fr/cours-actions/0',
        'https://www.brvm.org/fr/cours-actions/0',
        'https://www.google.com',
    ];

    $out = [];

    foreach ($urls as $url) {
        try {
            $res = Http::withOptions(['verify' => $ca])
                ->timeout(20)
                ->withHeaders([
                    'User-Agent' => 'CoachBRVM/1.0 (+https://coach-brvm.com)',
                ])
                ->get($url);

            $out[$url] = [
                'ok' => $res->successful(),
                'status' => $res->status(),
                'len' => strlen($res->body()),
            ];
        } catch (\Throwable $e) {
            $out[$url] = [
                'ok' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    return response()->json($out, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
});

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

/*
|--------------------------------------------------------------------------
| Radar marché
|--------------------------------------------------------------------------
*/

Route::get('/radar-marche', [PerformanceController::class, 'index'])->name('radar.index');
Route::get('/radar-marche/data', [PerformanceController::class, 'data'])->name('radar.data');

// bulles du radar latest
Route::get('/radar/bubbles-latest', [App\Http\Controllers\RadarController::class, 'bubblesLatest'])
    ->name('radar.bubblesLatest');

/*
|--------------------------------------------------------------------------
| Sociétés / dividendes / SGI / FAQ / Glossaire
|--------------------------------------------------------------------------
*/

Route::get('/societes', [SocieteController::class, 'index'])->name('societes.index');
Route::get('/societes/{slug}', [SocieteController::class, 'show'])->name('societes.show');

Route::get('/dividendes', [DividendeController::class, 'index'])->name('dividendes.index');

Route::get('/sgis', [SGIController::class, 'index'])->name('sgis.index');
Route::get('/sgis/{slug}', [SGIController::class, 'show'])->name('sgis.show');

Route::get('/faq', [FaqController::class, 'index'])->name('faq');

Route::get('/aide/glossaire', [GlossaireController::class, 'index'])
    ->name('aide.glossaire');

/*
|--------------------------------------------------------------------------
| ✅ Breeze routes (LOGIN/REGISTER/LOGOUT/PASSWORD RESET)
|--------------------------------------------------------------------------
| IMPORTANT: Breeze ajoute routes/auth.php.
| On l'inclut ici, point final.
|--------------------------------------------------------------------------
*/

// Route::get('/dashboard', function () {
//     return redirect()->route('landing'); // /welcome
// })->middleware(['auth', 'verified'])->name('dashboard');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');


Route::middleware(['auth'])->group(function () {
    Route::get('/wallet', [VirtualWalletController::class, 'index'])->name('wallet.index');

    // ✅ nouveau flow topup payant
    Route::post('/wallet/topup/confirm', [VirtualWalletController::class, 'topupConfirm'])->name('wallet.topup.confirm');
    Route::post('/wallet/topup/pay', [VirtualWalletController::class, 'topupPay'])->name('wallet.topup.pay');

    // achats/ventes
   Route::match(['GET','POST'], '/wallet/buy/recap', [VirtualWalletController::class, 'buyRecap'])
    ->name('wallet.buy.recap');
    Route::post('/wallet/buy', [VirtualWalletController::class, 'buy'])->name('wallet.buy');
    Route::match(['GET','POST'], '/wallet/sell/recap', [VirtualWalletController::class, 'sellRecap'])
    ->name('wallet.sell.recap');
    Route::post('/wallet/sell', [VirtualWalletController::class, 'sell'])->name('wallet.sell');
});

// ✅ callback serveur (IPN) + retour utilisateur : PAS auth
Route::post('/payments/cinetpay/ipn', [PaymentController::class, 'cinetpayIpn'])->name('cinetpay.ipn');

Route::match(['GET', 'POST'], '/payments/cinetpay/return', [PaymentController::class, 'cinetpayReturn'])
    ->name('cinetpay.return');


    Route::get('/debug/cloudflare/video/{uid}', function (string $uid, CloudflareStream $cf) {
    $video = $cf->getVideo($uid);
    return response()->json([
        'uid' => $uid,
        'meta' => [
            'status' => $video['status'] ?? null,
            'duration' => $video['duration'] ?? null,
            'created' => $video['created'] ?? null,
            'requireSignedURLs' => $video['requireSignedURLs'] ?? null,
        ],
    ]);
})->middleware('auth');

Route::get('/debug/cloudflare/token/{uid}', function (string $uid, CloudflareStream $cf) {
    $exp = config('services.cloudflare_stream.signed_exp', 3600);

    $token = $cf->createPlaybackToken($uid, $exp);

    $iframe = "https://" . config('services.cloudflare_stream.customer_subdomain') . "/{$uid}/iframe?token={$token}";

    return response()->json([
        'uid' => $uid,
        'expires_in' => $exp,
        'iframe' => $iframe,
        // 'token' => $token, // optionnel: tu peux le cacher si tu veux
    ]);
});

require __DIR__.'/auth.php';
