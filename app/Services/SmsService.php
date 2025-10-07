<?php

namespace App\Services;

use GuzzleHttp\Client;

class SmsService
{
    protected $client;
    protected $apiKey;
    protected $senderName;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('SEMAPHORE_API_KEY');
        $this->senderName = env('SEMAPHORE_SENDER_NAME', 'BPLD');
    }

    /**
     * Send SMS via Semaphore
     *
     * @param string|array $number Phone number or array of numbers
     * @param string $message The message to send
     * @return array Response from Semaphore API
     */
    public function sendSms($number, string $message): array
    {
        $numbers = is_array($number) ? implode(',', $number) : $number;

        $response = $this->client->post('https://semaphore.co/api/v4/messages', [
            'form_params' => [
                'apikey' => $this->apiKey,
                'number' => $numbers,
                'message' => $message,
                'sendername' => $this->senderName,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}
