<?php

declare(strict_types=1);

namespace Modules\Users\Services;

final class UiErrorReportSanitizer
{
    private const MASKED_VALUE = "[masked]";
    private const SENSITIVE_KEYS = [
        "password",
        "pass",
        "token",
        "authorization",
        "cookie",
        "secret",
        "api_key",
        "access_token",
        "refresh_token",
        "phone",
        "email",
    ];

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        return $this->maskRecursively($payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function maskRecursively(array $payload): array
    {
        $masked = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = strtolower((string) $key);
            if ($this->isSensitiveKey($normalizedKey)) {
                $masked[$key] = self::MASKED_VALUE;
                continue;
            }

            if (is_array($value)) {
                $masked[$key] = $this->maskRecursively($value);
                continue;
            }

            if (is_string($value)) {
                $masked[$key] = $this->sanitizeString($value);
                continue;
            }

            $masked[$key] = $value;
        }

        return $masked;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeString(string $value): string
    {
        $sanitized = $value;

        $sanitized = (string) preg_replace(
            "/([A-Z0-9._%+-]+@[A-Z0-9.-]+\\.[A-Z]{2,})/i",
            self::MASKED_VALUE,
            $sanitized,
        );

        $sanitized = (string) preg_replace(
            "/\\b(token|access_token|refresh_token|password)=([^&\\s]+)/i",
            "$1=" . self::MASKED_VALUE,
            $sanitized,
        );

        $sanitized = (string) preg_replace(
            "/Bearer\\s+[A-Za-z0-9._\\-]+/i",
            "Bearer " . self::MASKED_VALUE,
            $sanitized,
        );

        return $sanitized;
    }
}
