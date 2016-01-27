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

/**
 * Class MiddlewareTrait
 *
 * @package Kerisy\Core
 */
trait MiddlewareTrait
{
    public $middleware = [];

    private $_middlewareCalled = false;

    /**
     * Add a new middleware to the middleware stack of the object.
     *
     * @param $definition
     * @param $prepend
     */
    public function middleware($definition, $prepend = false)
    {
        if ($this->_middlewareCalled) {
            throw new InvalidCallException('The middleware stack is already called, no middleware can be added');
        }

        if ($prepend) {
            array_unshift($this->middleware, $definition);
        } else {
            $this->middleware[] = $definition;
        }
    }

    /**
     * Call the middleware stack.
     *
     * @throws InvalidConfigException
     */
    public function callMiddleware()
    {
        if ($this->_middlewareCalled) {
            return;
        }

        foreach ($this->middleware as $definition) {
            $middleware = Kerisy::make($definition);
            if (!$middleware instanceof MiddlewareContract) {
                throw new InvalidConfigException(sprintf("'%s' is not a valid middleware", get_class($middleware)));
            }

            if ($middleware->handle($this) === false) {
                break;
            }
        }

        $this->_middlewareCalled = true;
    }
}
