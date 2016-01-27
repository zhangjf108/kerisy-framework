<?php
/**
 * Kerisy Framework
 * 
 * PHP Version 7
 * 
 * @author          Jiaqing Zou <zoujiaqing@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      Http
 * @since           2015/11/11
 * @version         2.0.0
 */

namespace Kerisy\Http;

use Kerisy\Core\Object;
use Kerisy\Core\MiddlewareTrait;
use Kerisy\Core\ShouldBeRefreshed;
use Kerisy\Core\InvalidCallException;
use Kerisy\Core\InvalidParamException;

/**
 * Class Response
 *
 * @package Kerisy\Http
 */
class Response extends Object implements ShouldBeRefreshed
{
    use MiddlewareTrait;

    public $data;

    /**
     * @var HeaderBag
     */
    public $headers;

    /**
     * @var CookieBag
     */
    public $cookies;

    public $content;

    public $sessionId = null;

    public $version = '1.0';
    public $charset = 'UTF-8';

    public $statusCode = 200;
    public $statusText;

    /**
     * @var View
     */
    public $view;

    public static $httpStatuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        118 => 'Connection timed out',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range unsatisfiable',
        417 => 'Expectation failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable entity',
        423 => 'Locked',
        424 => 'Method failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway or Proxy Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        507 => 'Insufficient storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    const FORMAT_RAW = 'raw';
    const FORMAT_HTML = 'html';
    const FORMAT_JSON = 'json';
    const FORMAT_JSONP = 'jsonp';
    const FORMAT_XML = 'xml';

    protected $format = self::FORMAT_HTML;
    protected $formatters = [];

    protected $prepared = false;

    public function init()
    {
        $this->headers = new HeaderBag();
        $this->cookies = new CookieBag();
        $this->formatters = $this->defaultFormatters();
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * 设置Cookie
     * @param Cookie $cookie
     */
    public function setCookie(Cookie $cookie)
    {
        $this->cookies->add([$cookie->name => $cookie]);
    }

    public function status($code, $text = null)
    {
        if (!isset(self::$httpStatuses[$code])) {
            throw new InvalidParamException("The HTTP status code is invalid: $code");
        }

        $this->statusCode = $code;

        if ($text === null) {
            $this->statusText = isset(static::$httpStatuses[$this->statusCode]) ? static::$httpStatuses[$this->statusCode] : '';
        } else {
            $this->statusText = $text;
        }
    }

    public function with($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Prepare the response to ready to send to client.
     */
    public function prepare()
    {
        if (!$this->prepared) {
            if (isset($this->formatters[$this->format])) {
                $formatter = $this->formatters[$this->format];
                if (!is_object($formatter)) {
                    $this->formatters[$this->format] = $formatter = \Kerisy::make($formatter);
                }

                if ($formatter instanceof ResponseFormatterInterface) {
                    $formatter->format($this);
                } else {
                    throw new InvalidCallException("The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
                }
            } elseif ($this->format === self::FORMAT_RAW) {
                $this->content = $this->data;
            } else {
                throw new InvalidConfigException("Unsupported response format: {$this->format}");
            }

            if (is_array($this->content)) {
                $this->headers->set('Content-Type', 'application/json; charset=UTF-8');
                $this->content = json_encode($this->content, JSON_UNESCAPED_UNICODE);
            } elseif (is_object($this->content)) {
                if (method_exists($this->content, '__toString')) {
                    $this->content = $this->content->__toString();
                } else {
                    throw new InvalidParamException("Response content must be a string or an object implementing __toString().");
                }
            }

            $this->prepared = true;
        }
    }

    /**
     * Gets the raw response content.
     *
     * @return string
     */
    public function content()
    {
        if (!$this->prepared) {
            $this->prepare();
        }

        return $this->content;
    }

    public function setSessionId($sessionId = null)
    {
        $this->sessionId = $sessionId ? $sessionId : md5(microtime(true) . uniqid('', true) . uniqid('', true));
    }

    /**
     * @return array the formatters that are supported by default
     */
    protected function defaultFormatters()
    {
        return [
            self::FORMAT_HTML => 'Kerisy\Http\HtmlResponseFormatter',
            self::FORMAT_JSON => 'Kerisy\Http\JsonResponseFormatter',
            self::FORMAT_JSONP => [
                'class' => 'Kerisy\Http\JsonResponseFormatter',
                'useJsonp' => true,
            ],
        ];
    }
}
