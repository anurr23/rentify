<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Konfigurasi 9router lokal.
 * API key hanya dipakai di server. Jangan pernah mengirimkannya ke JavaScript.
 *
 * Prioritas konfigurasi:
 * 1. Environment variable OS/Docker
 * 2. File .env di root proyek
 * 3. Fallback legacy config/ai.local.php
 */
$localFile = __DIR__ . '/ai.local.php';
$local = is_file($localFile) ? require $localFile : [];
$inContainer = is_file('/.dockerenv');
$baseUrl = $inContainer
    ? (env_os('RENTIFY_AI_BASE_URL') ?? env_non_empty('RENTIFY_AI_DOCKER_BASE_URL') ?? env_non_empty('RENTIFY_AI_BASE_URL') ?? (string) ($local['docker_base_url'] ?? 'http://host.docker.internal:20128/v1'))
    : (env_non_empty('RENTIFY_AI_BASE_URL') ?? (string) ($local['base_url'] ?? 'http://localhost:20128/v1'));

$envApiKey = env('RENTIFY_AI_KEY');

return [
    'base_url' => rtrim($baseUrl, '/'),
    'api_key' => $envApiKey !== null ? $envApiKey : (string) ($local['api_key'] ?? ''),
    'model' => env_non_empty('RENTIFY_AI_MODEL') ?? (string) ($local['model'] ?? 'ag/gemini-3.8-flash-medium'),
    'fallback_model' => env_non_empty('RENTIFY_AI_FALLBACK_MODEL') ?? (string) ($local['fallback_model'] ?? 'cl/google/gemini-3.8-flash'),
    'timeout' => (int) (env_non_empty('RENTIFY_AI_TIMEOUT') ?? ($local['timeout'] ?? 60)),
    'connect_timeout' => 8,
    'temperature' => 0.2,
    'max_tokens' => 1600,
    'cache_ttl' => 86400,
    'rate_limit_per_hour' => 20,
];
