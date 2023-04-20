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

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Server\ServerFactory;
use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Runtime;
use Swoole\Process;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;
use MoChat\Framework\Command\Traits\Check;

trait Restart
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
    public function restart($output,$input)
    {
        $this->checkEnvironment($output);
        
        $io = new SymfonyStyle($input, $output);
		$this->check($io);
        $pidFile = BASE_PATH . '/runtime/hyperf.pid';
        $pid = file_exists($pidFile) ? intval(file_get_contents($pidFile)) : false;
        if (!$pid) {
            $io->note('mochat server pid is invalid.');
            return -1;
        }

        if (!Process::kill($pid, SIG_DFL)) {
            $io->note('mochat server process does not exist.');
            return -1;
        }

        if (!Process::kill($pid, SIGTERM)) {
            $io->error('mochat server stop error.');
            return -1;
        }

        while (Process::kill($pid, SIG_DFL)) {
            sleep(1);
        }

        if ($input->getOption('clear') !== false) {
            exec('rm -rf ' . BASE_PATH . '/runtime/container');
        }

        $serverFactory = $this->container->get(ServerFactory::class)
            ->setEventDispatcher($this->container->get(EventDispatcherInterface::class))
            ->setLogger($this->container->get(StdoutLoggerInterface::class));

        $serverConfig = $this->container->get(ConfigInterface::class)->get('server', []);
        if (!$serverConfig) {
            throw new InvalidArgumentException('At least one server should be defined.');
        }

        $serverConfig['settings']['daemonize'] = 1;

        $serverFactory->configure($serverConfig);

        Runtime::enableCoroutine(true, swoole_hook_flags());

        $serverFactory->start();
        return 0;
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
}
