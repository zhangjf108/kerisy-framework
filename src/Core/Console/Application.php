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

namespace Kerisy\Core\Console;

use Kerisy;
use Kerisy\Core\Configurable;
use Kerisy\Core\InvalidCallException;
use Kerisy\Core\ObjectTrait;
use Symfony\Component\Console\Application as SymfonyConsole;

/**
 * Class Application
 *
 * @package Kerisy\Core\Console
 */
class Application extends SymfonyConsole implements Configurable
{
    use ObjectTrait;

    public $name = 'Kerisy Console Application';
    public $version = '1.0.0';
    public $kerisy;

    public function __construct($config = [])
    {
        foreach ($config as $name => $value) {
            $this->$name = $value;
        }

        parent::__construct($this->name, $this->version);

        $this->init();
    }

    public function handleExec($operation, $arguments = [])
    {
        $operation = explode('/', $operation);
        $arguments = !empty($arguments) ? explode('_', $arguments) : [];

        $controller = isset($operation[0]) ? trim($operation[0]) : 'index';
        $action = isset($operation[1]) ? trim($operation[1]) : 'index';

        $class = "Console\\Controller\\" . ucfirst($controller) . "Controller";

        if (!class_exists($class)) {
            throw new InvalidCallException("Console Class {$class} Not Found");
        }

        $object = new $class;
        if (!method_exists($object, $action)) {
            throw new InvalidCallException("Console Class {$class}'s Action {$action} Not Found");
        }

        //启动初始化
        Kerisy::$app->bootstrapConsole();

        $object->$action($arguments);

        return 0;
    }

}
