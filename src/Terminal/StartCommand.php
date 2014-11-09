<?php

/*
 * This file is part of the NeoToolkit package.
 *
 * (c) Christophe Willemsen <chris@neoxygen.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Neoxygen\NeoToolkit\Terminal;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Neoxygen\NeoToolkit\Factory;
use GuzzleHttp\Client;

/**
 * This command provides information about NeoToolkit.
 */
class StartCommand extends Command
{
    private $appVersion;

    public function __construct($appVersion)
    {
        parent::__construct();

        $this->appVersion = $appVersion;
    }

    protected function configure()
    {
        $this
            ->setName('db:start')
            ->addArgument('name', InputArgument::REQUIRED)
            ->setDescription('Start the given instance name');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeStartMessage($output);
        $config = Factory::getConfig();
        $name = $input->getArgument('name');
        if (!array_key_exists($name, $config['instances'])) {
            $output->writeln('<error>'.sprintf('The instance "%s" is not registered', $name).'</error>');
            exit();
        }
        $port = $config['instances'][$name]['http_port'];
        $location = $config['instances'][$name]['location'];
        if (Factory::checkRunningViaJmx($port, $location)) {
            $output->writeln('<info>'.sprintf('The instance "%s" is already running', $name));
            exit();
        }
        $stop = new Process($location.'/bin/neo4j start');
        $stop->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo $buffer;
            } else {
                echo $buffer;
            }
        });
        $output->writeln('<comment>Checking Api accessibility...</comment>');
        sleep(3);
        if (!Factory::checkRunningViaJmx($port, $location)) {
            $output->writeln('<error>'.sprintf('The instance "%s" could not be stopped', $name).'</error>');
            exit();
        }
        $output->writeln('<info>'.sprintf('The instance "%s" has been successfully stopped', $name).'</info>');

    }

    protected function writeStartMessage(OutputInterface $output)
    {
        $output->writeln('-----------------------');
        $output->writeln('<comment>NeoToolkit Version ' . $this->appVersion . '</comment>');
        $output->writeln('<comment>This is a development version, do not use in production environment</comment>');
        $output->writeln('-----------------------');

    }
}
