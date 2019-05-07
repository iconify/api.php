<?php

$config = [
    'region'    => '',
    'env-region'    => true,
    'custom-icons-dir' => __DIR__ . '/json',
    'serve-default-icons' => true,
    'index-page' => 'https://iconify.design/',
    'cache' => [
        'timeout' => 604800,
        'min-refresh' => 604800,
        'private' => false
    ],
    'cors' => [
        'origins' => '*',
        'timeout' => 86400,
        'methods' => 'GET, OPTIONS',
        'headers' => 'Origin, X-Requested-With, Content-Type, Accept, Accept-Encoding',
    ],
    'sync' => [
        'versions' => __DIR__ . '/git-repos/versions.json',
        'storage' => __DIR__ . '/git-repos',
        'git' => 'git clone {repo} --depth 1 --no-tags {target}',
        'secret' => '',
        'iconify' => 'https://github.com/iconify/collections-json.git',
        'custom' => '',
        'custom-dir' => ''
    ],
    'cache-dir' => __DIR__ . '/cache'
];
