<?php

class Fcm_lib
{
    protected $server_key;

    public function __construct()
    {
        // Ideally load from ENV or config
        $this->server_key = 'AIzaSyAfwx-VlcJ90bG3ydhRiVvpQL5nXAUH0hE';
    }

    public function sendNotification($tokens, $title, $body)
    {
        $url = "https://fcm.googleapis.com/fcm/send";

        $fieldKey   = is_array($tokens) ? 'registration_ids' : 'to';
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
            'Authorization: key=' . $this->server_key,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $result      = curl_exec($ch);
        $httpStatus  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error       = curl_error($ch);
        curl_close($ch);

        if ($result === false) {
            log_message('error', '[FCM_LIB] Curl error: ' . $error);
            return false;
        }

        log_message('debug', '[FCM_LIB] HTTP Status: ' . $httpStatus);
        log_message('debug', '[FCM_LIB] Payload: ' . json_encode($payload));
        log_message('debug', '[FCM_LIB] Response: ' . $result);

        return json_decode($result, true);
    }
}