<?php
if (!function_exists('pushRealtimeNotifications')) {
    function pushRealtimeNotifications(array $userIds): void {
        $normalized = [];
        foreach ($userIds as $userId) {
            $value = (int) $userId;
            if ($value > 0) {
                $normalized[$value] = true;
            }
        }

        $targetIds = array_keys($normalized);
        if (empty($targetIds)) {
            return;
        }

        $notifyUrl = 'http://127.0.0.1:8081/notify';
        $secret = '';

        if (class_exists('Config')) {
            $notifyUrl = Config::get('WS_NOTIFY_URL', $notifyUrl);
            $secret = Config::get('WS_NOTIFY_SECRET', '');
        }

        if (!$notifyUrl) {
            return;
        }

        $payload = json_encode([
            'event' => 'notification',
            'user_ids' => $targetIds
        ]);

        if ($payload === false) {
            return;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($notifyUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 300);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 800);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_filter([
                'Content-Type: application/json',
                $secret ? ('X-Notify-Secret: ' . $secret) : null
            ]));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_exec($ch);
            curl_close($ch);
            return;
        }

        $headers = "Content-Type: application/json\r\n";
        if ($secret) {
            $headers .= 'X-Notify-Secret: ' . $secret . "\r\n";
        }

        @file_get_contents($notifyUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => $payload,
                'timeout' => 0.8
            ]
        ]));
    }
}
