<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Disallowed / sensitive keys that must never be recorded in audit logs.
     */
    protected const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'key_hash',
        'api_key',
        'plain_token',
        'card',
        'cvv',
        'auth_token',
    ];

    /**
     * Record an audit log event.
     */
    public static function log(
        string $action,
        ?int $userId = null,
        ?string $resourceType = null,
        ?string $resourceId = null,
        array $metadata = [],
        ?string $ipAddress = null
    ): AuditLog {
        $sanitizedMetadata = self::sanitizeMetadata($metadata);

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId ? (string) $resourceId : null,
            'ip_address' => $ipAddress ?? (Request::ip() ?? '127.0.0.1'),
            'metadata' => empty($sanitizedMetadata) ? null : $sanitizedMetadata,
        ]);
    }

    /**
     * Recursively sanitize metadata to remove sensitive data and secrets.
     */
    public static function sanitizeMetadata(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            // Check if key contains sensitive terms
            $isSensitive = false;
            foreach (self::SENSITIVE_KEYS as $sensitive) {
                if (str_contains($normalizedKey, $sensitive)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $clean[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = self::sanitizeMetadata($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
