<?php

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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/filters/options', [StatsController::class, 'getFilterOptions']);

// Protected routes
Route::group(['middleware' => ['auth:sanctum']], function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

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
    Route::post('/conversations/{matchId}/messages', [MessageController::class, 'sendMessage']);
    Route::put('/messages/{messageId}/read', [MessageController::class, 'markAsRead']);
    Route::delete('/messages/{messageId}', [MessageController::class, 'deleteMessage']);
    Route::get('/conversations/{matchId}/info', [MessageController::class, 'getConversationInfo']);

    // Photo management
    Route::post('/photos', [PhotoController::class, 'store']);
    Route::put('/photos/{id}', [PhotoController::class, 'update']);
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

    Route::post('/boosts/checkout', [BoostController::class, 'createCheckoutSession']);
    Route::get('/boosts/payment-status/{sessionId}', [BoostController::class, 'checkPaymentStatus']);

// Optional: Keep these for backward compatibility or admin use
    Route::post('/boosts/{boostId}/cancel', [BoostController::class, 'cancel']);
    Route::put('/boosts/{boostId}/stats', [BoostController::class, 'updateStats']);

    // Verification
    Route::post('/verification/photo', [VerificationController::class, 'submitPhoto']);
    Route::get('/verification/status', [VerificationController::class, 'getStatus']);

    // Subscription routes
    Route::get('/subscription', [SubscriptionController::class, 'index']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);

    // Stripe routes
    Route::post('/stripe/checkout', [SubscriptionController::class, 'createCheckoutSession']);

    // Profile stats
    Route::get('/stats', [StatsController::class, 'getUserStats']);
    Route::post('/location/update', [StatsController::class, 'updateLocation']);

    // Admin routes
    Route::prefix('admin')->group(function () { // In real app: add admin middleware
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
    });
});


Route::post('/webhooks/stripe', [BoostController::class, 'handleWebhook'])
    ->middleware(['verify.supabase.webhook']);
