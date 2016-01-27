<?php
/**
 * 上哪学.
 * @copyright Copyright (c) 2015 学识堂科技有限公司(Knowledge Academy Technology)
 * @license http://www.51xuetang.com/
 * @author Jeff Zhang <jeff.zhang@51xuetang.com>
 * @Date: 15/5/19 20:49
 *
 * $Id: $
 */

namespace Kerisy\Nosql;

use Kerisy\Core\Object;
use Lib\Util\SimpleLog;

class Redis extends Object
{
    public $host = 'locahost';

    public $port = 6379;

    public $timeout = 1;

    public $database = 0;

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @return \Redis
     * @throws \RedisException
     */
    public function connect()
    {
        if (!$this->redis) {
            $this->redis = new \Redis();
            $this->redis->pconnect($this->host, $this->port, $this->timeout);
            $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            //$this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_IGBINARY);
        } else {
            try {
                $this->redis->ping();
            } catch (\RedisException $e) {
                $this->redis = null;
                SimpleLog::log('redis_error', $e->getMessage());
                throw new \RedisException();
            }
        }
        return $this->redis;
    }

} 