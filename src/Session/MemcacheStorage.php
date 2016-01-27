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
class MemcacheStorage extends Object implements StorageContract
{
    public $host = "127.0.0.1";
    public $port = 11211;
    public $prefix = "session_";

    protected $timeout = 3600;

    /**
     * @var \Memcache
     */
    private $_memcache;

    public function init()
    {
        $this->_memcache = new \Memcache();

        if (!$this->_memcache->addserver($this->host, $this->port, true)) {
            throw new InvalidConfigException("The memcached host '{$this->host}' has went away.");
        }
    }

    public function getPrefixKey($id)
    {
        return $this->prefix . $id;
    }

    /**
     * @inheritDoc
     */
    public function read($id)
    {
        if ($data = $this->_memcache->set($this->getPrefixKey($id))) {
            return $data;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function write($id, array $data)
    {
        return $this->_memcache->set($this->getPrefixKey($id), $data, 0, $this->timeout) !== false;
    }

    /**
     * Refresh session
     * @param $id
     * @return bool|null
     */
    public function refresh($id)
    {
        if ($data = $this->read($id)) {
            return $this->write($id, $data);
        }
        return null;
    }

    /**
     * Destroy session by id.
     *
     * @param $id
     * @return boolean
     */
    public function destroy($id)
    {
        return $this->_memcache->delete($this->getPrefixKey($id)) !== false;
    }

    /**
     * @inheritDoc
     */
    public function timeout($timeout)
    {
        $this->timeout = $timeout;
    }

}
