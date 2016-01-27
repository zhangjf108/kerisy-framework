<?php
/**
 * Kerisy Framework
 * 
 * PHP Version 7
 * 
 * @author          Jiaqing Zou <zoujiaqing@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      Console
 * @since           2015/11/11
 * @version         2.0.0
 */

namespace Kerisy\Console;

use Kerisy;
use Kerisy\Core\Console\Command;
use Kerisy\Core\InvalidParamException;
use Kerisy\Core\InvalidValueException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class ServerCommand
 *
 * @package Kerisy\Console
 */
class ConsoleCommand extends Command
{
    public $name = 'console';
    public $description = 'Kerisy console management';

    protected function configure()
    {
        $this->addArgument('contronller/action', InputArgument::REQUIRED, 'contronller/action');
        $this->addArgument('arguments', InputArgument::OPTIONAL, '可选参数:如有多个请用下划线代替空格分割 eg:argv1_argv2_...');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = $input->getArgument('contronller/action');
        $arguments = $input->getArgument('arguments');

        $app = new Kerisy\Core\Console\Application();
        return $app->handleExec($operation, $arguments);
    }
}
