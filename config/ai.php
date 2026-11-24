<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * Konfigurasi AI (OpenAI-compatible: 9router, DeepSeek, OpenAI, dll).
 * API key hanya dipakai di server. Jangan pernah mengirimkannya ke JavaScript.
 *
 * Prioritas konfigurasi:
 * 1. Environment variable OS/Docker
 * 2. File .env di root proyek
 */
$inContainer = is_file('/.dockerenv');
$baseUrl = $inContainer
    ? (env_os('RENTIFY_AI_BASE_URL') ?? env_non_empty('RENTIFY_AI_DOCKER_BASE_URL') ?? (string) env('RENTIFY_AI_BASE_URL'))
    : (string) env('RENTIFY_AI_BASE_URL');

return [
    'base_url' => rtrim($baseUrl, '/'),
    'api_key' => (string) env('RENTIFY_AI_KEY'),
    'model' => (string) env('RENTIFY_AI_MODEL'),
    'fallback_model' => (string) env('RENTIFY_AI_FALLBACK_MODEL'),
    'timeout' => (int) env('RENTIFY_AI_TIMEOUT'),
    'connect_timeout' => 8,
    'temperature' => 0.2,
    'max_tokens' => (int) (env_non_empty('RENTIFY_AI_MAX_TOKENS') ?? 4096),
    'cache_ttl' => 86400,
    'rate_limit_per_hour' => 20,
];
