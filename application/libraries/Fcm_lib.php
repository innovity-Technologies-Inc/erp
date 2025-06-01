<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Fcm_lib {

    protected $CI;
    private $server_key = 'YOUR_SERVER_KEY_HERE'; // 🔁 Replace with your FCM server key

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    public function sendNotification($fcmToken, $title, $body)
    {
        $url = "https://fcm.googleapis.com/fcm/send";

        $payload = [
            "to" => $fcmToken,
            "notification" => [
                "title" => $title,
                "body" => $body,
                "sound" => "default"
            ],
            "priority" => "high"
        ];

        $headers = [
            'Authorization: key=' . $this->server_key,
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable in dev
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_message('info', "🔔 FCM response for token={$fcmToken} | HTTP Code: {$http_code} | Response: {$result}");
        return $result;
    }
}