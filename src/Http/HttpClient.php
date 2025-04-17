<?php

declare(strict_types=1);

namespace Croft\Http;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HttpClient
{
    private const USER_AGENT = 'Croft/1.0 (+https://github.com/ashleyhindle/croft)';

    private static ?self $instance = null;

    private function __construct()
    {
        // Private constructor to enforce singleton pattern
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function get(string $url, array $headers = []): Response
    {
        return Http::withHeaders(array_merge([
            'User-Agent' => self::USER_AGENT,
        ], $headers))->get($url);
    }

    public function post(string $url, array $data = [], array $headers = []): Response
    {
        return Http::withHeaders(array_merge([
            'User-Agent' => self::USER_AGENT,
        ], $headers))->post($url, $data);
    }

    // Add other HTTP methods as needed (put, patch, delete, etc.)
}
