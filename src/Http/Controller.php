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

use Kerisy;

class Controller
{
    public function __construct()
    {

    }

    /**
     * Renders a view and applies layout if available.
     *
     * @param string $template the template name.
     * @param array $params the parameters (name-value pairs) that should be made available in the view.
     * These parameters will not be available in the layout.
     * @return string the rendering result.
     * @throws InvalidParamException if the view file or the layout file does not exist.
     */
    public function render($template, $params = [])
    {
        $view = Kerisy::$app->getView();
        $view->setViewPath(Kerisy::$app->route->getPrefix() . '/' . Kerisy::$app->route->getModule());
        if (!empty($params)) {
            $view->replace($params);
        }
        return $view->render($template);
    }
}
