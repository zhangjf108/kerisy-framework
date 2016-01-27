<?php
/**
 * Kerisy Framework
 * 
 * PHP Version 7
 * 
 * @author          Jiaqing Zou <zoujiaqing@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      Core
 * @since           2015/11/11
 * @version         2.0.0
 */

namespace Kerisy\Core;

use Kerisy;
use Kerisy\Log\Logger;
use Kerisy\Http\Request;
use Kerisy\Http\Response;
use Lib\Exception\CustomException;

/**
 * Class Application
 * @package Kerisy\Core
 *
 * @property Kerisy\Http\Request $request
 * @property Kerisy\Http\Response $response
 * @property Kerisy\Session\Manager $session
 * @property Kerisy\Http\View $view
 * @property Kerisy\Auth\Contract $auth
 * @property Kerisy\Log\Logger $log
 * @property Kerisy\DB\Connection $db
 * @property Kerisy\Cache\Cache $cache
 * @property Kerisy\Nosql\Redis $reids
 * @property Kerisy\Search\Solr $solr
 *
 * @property \Lib\Component\BaseComponent $base
 *
 */
class Application extends ServiceLocator
{
    const VERSION = '2.0.0';

    /**
     * The name for the application.
     *
     * @var string
     */
    public $name = 'Kerisy';

    public $debug = false;

    /**
     * Available console commands.
     *
     * @var string[]
     */
    public $commands = [];

    /**
     * @var array
     */
    public $modules = [];

    /**
     * Application component definitions.
     *
     * @var array
     */
    public $components = [];

    /**
     * @var Route
     */
    public $route;

    /**
     * The environment that the application is running on. dev, prod or test.
     *
     * @var string
     */
    public $environment = 'production';

    public $timezone = 'UTC';

    public $runtime;

    /**
     * 是否使用Session
     * @var bool
     */
    public $useSession = false;

    /**
     * Swoole Server
     * @var \swoole_http_server
     */
    public $server;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;
    protected $bootstrapped = false;
    protected $refreshing = [];

    private $_configs;

    public function __construct() {
        Kerisy::$app = $this;
        parent::__construct($this->config('application')->all());
    }

    public function init()
    {
        if (!defined('APPLICATION_PATH') || !file_exists(APPLICATION_PATH)) {
            throw new InvalidParamException("The param: 'APPLICATION_PATH' is invalid");
        }

        $this->components = array_merge($this->defaultComponents(), $this->config('components')->all());

        if (defined('KERISY_ENV')) {
            $this->environment = KERISY_ENV;
        }
    }

    public function bootstrap()
    {
        if (!$this->bootstrapped) {
            $this->initializeConfig();
            $this->registerComponents();
            $this->registerRoutes();
            $this->bootstrapped = true;

            $this->get('log')->info('application started');
        }

        return $this;
    }

    public function bootstrapConsole()
    {
        if (!$this->bootstrapped) {
            $this->initializeConfig();
            $this->registerComponents();
            $this->bootstrapped = true;
        }

        return $this;
    }

    protected function initializeConfig()
    {
        date_default_timezone_set($this->timezone);
    }
    
    protected function registerComponents()
    {
        foreach ($this->components as $id => $definition) {
            //$this->bind($id, $definition);
            $this->set($id, $definition);
        }

        foreach ($this->components as $id => $_) {
            if ($this->get($id) instanceof ShouldBeRefreshed) {
                $this->refreshing[$id] = true;
            }
        }
    }

    /**
     * 获取配置对象
     * @param string $configGroup
     * @return Config
     */
    public function config($configGroup)
    {
        if (!isset($this->_configs[$configGroup]))
        {
            $config = new Config($configGroup);
            $this->_configs[$configGroup] = $config;
        }

        return $this->_configs[$configGroup];
    }

    public function defaultComponents()
    {
        return [
            'errorHandler' => [
                'class' => ErrorHandler::class,
            ],
            'log' => [
                'class' => Logger::class,
            ],
            'request' => [
                'class' => Request::class,
            ],
            'response' => [
                'class' => Response::class,
            ],
        ];
    }

    /**
     * make traditional http request
     * @param array $config
     * @return Kerisy\Http\Request
     */
    public function makeRequest($config = [])
    {
        /**
         * @var \Kerisy\Http\Request
         */
        $request = $this->get('request');

        foreach ($config as $name => $value) {
            $request->$name = $value;
        }

        return $request;
    }

    /**
     * @param Kerisy\Http\Request $request
     * @return Kerisy\Http\Response
     * @throws \Exception
     */
    public function handleRequest($request)
    {
        /**
         * @var \Kerisy\Http\Response
         */
        $response = $this->get('response');

        try {
            $this->exec($request, $response);
            $response->callMiddleware();
        } catch (CustomException $e) {
            $response->data = ['status' => $e->getCode(), 'msg' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->formatException($e, $response);
            $this->get('errorHandler')->handleException($e);
        }

        $response->prepare();

        $this->refreshComponents();

        return $response;
    }

    /**
     * @param \Kerisy\Http\Request $request
     * @param \Kerisy\Http\Response $response
     * @throws HttpException
     */
    protected function exec($request, $response)
    {
        /**
         * @var Route
         */
        $route = $this->dispatch($request);

        $this->route = $route;

        $action = $this->createAction($route);

        //如果已经启用session则初始化SessionId
        if ($this->useSession && $request->initSessionId()) {
            $response->setCookie(new Kerisy\Http\Cookie([
                'name' => $request->sessionKey, 'value' => $request->sessionId, 'expire' => time() + 15 * 86400
            ]));
        }

        $request->callMiddleware();
        
        $result = $this->runAction($action, $request, $response);

        if (!$result instanceof Response && $result != null) {
            $response->data = $result;
        }
    }

    protected function refreshComponents()
    {
        foreach($this->refreshing as $id => $_) {
            //$this->unbind($id);
            //$this->bind($id, $this->components[$id]);
            $this->set($id, $this->components[$id]);
        }
    }

    public function handleConsole($input, $output)
    {
        $app = new \Kerisy\Core\Console\Application([
            'name' => 'Kerisy Command Runner',
            'version' => self::VERSION,
            'kerisy' => $this,
        ]);

        $commands = array_merge($this->commands, [
            'Kerisy\Console\ConsoleCommand',
            'Kerisy\Console\ServerCommand',
        ]);

        foreach ($commands as $command) {
            $app->add(Kerisy::make(['class' => $command, 'kerisy' => $this]));
        }

        return $app->run($input, $output);
    }

    /**
     * @param  Kerisy\Http\Request $request
     * @return bool|Route|mixed|null
     * @throws HttpException
     */
    protected function dispatch($request)
    {
        if (!$route = $this->dispatcher->dispatch($request)) {
            throw new HttpException(404);
        }

        return $route;
    }

    /**
     * Create Action
     * @param Kerisy\Core\Route $route
     * @return array
     * @throws HttpException
     */
    protected function createAction($route)
    {
        $class = "App\\" . ucfirst($route->getModule()) . "\\Controller\\" . ucfirst($route->getPrefix()) . "\\" . ucfirst($route->getController()) . "Controller";

        if (!class_exists($class)) {
            throw new HttpException(404);
        }
;
        $method = $route->getAction();

        //$action = [$this->get($class), $method];
        $action = [new $class, $method];

        return $action;
    }

    /**
     * Run Action
     * @param array $action [controller, action]
     * @param \Kerisy\Http\Request $request
     * @param \Kerisy\Http\Response $response
     * @return mixed
     * @throws HttpException
     */
    protected function runAction($action, $request, $response)
    {
        if (!method_exists($action[0], $action[1])) {
            throw new HttpException(404);
        }

        return call_user_func_array($action, [$request, $response]);
    }

    /**
     * @param $service
     * @return mixed
     */
    public function getService($service)
    {
        return $this->get($service);
    }

    /**
     * Returns the view object.
     * @return \Kerisy\Http\View
     */
    public function getView()
    {
        return $this->get('view');
    }

    /**
     * Return the session object
     *
     * @return \Kerisy\Session\Contract
     */
    public function getSession()
    {
        return $this->get('session');
    }

    /**
     * Helper function to get auth service.
     *
     * @return \Kerisy\auth\Contract
     */
    public function getAuth()
    {
        return $this->get('auth');
    }

    /**
     * Helper function to get current request.
     *
     * @return \Kerisy\Http\Request
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Helper function to get current response.
     *
     * @return \Kerisy\Http\Response
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * Returns the database connection component.
     * @return \Kerisy\DB\Connection the database connection.
     */
    public function getDb()
    {
        return $this->get('db');
    }

    /**
     * Returns the cache connection component
     * @return \Kerisy\Cache\Cache
     */
    public function getCache()
    {
        return $this->get('cache');
    }

    /**
     * Returns the redis component.
     * @param string $instance  redis instance name
     * @return \Redis the redis application component.
     */
    public function getRedis($instance = 'redis')
    {
        return $this->get($instance)->connect();
    }

    /**
     * Returns the solr component.
     * @param string $instance  solr instance name
     * @return \SolrClient the solr application component.
     */
    public function getSolr($instance = 'solr')
    {
        return $this->get($instance)->connect();
    }

    /**
     * Abort the current request.
     *
     * @param $status
     * @param string $message
     * @throws \Kerisy\Core\HttpException
     */
    public function abort($status, $message = null)
    {
        throw new HttpException($status, $message);
    }

    protected function registerRoutes()
    {
        $this->dispatcher = new Dispatcher();
        $this->dispatcher->getRouter()->setConfig($this->config('routes')->all());
    }

    /**
     * 格式化异常
     * @param \Exception $e
     * @param Kerisy\Http\Response $response
     * @throws
     */
    protected function formatException($e, $response)
    {
        if ($e instanceof \Exception) {
            if ($e instanceof HttpException) {
                $response->status($e->statusCode);
            } else {
                $response->status(500);
            }

            if ($this->environment == 'development') {
                $response->data = $this->exceptionToArray($e);
            } else {
                $response->data = ['status' => $response->statusCode, 'msg' => 'just a moment'];
            }
        } else {
            $response->status(200);
            $response->data = ['status' => $response->statusCode, 'msg' => 'just a moment'];
        }
    }

    /**
     * 用数组形式输出异常
     * @param \Exception $exception
     * @return array
     */
    protected function exceptionToArray(\Exception $exception)
    {
        $array = [
            'name' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
        ];

        if ($exception instanceof HttpException) {
            $array['status'] = $exception->statusCode;
        }

        if ($this->debug) {
            $array['file'] = $exception->getFile();
            $array['line'] = $exception->getLine();
            $array['trace'] = explode("\n", $exception->getTraceAsString());
        }

        if (($prev = $exception->getPrevious()) !== null) {
            $array['previous'] = $this->exceptionToArray($prev);
        }

        return $array;
    }

    /************Server Operation*****/
    /**
     * Shutdown the application.
     */
    public function shutdown()
    {
        $this->server->shutdown();
    }

    /**
     * 服务的信息
     */
    public function stats()
    {
        return $this->server->stats();
    }
}
