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

use \Iconify\IconsJSON\Finder;

class Dirs {
    protected static $_instance = null;

    protected $_config;

    protected $_repos;

    protected $_dirs = [];
    protected $_versions = null;
    protected $_reposBaseDir = null;

    /**
     * Get instance. This class can have only 1 instance
     *
     * @param $config
     * @return Dirs
     */
    public static function instance($config)
    {
        if (self::$_instance === null) {
            self::$_instance = new Dirs($config);
        }
        return self::$_instance;
    }

    /**
     * Constructor
     *
     * @param array $config
     */
    protected function __construct($config)
    {
        $this->_config = $config;
        $this->_repos = [];

        // Setup default directories
        if (!empty($config['serve-default-icons'])) {
            $this->_dirs['iconify'] = Finder::rootDir();
        }

        if (!empty($config['custom-icons-dir'])) {
            $this->_dirs['custom'] = $config['custom-icons-dir'];
        }

        $this->_repos = array_keys($this->_dirs);
        $this->_checkSynchronizedRepositories();
    }

    /**
     * Get repositories time
     *
     * @return array|null
     */
    protected function _checkSynchronizedRepositories()
    {
        if ($this->_versions !== null) {
            return $this->_versions;
        }

        $this->_versions = [];
        if (empty($this->_config['sync']) || empty($this->_config['sync']['secret']) || empty($this->_config['sync']['versions']) || empty($this->_config['sync']['storage'])) {
            // Synchronization is inactive
            return $this->_versions;
        }

        // Check for possible repositories
        foreach($this->_repos as $repo) {
            if (!empty($this->_config['sync'][$repo])) {
                $this->_versions[$repo] = 0;
            }
        }
        if (!count($this->_versions)) {
            return $this->_versions;
        }

        // Check for versions.json
        $data = @file_get_contents($this->_config['sync']['versions']);
        $data = @json_decode($data, true);
        if (!is_array($data)) {
            return $this->_versions;
        }

        if ($this->_reposBaseDir === null) {
            $this->_getBaseReposDir();
        }
        foreach ($data as $repo => $value) {
            if (!isset($this->_versions[$repo])) {
                continue;
            }
            $dir = $this->_reposBaseDir . '/' . $repo . '.' . $value;
            if (@is_dir($dir)) {
                $this->setRepositoryDir($repo, $value, $dir);
            }
        }

        return $this->_versions;
    }

    /**
     * Get root directory for repository
     *
     * @param string $repo
     * @return string
     */
    public function rootDir($repo)
    {
        return isset($this->_dirs[$repo]) ? $this->_dirs[$repo] : '';
    }

    /**
     * Get icons directory
     *
     * @param string $repo
     * @return string
     */
    public function iconsDir($repo)
    {
        switch ($repo) {
            case 'iconify':
                $dir = $this->rootDir($repo);
                return $dir === '' ? '' : $dir . '/json';

            default:
                return $this->rootDir($repo);
        }
    }

    /**
     * Get root directory for repository
     *
     * @param string $repo
     * @param string $time
     * @param string $dir
     */
    public function setRepositoryDir($repo, $time, $dir = '')
    {
        $this->_versions[$repo] = $time;

        if ($dir === '') {
            if ($this->_reposBaseDir === null) {
                $this->_getBaseReposDir();
            }
            $dir = $this->_reposBaseDir . '/' . $repo . '.' . $time;
        }

        $this->_setRootDir($repo, $dir);
    }

    /**
     * Set custom root directory for repository
     *
     * @param string $repo
     * @param string $dir
     */
    protected function _setRootDir($repo, $dir)
    {
        $extraKey = $repo . '-dir';
        if (isset($this->_config['sync']) && !empty($this->_config['sync'][$extraKey])) {
            $extra = $this->_config['sync'][$extraKey];
            if (substr($extra, 0, 1) !== '/') {
                $extra = '/' . $extra;
            }
            if (substr($extra, -1) === '/') {
                $extra = substr($extra, 0, strlen($extra) - 1);
            }
            $dir .= $extra;
        }
        $this->_dirs[$repo] = $dir;
    }

    /**
     * Get all repositories
     *
     * @return array
     */
    public function getRepos()
    {
        return $this->_repos;
    }

    /**
     * Get base repositories directory
     */
    protected function _getBaseReposDir()
    {
        $this->_reposBaseDir = $this->_config['sync']['storage'];
    }

    /**
     * Save versions.json
     */
    public function saveVersions()
    {
        if ($this->_versions === null) {
            return;
        }

        if (empty($this->_config['sync']) || empty($this->_config['sync']['secret']) || empty($this->_config['sync']['versions']) || empty($this->_config['sync']['storage'])) {
            // Synchronization is inactive
            return;
        }

        // Make storage directory
        @mkdir($this->_config['sync']['storage']);

        // Save versions.json
        $filename = $this->_config['sync']['versions'];
        @file_put_contents($filename, json_encode($this->_versions, JSON_PRETTY_PRINT));
    }

    /**
     * Get last repository time for all synchronized repositories
     *
     * @return array|null
     */
    public function getLatestRepos()
    {
        return $this->_versions;
    }
}
