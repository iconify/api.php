<?php

include('vendor/autoload.php');

// Load config
$config = json_decode(file_get_contents(__DIR__ . '/config-default.json'), true);
$customConfig = @file_get_contents(__DIR__ . '/config.json');
if ($customConfig) {
    $customConfig = json_decode($customConfig, true);
    foreach ($customConfig as $key => $value) {
        if (!isset($config[$key])) {
            continue;
        }
        if (is_object($config[$key])) {
            // Merge objects
            foreach ($customConfig[$key] as $key2 => $value2) {
                $config[$key][$key2] = $value2;
            }
        } else {
            $config[$key] = $customConfig[$key];
        }
    }
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
    $registry = new SimpleSVG\WebsiteIcons\Registry(str_replace('{dir}', __DIR__, $config['cache-dir']), function() use ($config) {
        $collections = [];

        // Add premade collections
        if ($config['serve-default-icons']) {
            $list = \SimpleSVG\Icons\Finder::collections();
            foreach ($list as $key => $data) {
                $collections[$key] = \SimpleSVG\Icons\Finder::locate($key);
            }
        }

        foreach ($config['custom-icons-dirs'] as $dir) {
            $res = @opendir(str_replace('{dir}', __DIR__, $dir));
            if ($res !== null) {
                while (($file = readdir($res)) !== false) {
                    $dot = substr($file, 0, 1);
                    if ($dot === '.' || $dot === '_') {
                        continue;
                    }
                    $list = explode('.', $file);
                    if (count($list) !== 2 || $list[1] !== 'json') {
                        continue;
                    }
                    $collections[$list[0]] = $dir . '/' . $file;
                }
                closedir($res);
            }
        }

        return $collections;
    });

    // Find collection
    $collection = $registry->getCollection($prefix);
    if ($collection === null) {
        sendError(404);
        exit(0);
    }

    // Parse query
    $result = \SimpleSVG\WebsiteIcons\Query::parse($collection, $query, $ext, $_GET);

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
    $package = file_get_contents(dirname(__FILE__) . '/composer.json');
    $data = json_decode($package, true);
    $version = $data['version'];
    echo 'SimpleSVG CDN version ', $version, ' (PHP';

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
