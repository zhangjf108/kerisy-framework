<?php
/**
 * Kerisy Framework
 *
 * PHP Version 7
 *
 * @author          Jeff Zhang <zhangjf@putao.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      Http
 * @since           2015/11/11
 * @version         2.0.0
 */

namespace Kerisy\Http;

/**
 * Interface ResponseFormatterInterface
 * @package Kerisy\Http
 */
interface ResponseFormatterInterface
{
    /**
     * Formats the specified response.
     * @param Response $response the response to be formatted.
     */
    public function format($response);
}
