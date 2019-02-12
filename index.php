<?php

include('vendor/autoload.php');
include('./config-default.php');

/**
 * Merge default and custom config
 *
 * @param $default
 * @return array
 */
function getCustomConfig($default)
{
    $config = null;
    if (!@file_exists(__DIR__ . '/config.php')) {
        return $default;
    }

    try {
        if (!@include __DIR__ . '/config.php') {
            return $default;
        }
    } catch (\Throwable $e) {
        return $default;
    }

    if (is_array($config)) {
        return array_replace_recursive($default, $config);
    }
    return $default;
}

/**
 * Check cache headers
 *
 * @param $config
 */
function cacheHeaders($config)
{
    if (!$config['cache'] || !$config['cache']['timeout']) {
        return;
    }
    header('Cache-Control: ' . (empty($config['cache']['private']) ? 'public' : 'private') .
        ', max-age=' . $config['cache']['timeout'] .
        (empty($config['cache']['min-refresh']) ? '' : ', min-refresh=' . $config['cache']['min-refresh'])
    );
    if (empty($config['cache']['private'])) {
        header('Pragma: cache');
    }
}

/**
 * Send cache headers
 *
 * @param $config
 */
function sendCacheHeaders($config)
{
    // Check for caching
    $send = true;
    if (isset($_SERVER['HTTP_PRAGMA']) && strpos($_SERVER['HTTP_PRAGMA'], 'no-cache') !== false) {
        $send = false;
    } elseif (isset($_SERVER['HTTP_CACHE_CONTROL']) && strpos($_SERVER['HTTP_CACHE_CONTROL'], 'no-cache') !== false) {
        $send = false;
    }

    if ($send) {
        cacheHeaders($config);
    }
}

/**
 * Send error message
 *
 * @param int $code
 */
function sendError($code)
{
    http_response_code($code);
    switch ($code) {
        case 400:
            echo 'Bad request';
            break;

        case 404:
            echo 'Not found';
            break;
    }
}

/**
 * Parse query
 *
 * @param string $prefix
 * @param string $query
 * @param string $ext
 */
function parseRequest($prefix, $query, $ext)
{
    global $config;

    // Init registry
    $registry = new Iconify\API\Registry($config['cache-dir'], function() use ($config) {
        $repos = new \Iconify\API\Repositories($config);
        return $repos->locateCollections();
    });

    // Find collection
    $collection = $registry->getCollection($prefix);
    if ($collection === null) {
        sendError(404);
        exit(0);
    }

    // Parse query
    $result = \Iconify\API\Query::parse($collection, $query, $ext, $_GET);

    if (is_numeric($result)) {
        sendError($result);
        exit(0);
    }

    // Get collection cache time
    $time = $registry->getCollectionTime($prefix);
    if ($time) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s T', $time));
    }

    // Send response
    sendCacheHeaders($config);

    header('Content-Type: ' . $result['type']);
    header('ETag: ' . md5($result['body']));

    // Check for download
    if (isset($result['filename']) && isset($_GET['download']) && ($_GET['download'] === '1' || $_GET['download'] === 'true')) {
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    }

    // Echo body and exit
    echo $result['body'];
    exit(0);
}

// Load config
$config = getCustomConfig($config);

// Check Origin
if (isset($_SERVER['HTTP_ORIGIN']) && $config['cors']) {
    header('Access-Control-Allow-Origin: ' . $config['cors']['origins']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: ' . $config['cors']['timeout']);
}

// Access-Control headers are received during OPTIONS requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header('Access-Control-Allow-Methods: ' . $config['cors']['methods']);
    }

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header('Access-Control-Allow-Headers: ' . $config['cors']['headers']);
    }

    cacheHeaders($config);
    exit(0);
}

// Check for cache header
if (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $time = @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($config['cache'] && (!$time || $time > time() - $config['cache']['timeout'])) {
        http_response_code(304);
        exit(0);
    }
}

// Get URL
$url = $_SERVER['REQUEST_URI'];
$script = $_SERVER['SCRIPT_NAME'];
$prefix = substr($script, 0, strlen($script) - strlen('index.php'));
$url = substr($url, strlen($prefix));
$url = explode('?', $url);
$url = $url[0];

if ($url === '') {
    // Index
    http_response_code(301);
    header('Location: ' . $config['index-page']);
    exit(0);
}

if ($url === 'version') {
    // Send version response
    $package = file_get_contents(__DIR__ . '/composer.json');
    $data = json_decode($package, true);
    $version = $data['version'];
    echo 'Iconify API version ', $version, ' (PHP';

    // Try to get region
    if ($config['env-region']) {
        $value = getenv('region');
        if ($value !== false) {
            $config['region'] = $value;
        }
    }
    if ($config['region'] !== '') {
        echo ', ', $config['region'];
    }

    echo ')';
    exit(0);
}

if ($url === 'sync') {
    // Synchronize repository
    if (!isset($_REQUEST['repo']) || !isset($_REQUEST['key']) || !is_string($_REQUEST['repo']) || !is_string($_REQUEST['key'])) {
        sendError(400);
        exit(0);
    }

    $start = time();
    if (!isset($config['sync']) || empty($config['sync']['secret']) || $_REQUEST['key'] !== $config['sync']['secret']) {
        $result = false;
    } else {
        $sync = new \Iconify\API\Sync($config);
        $result = $sync->sync($_REQUEST['repo']);
    }

    // PHP cannot send response and then do stuff, so fake doing stuff few seconds
    $limit = 15;
    $end = time();
    $diff = $end - $start;
    if ($diff < $limit) {
        sleep($limit - $diff);
    }

    exit(0);
}

// Split URL parts
$url_parts = explode('.', $url);
if (count($url_parts) !== 2) {
    sendError(404);
    exit(0);
}
$ext = $url_parts[1];
if (!preg_match('/^[a-z0-9:\/-]+$/', $url_parts[0])) {
    sendError(404);
    exit(0);
}
$url_parts = explode('/', $url_parts[0]);

// Send to correct handler
switch (count($url_parts)) {
    case 1:
        // 1 part request
        if ($ext === 'svg') {
            $parts = explode(':', $url_parts[0]);
            if (count($parts) === 2) {
                // prefix:icon.svg
                parseRequest($parts[0], $parts[1], $ext);
            } elseif (count($parts) === 1) {
                $parts = explode('-', $parts[0]);
                if (count($parts) > 1) {
                    // prefix-icon.svg
                    parseRequest(array_shift($parts), implode('-', $parts), $ext);
                }
            }
        } elseif ($ext === 'js' || $ext === 'json') {
            // prefix.json
            parseRequest($url_parts[0], 'icons', $ext);
        }
        break;

    case 2:
        // 2 part request
        if ($ext === 'js' || $ext === 'json' || $ext === 'svg') {
            // prefix/icon.svg
            // prefix/icons.json
            parseRequest($url_parts[0], $url_parts[1], $ext);
        }
        break;
}

// Invalid request
sendError(404);
exit(0);
