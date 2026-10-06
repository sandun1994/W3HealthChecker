<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\GuideController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\ToolController;
use App\Http\Controllers\WorkspaceController;
use App\Models\Scan;
use Illuminate\Support\Facades\Route;

// Application Health Diagnostic Endpoint
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'check'])->name('health');

// Public SEO and Scanner Routes
Route::get('/', function () {
    return view('app', [
        'initialPage' => 'home',
        'pageTitle' => 'W3HealthChecker — Know Your Website. Improve Everything.',
        'metaDescription' => 'Comprehensive website health scanner, SEO audit, security headers, accessibility indicators, and AI search readiness SaaS platform.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('home');

// Comparison Page
Route::get('/compare', function () {
    return view('app', [
        'initialPage' => 'compare',
        'pageTitle' => 'Website Comparison Tool — Side-by-Side Health Audit | W3HealthChecker',
        'metaDescription' => 'Compare two websites across SEO, performance, security, accessibility, and AI search readiness.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('compare');

// Dedicated SEO Tool Landing Pages
Route::get('/tools', [ToolController::class, 'index'])->name('tools.index');
Route::get('/tools/{slug}', [ToolController::class, 'show'])->name('tools.show');

// Educational Guides Hub & Topic Clusters
Route::get('/guides', [GuideController::class, 'index'])->name('guides.index');
Route::get('/guides/{slug}', [GuideController::class, 'show'])->name('guides.show');

// About & Methodology Transparency Page
Route::get('/about', function () {
    return view('app', [
        'initialPage' => 'about',
        'pageTitle' => 'About W3HealthChecker — Diagnostic Integrity & Methodology',
        'metaDescription' => 'Learn how W3HealthChecker audits websites across SEO, security, performance, accessibility, and AI readiness using transparent, defensible scoring algorithms.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('about');

// Public Shareable Report Route
Route::get('/report/{domain}/{publicId}', function (string $domain, string $publicId) {
    $scan = Scan::where('public_id', $publicId)->first();
    $score = $scan?->overall_score ?? 80;
    return view('app', [
        'initialPage' => 'report',
        'reportDomain' => $domain,
        'reportPublicId' => $publicId,
        'pageTitle' => "Website Health Report for {$domain} ({$score}/100) | W3HealthChecker",
        'metaDescription' => "Review prioritized technical issues, SEO audit scores, security headers, and fix recommendations for {$domain}.",
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('report.show');

// Dynamic Sitemap & Robots.txt
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', function () {
    $content = "User-agent: *\nAllow: /\nDisallow: /api/\n\nSitemap: " . url('/sitemap.xml') . "\n";
    return response($content, 200, ['Content-Type' => 'text/plain']);
});

// Monitoring & Retention Dashboard
Route::get('/monitoring', function () {
    return view('app', [
        'initialPage' => 'monitoring',
        'pageTitle' => 'Website Health Monitoring & Change Detection | W3HealthChecker',
        'metaDescription' => 'Track website health over time with automated daily or weekly audits, change detection, and regression alerts.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('monitoring');

// User Authentication & Workspace Web Routes
Route::get('/login', function () {
    return view('app', [
        'initialPage' => 'login',
        'pageTitle' => 'Sign In to W3HealthChecker — User Workspace',
        'metaDescription' => 'Sign in to access your saved websites, projects, historical scans, and monitoring alerts.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('login');

Route::get('/register', function () {
    return view('app', [
        'initialPage' => 'register',
        'pageTitle' => 'Create Your W3HealthChecker Account — Free Workspace',
        'metaDescription' => 'Create a free account to track website health over time, organize projects, and set up automated alerts.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('register');

Route::get('/dashboard', function () {
    return view('app', [
        'initialPage' => 'dashboard',
        'pageTitle' => 'User Workspace Dashboard | W3HealthChecker',
        'metaDescription' => 'Manage your saved websites, monitoring schedules, projects, and regression history.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('dashboard');

Route::get('/billing', function () {
    return view('app', [
        'initialPage' => 'billing',
        'pageTitle' => 'Plans & Pricing — Scale Website Intelligence | W3HealthChecker',
        'metaDescription' => 'Transparent plans for creators, agencies, and engineering teams. Upgrade for daily monitoring, PDF exports, and API access.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('billing');

Route::get('/agency', function () {
    return view('app', [
        'initialPage' => 'agency',
        'pageTitle' => 'Agency Workspace & Client Portfolios | W3HealthChecker',
        'metaDescription' => 'Multi-client website intelligence, team collaboration, custom white-label reports, and automated monitoring for agencies.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('agency');

Route::get('/developers', function () {
    return view('app', [
        'initialPage' => 'developers',
        'pageTitle' => 'Developer API v1 & Webhooks Documentation | W3HealthChecker',
        'metaDescription' => 'Integrate website intelligence into your CI/CD and systems with our defensive REST API v1, Bearer token authentication, and signed webhooks.',
        'toolsData' => ToolController::getToolsList(),
        'guidesData' => GuideController::getGuidesList(),
    ]);
})->name('developers');

// Public Auth Endpoints (throttled)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/api/auth/register', [AuthController::class, 'register'])->name('api.auth.register');
    Route::post('/api/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
    Route::post('/api/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('/api/auth/user', [AuthController::class, 'user'])->name('api.auth.user');
    Route::get('/api/billing/plans', [BillingController::class, 'plans'])->name('api.billing.plans');
    Route::post('/api/billing/webhook', [BillingController::class, 'webhook'])->name('api.billing.webhook');
});

// Authenticated Account & Workspace Endpoints
Route::middleware('auth')->group(function () {
    Route::post('/api/auth/password', [AuthController::class, 'updatePassword'])->name('api.auth.password');
    Route::delete('/api/auth/account', [AuthController::class, 'deleteAccount'])->name('api.auth.account');

    // Workspace API
    Route::get('/api/workspace/overview', [WorkspaceController::class, 'overview'])->name('api.workspace.overview');
    Route::get('/api/workspace/websites', [WorkspaceController::class, 'websites'])->name('api.workspace.websites');
    Route::post('/api/workspace/websites', [WorkspaceController::class, 'storeWebsite'])->name('api.workspace.websites.store');
    Route::delete('/api/workspace/websites/{id}', [WorkspaceController::class, 'destroyWebsite'])->name('api.workspace.websites.destroy');
    Route::get('/api/workspace/projects', [WorkspaceController::class, 'projects'])->name('api.workspace.projects');
    Route::post('/api/workspace/projects', [WorkspaceController::class, 'storeProject'])->name('api.workspace.projects.store');
    Route::delete('/api/workspace/projects/{id}', [WorkspaceController::class, 'destroyProject'])->name('api.workspace.projects.destroy');
    Route::post('/api/workspace/sync-history', [WorkspaceController::class, 'syncHistory'])->name('api.workspace.sync-history');

    // Billing & Entitlements API
    Route::get('/api/billing/usage', [BillingController::class, 'usage'])->name('api.billing.usage');
    Route::post('/api/billing/subscribe', [BillingController::class, 'subscribe'])->name('api.billing.subscribe');
    Route::post('/api/billing/cancel', [BillingController::class, 'cancel'])->name('api.billing.cancel');
    Route::post('/api/billing/resume', [BillingController::class, 'resume'])->name('api.billing.resume');

    // Agency Workspace API
    Route::get('/api/agency/overview', [AgencyController::class, 'overview'])->name('api.agency.overview');
    Route::get('/api/agency/clients', [AgencyController::class, 'clients'])->name('api.agency.clients');
    Route::post('/api/agency/clients', [AgencyController::class, 'storeClient'])->name('api.agency.clients.store');
    Route::delete('/api/agency/clients/{id}', [AgencyController::class, 'destroyClient'])->name('api.agency.clients.destroy');
    Route::get('/api/agency/api-keys', [AgencyController::class, 'apiKeys'])->name('api.agency.api-keys');
    Route::post('/api/agency/api-keys', [AgencyController::class, 'storeApiKey'])->name('api.agency.api-keys.store');
    Route::delete('/api/agency/api-keys/{id}', [AgencyController::class, 'destroyApiKey'])->name('api.agency.api-keys.destroy');
    Route::get('/api/agency/webhooks', [AgencyController::class, 'webhooks'])->name('api.agency.webhooks');
    Route::post('/api/agency/webhooks', [AgencyController::class, 'storeWebhook'])->name('api.agency.webhooks.store');
    Route::delete('/api/agency/webhooks/{id}', [AgencyController::class, 'destroyWebhook'])->name('api.agency.webhooks.destroy');
});

// REST API V1 (Token Authenticated via Bearer Token, plan rate limited)
Route::prefix('api/v1')->middleware([\App\Http\Middleware\AuthenticateApiKey::class, 'throttle:60,1'])->group(function () {
    Route::post('/scans', [ApiController::class, 'triggerScan'])->name('api.v1.scans.store');
    Route::get('/scans/{id}', [ApiController::class, 'getScan'])->name('api.v1.scans.show');
    Route::get('/reports/{id}', [ApiController::class, 'getReport'])->name('api.v1.reports.show');
    Route::post('/scans/bulk', [ApiController::class, 'bulkScan'])->name('api.v1.scans.bulk');
    Route::post('/monitoring', [ApiController::class, 'storeMonitoring'])->name('api.v1.monitoring.store');
    Route::get('/monitoring', [ApiController::class, 'listMonitoring'])->name('api.v1.monitoring.index');
    Route::delete('/monitoring/{id}', [ApiController::class, 'destroyMonitoring'])->name('api.v1.monitoring.destroy');
});

// Scanner & Monitoring API Endpoints (Throttle: 30 per minute to prevent abuse)
Route::middleware('throttle:30,1')->group(function () {
    Route::post('/api/scan', [ScanController::class, 'store'])->name('api.scan.store');
    Route::get('/api/scan/status/{publicId}', [ScanController::class, 'status'])->name('api.scan.status');
    Route::get('/api/scan/report/{domain}/{publicId}', [ScanController::class, 'show'])->name('api.scan.report');
    Route::post('/api/compare', [ScanController::class, 'compare'])->name('api.scan.compare');
    Route::post('/api/crawl', [ScanController::class, 'crawl'])->name('api.scan.crawl');

    // Monitoring & Retention API
    Route::get('/api/monitoring', [MonitoringController::class, 'index'])->name('api.monitoring.index');
    Route::post('/api/monitoring', [MonitoringController::class, 'store'])->name('api.monitoring.store');
    Route::get('/api/monitoring/{id}', [MonitoringController::class, 'show'])->name('api.monitoring.show');
    Route::post('/api/monitoring/{id}/run', [MonitoringController::class, 'run'])->name('api.monitoring.run');
    Route::delete('/api/monitoring/{id}', [MonitoringController::class, 'destroy'])->name('api.monitoring.destroy');
    Route::get('/api/websites/{domain}/history', [MonitoringController::class, 'history'])->name('api.websites.history');
    Route::get('/api/websites/{domain}/trend', [MonitoringController::class, 'trend'])->name('api.websites.trend');
});
