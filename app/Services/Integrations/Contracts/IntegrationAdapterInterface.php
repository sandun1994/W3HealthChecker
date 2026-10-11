<?php

namespace App\Services\Integrations\Contracts;

use App\Models\Integration;

interface IntegrationAdapterInterface
{
    /**
     * Dispatch an event payload to the external provider.
     */
    public function dispatch(Integration $integration, string $event, array $payload): array;

    /**
     * Verify connectivity and credentials.
     */
    public function testConnection(Integration $integration): array;
}
