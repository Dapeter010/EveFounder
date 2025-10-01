# Frontend Adjustments Required

## Overview
The backend now implements comprehensive subscription and boost features with detailed error responses, feature gates, and enhanced data. The frontend needs to be updated to handle these new response formats.

## 🚨 CRITICAL BUGS DISCOVERED

### Likes Page (`app/likes/page.tsx`) - Multiple Critical Issues:
1. **❌ Premium users see blurred profiles** - Paywall shows for ALL users, even Premium subscribers
2. **❌ Tab counters always show 0** - Data only loads on tab click, not on page load
3. **❌ API response structure mismatch** - Backend uses `user_id`, frontend expects `id`
4. **❌ Photos array mismatch** - Backend returns string arrays, frontend expects objects

**Impact:** Premium users cannot use the "See Who Liked You" feature they paid for!

---

## 1. 🔴 CRITICAL: Fix Likes Page

### Multiple Critical Issues Found

#### Issue 1A: Tab Counters Show Zero Initially
**Problem:** Lines 168 and 178 in `app/likes/page.tsx` show `({receivedLikes.length})` and `({sentLikes.length})` but data only loads when clicking the tab.

**Current Code:**
```typescript
const [activeTab, setActiveTab] = useState('received');
const [receivedLikes, setReceivedLikes] = useState<Like[]>([]);
const [sentLikes, setSentLikes] = useState<Like[]>([]);

// Only loads when tab changes
useEffect(() => {
    loadLikes();
}, [activeTab]);
```

**Fix Required:**
```typescript
// Load both tabs on mount
useEffect(() => {
    loadAllLikes();
}, []);

const loadAllLikes = async () => {
    setIsLoading(true);
    try {
        // Load both received and sent likes
        const [receivedResult, sentResult] = await Promise.all([
            apiClient.getReceivedLikes(),
            apiClient.getSentLikes()
        ]);

        if (receivedResult.success && receivedResult.data) {
            setReceivedLikes(receivedResult.data);
        } else if (receivedResult.requires_upgrade) {
            // Show paywall - keep empty array
            setShowPaywall(true);
            setReceivedLikes([]);
        }

        if (sentResult.success && sentResult.data) {
            setSentLikes(sentResult.data);
        }
    } catch (error) {
        console.error('Error loading likes:', error);
    } finally {
        setIsLoading(false);
    }
};
```

#### Issue 1B: Received Likes Always Blurred (Even for Premium Users)
**Problem:** Lines 185-202 show the Premium upsell card and blurred photos **unconditionally** - even if the user has Premium access.

**Current Code (WRONG):**
```typescript
{activeTab === 'received' && (
    <div>
        {/* This ALWAYS shows, even for Premium users */}
        <Card className="mb-6 bg-gradient-to-r from-yellow-50 to-orange-50">
            <CardContent className="p-6 text-center">
                <Crown className="w-12 h-12 text-yellow-500 mx-auto mb-4"/>
                <h3>See Who Likes You</h3>
                <p>Upgrade to Premium...</p>
            </CardContent>
        </Card>

        {/* Profiles are ALWAYS blurred */}
        <img src={...} className="blur-md" />
    </div>
)}
```

**Fix Required:**
```typescript
// Add state to track subscription status
const [hasAccess, setHasAccess] = useState(false);
const [showPaywall, setShowPaywall] = useState(false);

const loadAllLikes = async () => {
    setIsLoading(true);
    try {
        const receivedResult = await apiClient.getReceivedLikes();

        if (receivedResult.success && receivedResult.data) {
            setReceivedLikes(receivedResult.data);
            setHasAccess(true);  // User has access
            setShowPaywall(false);
        } else if (receivedResult.requires_upgrade) {
            // User hit paywall
            setHasAccess(false);
            setShowPaywall(true);
            setReceivedLikes([]); // Keep empty
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        setIsLoading(false);
    }
};

// In JSX:
{activeTab === 'received' && (
    <div>
        {/* Only show paywall if user doesn't have access */}
        {showPaywall && (
            <Card className="mb-6 bg-gradient-to-r from-yellow-50 to-orange-50">
                {/* ... upsell content ... */}
            </Card>
        )}

        {/* Only show profiles if user has access */}
        {hasAccess && receivedLikes.length > 0 && (
            <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                {receivedLikes.map((like) => (
                    <Card key={like.id}>
                        {/* NO BLUR for Premium users */}
                        <img
                            src={getProfilePhoto(like.liker?.photos)}
                            className="w-full h-64 object-cover"
                        />
                        {/* Show actual profile data */}
                        <CardContent>
                            <h3>{like.liker?.first_name} {like.liker?.last_name}</h3>
                            <p>{like.age} years old</p>
                            {/* Action buttons */}
                        </CardContent>
                    </Card>
                ))}
            </div>
        )}
    </div>
)}
```

#### Issue 1C: API Response Structure Changed
**Problem:** Backend now uses `user_id` instead of `id` for user identification, and photos are nested in UserProfile.

**Backend Response Structure:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,  // This is the LIKE id
      "liker_id": 45,
      "liked_id": 67,
      "is_super_like": false,
      "status": "pending",
      "created_at": "2025-01-15 10:00:00",
      "age": 28,
      "liker": {
        "user_id": 45,  // ← Note: user_id, not id
        "first_name": "John",
        "last_name": "Doe",
        "date_of_birth": "1997-01-01",
        "location": "London",
        "photos": ["url1", "url2"]  // Array of URLs
      }
    }
  ]
}
```

**Frontend Fix Required:**
```typescript
// Update handleLikeBack and handlePass to use user_id
const handleLikeBack = async (userId: number) => {
    try {
        const result = await apiClient.likeUser(userId.toString(), false);
        if (result.success) {
            // Check for limits
            if (result.limit_reached) {
                alert(result.message);
                return;
            }
            loadAllLikes(); // Refresh
        }
    } catch (error) {
        console.error('Error liking back:', error);
    }
};

// In JSX - use user_id instead of id:
<Button onClick={() => handleLikeBack(like.liker.user_id)}>
    Like Back
</Button>
<Button onClick={() => handlePass(like.liker.user_id)}>
    Pass
</Button>
```

#### Issue 1D: Photos Array Handling
**Problem:** Backend returns photos as simple array of strings, but frontend code expects objects with `url` property.

**Fix getProfilePhoto function:**
```typescript
const getProfilePhoto = (photos: any) => {
    const defaultPhoto = 'https://images.pexels.com/photos/1130626/pexels-photo-1130626.jpeg?auto=compress&cs=tinysrgb&w=400';

    if (!photos || photos.length === 0) {
        return defaultPhoto;
    }

    // Photos is now just an array of strings
    if (typeof photos[0] === 'string') {
        return photos[0];
    }

    // Fallback for old format
    return photos[0]?.url || photos[0] || defaultPhoto;
};
```

---

## 2. 🔴 CRITICAL: Handle Feature Gate Errors

### Issue
When users hit feature limits or try to use premium features, the backend returns `403` status with specific error fields that the frontend currently doesn't handle.

### New Error Response Format

#### Example 1: Advanced Filters (Premium Only)
```json
{
  "success": false,
  "message": "Education filter requires Premium subscription",
  "requires_premium": true
}
```

#### Example 2: See Who Liked You (Basic/Premium Only)
```json
{
  "success": false,
  "message": "Upgrade to Basic or Premium to see who liked you",
  "requires_upgrade": true,
  "feature": "see_who_liked_you"
}
```

#### Example 3: Daily Like Limit Reached
```json
{
  "success": false,
  "message": "Daily like limit reached (10/day). Upgrade to Premium for unlimited likes.",
  "requires_upgrade": true,
  "limit_reached": true
}
```

#### Example 4: Super Like Limit Reached
```json
{
  "success": false,
  "message": "Daily super like limit reached (1/day). Upgrade to Basic for 5/day or Premium for unlimited.",
  "requires_upgrade": true,
  "limit_reached": true
}
```

### Affected Files
- `app/discover/page.tsx` - Discovery filters
- `app/dashboard/page.tsx` - Like/Super Like buttons
- `app/likes/page.tsx` - Received likes view

### Required Changes

#### 1. Update `app/discover/page.tsx`

**Current Code (Line 73-103):**
```typescript
const result = await apiClient.getDiscoverProfiles({
    education: filters.education_level,
    profession: filters.occupation,
    min_height: filters.height[0],
    max_height: filters.height[1],
    page: reset ? 1 : currentPage
});

if (result.success && result.data) {
    // Handle success
} else {
    setError('Failed to load profiles');
}
```

**New Code Needed:**
```typescript
try {
    const result = await apiClient.getDiscoverProfiles({
        education: filters.education_level,
        profession: filters.occupation,
        min_height: filters.height[0],
        max_height: filters.height[1],
        page: reset ? 1 : currentPage
    });

    if (result.success && result.data) {
        // Handle success
        setProfiles(result.data);
    } else if (result.requires_premium) {
        // Show premium upgrade modal
        setError(result.message);
        showPremiumUpgradeModal({
            feature: 'Advanced Filters',
            message: result.message,
            action: 'subscribe'
        });

        // Reset the filter that triggered this
        setFilters(prev => ({
            ...prev,
            education_level: '',
            occupation: '',
            height: [150, 200],
            relationship_goals: ''
        }));
    } else {
        setError(result.message || 'Failed to load profiles');
    }
} catch (error: any) {
    // Handle network errors
    console.error('Error loading profiles:', error);
    setError('Failed to load profiles. Please try again.');
}
```

#### 2. Update `app/dashboard/page.tsx` - Like Handlers

**Current Code (Line 153-175):**
```typescript
const handleLike = async (isSuperLike = false) => {
    if (profiles.length === 0 || isActionLoading) return;

    setIsActionLoading(true);
    try {
        const profile = profiles[currentProfile];
        const response = await apiClient.likeUser(profile.id.toString(), isSuperLike);

        if (response.success) {
            if (response.data?.is_match) {
                alert("🎉 It's a match!");
                loadMatches();
            }
            nextProfile();
        }
    } catch (error) {
        console.error('Error liking user:', error);
    } finally {
        setIsActionLoading(false);
    }
};
```

**New Code Needed:**
```typescript
const handleLike = async (isSuperLike = false) => {
    if (profiles.length === 0 || isActionLoading) return;

    setIsActionLoading(true);
    try {
        const profile = profiles[currentProfile];
        const response = await apiClient.likeUser(profile.id.toString(), isSuperLike);

        if (response.success) {
            if (response.data?.is_match) {
                alert("🎉 It's a match!");
                loadMatches();
            }
            nextProfile();
        } else if (response.limit_reached) {
            // Show limit reached modal with upgrade option
            showLimitReachedModal({
                title: isSuperLike ? 'Super Like Limit Reached' : 'Daily Like Limit Reached',
                message: response.message,
                action: 'upgrade',
                feature: isSuperLike ? 'super_likes' : 'daily_likes'
            });
        } else {
            // Other errors
            alert(response.message || 'Failed to like user');
        }
    } catch (error) {
        console.error('Error liking user:', error);
        alert('Network error. Please try again.');
    } finally {
        setIsActionLoading(false);
    }
};
```

#### 3. Update `app/likes/page.tsx` - Received Likes

**Add check before calling API:**
```typescript
const loadReceivedLikes = async () => {
    setIsLoading(true);
    setError(null);

    try {
        const result = await apiClient.getReceivedLikes();

        if (result.success && result.data) {
            setReceivedLikes(result.data);
        } else if (result.requires_upgrade) {
            // Show paywall for "See Who Liked You"
            setShowPaywall(true);
            setPaywallMessage(result.message);
            setPaywallFeature(result.feature); // "see_who_liked_you"
        } else {
            setError(result.message || 'Failed to load likes');
        }
    } catch (error) {
        console.error('Error loading likes:', error);
        setError('Network error occurred');
    } finally {
        setIsLoading(false);
    }
};
```

---

## 2. 🟡 MEDIUM: Update Subscription Response Handling

### Issue
The subscription API now returns additional fields that the frontend should use.

### New Response Format

**Old Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "plan_type": "premium",
    "status": "active",
    "amount": 19.99,
    "currency": "GBP",
    "starts_at": "2025-01-01 00:00:00",
    "ends_at": "2025-02-01 00:00:00"
  }
}
```

**New Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "plan_type": "premium",
    "status": "active",
    "amount": 19.99,
    "currency": "GBP",
    "price_id": "price_1RvJUZGhlS5RvknCyv6vX6lT",  // ← NEW
    "next_billing": "2025-02-01 00:00:00",          // ← NEW (replaces ends_at for display)
    "starts_at": "2025-01-01 00:00:00",
    "ends_at": "2025-02-01 00:00:00",
    "stripe_subscription_id": "sub_xxxxx",
    "created_at": "2025-01-01 00:00:00",
    "updated_at": "2025-01-01 00:00:00"
  }
}
```

### Affected Files
- `app/subscribe/page.tsx`
- `app/settings/page.tsx`
- `app/dashboard/page.tsx`

### Required Changes

The frontend already uses `price_id` correctly (see `app/subscribe/page.tsx:177`), but should also use `next_billing` instead of trying to access `current_period_end`:

**In `app/settings/page.tsx`:**
```typescript
// Use next_billing instead of ends_at for display
{subscription && (
    <p className="text-sm text-gray-600">
        Next billing: {new Date(subscription.next_billing).toLocaleDateString()}
    </p>
)}
```

---

## 3. 🟢 OPTIONAL: Use Enhanced Stats Endpoint

### Issue
The stats endpoint now returns comprehensive subscription and limit information that can improve UX.

### New Response Format

**GET `/api/stats`:**
```json
{
  "success": true,
  "data": {
    "profile_views_today": 15,
    "likes_received_new": 3,
    "total_matches": 8,
    "unread_messages": 2,

    "subscription": {
      "plan": "free",           // "free" | "basic" | "premium"
      "is_premium": false,
      "is_basic": false,
      "is_free": true
    },

    "daily_limits": {
      "likes_used": 7,
      "likes_limit": 10,
      "likes_remaining": 3,
      "super_likes_used": 1,
      "super_likes_limit": 1,
      "super_likes_remaining": 0
    },

    "features": {
      "unlimited_likes": false,
      "see_who_liked_you": false,
      "advanced_filters": false,
      "read_receipts": false,
      "monthly_boost": false,
      "priority_support": false
    }
  }
}
```

### Suggested Use Cases

#### 1. Show Like Counter in Dashboard
```typescript
// In app/dashboard/page.tsx
const [stats, setStats] = useState(null);

useEffect(() => {
    const loadStats = async () => {
        const result = await apiClient.getUserStats();
        if (result.success) {
            setStats(result.data);
        }
    };
    loadStats();
}, []);

// Display in UI
{stats && stats.daily_limits && (
    <div className="text-sm text-gray-600">
        {stats.subscription.is_free && (
            <p>
                Likes today: {stats.daily_limits.likes_used}/{stats.daily_limits.likes_limit}
            </p>
        )}
        {stats.daily_limits.super_likes_remaining === 0 && (
            <p className="text-orange-600">No super likes remaining today</p>
        )}
    </div>
)}
```

#### 2. Proactively Show Upgrade Prompt
```typescript
// Show upgrade prompt when user is close to limit
{stats && stats.daily_limits.likes_remaining <= 2 && stats.subscription.is_free && (
    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <p className="text-sm">
            Only {stats.daily_limits.likes_remaining} likes remaining today!
            <Link href="/subscribe" className="text-blue-600 ml-2">
                Upgrade for unlimited likes →
            </Link>
        </p>
    </div>
)}
```

#### 3. Disable Advanced Filters for Free Users
```typescript
// In discover/page.tsx - disable premium filters
{!stats?.features.advanced_filters && (
    <div className="relative">
        <Select disabled>
            <SelectTrigger>
                <SelectValue placeholder="Education (Premium)" />
            </SelectTrigger>
        </Select>
        <div className="absolute inset-0 flex items-center justify-center bg-gray-50/80 rounded">
            <Crown className="w-4 h-4 text-yellow-600 mr-1" />
            <span className="text-xs text-gray-600">Premium</span>
        </div>
    </div>
)}
```

---

## 4. 📋 Complete TypeScript Interface Updates

### Update `lib/api.ts` Interfaces

Add new response fields:

```typescript
// Update ApiResponse to include error fields
export interface ApiResponse<T> {
    success: boolean;
    message?: string;
    data?: T;
    // NEW - Feature gate fields
    requires_premium?: boolean;
    requires_upgrade?: boolean;
    limit_reached?: boolean;
    feature?: string;
}

// Update SubscriptionItem interface
export interface SubscriptionItem {
    id: number;
    plan_type: 'basic' | 'premium';
    status: 'active' | 'cancelled' | 'expired' | 'pending';
    amount: number;
    currency: string;
    price_id: string;              // NEW
    next_billing: string;          // NEW
    starts_at: string;
    ends_at: string;
    stripe_subscription_id?: string;
    created_at: string;
    updated_at: string;
}

// NEW - Stats response interface
export interface UserStats {
    profile_views_today: number;
    likes_received_new: number;
    total_matches: number;
    unread_messages: number;
    subscription: {
        plan: 'free' | 'basic' | 'premium';
        is_premium: boolean;
        is_basic: boolean;
        is_free: boolean;
    };
    daily_limits: {
        likes_used: number;
        likes_limit: number | null;
        likes_remaining: number | null;
        super_likes_used: number;
        super_likes_limit: number | null;
        super_likes_remaining: number | null;
    };
    features: {
        unlimited_likes: boolean;
        see_who_liked_you: boolean;
        advanced_filters: boolean;
        read_receipts: boolean;
        monthly_boost: boolean;
        priority_support: boolean;
    };
}
```

---

## 5. 🎨 UI Components to Create

### 1. Premium Upgrade Modal Component

Create `components/PremiumUpgradeModal.tsx`:
```typescript
interface PremiumUpgradeModalProps {
    isOpen: boolean;
    onClose: () => void;
    feature: string;
    message: string;
    requiredPlan?: 'basic' | 'premium';
}

export function PremiumUpgradeModal({ isOpen, onClose, feature, message, requiredPlan = 'premium' }: PremiumUpgradeModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Crown className="w-6 h-6 text-yellow-600" />
                        Upgrade to {requiredPlan === 'premium' ? 'Premium' : 'Basic'}
                    </DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                    <p className="text-gray-600">{message}</p>
                    <div className="bg-gradient-to-r from-purple-50 to-pink-50 p-4 rounded-lg">
                        <h4 className="font-semibold mb-2">This feature includes:</h4>
                        <ul className="space-y-1 text-sm">
                            {requiredPlan === 'premium' && (
                                <>
                                    <li>✓ Advanced filters (education, profession, height)</li>
                                    <li>✓ Unlimited super likes</li>
                                    <li>✓ Read receipts</li>
                                    <li>✓ Monthly profile boost</li>
                                </>
                            )}
                            {requiredPlan === 'basic' && (
                                <>
                                    <li>✓ See who liked you</li>
                                    <li>✓ 5 super likes per day</li>
                                    <li>✓ Message priority</li>
                                </>
                            )}
                        </ul>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>Maybe Later</Button>
                    <Link href="/subscribe">
                        <Button className="bg-gradient-to-r from-purple-600 to-pink-600">
                            Upgrade Now
                        </Button>
                    </Link>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
```

### 2. Limit Reached Modal Component

Create `components/LimitReachedModal.tsx`:
```typescript
interface LimitReachedModalProps {
    isOpen: boolean;
    onClose: () => void;
    title: string;
    message: string;
    feature: 'daily_likes' | 'super_likes';
}

export function LimitReachedModal({ isOpen, onClose, title, message, feature }: LimitReachedModalProps) {
    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>
                <div className="space-y-4">
                    <p className="text-gray-600">{message}</p>
                    <div className="bg-blue-50 p-4 rounded-lg">
                        <p className="text-sm text-gray-600">
                            Your daily limit will reset at midnight. Or upgrade now for unlimited access!
                        </p>
                    </div>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose}>Got it</Button>
                    <Link href="/subscribe">
                        <Button>View Plans</Button>
                    </Link>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
```

---

## 6. 📝 Summary Checklist

### Critical (Must Do)
- [ ] Add error handling for `requires_premium` responses in `app/discover/page.tsx`
- [ ] Add error handling for `requires_upgrade` responses in `app/dashboard/page.tsx`
- [ ] Add error handling for `limit_reached` responses in like handlers
- [ ] Add paywall check in `app/likes/page.tsx` for "See Who Liked You"
- [ ] Update TypeScript interfaces in `lib/api.ts`

### Recommended (Should Do)
- [ ] Create `PremiumUpgradeModal` component
- [ ] Create `LimitReachedModal` component
- [ ] Use `next_billing` field from subscription response
- [ ] Add stats endpoint call to dashboard
- [ ] Show like/super like counters to free users

### Optional (Nice to Have)
- [ ] Proactive upgrade prompts when limits are close
- [ ] Visual indicators on premium features (crown icon)
- [ ] Disable premium filters with upgrade overlay
- [ ] Show feature availability based on `stats.features`

---

## 7. 🧪 Testing Checklist

After implementing changes, test:

### Free User Testing
- [ ] Try to use education filter → Should show premium modal
- [ ] Send 10 regular likes → 11th should show limit modal
- [ ] Send 1 super like → 2nd should show limit modal
- [ ] Try to view "Who Liked You" → Should show paywall

### Basic User Testing
- [ ] Can view "Who Liked You" ✓
- [ ] Has unlimited regular likes ✓
- [ ] Send 5 super likes → 6th should show limit modal
- [ ] Cannot use education/profession filters → Should show premium modal

### Premium User Testing
- [ ] Can use all advanced filters ✓
- [ ] Has unlimited regular likes ✓
- [ ] Has unlimited super likes ✓
- [ ] Can view "Who Liked You" ✓

---

## 8. 🔗 Related Documentation

- Backend CLAUDE.md: `/Users/qwerty/www/Personal/evefounder/CLAUDE.md`
- Backend Controllers:
  - `app/Http/Controllers/Api/DiscoveryController.php`
  - `app/Http/Controllers/Api/MatchController.php`
  - `app/Http/Controllers/Api/StatsController.php`
  - `app/Http/Controllers/Api/SubscriptionController.php`
- Frontend API Client: `lib/api.ts`

---

## Migration Path

1. **Phase 1 (Critical):** Add error handling for all feature gates
2. **Phase 2 (UX):** Create modal components and update UI
3. **Phase 3 (Enhancement):** Add stats-based features and proactive prompts
4. **Phase 4 (Polish):** Add visual indicators and loading states

**Estimated Implementation Time:** 4-6 hours
