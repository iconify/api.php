<?php

/**
 * This file is part of the iconify/api package.
 *
 * (c) Vjacheslav Trushkin <cyberalien@gmail.com>
 *
 * For the full copyright and license information, please view the license.txt
 * file that was distributed with this source code.
 * @license MIT
 */

namespace Iconify\API;

use \Iconify\JSONTools\Collection;
use \Iconify\JSONTools\SVG;

class Query {
    /**
     * Generate data for query
     *
     * @param Collection $collection
     * @param string $query
     * @param string $ext
     * @param array $params
     * @return int|array
     */
    public static function parse($collection, $query, $ext, $params)
    {
        switch ($ext) {
            case 'svg':
                // Generate SVG
                // query = icon name
                $icon = $collection->getIconData($query);
                if ($icon === null) {
                    return 404;
                }
                $svg = new SVG($icon);
                $body = $svg->getSVG($params);
                return [
                    'filename'  => $query . '.svg',
                    'type'  => 'image/svg+xml; charset=utf-8',
                    'body'  => $body
                ];

            case 'js':
            case 'json':
                if ($query !== 'icons' || !isset($params['icons']) || !is_string($params['icons'])) {
                    return 404;
                }

                $result = $collection->getIcons(explode(',', $params['icons']));

                if ($result === null || empty($result['icons'])) {
                    return 404;
                }
                if (isset($result['aliases']) && empty($result['aliases'])) {
                    unset($result['aliases']);
                }
                $result = json_encode($result);

                if ($ext === 'js') {
                    if (isset($params['callback'])) {
                        if (!preg_match('/^[a-z0-9_.]+$/i', $params['callback'])) {
                            return 400;
                        }
                        $callback = $params['callback'];
                    } else {
                        $callback = 'SimpleSVG._loaderCallback';
                    }
                    return [
                        'type'  => 'application/javascript; charset=utf-8',
                        'body'  => $callback . '(' . $result . ')'
                    ];
                }
                return [
                    'type'  => 'application/json; charset=utf-8',
                    'body'  => $result
                ];

            default:
                return 404;
        }
    }
}
