<?php
/**
 * Kerisy Framework
 * 
 * PHP Version 7
 * 
 * @author          Jiaqing Zou <zoujiaqing@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      functions
 * @since           2015/11/11
 * @version         2.0.0
 */

use Kerisy\Di\Container;
use Kerisy\Core\InvalidConfigException;

class Kerisy
{
    /**
     * @var \Kerisy\Core\Application
     */
    public static $app;

    /**
     * Shortcut helper function to create object via Object Configuration.
     * @param mixed $type
     * @param array $params
     * @return mixed|object
     * @throws InvalidConfigException
     */
    public static function make($type, $params = [])
    {
        if (is_string($type)) {
            return  Container::getInstance()->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return  Container::getInstance()->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return call_user_func($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        } else {
            throw new InvalidConfigException("Unsupported configuration type: " . gettype($type));
        }
    }
}
