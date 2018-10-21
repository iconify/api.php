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

class Registry {
    /**
     * @var int Version number used to bust old cache
     */
    protected static $_version = 1;

    /**
     * @var null|array List of collections
     */
    protected $_collections = null;

    /**
     * @var string|null
     */
    protected $_cacheDir;

    /**
     * Registry constructor.
     *
     * @param string $cacheDir
     * @param callable $callback
     */
    public function __construct($cacheDir, $callback)
    {
        $this->_cacheDir = $cacheDir;
        $cacheFile = $cacheDir ? $cacheDir . '/collections-' . self::$_version . '.php' : null;

        if ($cacheFile !== null && @file_exists($cacheFile)) {
            // Try to load from cache
            $this->_loadFromCache($cacheFile);
            if ($this->_collections) {
                return;
            }
        }

        // Get data from callback
        $this->_collections = $callback();

        // Save cache
        if ($this->_collections && $cacheFile !== null) {
            $this->_saveCache($cacheFile);
        }
    }

    /**
     * Get collection
     *
     * @param string $prefix
     * @return null|Collection
     */
    public function getCollection($prefix)
    {
        if (!$this->_collections || !isset($this->_collections[$prefix])) {
            return null;
        }

        $collection = new Collection($prefix);
        $collection->loadFromFile($this->_collections[$prefix], $this->_cacheDir ? $this->_cacheDir . '/collection-' . self::$_version . '-' . $prefix . '.php' : null);
        return $collection;
    }

    /**
     * Get collection file modification time
     *
     * @param string $prefix
     * @return bool|int
     */
    public function getCollectionTime($prefix)
    {
        return @filemtime($this->_cacheDir ? $this->_cacheDir . '/collection-' . self::$_version . '-' . $prefix . '.php' : $this->_collections[$prefix]);
    }

    /**
     * Load collections list from cache
     *
     * @param string $filename
     */
    protected function _loadFromCache($filename)
    {
        $cache_file = null;
        $cache_version = null;
        $cached_collections = null;

        try {
            /** @noinspection PhpIncludeInspection */
            include $filename;
        } catch (\Exception $e) {
            return;
        }

        if (
            $cache_file !== $filename ||
            $cache_version !== self::$_version ||
            $cached_collections === null
        ) {
            return;
        }

        $this->_collections = $cached_collections;
    }

    /**
     * Save cache
     *
     * @param string $filename Cache filename
     */
    protected function _saveCache($filename)
    {
        $content = "<?php \nif (!class_exists('\\\\Iconify\\\\API\\\\Registry', false)) { die(); }\n
            \$cache_file = " . var_export($filename, true) . ";
            \$cache_version = " . var_export(self::$_version, true) . ";
            \$cached_collections = " . var_export($this->_collections, true) . ";
        ";
        file_put_contents($filename, $content);
        @chmod($filename, 0644);
    }
}
