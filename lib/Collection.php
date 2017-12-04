<?php

/**
 * This file is part of the simple-svg/website-icons package.
 *
 * (c) Vjacheslav Trushkin <cyberalien@gmail.com>
 *
 * For the full copyright and license information, please view the license.txt
 * file that was distributed with this source code.
 * @license MIT
 */

namespace SimpleSVG\WebsiteIcons;

class Collection {
    /**
     * @var int Version number used to bust old cache
     */
    protected static $_version = 1;

    /**
     * @var string|null Collection prefix
     */
    public $prefix;

    /**
     * @var bool
     */
    public $loaded = false;

    /**
     * @var array
     */
    protected $_items = null;

    /**
     * @var array
     */
    protected $_result;

    /**
     * Collection constructor.
     *
     * @param string|null $prefix
     */
    public function __construct($prefix = null)
    {
        $this->prefix = $prefix;
    }

    /**
     * Load from file
     *
     * @param string $filename File to load from
     * @param string|null $cacheFile File to save cache
     */
    public function loadFromFile($filename, $cacheFile = null)
    {
        if ($cacheFile !== null && @file_exists($cacheFile)) {
            // Try to load from cache
            $this->loadFromCache($cacheFile, filemtime($filename));
            if ($this->loaded) {
                return;
            }
        }

        // Load from file
        $data = file_get_contents($filename);
        $this->loadJSON($data);

        // Save cache
        if ($this->loaded && $cacheFile !== null) {
            $this->saveCache($cacheFile, filemtime($filename));
        }
    }

    /**
     * Load data from JSON string or decoded array
     *
     * @param string|array $data
     */
    public function loadJSON($data)
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        // Validate
        if (!is_array($data) || !isset($data['icons'])) {
            return;
        }

        // DeOptimize
        $keys = null;
        foreach ($data as $prop => $value) {
            if (is_numeric($value) || is_bool($value)) {
                if ($keys === null) {
                    $keys = array_keys($data['icons']);
                }
                foreach ($keys as $key) {
                    if (!isset($data['icons'][$key][$prop])) {
                        $data['icons'][$key][$prop] = $value;
                    }
                }
                unset ($data[$prop]);
            }
        }

        // Remove prefix from icons
        if (!isset($data['prefix']) || $data['prefix'] === '') {
            if ($this->prefix === null) {
                return;
            }
            $prefixLength = strlen($this->prefix);
            $sliceLength = $prefixLength + 1;

            foreach(['icons', 'aliases'] as $prop) {
                if (!isset($data[$prop])) {
                    continue;
                }
                $newItems = [];
                foreach ($data[$prop] as $key => $item) {
                    if (strlen($key) <= $sliceLength || substr($key, 0, $prefixLength) !== $this->prefix) {
                        return;
                    }
                    $newKey = substr($key, $sliceLength);
                    if (isset($item['parent'])) {
                        $parent = $item['parent'];
                        if (strlen($parent) <= $sliceLength || substr($parent, 0, $prefixLength) !== $this->prefix) {
                            return;
                        }
                        $item['parent'] = substr($parent, $sliceLength);
                    }
                    $newItems[$newKey] = $item;
                }
                $data[$prop] = $newItems;
            }
        } else {
            $this->prefix = $data['prefix'];
        }

        // Success
        $this->_items = $data;
        $this->loaded = true;
    }

    /**
     * Load from cache
     *
     * @param string $filename Cache filename
     * @param int $fileTime Time stamp of source file
     */
    public function loadFromCache($filename, $fileTime = 0)
    {
        $cache_file = null;
        $cache_time = null;
        $cache_version = null;
        $cached_items = null;
        $cached_prefix = null;

        try {
            /** @noinspection PhpIncludeInspection */
            include $filename;
        } catch (\Exception $e) {
            return;
        }

        if (
            $cache_file !== $filename ||
            $cache_version !== self::$_version ||
            $cache_time === null || ($fileTime > 0 && $cache_time !== $fileTime) ||
            $cached_prefix === null ||
            $cached_items === null
        ) {
            return;
        }

        $this->prefix = $cached_prefix;
        $this->_items = $cached_items;
        $this->loaded = true;
    }

    /**
     * Save cache
     *
     * @param string $filename Cache filename
     * @param int $fileTime Time stamp of source file
     */
    public function saveCache($filename, $fileTime)
    {
        if (!$this->loaded) {
            return;
        }
        $content = "<?php \nif (!class_exists('\\\\SimpleSVG\\\\WebsiteIcons\\\\Collection', false)) { die(); }\n
            \$cache_file = " . var_export($filename, true) . ";
            \$cache_time = " . var_export($fileTime, true) . ";
            \$cache_version = " . var_export(self::$_version, true) . ";
            \$cached_prefix = " . var_export($this->prefix, true) . ";
            \$cached_items = " . var_export($this->_items, true) . ";
        ";
        file_put_contents($filename, $content);
        @chmod($filename, 0644);
    }

    // Functions used by getIcons()
    /**
     * Copy icon
     *
     * @param $name
     * @param $iteration
     * @return bool
     */
    protected function _copy($name, $iteration)
    {
        if ($this->_copied($name) || $iteration > 5) {
            return true;
        }
        if (isset($this->_items['icons'][$name])) {
            $this->_result['icons'][$name] = $this->_items['icons'][$name];
            return true;
        }
        if (isset($this->_items['aliases']) && isset($this->_items['aliases'][$name])) {
            if (!$this->_copy($this->_items['aliases'][$name]['parent'], $iteration + 1)) {
                return false;
            }
            $this->_result['aliases'][$name] = $this->_items['aliases'][$name];
            return true;
        }
        return false;
    }

    /**
     * Check if icon has already been copied
     *
     * @param string $name
     * @return bool
     */
    protected function _copied($name)
    {
        return isset($this->_result['icons'][$name]) || isset($this->_result['aliases'][$name]);
    }

    /**
     * Get data for selected icons
     * This function assumes collection has been loaded. Verification should be done during loading
     *
     * @param array $icons
     * @return array
     */
    public function getIcons($icons)
    {
        $this->_result = [
            'prefix'    => $this->prefix,
            'icons' => [],
            'aliases'   => []
        ];

        foreach ($icons as $icon) {
            $this->_copy($icon, 0);
        }

        return $this->_result;
    }

    // Functions used by getIcon()
    /**
     * Merge icon data with $this->_result
     *
     * @param $data
     */
    protected function _mergeIcon($data)
    {
        foreach($data as $key => $value) {
            if (!isset($this->_result[$key])) {
                $this->_result[$key] = $value;
                continue;
            }
            // Merge transformations, ignore the rest because alias overwrites parent items's attributes
            switch ($key) {
                case 'rotate':
                    $this->_result['rotate'] += $value;
                    break;

                case 'hFlip':
                case 'vFlip':
                    $this->_result[$key] = $this->_result[$key] !== $value;
            }
        }
    }

    /**
     * Add missing properties to array
     *
     * @param array $data
     * @return array
     */
    protected function _addMissingAttributes($data)
    {
        $item = array_merge([
            'left'  => 0,
            'top'   => 0,
            'width' => 16,
            'height'    => 16,
            'rotate'    => 0,
            'hFlip' => false,
            'vFlip' => false
        ], $data);
        if (!isset($item['inlineTop'])) {
            $item['inlineTop'] = $item['top'];
        }
        if (!isset($item['inlineHeight'])) {
            $item['inlineHeight'] = $item['height'];
        }
        if (!isset($item['verticalAlign'])) {
            // -0.143 if icon is designed for 14px height,
            // otherwise assume icon is designed for 16px height
            $item['verticalAlign'] = $item['height'] % 7 === 0 && $item['height'] % 8 !== 0 ? -0.143 : -0.125;
        }
        return $item;
    }

    /**
     * Get icon data for SVG
     * This function assumes collection has been loaded. Verification should be done during loading
     *
     * @param string $name
     * @return array|null
     */
    public function getIcon($name)
    {
        if (isset($this->_items['icons'][$name])) {
            return $this->_addMissingAttributes($this->_items['icons'][$name]);
        }

        // Alias
        if (!isset($this->_items['aliases']) || !isset($this->_items['aliases'][$name])) {
            return null;
        }
        $this->_result = $this->_items['aliases'][$name];

        $parent = $this->_items['aliases'][$name]['parent'];
        $iteration = 0;

        while ($iteration < 5) {
            if (isset($this->_items['icons'][$parent])) {
                // Merge with icon
                $this->_mergeIcon($this->_items['icons'][$parent]);
                return $this->_addMissingAttributes($this->_result);
            }

            if (!isset($this->_items['aliases'][$parent])) {
                return null;
            }
            $this->_mergeIcon($this->_items['aliases'][$parent]);
            $parent = $this->_items['aliases'][$parent]['parent'];
            $iteration ++;
        }
        return null;
    }

    /**
     * Get items
     *
     * @return array
     */
    public function getItems()
    {
        return $this->_items;
    }
}
