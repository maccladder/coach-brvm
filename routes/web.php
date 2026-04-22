<?php

use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminAnnouncementController;
use App\Http\Controllers\AdminController;



use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\AdminEmailController;

use App\Http\Controllers\AdminFinancialReportController;
use App\Http\Controllers\AdminMarketController;
use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AdminPerformanceController;
use App\Http\Controllers\AdminTopupController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminVirtualWalletController;

use App\Http\Controllers\AnnouncementController;

use App\Http\Controllers\AdminBookController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ChocsMarcheController;
use App\Http\Controllers\ClientBocController;
use App\Http\Controllers\ClientFinancialController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CoursePaymentController;
use App\Http\Controllers\DividendeController;
use App\Http\Controllers\DocumentAdminController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentPaymentController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\GlossaireController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MarketplaceCategoryAdminController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\MarketplaceMyProductsController;
use App\Http\Controllers\MarketplaceProductAdminController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaystackTestController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\PerformanceController;
use App\Http\Controllers\SGIController;
use App\Http\Controllers\SocieteController;
use App\Http\Controllers\SummaryController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\VendorDashboardController;
use App\Http\Controllers\VendorDocumentController;
use App\Http\Controllers\VendorEarningsController;
use App\Http\Controllers\VendorModeController;
use App\Http\Controllers\VendorPayoutAdminController;
use App\Http\Controllers\VendorProductController;
use App\Http\Controllers\StagiaireController;
use App\Http\Controllers\StagiaireLogController;
use App\Http\Controllers\VirtualWalletController;
use App\Services\BrvmActionsAiService;
use App\Services\BrvmMarketAiService;
use App\Services\CloudflareStream;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Page d'accueil
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

// auth google

Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');

//LES ROUTES DES LIVRES
Route::get('/livres', [BookController::class, 'index'])->name('books.index');
Route::get('/livres/{book:slug}', [BookController::class, 'show'])->name('books.show');

// ✅ Marketplace (PUBLIC) — route spécifique AVANT le wildcard {slug}
Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/marketplace/lettreci', [\App\Http\Controllers\LettreCI\LettreCIController::class, 'showcase'])->name('lettreci.showcase');
Route::get('/marketplace/{slug}', [MarketplaceController::class, 'show'])->name('marketplace.show');

// ✅ Activation software (après achat) — page d'instructions/licence
Route::middleware('auth')->group(function () {
    Route::get('/marketplace/{product}/activation', [MarketplaceController::class, 'activation'])
        ->name('marketplace.activation');
});

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

    Route::get('/latest/public', [ClientBocController::class, 'latestPublic'])
    ->name('latest.public');


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

    Route::get('/payment/callback', [ClientFinancialController::class, 'paystackCallback'])
        ->name('payment.callback');

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

    // mass mail campagne

    Route::get('/emails', [\App\Http\Controllers\AdminEmailController::class, 'index'])
    ->name('emails.index');

Route::post('/emails/send', [AdminEmailController::class, 'send'])
    ->name('emails.send');

    Route::middleware(['admin.code', 'stagiaire.log'])->group(function () {

        Route::post('/logout', [AdminController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/performances', [AdminPerformanceController::class, 'index'])->name('performances.index');
        Route::get('/performances/data', [AdminPerformanceController::class, 'data'])->name('performances.data');

        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/data', [AdminAnalyticsController::class, 'data'])->name('analytics.data');

        Route::get('/bocs', [AdminController::class, 'dailyBocsIndex'])->name('bocs.index');
        Route::post('/bocs', [AdminController::class, 'dailyBocsStore'])->name('bocs.store');

        // ajout document admin

Route::resource('documents', DocumentAdminController::class);
        Route::patch('documents/{document}/toggle', [DocumentAdminController::class, 'toggle'])
            ->name('documents.toggle');

// ✅ NEW: validation admin
Route::post('documents/{document}/approve', [DocumentAdminController::class, 'approve'])
    ->name('documents.approve');

Route::post('documents/{document}/reject', [DocumentAdminController::class, 'reject'])
    ->name('documents.reject');


        // ✅ États financiers (ADMIN)
        Route::get('/financial-reports/{year}', [AdminFinancialReportController::class, 'index'])
            ->name('financial_reports.index');

        Route::get('/financial-reports/{year}/societes/{societe}', [AdminFinancialReportController::class, 'showSociete'])
            ->name('financial_reports.societe');

        Route::post('/financial-reports/{year}/societes/{societe}/{period}/upload', [AdminFinancialReportController::class, 'upload'])
            ->name('financial_reports.upload');

        Route::post('/financial-reports/{year}/societes/{societe}/{period}/not-published', [AdminFinancialReportController::class, 'markNotPublished'])
            ->name('financial_reports.not_published');

           // // ✅ Marketplace (ADMIN)
Route::prefix('marketplace')->name('marketplace.')->group(function () {

    Route::get('/', [MarketplaceProductAdminController::class, 'index'])->name('index');           // /admin/marketplace
    Route::get('/create', [MarketplaceProductAdminController::class, 'create'])->name('create');  // /admin/marketplace/create
    Route::post('/', [MarketplaceProductAdminController::class, 'store'])->name('store');         // POST

    // ✅ Pré-approuver / publier une vidéo (Cloudflare)
Route::get('/{product}/publish', [MarketplaceProductAdminController::class, 'publishForm'])
    ->name('publish.form');

Route::post('/{product}/publish', [MarketplaceProductAdminController::class, 'publish'])
    ->name('publish');


    // ✅ inspecter / review (admin)
    Route::get('/{product}', [MarketplaceProductAdminController::class, 'show'])->name('show');

    // ✅ télécharger un asset (admin)
    Route::get('/assets/{asset}/download', [MarketplaceProductAdminController::class, 'downloadAsset'])
        ->name('assets.download');

    Route::get('/{product}/edit', [MarketplaceProductAdminController::class, 'edit'])->name('edit');
    Route::put('/{product}', [MarketplaceProductAdminController::class, 'update'])->name('update');
    Route::delete('/{product}', [MarketplaceProductAdminController::class, 'destroy'])->name('destroy');

    Route::post('/{product}/approve', [MarketplaceProductAdminController::class, 'approve'])->name('approve');
    Route::post('/{product}/reject', [MarketplaceProductAdminController::class, 'reject'])->name('reject');

    // 🎮 Test admin du jeu (adminMode forcé à true, sans vérif achat)
    Route::get('/{product}/play', [MarketplaceProductAdminController::class, 'playAdmin'])->name('play');
    Route::get('/{product}/game-html', [MarketplaceProductAdminController::class, 'gameHtmlAdmin'])->name('game-html');
});

// ✅ Catégories (ADMIN)
Route::resource('marketplace-categories', MarketplaceCategoryAdminController::class)->except(['show']);

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

        // ✅ TOPUPS (ADMIN)
Route::prefix('topups')->name('topups.')->group(function () {
    Route::get('/', [AdminTopupController::class, 'index'])->name('index'); // /admin/topups
});

        // ✅ Annonces ADMIN (CRUD)
        Route::resource('announcements', AdminAnnouncementController::class)->except(['show']);

        // 📋 Logs stagiaire (admin only)
        Route::get('/stagiaire/logs', [StagiaireLogController::class, 'index'])->name('stagiaire.logs');
        Route::post('/stagiaire/logs/clear', [StagiaireLogController::class, 'clear'])->name('stagiaire.logs.clear');

        // 📚 Livres & Études de marché (admin CRUD)
        Route::resource('books', AdminBookController::class);
        Route::post('books/{book}/pages', [AdminBookController::class, 'storePage'])->name('books.pages.store');
        Route::delete('books/{book}/pages/{page}', [AdminBookController::class, 'destroyPage'])->name('books.pages.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Stagiaire
|--------------------------------------------------------------------------
*/
Route::prefix('stagiaire')->name('stagiaire.')->group(function () {
    Route::get('/login',  [StagiaireController::class, 'showLoginForm'])->name('login.form');
    Route::post('/login', [StagiaireController::class, 'login'])->name('login');
    Route::post('/logout', [StagiaireController::class, 'logout'])
        ->middleware('stagiaire.code')
        ->name('logout');
});

/*
|--------------------------------------------------------------------------
| Portfolio virtuel (Breeze auth)
|--------------------------------------------------------------------------
| Ici c'est le portefeuille des utilisateurs "normaux" (user_id)
| On protège avec auth (Breeze).
|--------------------------------------------------------------------------
|
| ⚠️ Si tu n'as pas encore ces controllers/routes, tu peux laisser ce bloc vide
| pour le moment. Mais c'est ici qu'on va travailler pour le wallet user.
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
    $myAnalysesCount = \App\Models\ClientFinancial::where('user_id', auth()->id())
        ->whereIn('status', ['paid', 'published'])
        ->count();
    return view('dashboard', compact('myAnalysesCount'));
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

// route du choc du marché

Route::get('/chocs-marche', [ChocsMarcheController::class, 'index'])->name('chocs.index');
Route::get('/chocs-marche/{sector}', [ChocsMarcheController::class, 'show'])->name('chocs.show');

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
// Route download sécurisée de mes produits

Route::middleware(['auth'])->group(function () {

    Route::get('/mon-espace/mes-produits', [MarketplaceMyProductsController::class, 'index'])
        ->name('my.products');

    Route::get('/mon-espace/mes-produits/{product}/download', [MarketplaceMyProductsController::class, 'download'])
        ->name('my.products.download');

    Route::get('/mon-espace/mes-produits/{product}/watch', [MarketplaceMyProductsController::class, 'watch'])
        ->name('my.products.watch');

    Route::get('/mon-espace/mes-produits/{product}/play', [MarketplaceMyProductsController::class, 'play'])
        ->name('my.products.play');

    Route::get('/mon-espace/mes-produits/{product}/game-html', [MarketplaceMyProductsController::class, 'gameHtml'])
        ->name('my.products.game-html');

    // Score d'une partie (appelé en fetch/AJAX depuis la page play)
    Route::post('/api/games/{product}/score', [\App\Http\Controllers\GameScoreController::class, 'store'])
        ->name('games.score.store');

    // Classement du jeu
    Route::get('/games/{product}/leaderboard', [\App\Http\Controllers\GameScoreController::class, 'leaderboard'])
        ->name('games.leaderboard');

    // Déverrouillage premium in-game (paiement Paystack depuis l'iframe)
    Route::post('/games/{product}/verify-premium', [\App\Http\Controllers\GameScoreController::class, 'verifyPremiumUnlock'])
        ->name('games.verify-premium');

    // Initialisation paiement premium (server-side, retourne l'authorization_url)
    Route::post('/games/{product}/init-premium-payment', [\App\Http\Controllers\GameScoreController::class, 'initPremiumPayment'])
        ->name('games.init-premium-payment');

    // Callback Paystack pour mobile (redirect flow)
    Route::get('/games/{product}/premium-callback', [\App\Http\Controllers\GameScoreController::class, 'premiumCallback'])
        ->name('games.premium-callback');

});



// Marketplace payment (auth pour buy)


// ✅ return spécifique marketplace (PUBLIC, GET/POST)
Route::match(['GET','POST'], '/marketplace/payment/cinetpay/return', [\App\Http\Controllers\MarketplacePaymentController::class, 'return'])
    ->name('cinetpay.return.marketplace');

// ✅ notify spécifique marketplace (POST, sans auth)
Route::post('/marketplace/payment/cinetpay/notify', [\App\Http\Controllers\MarketplacePaymentController::class, 'notify'])
    ->name('cinetpay.notify.marketplace');

// buy reste protégé (normal)
Route::middleware('auth')->group(function () {
    Route::post('/marketplace/{product}/buy', [\App\Http\Controllers\MarketplacePaymentController::class, 'buy'])
        ->name('marketplace.buy');
});


// Public
// Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
// Route::get('/documents/{slug}', [DocumentController::class, 'show'])->name('documents.show');

// Client
Route::middleware('auth')->group(function () {
    Route::get('/mes-documents', [DocumentController::class, 'myDocuments'])->name('documents.mine');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
});


// Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
//     Route::resource('documents', DocumentAdminController::class);
//     Route::patch('documents/{document}/toggle', [DocumentAdminController::class, 'toggle'])
//         ->name('documents.toggle');
// });


Route::get('/etudes-businessplans', [DocumentController::class, 'index'])->name('docs.public.index');
Route::get('/etudes-businessplans/{slug}', [DocumentController::class, 'show'])->name('docs.public.show');


Route::middleware('auth')->group(function () {
    Route::post('/documents/{document}/buy', [DocumentPaymentController::class, 'buy'])
        ->name('documents.buy');
});

// retour user (public GET/POST)
Route::match(['GET','POST'], '/documents/payment/cinetpay/return', [DocumentPaymentController::class, 'return'])
    ->name('cinetpay.return.documents');

// notify serveur (IPN) (public POST)
Route::post('/documents/payment/cinetpay/notify', [DocumentPaymentController::class, 'notify'])
    ->name('cinetpay.notify.documents');

    //routes test pour paystack



// Faux inline.js Paystack pour proxy de paiement sandbox-safe
Route::get('/paystack-inline-mock.js', function () {
    $js = 'window.PaystackPop={setup:function(o){try{window.parent.postMessage({type:"GAME_REQUEST_PAYMENT",char:(o.metadata&&o.metadata.char)||null},"*")}catch(e){}return{openIframe:function(){},openPopup:function(){}}}};';
    return response($js, 200, ['Content-Type' => 'application/javascript', 'Cache-Control' => 'no-store']);
})->name('paystack.mock-inline');

Route::get('/test/paystack', [PaystackTestController::class, 'form'])->name('paystack.test');
Route::post('/test/paystack/start', [PaystackTestController::class, 'start'])->name('paystack.start');
Route::get('/paystack/callback', [PaystackTestController::class, 'callback'])->name('paystack.callback');



Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle'])
    ->name('paystack.webhook');



Route::post('/wallet/topup/paystack', [VirtualWalletController::class, 'topupPayPaystack'])
    ->middleware('auth')
    ->name('wallet.topup.paystack');

    Route::get('/wallet/topup/paystack/callback', [VirtualWalletController::class, 'paystackCallback'])
    ->name('wallet.paystack.callback');

    // ✅ Marketplace Paystack (auth)
Route::middleware('auth')->group(function () {

    Route::post('/marketplace/{product}/buy/paystack', [\App\Http\Controllers\MarketplacePaymentController::class, 'buyPaystack'])
        ->name('paystack.marketplace.buy');

});

// ✅ Callback Paystack (PUBLIC) - Paystack redirige ici
Route::get('/paystack/marketplace/callback', [\App\Http\Controllers\MarketplacePaymentController::class, 'paystackCallback'])
    ->name('paystack.marketplace.callback');


    // ✅ Paystack courses (auth)
Route::middleware('auth')->group(function () {
    Route::post('/formations/{course}/buy/paystack', [\App\Http\Controllers\CoursePaymentController::class, 'buyPaystack'])
        ->name('courses.buy.paystack');
});

// ✅ Callback Paystack (PUBLIC)
Route::get('/paystack/courses/callback', [\App\Http\Controllers\CoursePaymentController::class, 'paystackCallback'])
    ->name('paystack.courses.callback');


Route::post('/admin/daily-bocs/{dailyBoc}/replace', [AdminController::class, 'dailyBocsReplace'])
    ->name('admin.bocs.replace');


Route::view('/strategies-investissement', 'aide.strategies')
    ->name('aide.strategies');

Route::middleware('auth')->group(function () {

    // ✅ Devenir vendeur
    Route::post('/devenir-vendeur', [VendorModeController::class, 'becomeVendor'])
        ->name('vendor.become');

    // ✅ Switch modes
    Route::post('/switch-to-vendor', [VendorModeController::class, 'switchToVendor'])
        ->name('switch.vendor');

    Route::post('/switch-to-user', [VendorModeController::class, 'switchToUser'])
        ->name('switch.user');
});

Route::middleware(['auth','vendor.mode'])
    ->prefix('vendor')
    ->name('vendor.')
    ->group(function () {

        // autres routes vendor...
         Route::post('documents/{document}/submit', [VendorDocumentController::class, 'submit'])
        ->name('documents.submit');

        Route::resource('documents', VendorDocumentController::class)->except(['show']);
    });



Route::middleware(['auth', 'vendor.mode'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/', function () {
        return view('vendor.dashboard'); // on la créera à l'étape 4
    })->name('dashboard');
});


Route::middleware(['auth','vendor.mode'])->prefix('vendor')->name('vendor.')->group(function () {

    Route::get('/', [VendorDashboardController::class, 'index'])->name('dashboard');

    Route::get('/products', [VendorProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [VendorProductController::class, 'create'])->name('products.create');
    Route::post('/products', [VendorProductController::class, 'store'])->name('products.store');

    Route::get('/products/{product}/edit', [VendorProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [VendorProductController::class, 'update'])->name('products.update');

    Route::post('/products/{product}/submit', [VendorProductController::class, 'submit'])->name('products.submit');
});


Route::middleware('auth')->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.readAll');
});

Route::middleware('admin.code')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [AdminNotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [AdminNotificationController::class, 'readAll'])->name('notifications.readAll');
});



Route::middleware(['auth']) // + ton middleware admin
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/payouts', [VendorPayoutAdminController::class, 'index'])->name('payouts.index');

        Route::post('/payouts/{payout}/approve', [VendorPayoutAdminController::class, 'approve'])->name('payouts.approve');
        Route::post('/payouts/{payout}/reject', [VendorPayoutAdminController::class, 'reject'])->name('payouts.reject');
        Route::post('/payouts/{payout}/paid', [VendorPayoutAdminController::class, 'markPaid'])->name('payouts.paid');
});

Route::middleware(['auth'])->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/earnings', [VendorEarningsController::class, 'index'])->name('earnings');
    Route::post('/payouts/request', [VendorEarningsController::class, 'requestPayout'])->name('payouts.request');
});

// ═══════════════════════════════════════════════════════
// LettreCI
// ═══════════════════════════════════════════════════════
use App\Http\Controllers\LettreCI\LettreCIController;
use App\Http\Controllers\LettreCI\LettreGeneratorController;
use App\Http\Controllers\LettreCI\LettrePaymentController;

Route::middleware('auth')->prefix('lettreci')->name('lettreci.')->group(function () {
    Route::get('/', [LettreCIController::class, 'landing'])->name('landing');
    Route::post('/acheter', [LettrePaymentController::class, 'checkout'])->name('checkout');
    Route::get('/paiement/callback', [LettrePaymentController::class, 'callback'])->name('payment.callback');

    Route::middleware('lettreci.access')->group(function () {
        Route::get('/dashboard', [LettreCIController::class, 'dashboard'])->name('dashboard');
        Route::get('/historique', [LettreCIController::class, 'history'])->name('history');
        Route::get('/nouvelle', [LettreGeneratorController::class, 'chooseType'])->name('new');
        Route::get('/nouvelle/{slug}', [LettreGeneratorController::class, 'showForm'])->name('form');
        Route::post('/nouvelle/{slug}', [LettreGeneratorController::class, 'submitForm'])
            ->middleware('throttle:letter-generation')->name('submit');
        Route::get('/generation/{letter}', [LettreGeneratorController::class, 'generating'])->name('generating');
        Route::get('/generation/{letter}/status', [LettreGeneratorController::class, 'status'])->name('status');
        Route::get('/lettre/{letter}', [LettreGeneratorController::class, 'preview'])->name('preview');
        Route::patch('/lettre/{letter}', [LettreGeneratorController::class, 'update'])->name('update');
        Route::get('/lettre/{letter}/pdf', [LettreGeneratorController::class, 'downloadPdf'])->name('pdf');
    });
});

require __DIR__.'/auth.php';
