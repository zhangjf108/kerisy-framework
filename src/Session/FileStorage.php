<?php
/**
 * Kerisy Framework
 * 
 * PHP Version 7
 * 
 * @author          Jiaqing Zou <zoujiaqing@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      Session
 * @since           2015/11/11
 * @version         2.0.0
 */

namespace Kerisy\Session;

use Kerisy\Core\InvalidConfigException;
use Kerisy\Core\Object;
use Kerisy\Session\Contract as SessionContract;

/**
 * Class FileStorage
 *
 * @package Kerisy\Session
 */
class FileStorage extends Object implements StorageContract
{
    public $path;
    public $divisor = 1000;

    protected $timeout;

    public function init()
    {
        if (!$this->path || !file_exists($this->path) || !is_writable($this->path)) {
            throw new InvalidConfigException("The param: '{$this->path}' is invalid or not writable");
        }

        if (rand(0, $this->divisor) <= 0) {
            $this->gc();
        }
    }

    /**
     * @inheritDoc
     */
    public function read($id)
    {
        if (file_exists($this->path . '/' . $id)) {
            return unserialize(file_get_contents($this->path . '/' . $id));
        }
    }

    /**
     * @inheritDoc
     */
    public function write($id, array $data)
    {
        return file_put_contents($this->path . '/' . $id, serialize($data)) !== false;
    }

    /**
     * @inheritDoc
     */
    public function destroy($id)
    {
        if (file_exists($this->path . '/' . $id)) {
            return unlink($this->path . '/' . $id);
        } else {
            return false;
        }
    }

    /**
     * Refresh session
     * @param $id
     * @return bool|null
     */
    public function refresh($id)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function timeout($timeout)
    {
        $this->timeout = $timeout;
    }

    protected function gc()
    {
        $iterator = new \DirectoryIterator($this->path);
        $now = time();

        foreach ($iterator as $file) {
            if ($file->isDot()) {
                continue;
            }
            if ($file->getMTime() < $now - $this->timeout) {
                unlink($file->getRealPath());
            }
        }
    }
}
