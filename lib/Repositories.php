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

class Repositories {
    protected $_config;
    protected $_baseDir;

    /**
     * Constructor
     *
     * @param $config
     * @param $baseDir
     */
    public function __construct($config, $baseDir)
    {
        $this->_config = $config;
        $this->_baseDir = $baseDir;
    }

    /**
     * Locate all collections
     *
     * @return array
     */
    public function locateCollections()
    {
        $collections = [];

        $dirs = Dirs::instance($this->_config, $this->_baseDir);
        $repos = $dirs->getRepos();

        foreach ($repos as $repo) {
            $dir = $dirs->iconsDir($repo);
            if ($dir !== '') {
                $collections = array_merge($collections, $this->_scanDirectory($dir));
            }
        }

        return $collections;
    }

    /**
     * Find all collections in directory
     *
     * @param string $dir
     * @return array
     */
    protected function _scanDirectory($dir)
    {
        // List all json files
        $collections = [];
        $res = @opendir($dir);
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

        return $collections;
    }
}
