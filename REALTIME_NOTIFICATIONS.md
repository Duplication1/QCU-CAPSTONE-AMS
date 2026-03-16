# Realtime Notifications (WebSocket)

## What was added
- WebSocket server: `realtime/notification-server.js`
- PHP push helper: `controller/realtime_notification_helper.php`
- Header client listener (shared): `view/components/header.php`

## Run WebSocket server
```bash
npm run dev:ws
```

Default port is `8081`.

## Optional `.env` settings
- `WS_PORT=8081`
- `WS_NOTIFY_URL=http://127.0.0.1:8081/notify`
- `WS_NOTIFY_SECRET=your-secret`

If `WS_NOTIFY_SECRET` is set, configure the same secret in the WebSocket server environment.

## How it works
1. Browser connects to WS server with current `user_id`.
2. PHP controllers insert notifications into DB.
3. Controllers call `pushRealtimeNotifications([...userIds])`.
4. WS server pushes `{"event":"notification"}` to matching users.
5. Client receives event and calls `loadNotifications()` immediately.

Polling every 30s remains as fallback.
