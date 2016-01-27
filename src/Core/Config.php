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

class Config extends Set
{
    public function __construct($configGroup)
    {
        $extName = '.php';
        
        $configFile = CONFIG_PATH . $configGroup . $extName;

        /* 环境变量加载不同扩展名的配置文件 */
        $envExtName = (KERISY_ENV == 'development' ? '.dev' : (KERISY_ENV == 'test' ? '.test' : '')) . $extName;
        $envConfigFile = CONFIG_PATH . $configGroup . $envExtName;
        
        /* ENV配置文件不存在的情况下默认加载正式环境配置文件 */
        if (file_exists($envConfigFile)) {
            $configFile = $envConfigFile;
        } else if (file_exists($configFile)) {
            $configFile = $configFile;
        } else {
            throw new ErrorException('system error', 500);
        }

        $this->data = require $configFile;
    }
}
