<?php

use App\Http\Controllers\Api\SettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DiscoveryController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PhotoController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\BoostController;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\VerificationController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\MobileNotificationController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\DarkModeSettingsController;
use App\Http\Controllers\Api\CallController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/send-verification-code', [AuthController::class, 'sendEmailVerificationCode']);
Route::post('/verify-email-code', [AuthController::class, 'verifyEmailCode']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth');
Route::get('/filters/options', [StatsController::class, 'getFilterOptions']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    // Settings routes - ADD THESE
    Route::get('/settings', [SettingsController::class, 'getSettings']);
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications']);
    Route::put('/settings/privacy', [SettingsController::class, 'updatePrivacy']);
    Route::put('/settings/discovery', [SettingsController::class, 'updateDiscovery']);
    Route::delete('/account', [SettingsController::class, 'deleteAccount']);
    Route::get('/account/delete-info', [SettingsController::class, 'getDeleteAccountInfo']);

    // Discovery routes
    Route::get('/discover', [DiscoveryController::class, 'discover']);
    Route::post('/users/{targetUser}/like', [DiscoveryController::class, 'likeUser']);
    Route::post('/users/{targetUser}/pass', [DiscoveryController::class, 'passUser']);

    // Matches routes
    Route::get('/matches', [MatchController::class, 'index']);

    // Likes routes`
    Route::get('/likes/received', [MatchController::class, 'getReceivedLikes']);
    Route::get('/likes/sent', [MatchController::class, 'getSentLikes']);

    // Messages routes
    Route::get('/conversations', [MessageController::class, 'getConversations']);
    Route::get('/conversations/{matchId}/messages', [MessageController::class, 'getMessages']);
    Route::post('/conversations/{matchId}/messages', [MessageController::class, 'sendMessage'])->middleware('throttle:messaging');
    Route::post('/conversations/{matchId}/messages/media', [MessageController::class, 'sendMediaMessage'])->middleware('throttle:uploads');
    Route::post('/conversations/{matchId}/messages/{messageId}/viewed', [MessageController::class, 'markMediaAsViewed']);
    Route::put('/messages/{messageId}/read', [MessageController::class, 'markAsRead']);
    Route::delete('/messages/{messageId}', [MessageController::class, 'deleteMessage']);
    Route::get('/conversations/{matchId}/info', [MessageController::class, 'getConversationInfo']);
    Route::post('/conversations/{matchId}/typing', [MessageController::class, 'sendTypingIndicator'])->middleware('throttle:messaging');

    // Photo management
    Route::post('/photos', [PhotoController::class, 'store'])->middleware('throttle:uploads');
    Route::put('/photos/{id}', [PhotoController::class, 'update'])->middleware('throttle:uploads');
    Route::delete('/photos/{id}', [PhotoController::class, 'destroy']);

    // Safety & Reporting
    Route::post('/reports', [ReportController::class, 'store']);
    Route::post('/users/{userId}/block', [ReportController::class, 'blockUser']);
    Route::get('/blocked-users', [ReportController::class, 'getBlockedUsers']);

    // Profile Boosts
    Route::get('/boosts', [BoostController::class, 'index']);
    Route::post('/boosts', [BoostController::class, 'store']);
    Route::get('/boosts/history', [BoostController::class, 'history']);
    Route::get('/boosts/current', [BoostController::class, 'current']);
    Route::post('/boosts/{boostId}/cancel', [BoostController::class, 'cancel']);
    Route::put('/boosts/{boostId}/stats', [BoostController::class, 'updateStats']);

    // Stripe Checkout (web/browser redirect)
    Route::post('/boosts/checkout', [BoostController::class, 'createCheckoutSession']);
    Route::get('/boosts/payment-status/{sessionId}', [BoostController::class, 'checkPaymentStatus']);

    // Stripe Payment Intent (Flutter in-app payments)
    Route::post('/boosts/payment-intent', [BoostController::class, 'createPaymentIntent']);

    // Verification
    Route::post('/verification/photo', [VerificationController::class, 'submitPhoto']);
    Route::get('/verification/status', [VerificationController::class, 'getStatus']);

    // Subscription routes
    Route::get('/subscription', [SubscriptionController::class, 'index']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);

    // Stripe Checkout (web/browser redirect)
    Route::post('/stripe/checkout', [SubscriptionController::class, 'createCheckoutSession']);

    // Stripe Payment Intent (Flutter in-app payments)
    Route::post('/subscription/payment-intent', [SubscriptionController::class, 'createPaymentIntent']);

    // Payment methods
    Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods/setup-intent', [PaymentMethodController::class, 'createSetupIntent']);
    Route::post('/payment-methods/attach', [PaymentMethodController::class, 'attach']);
    Route::delete('/payment-methods/{paymentMethodId}', [PaymentMethodController::class, 'detach']);
    Route::post('/payment-methods/{paymentMethodId}/default', [PaymentMethodController::class, 'setDefault']);

    // Profile stats
    Route::get('/stats', [StatsController::class, 'getUserStats']);
    Route::post('/location/update', [StatsController::class, 'updateLocation']);

    // Mobile notifications (FCM)
    Route::post('/notifications/fcm-token', [MobileNotificationController::class, 'updateFcmToken']);
    Route::delete('/notifications/fcm-token', [MobileNotificationController::class, 'removeFcmToken']);
    Route::put('/notifications/settings', [MobileNotificationController::class, 'updateSettings']);
    Route::get('/notifications/settings', [MobileNotificationController::class, 'getSettings']);

    // Promo codes
    Route::post('/promo-codes/validate', [PromoCodeController::class, 'validate']);

    // Dark Mode Settings
    Route::get('/dark-mode/settings', [DarkModeSettingsController::class, 'getSettings']);
    Route::put('/dark-mode/settings', [DarkModeSettingsController::class, 'updateSettings']);
    Route::post('/dark-mode/settings/reset', [DarkModeSettingsController::class, 'resetSettings']);

    // Call routes
    Route::post('/calls/initiate', [CallController::class, 'initiate']);
    Route::post('/calls/{callId}/accept', [CallController::class, 'accept']);
    Route::post('/calls/{callId}/decline', [CallController::class, 'decline']);
    Route::post('/calls/{callId}/end', [CallController::class, 'end']);
    Route::post('/calls/{callId}/signal', [CallController::class, 'signal']);
    Route::get('/calls/history', [CallController::class, 'history']);
    Route::get('/calls/{callId}', [CallController::class, 'show']);
    Route::get('/matches/{matchId}/active-call', [CallController::class, 'activeCall']);

    // Admin routes
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::get('/users/{userId}', [AdminController::class, 'userDetails']);
        Route::put('/users/{userId}', [AdminController::class, 'updateUser']);
        Route::get('/reports', [AdminController::class, 'reports']);
        Route::put('/reports/{reportId}', [AdminController::class, 'updateReport']);
        Route::get('/subscriptions', [AdminController::class, 'subscriptions']);
        Route::get('/analytics', [AdminController::class, 'analytics']);
        Route::get('/content', [AdminController::class, 'getContent']);
        Route::put('/content/{type}/{id}', [AdminController::class, 'updateContent']);
        Route::get('/settings', [AdminController::class, 'getSettings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);
        Route::post('/notifications', [AdminController::class, 'sendNotification']);
        Route::get('/export/users', [AdminController::class, 'exportUsers']);
        Route::post('/users/{userId}/toggle-dark-mode', [AdminController::class, 'toggleDarkMode']);

        // Promo codes
        Route::get('/promo-codes', [PromoCodeController::class, 'index']);
        Route::post('/promo-codes', [PromoCodeController::class, 'store']);
        Route::get('/promo-codes/stats', [PromoCodeController::class, 'stats']);
        Route::get('/promo-codes/{id}', [PromoCodeController::class, 'show']);
        Route::put('/promo-codes/{id}', [PromoCodeController::class, 'update']);
        Route::delete('/promo-codes/{id}', [PromoCodeController::class, 'destroy']);
        Route::post('/promo-codes/{id}/toggle', [PromoCodeController::class, 'toggleActive']);
    });
});


// Stripe webhooks - no auth middleware, verified by Stripe signature
Route::post('/webhooks/stripe', [\App\Http\Controllers\Api\StripeWebhookController::class, 'handle']);

// Legacy Supabase webhook (for boost purchases via Supabase Edge Function)
Route::post('/webhooks/stripe/supabase', [BoostController::class, 'handleWebhook'])
    ->middleware(['verify.supabase.webhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/broadcasting/auth', [AuthController::class, 'authReverb']);
});
