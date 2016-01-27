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
class ServerCommand extends Command
{
    public $name = 'server';
    public $description = 'Kerisy server management';

    protected function configure()
    {
        $this->addArgument('operation', InputArgument::REQUIRED, 'the operation: serve, start, restart or stop');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $operation = $input->getArgument('operation');

        if (!in_array($operation, ['run', 'start', 'restart', 'stop', 'reload'])) {
            throw new InvalidParamException('The <operation> argument is invalid');
        }

        return call_user_func([$this, 'handle' . $operation]);
    }

    /**
     * Run Server
     * @return mixed
     * @throws Kerisy\Core\InvalidConfigException
     */
    protected function handleRun()
    {
        $server = Kerisy::$app->config('service')->all();
        $server['asDaemon'] = 0;
        $server['pidFile'] = RUNTIME_PATH . 'server.pid';

        return Kerisy::make($server)->run();
    }

    /**
     * Start Server
     * @return mixed
     * @throws Kerisy\Core\InvalidConfigException
     */
    protected function handleStart()
    {
        $pidFile = RUNTIME_PATH . 'server.pid';

        if (file_exists($pidFile)) {
            throw new InvalidValueException('The pidfile exists, it seems the server is already started');
        }

        $server = Kerisy::$app->config('service')->all();
        $server['asDaemon'] = 1;
        $server['pidFile'] =$pidFile;

        return Kerisy::make($server)->run();
    }

    /**
     * Restart Server
     * @return mixed
     */
    protected function handleRestart()
    {
        $this->handleStop();
        return $this->handleStart();
    }

    /**
     * Stop Server
     * @return int
     */
    protected function handleStop()
    {
        $pidFile = RUNTIME_PATH . 'server.pid';

        $ret = 1;

        while (file_exists($pidFile) && posix_kill(file_get_contents($pidFile), SIGTERM)) {
            unlink($pidFile);
            $ret = 0;
        }

        return $ret;
    }

    /**
     * Reload Server
     */
    protected function handleReload()
    {
        $pidFile = RUNTIME_PATH . 'server.pid';
        if (file_exists($pidFile) && posix_kill(file_get_contents($pidFile), SIGUSR1)) {
            return 0;
        }
        return 1;
    }
}
