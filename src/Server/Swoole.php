<?php
/**
 * Kerisy Framework
 * 
 * PHP Version 7
 * 
 * @author          Jiaqing Zou <zoujiaqing@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      Server
 * @since           2015/11/11
 * @version         2.0.0
 */

namespace Kerisy\Server;

use Kerisy;

/**
 * A Swoole based server implementation.
 *
 * @package Kerisy\Server
 */
class Swoole extends Base
{
    /**
     * The number of requests each process should execute before respawning, This can be useful to work around
     * with possible memory leaks.
     *
     * @var int
     */
    public $maxRequests = 65535;

    /**
     * The number of workers should be started to serve requests.
     *
     * @var int
     */
    public $numWorkers;

    /**
     * Detach the server process and run as daemon.
     *
     * @var bool
     */
    public $asDaemon = false;

    /**
     * Specifies the path where logs should be stored in.
     *
     * @var string
     */
    public $logFile;

    /**
     * @var \swoole_http_server
     */
    private $_server;

    private function normalizedConfig()
    {
        $config = [];

        $config['max_request'] = $this->maxRequests;
        $config['daemonize'] = $this->asDaemon;

        if ($this->numWorkers) {
            $config['worker_num'] = $this->numWorkers;
        }

        if ($this->logFile) {
            $config['log_file'] = $this->logFile;
        }

        return $config;
    }

    private function createServer()
    {
        $server = new \swoole_http_server($this->host, $this->port);

        $server->on('start', [$this, 'onServerStart']);
        $server->on('shutdown', [$this, 'onServerStop']);

        $server->on('managerStart', [$this, 'onManagerStart']);

        $server->on('workerStart', [$this, 'onWorkerStart']);
        $server->on('workerStop', [$this, 'onWorkerStop']);

        $server->on('request', [$this, 'onRequest']);

        if (method_exists($this, 'onOpen')) {
            $server->on('open', [$this, 'onOpen']);
        }
        if (method_exists($this, 'onClose')) {
            $server->on('close', [$this, 'onClose']);
        }

        if (method_exists($this, 'onWsHandshake')) {
            $server->on('handshake', [$this, 'onWsHandshake']);
        }
        if (method_exists($this, 'onWsMessage')) {
            $server->on('message', [$this, 'onWsMessage']);
        }

        if (method_exists($this, 'onTask')) {
            $server->on('task', [$this, 'onTask']);
        }
        if (method_exists($this, 'onFinish')) {
            $server->on('finish', [$this, 'onFinish']);
        }

        $server->set($this->normalizedConfig());

        return $server;
    }


    /**
     * Master Process Start
     * @param \swoole_http_server $server
     */
    public function onServerStart($server)
    {
        if (PHP_OS != 'Darwin') {
            cli_set_process_title($this->name . ': master');
        }

        if ($this->pidFile) {
            file_put_contents($this->pidFile, $server->master_pid);
        }
    }

    /**
     * Manager Process Start
     * @param \swoole_http_server $server
     */
    public function onManagerStart($server)
    {
        if (PHP_OS != 'Darwin') {
            cli_set_process_title($this->name . ': manager');
        }
    }

    /**
     * Master Process Stop
     */
    public function onServerStop()
    {
        if ($this->pidFile) {
            unlink($this->pidFile);
        }
    }

    /**
     * Worker Process Start
     * @param \swoole_http_server $server
     * @param int $worker_id
     */
    public function onWorkerStart($server, $worker_id)
    {
        if (PHP_OS != 'Darwin') {
            cli_set_process_title($this->name . ': worker');
        }
        $this->startApp();
    }

    public function onWorkerStop()
    {
        $this->stopApp();
    }

    public function onTask()
    {

    }

    public function onFinish()
    {

    }

    /**
     * Prepare Request
     * @param \swoole_http_request $request
     * @return mixed
     */
    protected function prepareRequest($request)
    {
        //list($host, $port) = explode(':', $request->header['host']);

        $get = isset($request->get) ? $request->get : [];
        $post = isset($request->post) ? $request->post : [];
        $cookies = isset($request->cookie) ? $request->cookie : [];
        $files = isset($request->files) ? $request->files : [];

        $config = [
            'protocol' => $request->server['server_protocol'],
            'host' => $request->header['host'],
            'port' => 80,
            'method' => $request->server['request_method'],
            'path' => $request->server['request_uri'],
            'headers' => $request->header,
            'cookies' => $cookies,
            'params' => array_merge($get, $post),
            'content' => $request->rawcontent(),
            'files' => $files
        ];

        return Kerisy::$app->makeRequest($config);
    }

    /**
     * 监听request
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    public function onRequest($request, $response)
    {
        if (XHPROF_OPEN && function_exists("xhprof_enable")) {
            xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
        }

        /**
         * @var \Kerisy\Http\Response
         */
        $res = $this->handleRequest($this->prepareRequest($request));

        $content = $res->content();

        foreach ($res->headers->all() as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            foreach($values as $value) {
                $response->header($name, $value);
            }
        }

        //设置cookie
        foreach ($res->cookies->all() as $name => $cookie) {
            $response->cookie($cookie->name, $cookie->value, $cookie->expire, $cookie->path,
                                $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }

        if (XHPROF_OPEN && function_exists("xhprof_enable")) {
            static $xhprof_runs = null;
            if (!$xhprof_runs) {
                include(APPLICATION_PATH . "libraries/Util/xhprof_lib/utils/xhprof_lib.php");
                include(APPLICATION_PATH . "libraries/Util/xhprof_lib/utils/xhprof_runs.php");
                $xhprof_runs = new \XHProfRuns_Default();
            }

            $xhprof_data = xhprof_disable();
            //记录执行超过200毫秒的请求, xhprof 时间单位是 微秒
            if ($xhprof_data['main()']['wt'] / 1000000 > 0.2) {
                $run_id = $xhprof_runs->save_run($xhprof_data, "kids2_swoole");
                //echo $run_id . PHP_EOL;
            }
        }

        //$response->gzip(1);
        //$response->header('Content-Length', strlen($content));
        $response->status($res->statusCode);
        $response->end($content);
    }

    public function run()
    {
        $this->_server = $this->createServer();

        Kerisy::$app->server = $this->_server;

        $this->_server->start();
    }
}
