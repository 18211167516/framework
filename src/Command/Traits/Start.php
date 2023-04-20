<?php

declare(strict_types=1);
/**
 * This file is part of MoChat.
 * @link     https://mo.chat
 * @document https://mochat.wiki
 * @contact  group@mo.chat
 * @license  https://github.com/mochat-cloud/mochat/blob/master/LICENSE
 */
namespace MoChat\Framework\Command\Traits;

use Symfony\Component\Console\Output\OutputInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Server\ServerFactory;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Runtime;
use Swoole\Process;
use MoChat\Framework\Command\Traits\Check;

trait Start
{
    use Check;
      /**
     * @var ContainerInterface
     */
    private $container;
      /**
     * @var int
     */
    private $interval;

    /**
     * @var bool
     */
    private $clear;

    /**
     * @var bool
     */
    private $daemonize;

    /**
     * @var string
     */
    private $php;
    public function start($output,$input)
    {
        $this->checkEnvironment($output);

        $this->check($this->io);

        $this->stopServer();

        $this->clear = ($input->getOption('clear') !== false);

        $this->daemonize = ($input->getOption('daemonize') !== false);


        if ($this->clear) {
            $this->clearRuntimeContainer();
        }

        $this->startServer();
    }


    private function checkEnvironment(OutputInterface $output)
    {
        /**
         * swoole.use_shortname = true       => string(1) "1"     => enabled
         * swoole.use_shortname = "true"     => string(1) "1"     => enabled
         * swoole.use_shortname = on         => string(1) "1"     => enabled
         * swoole.use_shortname = On         => string(1) "1"     => enabled
         * swoole.use_shortname = "On"       => string(2) "On"    => enabled
         * swoole.use_shortname = "on"       => string(2) "on"    => enabled
         * swoole.use_shortname = 1          => string(1) "1"     => enabled
         * swoole.use_shortname = "1"        => string(1) "1"     => enabled
         * swoole.use_shortname = 2          => string(1) "1"     => enabled
         * swoole.use_shortname = false      => string(0) ""      => disabled
         * swoole.use_shortname = "false"    => string(5) "false" => disabled
         * swoole.use_shortname = off        => string(0) ""      => disabled
         * swoole.use_shortname = Off        => string(0) ""      => disabled
         * swoole.use_shortname = "off"      => string(3) "off"   => disabled
         * swoole.use_shortname = "Off"      => string(3) "Off"   => disabled
         * swoole.use_shortname = 0          => string(1) "0"     => disabled
         * swoole.use_shortname = "0"        => string(1) "0"     => disabled
         * swoole.use_shortname = 00         => string(2) "00"    => disabled
         * swoole.use_shortname = "00"       => string(2) "00"    => disabled
         * swoole.use_shortname = ""         => string(0) ""      => disabled
         * swoole.use_shortname = " "        => string(1) " "     => disabled.
         */
        $useShortname = ini_get_all('swoole')['swoole.use_shortname']['local_value'];
        $useShortname = strtolower(trim(str_replace('0', '', $useShortname)));
        if (!in_array($useShortname, ['', 'off', 'false'], true)) {
            $output->writeln('<error>ERROR</error> Swoole short name have to disable before start server, please set swoole.use_shortname = off into your php.ini.');
            exit(0);
        }
    }


    private function clearRuntimeContainer()
    {
        exec('rm -rf ' . BASE_PATH . '/runtime/container');
    }

    private function startServer()
    {
        $serverFactory = $this->container->get(ServerFactory::class)
            ->setEventDispatcher($this->container->get(EventDispatcherInterface::class))
            ->setLogger($this->container->get(StdoutLoggerInterface::class));

        $serverConfig = $this->container->get(ConfigInterface::class)->get('server', []);
        if (!$serverConfig) {
            throw new InvalidArgumentException('At least one server should be defined.');
        }

        if ($this->daemonize) {
            $serverConfig['settings']['daemonize'] = 1;
            $this->io->success('mochat server start success.');
        }

        Runtime::enableCoroutine(true, swoole_hook_flags());

        $serverFactory->configure($serverConfig);

        $serverFactory->start();
    }

    private function stopServer()
    {
        $pidFile = BASE_PATH . '/runtime/hyperf.pid';
        $pid = file_exists($pidFile) ? intval(file_get_contents($pidFile)) : false;
        if ($pid && Process::kill($pid, SIG_DFL)) {
            if (!Process::kill($pid, SIGTERM)) {
                $this->io->error('old mochat server stop error.');
                die();
            }

            while (Process::kill($pid, SIG_DFL)) {
                sleep(1);
            }
        }
    }
   
}
