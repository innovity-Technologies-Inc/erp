<?php

// application/helpers/firebase_helper.php

if (!function_exists('send_firebase_notification')) {
    function send_firebase_notification($tokens, $title, $body)
    {
        $url = "https://fcm.googleapis.com/fcm/send";
        // $serverKey = "YOUR_SERVER_KEY"; // Replace with your actual Firebase Server Key
        $serverKey = "AIzaSyAfwx-VlcJ90bG3ydhRiVvpQL5nXAUH0hE";
        // Support both single token and multiple
        $fieldKey = is_array($tokens) ? 'registration_ids' : 'to';
        $fieldValue = $tokens;

        $payload = [
            $fieldKey => $fieldValue,
            'notification' => [
                'title' => $title,
                'body'  => $body,
                'sound' => 'default',
            ],
            'priority' => 'high',
        ];

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $result = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            log_message('error', '[FCM] Curl failed: ' . $error);
            return false;
        }

        $decoded = json_decode($result, true);
        log_message('debug', '[FCM] HTTP Status: ' . $httpStatus);
        log_message('debug', '[FCM] Payload: ' . json_encode($payload));
        log_message('debug', '[FCM] Response: ' . $result);

        return $decoded;
    }
}