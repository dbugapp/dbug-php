<?php

namespace DbugApp;

class Dbug
{
    protected static string $endpoint = 'http://127.0.0.1:53821';

    public static function setEndpoint(string $endpoint): void
    {
        self::$endpoint = $endpoint;
    }

    public static function send(array $payload): void
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);

        if ($json === false) {
            $json = json_encode([
                'error' => 'Serialization failed',
                'reason' => json_last_error_msg(),
            ], JSON_PRETTY_PRINT);
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $json,
                'timeout' => 0.5,
            ],
        ]);

        @file_get_contents(self::$endpoint, false, $context);
    }

    private static function stringify($payload): string
    {
        try {
            return json_encode(self::sanitize($payload), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return json_encode([
                'error' => 'Serialization failed',
                'reason' => $e->getMessage(),
            ], JSON_PRETTY_PRINT);
        }
    }

    private static function sanitize($data, &$seen = []): mixed
    {
        if (is_object($data) || is_array($data)) {
            if (in_array($data, $seen, true)) {
                return '[circular]';
            }
            $seen[] = $data;
        }

        if (is_object($data)) {
            $sanitized = ['__class' => get_class($data)];
            foreach (get_object_vars($data) as $key => $value) {
                $sanitized[$key] = self::sanitize($value, $seen);
            }

            return $sanitized;
        }

        if (is_array($data)) {
            return array_map(fn ($v) => self::sanitize($v, $seen), $data);
        }

        if (is_resource($data)) {
            return '[resource]';
        }

        return $data;
    }
}
