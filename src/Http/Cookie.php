<?php
/**
 * 葡萄科技.
 * @copyright Copyright (c) 2015 Putao Inc.
 * @license http://www.putao.com/
 * @author Jeff Zhang <zhangjf@putao.com>
 * @Date: 15/12/9 23:24
 *
 * $Id $
 */

namespace Kerisy\Http;


use Kerisy\Core\Object;

class Cookie extends Object
{
    /**
     * @var string name of the cookie
     */
    public $name;
    /**
     * @var string value of the cookie
     */
    public $value = '';
    /**
     * @var string domain of the cookie
     */
    public $domain = '';
    /**
     * @var integer the timestamp at which the cookie expires. This is the server timestamp.
     * Defaults to 0, meaning "until the browser is closed".
     */
    public $expire = 0;
    /**
     * @var string the path on the server in which the cookie will be available on. The default is '/'.
     */
    public $path = '/';
    /**
     * @var boolean whether cookie should be sent via secure connection
     */
    public $secure = false;
    /**
     * @var boolean whether the cookie should be accessible only through the HTTP protocol.
     * By setting this property to true, the cookie will not be accessible by scripting languages,
     * such as JavaScript, which can effectively help to reduce identity theft through XSS attacks.
     */
    public $httpOnly = true;


    /**
     * Magic method to turn a cookie object into a string without having to explicitly access [[value]].
     *
     * ~~~
     * if (isset($request->cookies['name'])) {
     *     $value = (string) $request->cookies['name'];
     * }
     * ~~~
     *
     * @return string The value of the cookie. If the value property is null, an empty string will be returned.
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}