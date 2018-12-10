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

class Sync {
    protected $_config;
    protected $_versions;

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->_config = $config;
    }

    /**
     * Synchronize repository
     *
     * @param string $repo
     * @return bool True on success, false on failure
     */
    public function sync($repo)
    {
        $dirs = Dirs::instance($this->_config);
        if (!in_array($repo, $dirs->getRepos()) || empty($this->_config['sync'][$repo])) {
            return false;
        }

        // Clean up old directories
        $this->cleanup();

        // Start synchronizing
        $time = time();
        $url = $this->_config['sync'][$repo];
        $target = $this->_config['sync']['storage'] . '/' . $repo . '.' . $time;

        $cmd = strtr($this->_config['sync']['git'], [
            '{repo}'    => '"' . $url . '"',
            '{target}'  => '"' . $target . '"'
        ]);

        exec($cmd);
        if (!is_dir($target)) {
            return false;
        }

        // Save new version
        $dirs->setRepositoryDir($repo, $time);
        $dirs->saveVersions();

        $this->_purgeCache();

        return true;
    }

    /**
     * Purge old cache
     */
    protected function _purgeCache()
    {
        $dir = $this->_config['cache-dir'];

        foreach (new \DirectoryIterator($dir) as $entry) {
            if (!$entry->isFile()) {
                continue;
            }

            $file = $entry->getFilename();
            $parts = explode('.', $file);

            if (count($parts) < 2 || $file === 'index.php') {
                continue;
            }

            $ext = array_pop($parts);
            if ($ext !== 'php') {
                continue;
            }

            @unlink($dir . '/' . $file);
        }
    }

    /**
     * Remove old repositories
     */
    public function cleanup()
    {
        $base = $this->_config['sync']['storage'];
        $dirs = Dirs::instance($this->_config);
        $repos = $dirs->getLatestRepos();

        // Find directories that require cleaning
        foreach (new \DirectoryIterator($base) as $entry) {
            if (!$entry->isDir() || $entry->isDot()) {
                continue;
            }

            $dir = $entry->getFilename();
            $parts = explode('.', $dir);
            $repo = $parts[0];
            if (count($parts) !== 2 || empty($repos[$repo])) {
                continue;
            }

            $time = intval($parts[1]);
            if ($time > ($repos[$repo] - 3600)) {
                // wait 1 hour before deleting old repository
                continue;
            }

            $this->rmdir($base . '/' . $dir);
        }
    }

    /**
     * Remove directory and contents
     *
     * @param string $dir
     */
    protected function rmdir($dir)
    {
        foreach (new \DirectoryIterator($dir) as $entry) {
            if ($entry->isDot()) {
                continue;
            }
            $filename = $dir . '/' . $entry->getFilename();
            if ($entry->isDir()) {
                $this->rmdir($filename);
            } else {
                unlink($filename);
            }
        }
        rmdir($dir);
    }
}
