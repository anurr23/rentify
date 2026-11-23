<?php
declare(strict_types=1);

/**
 * AI Gear Planner.
 *
 * AI hanya boleh memilih item_id dari daftar kandidat yang dikirim server.
 * Harga, stok, dan kuantitas selalu divalidasi ulang di sisi server. Jika
 * 9router tidak tersedia, rekomendasi tetap dihasilkan lewat aturan kategori.
 */
class AiRecommender extends Model
{
    private array $config;
    private array $candidates = [];
    private array $candidateMap = [];
    private string $activityName = 'Camping';
    private string $destinationName = 'Karawang';
    private int $duration = 1;
    private int $participants = 1;
    private string $startDate = '';
    private string $endDate = '';
    private int $storeId = 0;
    private string $storeName = '';

    public function __construct()
    {
        parent::__construct();
        $this->config = require ROOT_PATH . '/config/ai.php';
    }

    public static function activities(): array
    {
        return Activity::allActive();
    }

    public static function destinations(): array
    {
        return Destination::allActive();
    }

    /**
     * @param array{activity_id?:int,activity?:string,destination_id?:int,destination?:string,
     *              days?:int,participants?:int,start_date?:string,end_date?:string,notes?:string} $input
     */
    public function recommend(array $input, ?array $user = null): array
    {
        $activity = !empty($input['activity_id'])
            ? Activity::find((int) $input['activity_id'])
            : Activity::findBySlug((string) ($input['activity'] ?? 'camping'));
        if (!$activity) {
            $activity = Activity::findBySlug('camping');
        }
        if (!$activity) {
            return ['success' => false, 'message' => 'Aktivitas tidak ditemukan.'];
        }

        $destination = !empty($input['destination_id']) ? Destination::find((int) $input['destination_id']) : null;
        $destinationText = $destination['name'] ?? trim((string) ($input['destination'] ?? 'Karawang'));
        if ($destinationText === '') {
            $destinationText = 'Karawang';
        }
        $days = min(14, max(1, (int) ($input['days'] ?? 1)));
        $participants = min(12, max(1, (int) ($input['participants'] ?? 2)));
        $start = (string) ($input['start_date'] ?? '');
        $end = (string) ($input['end_date'] ?? '');
        if ($start && !$end) {
            $end = date('Y-m-d', strtotime($start . ' +' . ($days - 1) . ' days'));
        }
        if (!$start) {
            $start = tomorrow();
            $end = date('Y-m-d', strtotime($start . ' +' . ($days - 1) . ' days'));
        }
        $duration = duration_days($start, $end);
        $notes = trim((string) ($input['notes'] ?? ''));
        $storeId = (int) ($input['store_id'] ?? 0);
        if ($storeId <= 0) {
            $store = null;
            if ($user && $user['last_latitude'] !== null && $user['last_longitude'] !== null) {
                $nearest = Store::nearest((float) $user['last_latitude'], (float) $user['last_longitude'], 1);
                $store = $nearest[0] ?? null;
            }
            if (!$store) {
                $store = current_store($user);
            }
            $storeId = $store ? (int) $store['id'] : 0;
        } else {
            $store = Store::find($storeId);
            if (!$store || (int) $store['is_active'] !== 1) {
                $storeId = 0;
            }
        }
        if ($storeId <= 0) {
            $store = Store::defaultStore();
            $storeId = $store ? (int) $store['id'] : 0;
        }

        $this->activityName = $activity['name'];
        $this->destinationName = $destinationText;
        $this->duration = $duration;
        $this->participants = $participants;
        $this->startDate = $start;
        $this->endDate = $end;
        $this->storeId = $storeId;
        $this->storeName = $store['name'] ?? '';
        $this->buildCandidates($activity, $destination, $participants, $duration, $storeId);

        if (!$this->candidates) {
            return ['success' => false, 'message' => 'Belum ada gear tersedia di cabang ini untuk aktivitas tersebut.'];
        }

        $promptHash = hash('sha256', implode('|', [
            $activity['id'], $destination['id'] ?? 0, strtolower($destinationText), $days, $participants,
            $notes, $storeId, $this->config['model'], $this->candidateHash(),
        ]));
        $cache = $this->readCache($promptHash);
        if ($cache !== null) {
            $result = $this->normalize($cache, 'cache', $promptHash, $activity, $destination);
            if ($result['success']) {
                return $result;
            }
        }

        if (!$this->withinRateLimit()) {
            $fallback = $this->fallback($activity, $promptHash);
            $fallback['notice'] = 'Batas pemakaian AI tercapai. Rekomendasi dibuat dari aturan gear Rentify.';
            return $fallback;
        }

        $started = microtime(true);
        $raw = $this->callAi($this->buildPrompt($activity, $destination, $notes, $participants, $duration, $start, $end));
        $latency = (int) round((microtime(true) - $started) * 1000);

        if ($raw !== null) {
            $parsed = $this->parseAiJson($raw);
            if ($parsed !== null) {
                $normalized = $this->normalize($parsed, 'ai', $promptHash, $activity, $destination, $raw, $latency);
                if ($normalized['success']) {
                    $this->writeCache($promptHash, $normalized);
                    return $normalized;
                }
            }
            error_log('AI recommendation parse/validate failed: ' . substr($raw, 0, 400));
        }

        $fallback = $this->fallback($activity, $promptHash);
        $fallback['notice'] = 'AI sedang tidak bisa dihubungi, jadi rekomendasi disusun otomatis dari aturan perlengkapan Rentify.';
        return $fallback;
    }

    private function candidateHash(): string
    {
        return substr(hash('sha256', implode(',', array_keys($this->candidateMap))), 0, 12);
    }

    private function buildCandidates(array $activity, ?array $destination, int $participants, int $duration, int $storeId): void
    {
        $rules = self::fetchAll(
            'SELECT r.*, c.name AS category_name, c.slug AS category_slug
             FROM activity_gear_rules r LEFT JOIN categories c ON c.id = r.category_id
             WHERE r.activity_id = ? AND r.is_active = 1 ORDER BY FIELD(r.priority, "wajib", "opsional"), r.id ASC',
            [$activity['id']]
        );
        $categoryIds = [];
        foreach ($rules as $rule) {
            if ($rule['category_id']) {
                $categoryIds[] = (int) $rule['category_id'];
            }
        }
        if (!$categoryIds) {
            $categoryIds = array_map('intval', $activity['default_category_ids'] ?: [1, 2, 5, 6, 8]);
        }
        $categoryIds = array_values(array_unique($categoryIds));
        if (!$categoryIds) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $params = [$storeId, ...$categoryIds];
        $rows = self::fetchAll(
            "SELECT i.id, i.name, i.brand, i.category_id, si.price_per_day, si.deposit, si.stock_total,
                si.id AS store_item_id, i.capacity_person, i.weight_gram, i.features, i.short_description,
                i.rating_avg, i.rented_count, c.name AS category_name, s.name AS store_name, s.slug AS store_slug
             FROM store_items si
             JOIN items i ON i.id = si.item_id
             JOIN categories c ON c.id = i.category_id
             JOIN stores s ON s.id = si.store_id
             WHERE si.store_id = ? AND si.is_active = 1 AND s.is_active = 1
               AND i.is_active = 1 AND si.stock_total > 0 AND i.category_id IN ($placeholders)
             ORDER BY si.is_featured DESC, i.rented_count DESC, i.rating_avg DESC
             LIMIT 70",
            $params
        );

        $ruleByCategory = [];
        foreach ($rules as $rule) {
            if ($rule['category_id'] && !isset($ruleByCategory[(int) $rule['category_id']])) {
                $ruleByCategory[(int) $rule['category_id']] = $rule;
            }
        }

        $selectedPerCategory = [];
        foreach ($rows as $row) {
            $categoryId = (int) $row['category_id'];
            $count = $selectedPerCategory[$categoryId] ?? 0;
            if ($count >= 6) {
                continue;
            }
            $selectedPerCategory[$categoryId] = $count + 1;
            $features = safe_json_decode($row['features'], []);
            $variant = null;
            $variantStmt = self::pdo()->prepare(
                'SELECT v.id, v.label, siv.stock_total, COALESCE(siv.price_per_day, si.price_per_day) AS price_per_day
                 FROM item_variants v
                 JOIN store_items si ON si.id = ?
                 LEFT JOIN store_item_variants siv ON siv.store_item_id = si.id AND siv.variant_id = v.id
                 WHERE v.item_id = ? AND v.is_active = 1 AND COALESCE(siv.stock_total, 0) > 0
                 ORDER BY siv.stock_total DESC, v.sort_order ASC LIMIT 1'
            );
            $variantStmt->execute([(int) $row['store_item_id'], (int) $row['id']]);
            $variant = $variantStmt->fetch() ?: null;
            $rule = $ruleByCategory[$categoryId] ?? null;
            if ($rule) {
                $suggested = max(1, (int) ceil($participants * (float) $rule['qty_per_person']));
            } else {
                $suggested = 1;
            }
            $candidate = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'brand' => $row['brand'],
                'category_id' => $categoryId,
                'category' => $row['category_name'],
                'price' => (float) $row['price_per_day'],
                'deposit' => (float) $row['deposit'],
                'stock' => (int) $row['stock_total'],
                'store_id' => $storeId,
                'store_name' => $row['store_name'],
                'store_slug' => $row['store_slug'],
                'store_item_id' => (int) $row['store_item_id'],
                'capacity' => $row['capacity_person'] !== null ? (int) $row['capacity_person'] : null,
                'features' => array_slice($features, 0, 4),
                'rating' => (float) $row['rating_avg'],
                'rented' => (int) $row['rented_count'],
                'variant_id' => $variant ? (int) $variant['id'] : null,
                'variant_label' => $variant['label'] ?? null,
                'rule_priority' => $rule['priority'] ?? 'opsional',
                'suggested_qty' => $suggested,
                'note' => $rule['note'] ?? null,
            ];
            $this->candidates[] = $candidate;
            $this->candidateMap[$candidate['id']] = $candidate;
        }
    }

    private function buildPrompt(array $activity, ?array $destination, string $notes, int $participants, int $duration, string $start, string $end): array
    {
        $destinationContext = $destination
            ? $destination['name'] . ' (' . ($destination['region'] ?? 'Jawa Barat') . ', elevasi ' . ($destination['elevation_m'] ?? '-') . ' mdpl, medan ' . ($destination['terrain'] ?? '-') . '). ' . ($destination['notes'] ?? '')
            : $this->destinationName . ' (area Karawang/Jawa Barat, cek kondisi cuaca sebelum berangkat)';

        $list = [];
        foreach ($this->candidates as $candidate) {
            $list[] = [
                'item_id' => $candidate['id'],
                'nama' => $candidate['name'],
                'brand' => $candidate['brand'],
                'kategori' => $candidate['category'],
                'harga_per_hari' => (int) $candidate['price'],
                'stok' => $candidate['stock'],
                'kapasitas_orang' => $candidate['capacity'],
                'fitur' => $candidate['features'],
                'prioritas_rule' => $candidate['rule_priority'],
                'qty_saran' => $candidate['suggested_qty'],
                'varian_tersedia' => $candidate['variant_label'],
            ];
        }

        $system = <<<'PROMPT'
Kamu adalah "Rentify Gear Planner", ahli perlengkapan outdoor Karawang yang ramah dan praktis.
Tugasmu memilih perlengkapan sewa dari DAFTAR KANDIDAT yang diberikan. Kamu TIDAK BOLEH mengarang item,
mengubah harga, atau memilih item_id yang tidak ada di daftar.

Aturan:
1. Pilih 5-10 item yang paling penting dan saling melengkapi.
2. Sesuaikan jumlah dengan jumlah peserta dan durasi. Tenda 4P untuk 2 orang cukup 1 unit.
3. Tandai prioritas "wajib" atau "opsional". Maksimal 7 item wajib.
4. Pertimbangkan cuaca, medan, elevasi, dan aktivitas. Jika destinasi pegunungan, tekankan layering hangat,
   hardshell, headlamp, dan sleeping bag.
5. Jangan memilih dua item yang fungsinya sama (mis. dua carrier untuk orang yang sama), kecuali qty.
6. Alasan maksimal 160 karakter, bahasa Indonesia, konkret dan menyebut konteks destinasi/durasi.
7. Ringkasan maksimal 280 karakter. Tips 3-4 poin singkat dan actionable.
8. Balas HANYA JSON valid tanpa markdown, tanpa code fence.

Format JSON:
{"summary":"...","tips":["...","..."],"items":[{"item_id":1,"qty":1,"priority":"wajib","reason":"..."}]}
PROMPT;

        $user = "RENCANA PERJALANAN\n"
            . "Aktivitas: {$this->activityName}\n"
            . "Destinasi: {$destinationContext}\n"
            . 'Tanggal: ' . format_date_id($start) . ' s/d ' . format_date_id($end) . " ({$duration} hari, inklusif)\n"
            . "Peserta: {$participants} orang\n"
            . 'Catatan customer: ' . ($notes !== '' ? $notes : '-') . "\n\n"
            . "DAFTAR KANDIDAT (hanya pilih dari sini):\n"
            . json_encode($list, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . "\n\nPilih gear terbaik dan balas dengan JSON valid saja.";

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ];
    }

    private function callAi(array $messages): ?string
    {
        if (!function_exists('curl_init')) {
            error_log('AI recommendation: ekstensi cURL tidak aktif.');
            return null;
        }
        $payload = json_encode([
            'model' => $this->config['model'],
            'messages' => $messages,
            'temperature' => $this->config['temperature'],
            'max_tokens' => $this->config['max_tokens'],
            'stream' => false,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init(rtrim($this->config['base_url'], '/') . '/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->config['api_key'],
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->config['connect_timeout'],
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_NOPROXY => '*',
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($response === false || $status >= 400) {
            error_log('AI recommendation call failed: HTTP ' . $status . ' ' . $error . ' ' . substr((string) $response, 0, 300));
            return null;
        }
        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            error_log('AI recommendation: response bukan JSON.');
            return null;
        }
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (is_array($content)) {
            $content = implode("\n", array_map(static fn ($part) => is_array($part) ? ($part['text'] ?? '') : (string) $part, $content));
        }
        return is_string($content) && trim($content) !== '' ? $content : null;
    }

    private function parseAiJson(string $raw): ?array
    {
        $text = trim($raw);
        $text = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $text) ?? $text;
        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            $start = strpos($text, '{');
            $end = strrpos($text, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            }
        }
        if (!is_array($decoded) || !isset($decoded['items']) || !is_array($decoded['items'])) {
            return null;
        }
        return $decoded;
    }

    private function normalize(array $payload, string $source, string $promptHash, array $activity, ?array $destination, ?string $raw = null, int $latency = 0): array
    {
        $items = [];
        $seen = [];
        foreach (($payload['items'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $itemId = (int) ($entry['item_id'] ?? 0);
            if (!isset($this->candidateMap[$itemId]) || isset($seen[$itemId])) {
                continue;
            }
            $seen[$itemId] = true;
            $candidate = $this->candidateMap[$itemId];
            $qty = max(1, min((int) ($entry['qty'] ?? $candidate['suggested_qty']), max(1, min(6, $candidate['stock']))));
            $priority = in_array(($entry['priority'] ?? ''), ['wajib', 'opsional'], true) ? $entry['priority'] : 'opsional';
            $reason = mb_substr(trim((string) ($entry['reason'] ?? '')), 0, 180);
            if ($reason === '') {
                $reason = 'Pilihan tepat untuk ' . $this->activityName . ' ' . $this->duration . ' hari di ' . $this->destinationName . '.';
            }
            $items[] = $this->decorate($candidate, $qty, $priority, $reason);
        }

        if (count($items) < 3) {
            return ['success' => false, 'message' => 'Rekomendasi AI tidak valid.'];
        }

        $wajibCategories = [];
        foreach ($this->candidates as $candidate) {
            if ($candidate['rule_priority'] === 'wajib') {
                $wajibCategories[$candidate['category_id']] = true;
            }
        }
        $covered = [];
        foreach ($items as $item) {
            $covered[$item['category_id']] = true;
        }
        $missing = array_diff_key($wajibCategories, $covered);
        if ($missing) {
            $fallback = $this->fallback($activity, $promptHash);
            foreach ($fallback['items'] as $fallbackItem) {
                if (isset($missing[$fallbackItem['category_id']]) && count($items) < 11) {
                    $items[] = $fallbackItem;
                    unset($missing[$fallbackItem['category_id']]);
                }
            }
        }

        $items = array_slice($items, 0, 11);
        usort($items, static function ($a, $b) {
            $priority = ($a['priority'] === 'wajib' ? 0 : 1) <=> ($b['priority'] === 'wajib' ? 0 : 1);
            return $priority !== 0 ? $priority : ((float) ($a['price_per_day'] ?? 0) <=> (float) ($b['price_per_day'] ?? 0));
        });

        $summary = trim((string) ($payload['summary'] ?? ''));
        if ($summary === '') {
            $summary = 'Rekomendasi gear ' . $this->activityName . ' ' . $this->duration . ' hari untuk ' . $this->participants . ' orang di ' . $this->destinationName . '.';
        }
        $summary = mb_substr($summary, 0, 400);
        $tips = array_values(array_filter(array_map(
            static fn ($tip) => mb_substr(trim((string) $tip), 0, 160),
            is_array($payload['tips'] ?? null) ? $payload['tips'] : []
        )));
        if (!$tips) {
            $tips = $this->defaultTips();
        }
        $tips = array_slice($tips, 0, 5);

        $sessionId = $this->persist(
            $source === 'cache' ? 'success' : 'success',
            $summary,
            $tips,
            $items,
            $raw,
            $latency ?: 0,
            $activity,
            $destination,
            $promptHash
        );

        $totals = $this->totals($items);
        return [
            'success' => true,
            'message' => $source === 'cache' ? 'Rekomendasi dari cache AI.' : 'Rekomendasi AI berhasil dibuat.',
            'source' => $source,
            'session_id' => $sessionId,
            'activity' => $activity,
            'destination_name' => $this->destinationName,
            'duration_days' => $this->duration,
            'participants' => $this->participants,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'store_id' => $this->storeId,
            'store_name' => $this->storeName,
            'summary' => $summary,
            'tips' => $tips,
            'items' => $items,
            'totals' => $totals,
            'notice' => null,
        ];
    }

    private function decorate(array $candidate, int $qty, string $priority, string $reason): array
    {
        $item = Item::find($candidate['id']);
        $subtotal = $candidate['price'] * $qty * $this->duration;
        return [
            'item_id' => $candidate['id'],
            'variant_id' => $candidate['variant_id'],
            'variant_label' => $candidate['variant_label'],
            'name' => $candidate['name'],
            'brand' => $candidate['brand'],
            'category_id' => $candidate['category_id'],
            'category' => $candidate['category'],
            'slug' => $item['slug'] ?? '',
            'image' => $item['primary_image'] ?? null,
            'price_per_day' => $candidate['price'],
            'qty' => $qty,
            'stock' => $candidate['stock'],
            'store_id' => $candidate['store_id'] ?? $this->storeId,
            'store_name' => $candidate['store_name'] ?? $this->storeName,
            'store_item_id' => $candidate['store_item_id'] ?? null,
            'duration_days' => $this->duration,
            'subtotal' => $subtotal,
            'deposit' => (float) ($candidate['deposit'] ?? $item['deposit'] ?? 0) * $qty,
            'priority' => $priority,
            'reason' => $reason,
            'features' => $candidate['features'],
            'detail_url' => 'detail.php?slug=' . ($item['slug'] ?? '') . '&store=' . rawurlencode((string) ($candidate['store_slug'] ?? '')),
        ];
    }

    private function totals(array $items): array
    {
        $subtotal = 0.0;
        $deposit = 0.0;
        $wajib = 0;
        foreach ($items as $item) {
            $subtotal += (float) $item['subtotal'];
            $deposit += (float) $item['deposit'];
            if ($item['priority'] === 'wajib') {
                $wajib++;
            }
        }
        return [
            'subtotal' => $subtotal,
            'deposit' => $deposit,
            'total' => $subtotal + $deposit,
            'items' => count($items),
            'wajib' => $wajib,
        ];
    }

    public function fallback(array $activity, string $promptHash = ''): array
    {
        $rules = self::fetchAll(
            'SELECT r.*, c.name AS category_name
             FROM activity_gear_rules r JOIN categories c ON c.id = r.category_id
             WHERE r.activity_id = ? AND r.is_active = 1
             ORDER BY FIELD(r.priority, "wajib", "opsional"), r.id ASC',
            [$activity['id']]
        );
        $items = [];
        $usedCategories = [];
        foreach ($rules as $rule) {
            $categoryId = (int) $rule['category_id'];
            if (!$categoryId || isset($usedCategories[$categoryId])) {
                continue;
            }
            $options = array_values(array_filter(
                $this->candidates,
                static fn ($candidate) => $candidate['category_id'] === $categoryId
            ));
            if (!$options) {
                continue;
            }
            $participants = $this->participants;
            usort($options, static function ($a, $b) use ($rule, $participants) {
                if ($rule['priority'] === 'wajib') {
                    $aCapacity = $a['capacity'] ?? 0;
                    $bCapacity = $b['capacity'] ?? 0;
                    $aFits = $aCapacity >= $participants || $aCapacity === 0;
                    $bFits = $bCapacity >= $participants || $bCapacity === 0;
                    if ($aFits !== $bFits) {
                        return $aFits ? -1 : 1;
                    }
                }
                $popularity = $b['rented'] <=> $a['rented'];
                return $popularity !== 0 ? $popularity : ($a['price'] <=> $b['price']);
            });
            $chosen = $options[0];
            $usedCategories[$categoryId] = true;
            $qty = max(1, (int) ceil($this->participants * (float) $rule['qty_per_person']));
            if ($chosen['category_id'] === 1 && $chosen['capacity']) {
                $qty = max(1, (int) ceil($this->participants / max(1, (int) $chosen['capacity'])));
            }
            $qty = min($qty, max(1, $chosen['stock']));
            $reason = $rule['note'] ?: ('Gear ' . $rule['priority'] . ' untuk ' . $activity['name'] . ' di ' . $this->destinationName . '.');
            $items[] = $this->decorate($chosen, $qty, $rule['priority'], mb_substr($reason, 0, 180));
            if (count($items) >= 10) {
                break;
            }
        }

        if (!$items) {
            foreach (array_slice($this->candidates, 0, 6) as $candidate) {
                $items[] = $this->decorate($candidate, max(1, $candidate['suggested_qty']), $candidate['rule_priority'], 'Gear rekomendasi untuk ' . $activity['name'] . '.');
            }
        }

        $summary = 'Rekomendasi ' . $activity['name'] . ' ' . $this->duration . ' hari untuk ' . $this->participants . ' orang di ' . $this->destinationName . '. Susunan gear dipilih dari aturan perlengkapan Rentify dan ketersediaan stok.';
        $tips = $this->defaultTips();
        $sessionId = $this->persist('fallback', $summary, $tips, $items, null, 0, $activity, null, $promptHash);

        return [
            'success' => true,
            'message' => 'Rekomendasi berhasil dibuat.',
            'source' => 'fallback',
            'session_id' => $sessionId,
            'activity' => $activity,
            'destination_name' => $this->destinationName,
            'duration_days' => $this->duration,
            'participants' => $this->participants,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'store_id' => $this->storeId,
            'store_name' => $this->storeName,
            'summary' => $summary,
            'tips' => $tips,
            'items' => $items,
            'totals' => $this->totals($items),
            'notice' => 'Rekomendasi disusun otomatis dari aturan gear Rentify.',
        ];
    }

    private function defaultTips(): array
    {
        return [
            'Cek prakiraan cuaca 1 hari sebelum berangkat dan siapkan layer tambahan.',
            'Bawa air minum minimal 1 liter per orang per aktivitas.',
            'Simpan barang berharga di dry bag dan pastikan carrier tidak melebihi 20% berat badan.',
            'Kembalikan gear sesuai tanggal agar tidak terkena biaya keterlambatan.',
        ];
    }

    private function persist(
        string $status,
        string $summary,
        array $tips,
        array $items,
        ?string $raw,
        int $latency,
        array $activity,
        ?array $destination,
        string $promptHash
    ): int {
        try {
            $stmt = self::pdo()->prepare(
                'INSERT INTO ai_sessions (user_id, activity_id, destination_id, destination_text, duration_days,
                    participants, start_date, end_date, notes, model, prompt_hash, status, summary, tips, raw_response, latency_ms, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $_SESSION['user_id'] ?? null,
                $activity['id'],
                $destination['id'] ?? null,
                $this->destinationName,
                $this->duration,
                $this->participants,
                $this->startDate ?: null,
                $this->endDate ?: null,
                trim((string) ($_SERVER['HTTP_X_RENTIFY_NOTES'] ?? '')) ?: null,
                $this->config['model'],
                $promptHash ?: null,
                $status,
                $summary,
                json_encode($tips, JSON_UNESCAPED_UNICODE),
                $raw,
                $latency,
            ]);
            $sessionId = (int) self::pdo()->lastInsertId();
            $insertItem = self::pdo()->prepare(
                'INSERT INTO ai_session_items (ai_session_id, item_id, variant_id, qty, priority, reason, is_selected, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?)'
            );
            foreach ($items as $index => $item) {
                $insertItem->execute([
                    $sessionId, $item['item_id'], $item['variant_id'], $item['qty'], $item['priority'],
                    $item['reason'], $index + 1,
                ]);
            }
            return $sessionId;
        } catch (Throwable $exception) {
            error_log('AI persist failed: ' . $exception->getMessage());
            return 0;
        }
    }

    private function readCache(string $hash): ?array
    {
        try {
            $row = self::fetchOne('SELECT response FROM ai_cache WHERE prompt_hash = ? AND expires_at > NOW() LIMIT 1', [$hash]);
            if (!$row) {
                return null;
            }
            $decoded = json_decode($row['response'], true);
            return is_array($decoded) ? $decoded : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function writeCache(string $hash, array $result): void
    {
        try {
            $ttl = max(300, (int) ($this->config['cache_ttl'] ?? 86400));
            $payload = json_encode([
                'summary' => $result['summary'] ?? '',
                'tips' => $result['tips'] ?? [],
                'items' => array_map(static fn ($item) => [
                    'item_id' => $item['item_id'],
                    'qty' => $item['qty'],
                    'priority' => $item['priority'],
                    'reason' => $item['reason'],
                ], $result['items'] ?? []),
            ], JSON_UNESCAPED_UNICODE);
            self::pdo()->prepare(
                'INSERT INTO ai_cache (prompt_hash, response, created_at, expires_at) VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? SECOND))
                 ON DUPLICATE KEY UPDATE response = VALUES(response), created_at = NOW(), expires_at = VALUES(expires_at)'
            )->execute([$hash, $payload, $ttl]);
        } catch (Throwable $exception) {
            error_log('AI cache write failed: ' . $exception->getMessage());
        }
    }

    private function withinRateLimit(): bool
    {
        $limit = max(1, (int) ($this->config['rate_limit_per_hour'] ?? 20));
        $now = time();
        $calls = array_values(array_filter(
            is_array($_SESSION['_ai_calls'] ?? null) ? $_SESSION['_ai_calls'] : [],
            static fn ($timestamp) => $timestamp > $now - 3600
        ));
        if (count($calls) >= $limit) {
            return false;
        }
        $calls[] = $now;
        $_SESSION['_ai_calls'] = $calls;
        return true;
    }

    public static function recentSessions(int $limit = 10): array
    {
        return self::fetchAll(
            'SELECT s.*, a.name AS activity_name, u.name AS user_name,
                (SELECT COUNT(*) FROM ai_session_items i WHERE i.ai_session_id = s.id) AS item_count
             FROM ai_sessions s
             LEFT JOIN activities a ON a.id = s.activity_id
             LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.created_at DESC LIMIT ?',
            [$limit]
        );
    }

    public static function stats(): array
    {
        return [
            'sessions' => (int) self::fetchValue('SELECT COUNT(*) FROM ai_sessions'),
            'success' => (int) self::fetchValue("SELECT COUNT(*) FROM ai_sessions WHERE status = 'success'"),
            'fallback' => (int) self::fetchValue("SELECT COUNT(*) FROM ai_sessions WHERE status = 'fallback'"),
            'avg_latency' => (int) self::fetchValue('SELECT COALESCE(AVG(latency_ms), 0) FROM ai_sessions WHERE latency_ms > 0'),
        ];
    }
}
