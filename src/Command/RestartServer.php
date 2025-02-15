<?php

declare(strict_types=1);
/**
 * This file is part of MoChat.
 * @link     https://mo.chat
 * @document https://mochat.wiki
 * @contact  group@mo.chat
 * @license  https://github.com/mochat-cloud/mochat/blob/master/LICENSE
 */
namespace MoChat\Framework\Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use MoChat\Framework\Command\Traits\Restart;

/**
 * @\Hyperf\Command\Annotation\Command()
 */
class RestartServer extends Command
{
    use Restart;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('server:restart');
    }

    protected function configure()
    {
        $this->setDescription('Restart mochat servers.')
            ->addOption('clear', 'c', InputOption::VALUE_OPTIONAL, 'clear runtime container', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return $this->restart($output,$input);
    }
    
}