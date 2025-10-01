# Browser Notifications System for EveFound

## Overview
The backend now broadcasts real-time notification events through Laravel Reverb (Pusher protocol) for all user activities. The frontend can listen to these events and display browser notifications to users.

## How It Works

### 1. Backend Broadcasting
The backend broadcasts notification events through Reverb on the user's private channel: `private-user.{userId}`

### 2. Frontend Listening
The frontend subscribes to the user's private channel and listens for notification events, then displays browser notifications using the Notification API.

---

## Notification Events

### Event 1: New Match Notification
**Event Name:** `notification.newMatch`
**Channel:** `private-user.{userId}`

**Triggered When:** Two users like each other (mutual match)

**Payload:**
```json
{
  "type": "new_match",
  "title": "It's a Match! 💕",
  "body": "You and Sarah liked each other!",
  "match": {
    "id": 123,
    "first_name": "Sarah",
    "photo": "https://example.com/photo.jpg"
  },
  "url": "/messages?match=123",
  "timestamp": "2025-10-01T18:30:00.000000Z"
}
```

---

### Event 2: New Like Notification
**Event Name:** `notification.newLike`
**Channel:** `private-user.{userId}`

**Triggered When:** Someone likes the user's profile

**Payload:**
```json
{
  "type": "new_like",
  "title": "Someone likes you! 😍",
  "body": "You have a new like. Check who it is!",
  "url": "/likes",
  "timestamp": "2025-10-01T18:30:00.000000Z"
}
```

---

### Event 3: New Message Notification
**Event Name:** `message.sent`
**Channel:** `private-user.{userId}`

**Triggered When:** User receives a new message

**Payload:**
```json
{
  "message": {
    "id": 456,
    "match_id": 123,
    "sender_id": 789,
    "receiver_id": 101,
    "content": "Hey! How are you?",
    "created_at": "2025-10-01T18:30:00.000000Z"
  },
  "sender": {
    "id": 789,
    "first_name": "Sarah",
    "photos": [
      {
        "url": "https://example.com/photo.jpg"
      }
    ]
  }
}
```

---

### Event 4: Match Online Notification
**Event Name:** `notification.matchOnline`
**Channel:** `private-user.{userId}`

**Triggered When:** A match comes online

**Payload:**
```json
{
  "type": "match_online",
  "title": "Sarah is online",
  "body": "Say hello! 👋",
  "match": {
    "id": 123,
    "first_name": "Sarah",
    "photo": "https://example.com/photo.jpg"
  },
  "url": "/messages?match=123",
  "timestamp": "2025-10-01T18:30:00.000000Z"
}
```

---

## Frontend Implementation Example

### Step 1: Request Notification Permission
```typescript
useEffect(() => {
  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
  }
}, []);
```

### Step 2: Subscribe to Notification Events
```typescript
useEffect(() => {
  const token = localStorage.getItem('auth_token');
  if (!token) return;

  const pusher = new Pusher(process.env.NEXT_PUBLIC_REVERB_APP_KEY!, {
    wsHost: process.env.NEXT_PUBLIC_REVERB_HOST,
    wsPort: parseInt(process.env.NEXT_PUBLIC_REVERB_PORT || '6001'),
    forceTLS: process.env.NEXT_PUBLIC_REVERB_SCHEME === 'https',
    enabledTransports: ['ws', 'wss'],
    cluster: 'eu',
    authEndpoint: `${process.env.NEXT_PUBLIC_API_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  });

  const userId = getCurrentUserId();
  const channel = pusher.subscribe(`private-user.${userId}`);

  // Listen for new match notifications
  channel.bind('notification.newMatch', (data: any) => {
    showNotification(data.title, data.body, data.match?.photo, data.url);
  });

  // Listen for new like notifications
  channel.bind('notification.newLike', (data: any) => {
    showNotification(data.title, data.body, null, data.url);
  });

  // Listen for new message notifications
  channel.bind('message.sent', (data: any) => {
    if (data.message.sender_id !== getCurrentUserId()) {
      const photo = data.sender.photos?.[0]?.url;
      showNotification(
        `${data.sender.first_name} sent you a message`,
        data.message.content,
        photo,
        `/messages?match=${data.message.match_id}`
      );
    }
  });

  // Listen for match online notifications
  channel.bind('notification.matchOnline', (data: any) => {
    showNotification(data.title, data.body, data.match?.photo, data.url);
  });

  return () => {
    pusher.disconnect();
  };
}, []);
```

### Step 3: Show Browser Notification
```typescript
const showNotification = (
  title: string,
  body: string,
  icon?: string | null,
  url?: string
) => {
  if ('Notification' in window && Notification.permission === 'granted') {
    const notification = new Notification(title, {
      body,
      icon: icon || '/icons/icon-192x192.png',
      badge: '/icons/icon-192x192.png',
      tag: 'evefound-notification',
      vibrate: [200, 100, 200],
      requireInteraction: false,
    });

    notification.onclick = () => {
      window.focus();
      if (url) {
        window.location.href = url;
      }
      notification.close();
    };

    // Auto-close after 5 seconds
    setTimeout(() => notification.close(), 5000);
  }
};
```

---

## Important Notes

### Notification Permissions
- Always request notification permission on app load or when user enables notifications in settings
- Check permission status before showing notifications: `Notification.permission === 'granted'`

### Notification Best Practices
1. **Don't spam users** - Only show notifications for important events
2. **Respect user preferences** - Allow users to disable notifications in settings
3. **Make notifications actionable** - Always include a URL to navigate to when clicked
4. **Use appropriate icons** - Show user photos for personalized notifications
5. **Auto-dismiss** - Close notifications after 5-10 seconds automatically

### Online Status Detection
- To trigger `notification.matchOnline` events, the frontend needs to update the user's online status
- Send periodic heartbeat requests to `/api/location/update` or implement a dedicated online status endpoint

### Testing Notifications
1. Open browser console
2. Subscribe to your user channel
3. Trigger actions (like someone, get matched, send message)
4. Check if notifications appear

### Browser Compatibility
- Modern browsers (Chrome, Firefox, Safari, Edge) support the Notification API
- PWAs can show notifications even when the app is closed (requires Service Worker)
- iOS Safari has limited support - works best when app is installed as PWA

---

## Email Configuration

For email notifications (verification codes, etc.), configure `.env`:

```env
# Development (Mailtrap)
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@evefound.com"
MAIL_FROM_NAME="EveFound"

# Production (SendGrid example)
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
```

---

## Backend Events Reference

All notification events are located in: `app/Events/`

- `NewMatchNotification.php` - Broadcasts when two users match
- `NewLikeNotification.php` - Broadcasts when someone likes a user
- `MatchOnlineNotification.php` - Broadcasts when a match comes online
- `MessageSent.php` - Broadcasts when a new message is sent (already exists)

---

## Troubleshooting

### Notifications not showing?
1. Check if notification permission is granted: `Notification.permission`
2. Verify Reverb connection is established in browser console
3. Check if you're subscribed to the correct channel: `private-user.{userId}`
4. Ensure events are being broadcasted from backend (check Laravel logs)

### Messages received but no notification?
1. Check if `message.sent` event listener is properly bound
2. Verify the sender_id check is working (don't notify for own messages)
3. Test with browser console open to see event data

### Match notifications not working?
1. Verify both users are liking each other (mutual like)
2. Check backend logs for broadcast errors
3. Ensure `NewMatchNotification` event is properly fired in DiscoveryController

---

Last Updated: 2025-10-01
