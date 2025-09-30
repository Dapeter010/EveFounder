# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the **Laravel 11 backend API** for EveFound, a premium dating platform for UK singles. The frontend is a separate Next.js 13 project located at `~/www/Personal/evefound`.

**Tech Stack:**
- Laravel 11 with Sanctum authentication
- MySQL/SQLite database
- Laravel Reverb for WebSocket real-time features
- Stripe for payment processing
- Laravel Pail for log monitoring
- Vite for frontend asset compilation (minimal frontend, mainly API-driven)

**Key Features:**
- RESTful API for all platform operations
- Real-time messaging via Laravel Reverb (WebSocket)
- AI-powered matching (frontend handles OpenAI integration)
- Stripe payments for subscriptions and profile boosts
- Comprehensive admin dashboard API
- Queue-based email notifications

## Development Commands

### Starting the Development Environment
```bash
# Full development environment (server, queue, logs, vite) - recommended
composer dev

# Individual services
php artisan serve          # Start Laravel dev server (port 8000)
php artisan queue:listen   # Start queue worker for jobs
php artisan pail           # View logs in real-time
npm run dev                # Start Vite dev server for assets
php artisan reverb:start   # Start WebSocket server for real-time features
```

### Database Operations
```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed # Fresh database with seeders
php artisan migrate:rollback     # Rollback last migration
php artisan db:seed              # Run seeders only
```

### Testing
```bash
php artisan test         # Run PHPUnit tests
./vendor/bin/phpunit     # Alternative test runner
```

### Code Quality
```bash
./vendor/bin/pint        # Format code using Laravel Pint
```

### Asset Building
```bash
npm run build            # Build production assets
npm run dev              # Development mode with hot reload
```

## Frontend Integration

The Next.js frontend communicates with this backend via:
- **API Base URL**: `http://localhost:8000/api` (in development)
- **Authentication**: Bearer token in `Authorization` header (managed by frontend's `ApiClient` in `lib/api.ts`)
- **WebSocket**: Laravel Reverb on port 6001 for real-time messaging
- **Frontend Location**: `~/www/Personal/evefound`
- **Frontend CLAUDE.md**: See `~/www/Personal/evefound/CLAUDE.md` for frontend architecture

## Architecture Overview

### Authentication & Authorization
- **Laravel Sanctum** for API token authentication (primary auth method)
- **Laravel Passport** installed (OAuth2 support available)
- Tokens issued on login via `/api/login` and `/api/register`
- Frontend stores token in localStorage and includes it in all API requests
- Admin routes exist at `/api/admin/*` but currently lack middleware protection
- Real-time broadcasting auth via `/api/broadcasting/auth`

### Core Models & Relationships
- **User**: Central model with relationships to photos, preferences, subscription, likes (sent/received), matches, messages, profile boosts, profile views, notifications, and user profile
- **Matcher**: Represents matches between two users (user1_id, user2_id), stores matched_at timestamp and is_active status
- **Like**: Tracks user likes (liker_id, liked_id) - mutual likes create a Match
- **Message**: Stores messages between matched users with sender_id, receiver_id, and match_id
- **Subscription**: Handles Basic/Premium subscription tiers with Stripe integration
- **ProfileBoost**: Paid visibility boosts with Stripe checkout sessions
- **UserProfile**: Extended profile data separate from core User model
- **UserPreference**: Stores matching preferences (age range, distance, gender preferences)

### Real-time Features (Laravel Reverb)
- Configuration in `config/reverb.php` and `resources/js/echo.js`
- Uses Pusher protocol with Laravel Echo client
- Frontend connects via `lib/websocket.ts` in Next.js app
- WebSocket connections for:
  - Real-time messaging (MessageSent event on `match.{matchId}` private channels)
  - Typing indicators (UserTyping event)
  - Match notifications
- Reverb server runs on port 6001 (configurable via REVERB_PORT)
- WSS/TLS support via REVERB_SCHEME environment variable
- Broadcasting authentication endpoint: `/api/broadcasting/auth` (requires Sanctum token)

### Payment Integration
- **Stripe** for subscriptions and profile boosts
- Models: StripeCustomer, StripeSubscription, StripeOrder
- Webhook endpoint: `/api/webhooks/stripe` with custom middleware verification
- Checkout session creation via `/api/boosts/checkout` and `/api/stripe/checkout`
- **Note**: Frontend documentation mentions Supabase Edge Functions for payments, but this backend handles Stripe directly

### Distance Calculation
- User model includes `distanceFrom()` method using Haversine formula
- Calculates distances in miles using latitude/longitude
- Used for discovery filtering and match recommendations

### Queue System
- Queue driver configurable (default: database)
- Jobs: SendWelcomeMail for new user onboarding
- Queue worker should run in production: `php artisan queue:work`

### API Structure
All API routes are in `routes/api.php`:
- **Public routes**: `/api/register`, `/api/login`, `/api/filters/options`
- **Protected routes** (require `auth:sanctum` middleware):
  - **Profile**: `/api/me`, `/api/profile` (PUT)
  - **Discovery**: `/api/discover`, `/api/users/{targetUser}/like`, `/api/users/{targetUser}/pass`
  - **Matches**: `/api/matches`, `/api/likes/received`, `/api/likes/sent`
  - **Messaging**: `/api/conversations`, `/api/conversations/{matchId}/messages`, `/api/conversations/{matchId}/typing`
  - **Photos**: `/api/photos` (POST/PUT/DELETE)
  - **Reporting**: `/api/reports`, `/api/users/{userId}/block`, `/api/blocked-users`
  - **Boosts**: `/api/boosts`, `/api/boosts/checkout`, `/api/boosts/history`, `/api/boosts/current`
  - **Subscriptions**: `/api/subscription`, `/api/subscription/cancel`, `/api/stripe/checkout`
  - **Settings**: `/api/settings`, `/api/settings/notifications`, `/api/settings/privacy`, `/api/settings/discovery`
  - **Stats**: `/api/stats`, `/api/location/update`
  - **Verification**: `/api/verification/photo`, `/api/verification/status`
  - **Admin**: `/api/admin/*` (currently unprotected - needs AdminMiddleware)

Frontend's `ApiClient` class (`lib/api.ts`) provides typed methods for all these endpoints.

## Important Implementation Details

### User Online Status
- `isOnline()` method checks if last_active_at is within 15 minutes
- Update last_active_at via `/api/location/update` endpoint

### Photo Management
- Photos stored with order field for sequencing
- Primary photo should have order = 1
- UserPhoto model linked to User via hasMany relationship

### Match Creation
- Matches require mutual likes between two users
- Discovery system handles like/pass logic
- Matches table stores bidirectional relationships (user1_id, user2_id)

### Admin Dashboard
- Full admin routes defined but middleware not enforced
- When adding admin features, apply AdminMiddleware to routes
- Dashboard at `/api/admin/dashboard` provides overview stats

### Subscription Tiers
- Basic: Free tier with limited features
- Premium: Paid tier with advanced features (unlimited likes, see who liked you, etc.)
- Check via `$user->isPremium()` or `$user->hasActiveSubscription()`

### Profile Boosts
- Time-limited visibility increases
- Stripe payment integration with session-based checkout
- Boost status tracked via pending/active/completed states

## Environment Configuration

Key environment variables (see `.env.example`):
- **Database**: SQLite by default, MySQL/PostgreSQL supported
- **Reverb**: REVERB_APP_KEY, REVERB_APP_SECRET, REVERB_APP_ID, REVERB_HOST, REVERB_PORT, REVERB_SCHEME
- **Stripe**: STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET
- **Queue**: QUEUE_CONNECTION (database, redis, etc.)
- **Mail**: MAIL_MAILER, MAIL_HOST (for welcome emails)

**Frontend Environment Variables** (in `~/www/Personal/evefound/.env.local`):
- NEXT_PUBLIC_API_URL=http://localhost:8000/api
- NEXT_PUBLIC_REVERB_APP_KEY (must match backend REVERB_APP_KEY)
- NEXT_PUBLIC_REVERB_HOST (must match backend REVERB_HOST)
- NEXT_PUBLIC_REVERB_PORT (must match backend REVERB_PORT)
- NEXT_PUBLIC_REVERB_SCHEME (must match backend REVERB_SCHEME)

## Common Development Tasks

### Running Both Frontend and Backend
```bash
# Terminal 1 - Backend (this repo)
cd ~/www/Personal/evefounder
composer dev  # Starts Laravel server, queue, logs, vite

# Terminal 2 - Backend WebSocket server
cd ~/www/Personal/evefounder
php artisan reverb:start

# Terminal 3 - Frontend
cd ~/www/Personal/evefound
npm run dev
```

### Adding a New API Endpoint
1. Add route to `routes/api.php` with appropriate middleware
2. Create or update controller in `app/Http/Controllers/Api/`
3. Add corresponding method to frontend's `ApiClient` class in `~/www/Personal/evefound/lib/api.ts`
4. Update TypeScript interfaces in frontend if needed

### Working with Real-time Features
1. Backend: Create event class in `app/Events/` (extends `ShouldBroadcast`)
2. Backend: Dispatch event via `broadcast(new EventName($data))`
3. Backend: Define channel authorization in `routes/channels.php`
4. Frontend: Subscribe to channel in `lib/websocket.ts`
5. Test with both backend Reverb server and frontend running

### Database Changes
1. Create migration: `php artisan make:migration migration_name`
2. Update model relationships and fillable fields
3. Run migration: `php artisan migrate`
4. Update seeders if needed: `database/seeders/`

## Known Issues

- **AdminMiddleware** exists (`app/Http/Middleware/AdminMiddleware.php`) but not applied to `/api/admin/*` routes
- **AuthController** extends non-existent `App\Http\Controllers\Api\Controller` class (causes `route:list` errors)
- **Webhook middleware** named `VerifySupabaseWebhook` but actually verifying Stripe webhooks (naming inconsistency)
- **Payment handling**: Frontend docs mention Supabase Edge Functions for payments, but backend handles Stripe directly - clarify which approach is active
