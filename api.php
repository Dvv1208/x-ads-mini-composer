<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config.php';

App\Auth::start();
if (!App\Auth::check()) {
    respond(['error' => 'Authentication required.'], 401);
}

$requestMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (
    !in_array($requestMethod, ['GET', 'HEAD'], true)
    && !App\Auth::verifyCsrf($_SERVER['HTTP_X_APP_CSRF_TOKEN'] ?? null)
) {
    respond(['error' => 'Invalid CSRF token.'], 403);
}

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function bodyJson(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        respond(['error' => 'Invalid JSON request body.'], 400);
    }

    return $data;
}

function getCt0(string $cookie): ?string
{
    if (preg_match('/(?:^|;\s*)ct0=([^;]+)/', $cookie, $matches)) {
        return urldecode($matches[1]);
    }

    return null;
}

function normalizeCookie(string $cookie): string
{
    $cookie = trim($cookie);

    if (stripos($cookie, 'cookie:') === 0) {
        $cookie = trim(substr($cookie, strlen('cookie:')));
    }

    return $cookie;
}

function configuredCt0(string $cookie): ?string
{
    return getCt0($cookie);
}

function authStatus(array $config): array
{
    $cookie = normalizeCookie((string)($config['cookie'] ?? ''));
    $bearer = trim((string)($config['bearer'] ?? ''));
    $ct0 = configuredCt0($cookie);

    return [
        'cookie_configured' => $cookie !== '',
        'ct0_configured' => $ct0 !== null && $ct0 !== '',
        'bearer_configured' => $bearer !== '',
        'ready' => $ct0 !== null && $ct0 !== '' && $bearer !== '',
    ];
}

function database(array $config): PDO
{
    try {
        return App\Database::connect($config);
    } catch (Throwable $exception) {
        error_log('X Ads database connection failed: ' . $exception->getMessage());
        respond(['error' => 'Could not connect to the X Ads database.'], 500);
    }
}

function userAccounts(array $config): array
{
    try {
        $statement = database($config)->query(
            'SELECT entity_id, account_id, user_id FROM `user` ORDER BY entity_id ASC'
        );

        return $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log('X Ads account query failed: ' . $exception->getMessage());
        respond(['error' => 'Could not load X Ads accounts from the database.'], 500);
    }
}

function resolveUserAccount(array $accounts, int $entityId): array
{
    if ($accounts === []) {
        respond(['error' => 'No X Ads accounts exist in the database.'], 422);
    }

    if ($entityId <= 0) {
        return $accounts[0];
    }

    foreach ($accounts as $account) {
        if ((int)$account['entity_id'] === $entityId) {
            return $account;
        }
    }

    respond(['error' => 'Selected X Ads account was not found.'], 404);
}

function cleanParams(array $params, bool $keepEmpty = false): array
{
    return array_filter(
        $params,
        static function ($value) use ($keepEmpty): bool {
            if ($keepEmpty && $value === '') {
                return true;
            }

            return $value !== null && $value !== '';
        }
    );
}

function internalName(string $value, string $fallback = 'Scheduled Post'): string
{
    $value = trim($value);
    if ($value === '') {
        $value = $fallback;
    }

    if (function_exists('iconv')) {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($ascii !== false) {
            $value = $ascii;
        }
    }

    $value = preg_replace('/[^A-Za-z0-9 _.,()\-]/', '', $value) ?? '';
    $value = preg_replace('/\s+/', ' ', trim($value)) ?? '';

    if ($value === '') {
        $value = $fallback;
    }

    return mb_substr($value, 0, 80);
}

function base62Timestamp(): string
{
    $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $value = (int)floor(microtime(true) * 1000);
    $encoded = '';

    do {
        $encoded = $alphabet[$value % 62] . $encoded;
        $value = intdiv($value, 62);
    } while ($value > 0);

    return $encoded;
}

function xRequest(
    array $config,
    string $method,
    string $path,
    array $query = [],
    ?array $jsonBody = null
): array {
    $cookie = normalizeCookie((string)($config['cookie'] ?? ''));
    $bearer = trim((string)($config['bearer'] ?? ''));
    $ct0 = configuredCt0($cookie);

    if ($ct0 === null || $ct0 === '') {
        respond([
            'error' => 'The configured Cookie does not contain ct0.',
            'hint' => 'Copy a complete X Cookie request header containing ct0 into config.php.',
        ], 500);
    }

    if ($bearer === '') {
        respond(['error' => 'Missing bearer token in config.php.'], 500);
    }

    $version = (string)($config['api_version'] ?? '12');
    $url = 'https://ads-api.x.com/' . rawurlencode($version) . '/' . ltrim($path, '/');

    if ($query !== []) {
        $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    $ch = curl_init($url);
    if ($ch === false) {
        respond(['error' => 'Unable to initialize cURL.'], 500);
    }

    $headers = [
        'Authorization: Bearer ' . $bearer,
        'X-CSRF-Token: ' . $ct0,
        'X-Twitter-Auth-Type: OAuth2Session',
        'Content-Type: application/json',
        'Accept: application/json',
        'Origin: https://ads.x.com',
        'Referer: https://ads.x.com/',
        'User-Agent: Mozilla/5.0',
    ];

    if ($cookie !== '') {
        $headers[] = 'Cookie: ' . $cookie;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => (int)($config['timeout'] ?? 30),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING => '',
        CURLOPT_FOLLOWLOCATION => false,
    ]);

    // The browser scheduled-tweet flow sends "{}"; card creation needs a JSON body.
    if (in_array(strtoupper($method), ['POST', 'PUT'], true)) {
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            $jsonBody === null
                ? '{}'
                : json_encode($jsonBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($raw === false) {
        respond([
            'error' => 'cURL request failed.',
            'details' => $curlError,
        ], 502);
    }

    $decoded = json_decode($raw, true);

    if (!is_array($decoded)) {
        return [
            'status' => $status,
            'body' => [
                'error' => 'X returned a non-JSON response.',
                'raw' => mb_substr($raw, 0, 5000),
            ],
        ];
    }

    $firstError = (string)($decoded['errors'][0]['message'] ?? $decoded['error'] ?? $decoded['message'] ?? '');
    $needsSessionCookie = in_array($status, [401, 403], true)
        || stripos($firstError, 'could not authenticate') !== false;

    if ($needsSessionCookie && $cookie === '') {
        $decoded['hint'] = 'This PHP request needs the full X Cookie request header. The old console script worked because credentials: "include" made the browser send those cookies automatically from ads.x.com.';
    }

    return [
        'status' => $status,
        'body' => $decoded,
    ];
}

function proxyResult(array $result): never
{
    $status = (int)($result['status'] ?? 500);
    $body = is_array($result['body'] ?? null)
        ? $result['body']
        : ['error' => 'Unknown proxy response.'];

    respond($body, $status >= 100 ? $status : 500);
}

function decorateMediaResult(array $result): array
{
    $body = $result['body'] ?? null;
    if (!is_array($body) || !isset($body['data']) || !is_array($body['data'])) {
        return $result;
    }

    if (array_is_list($body['data'])) {
        foreach ($body['data'] as &$media) {
            if (is_array($media)) {
                $media['validation'] = App\MediaValidator::inspect($media);
            }
        }
        unset($media);
    } elseif (isset($body['data']['media_key'])) {
        $body['data']['validation'] = App\MediaValidator::inspect($body['data']);
    }

    $result['body'] = $body;

    return $result;
}

function mediaFromResult(array $result, string $mediaKey): ?array
{
    $data = $result['body']['data'] ?? null;
    if (!is_array($data)) {
        return null;
    }

    if (isset($data['media_key'])) {
        return $data;
    }

    foreach ($data as $media) {
        if (is_array($media) && (string)($media['media_key'] ?? '') === $mediaKey) {
            return $media;
        }
    }

    return null;
}

function previewHtml(array $body): ?string
{
    $data = $body['data'] ?? null;

    if (is_array($data) && isset($data['preview']) && is_string($data['preview'])) {
        return trim($data['preview']) !== '' ? $data['preview'] : null;
    }

    if (is_array($data)) {
        foreach ($data as $item) {
            if (is_array($item) && isset($item['preview']) && is_string($item['preview'])) {
                return trim($item['preview']) !== '' ? $item['preview'] : null;
            }
        }
    }

    return null;
}

function requestTweetPreview(
    array $config,
    string $accountId,
    string $tweetId,
    string $tweetType
): array {
    return xRequest(
        $config,
        'GET',
        "accounts/{$accountId}/tweet_previews",
        [
            'tweet_ids' => $tweetId,
            'tweet_type' => $tweetType,
        ]
    );
}

function scheduleAt(array $config, string $requestedAt = ''): string
{
    $requestedAt = trim($requestedAt);

    if ($requestedAt !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $requestedAt)) {
            respond(['error' => 'Invalid schedule date/time format.'], 422);
        }

        $localTimezone = new DateTimeZone('Asia/Ho_Chi_Minh');
        $scheduled = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            $requestedAt,
            $localTimezone
        );
        $dateErrors = DateTimeImmutable::getLastErrors();
        $hasDateErrors = is_array($dateErrors)
            && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0);

        if (
            $scheduled === false
            || $hasDateErrors
            || $scheduled->format('Y-m-d\TH:i') !== $requestedAt
        ) {
            respond(['error' => 'Invalid schedule date/time.'], 422);
        }

        if ($scheduled->getTimestamp() <= time()) {
            respond(['error' => 'Schedule date/time must be in the future.'], 422);
        }

        return $scheduled
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:00\Z');
    }

    $minutes = max(1, (int)($config['schedule_after_minutes'] ?? 1));
    $timestamp = time();

    $nextMinute = (int)(ceil($timestamp / 60) * 60);
    $scheduledTimestamp = $nextMinute + ($minutes * 60);

    return gmdate('Y-m-d\TH:i:00\Z', $scheduledTimestamp);
}

function createWebsiteCard(
    array $config,
    string $accountId,
    string $name,
    string $headline,
    string $websiteUrl,
    array $mediaKeys
): array {
    $mediaComponent = [
        'type' => 'MEDIA',
        'media_key' => $mediaKeys[0],
    ];

    return xRequest(
        $config,
        'POST',
        "accounts/{$accountId}/cards",
        [],
        [
            'name' => mb_substr($name, 0, 80),
            'components' => [
                $mediaComponent,
                [
                    'type' => 'DETAILS',
                    'title' => mb_substr($headline, 0, 80),
                    'destination' => [
                        'type' => 'WEBSITE',
                        'url' => $websiteUrl,
                    ],
                ],
            ],
        ]
    );
}

$action = (string)($_GET['action'] ?? '');

$accounts = userAccounts($config);
$selectedAccount = resolveUserAccount($accounts, (int)($_GET['entity_id'] ?? 0));
$entityId = (int)$selectedAccount['entity_id'];
$accountId = (string)$selectedAccount['account_id'];
$userId = (string)$selectedAccount['user_id'];

switch ($action) {
    case 'config':
        $auth = authStatus($config);

        respond([
            'data' => [
                'account_id' => $accountId,
                'user_id' => $userId,
                'entity_id' => $entityId,
                'accounts' => array_map(
                    static fn(array $account): array => [
                        'entity_id' => (int)$account['entity_id'],
                        'account_id' => (string)$account['account_id'],
                        'user_id' => (string)$account['user_id'],
                    ],
                    $accounts
                ),
                'api_version' => (string)($config['api_version'] ?? '12'),
                'schedule_after_minutes' => (int)($config['schedule_after_minutes'] ?? 1),
                'authenticated' => $auth['ready'],
                'auth' => $auth,
            ],
        ]);

    case 'media':
        $mediaKey = trim((string)($_GET['id'] ?? ''));

        if ($mediaKey !== '') {
            proxyResult(decorateMediaResult(xRequest(
                $config,
                'GET',
                "accounts/{$accountId}/media_library/" . rawurlencode($mediaKey)
            )));
        }

        $query = cleanParams([
            'count' => min(50, max(1, (int)($_GET['count'] ?? 50))),
            'cursor' => $_GET['cursor'] ?? null,
            'media_type' => $_GET['media_type'] ?? null,
            'q' => $_GET['q'] ?? null,
        ]);

        proxyResult(decorateMediaResult(xRequest(
            $config,
            'GET',
            "accounts/{$accountId}/media_library",
            $query
        )));

    case 'tweet':
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if ($method !== 'POST') {
            respond(['error' => 'Method not allowed.'], 405);
        }

        $data = bodyJson();

        $shortId = base62Timestamp();
        $defaultPostValue = 'Wataa 👅 ' . $shortId;
        $text = $defaultPostValue;
        $requestedScheduledAt = trim((string)($data['scheduled_at'] ?? ''));
        $websiteUrl = trim((string)($data['website_url'] ?? ''));
        $headline = trim((string)($data['headline'] ?? ''));
        $mediaKeys = $data['media_keys'] ?? [];

        if (!is_array($mediaKeys)) {
            $mediaKeys = [];
        }

        $mediaKeys = array_values(array_filter(array_map('strval', $mediaKeys)));

        if ($websiteUrl === '' || filter_var($websiteUrl, FILTER_VALIDATE_URL) === false) {
            respond(['error' => 'Provide a valid Website URL.'], 422);
        }

        if ($headline === '') {
            respond(['error' => 'Headline is required for the website card.'], 422);
        }

        if (count($mediaKeys) !== 1) {
            respond(['error' => 'Choose exactly one media item for the website card.'], 422);
        }

        $mediaKey = $mediaKeys[0];
        $mediaResult = xRequest(
            $config,
            'GET',
            "accounts/{$accountId}/media_library/" . rawurlencode($mediaKey)
        );
        $mediaStatus = (int)($mediaResult['status'] ?? 500);
        if ($mediaStatus < 200 || $mediaStatus >= 300) {
            proxyResult($mediaResult);
        }

        $media = mediaFromResult($mediaResult, $mediaKey);
        if ($media === null) {
            respond(['error' => 'X did not return the selected media item.'], 502);
        }

        $mediaValidation = App\MediaValidator::inspect($media);
        if (!$mediaValidation['selectable']) {
            respond([
                'error' => (string)$mediaValidation['reason'],
                'media_key' => $mediaKey,
                'validation' => $mediaValidation,
            ], 422);
        }

        $name = internalName(
            $defaultPostValue,
            'Scheduled Post ' . gmdate('Ymd His')
        );

        $scheduledAt = scheduleAt($config, $requestedScheduledAt);
        $cardUri = null;

        if ($websiteUrl !== '') {
            $cardResult = createWebsiteCard(
                $config,
                $accountId,
                $name,
                $headline,
                $websiteUrl,
                $mediaKeys
            );

            $cardStatus = (int)($cardResult['status'] ?? 500);
            $cardBody = is_array($cardResult['body'] ?? null)
                ? $cardResult['body']
                : ['error' => 'Unknown card response.'];

            if ($cardStatus < 200 || $cardStatus >= 300) {
                respond($cardBody, $cardStatus >= 100 ? $cardStatus : 500);
            }

            $cardUri = (string)($cardBody['data']['card_uri'] ?? '');
            if ($cardUri === '') {
                respond(['error' => 'X did not return a card_uri for the website card.'], 502);
            }
        }

        $query = cleanParams([
            'as_user_id' => $userId,
            'scheduled_at' => $scheduledAt,
            'text' => $text !== '' ? $text : null,
            'name' => mb_substr($name, 0, 80),
            'nullcast' => 'false',
            'media_keys' => $cardUri === null && $mediaKeys !== [] ? implode(',', $mediaKeys) : null,
            'card_uri' => $cardUri
        ]);

        $result = xRequest(
            $config,
            'POST',
            "accounts/{$accountId}/scheduled_tweets",
            $query
        );

        $status = (int)($result['status'] ?? 500);
        $body = is_array($result['body'] ?? null)
            ? $result['body']
            : ['error' => 'Unknown proxy response.'];

        if ($status < 200 || $status >= 300) {
            respond($body, $status >= 100 ? $status : 500);
        }

        if ($status === 200 && is_array($body['data'] ?? null)) {
            $body['data']['scheduled_at'] = $body['data']['scheduled_at'] ?? $scheduledAt;
        }

        respond($body, $status >= 100 ? $status : 500);

    case 'preview':
        $id = trim((string)($_GET['id'] ?? ''));

        if ($id === '') {
            respond(['error' => 'Tweet id is required for preview.'], 422);
        }

        // X can briefly return an empty data array while a new scheduled post is
        // being indexed, so retry before deciding that the ID is no longer scheduled.
        $previewResult = [];
        for ($attempt = 0; $attempt < 3; $attempt++) {
            if ($attempt > 0) {
                usleep(400000);
            }

            $previewResult = requestTweetPreview($config, $accountId, $id, 'SCHEDULED');
            $previewBody = is_array($previewResult['body'] ?? null)
                ? $previewResult['body']
                : [];

            if (previewHtml($previewBody) !== null) {
                proxyResult($previewResult);
            }

            $previewStatus = (int)($previewResult['status'] ?? 500);
            if (in_array($previewStatus, [401, 403], true)) {
                proxyResult($previewResult);
            }
        }

        // Once the scheduled post has gone live, X requires tweet_type=PUBLISHED
        // and the generated tweet_id instead of the original scheduled Tweet ID.
        $scheduledResult = xRequest(
            $config,
            'GET',
            "accounts/{$accountId}/scheduled_tweets/" . rawurlencode($id)
        );
        $scheduledBody = is_array($scheduledResult['body'] ?? null)
            ? $scheduledResult['body']
            : [];
        $publishedId = trim((string)($scheduledBody['data']['tweet_id'] ?? ''));

        if ($publishedId !== '') {
            $publishedPreview = requestTweetPreview(
                $config,
                $accountId,
                $publishedId,
                'PUBLISHED'
            );
            $publishedBody = is_array($publishedPreview['body'] ?? null)
                ? $publishedPreview['body']
                : [];

            if (previewHtml($publishedBody) !== null) {
                proxyResult($publishedPreview);
            }
        }

        respond([
            'error' => 'X has not generated the post preview yet. Please try Preview again in a few seconds.',
            'scheduled_status' => $scheduledBody['data']['scheduled_status'] ?? null,
        ], 409);

    default:
        respond([
            'error' => 'Unknown action.',
            'actions' => ['config', 'media', 'tweet', 'preview'],
        ], 404);
}
