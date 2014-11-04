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

use GuzzleHttp\Exception\RequestException;
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
class SwitchCommand extends Command
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
            ->setName('db:switch')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the instance to be started')
            ->setDescription('Stop all instances and run the given instance named')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeStartMessage($output);
        $config = Factory::getConfig();
        $name = $input->getArgument('name');
        if (!array_key_exists($name, $config['instances'])) {
            $output->writeln('<error>The instance with name '.$name.' is not registered</error>');
            exit();
        }
        $client = new Client();
        $port = $config['instances'][$name]['http_port'];
        $ilocation = $config['instances'][$name]['location'];
        $jmx = 'http://localhost:'.$port.'/db/manage/server/jmx/domain/org.neo4j/instance%3Dkernel%230%2Cname%3DKernel';

        try {
            $kernel = $client->get($jmx);
        } catch (RequestException $e) {

        }
        if (isset($kernel)) {
            $response = (string) $kernel->getBody();
            $info = json_decode($response, true);
            $location = null;
            foreach($info[0]['attributes'] as $info) {
                if ($info['name'] == 'StoreDirectory') {
                    $location = $info['value'];
                    break;
                }
            }
            if (null === $location) {
                exit('error');
            }
            $location = str_replace('/data/graph.db', '', $location);
            if ($location == $ilocation) {
                $output->writeln('<info>Neo4j instance "'.$name.'" already running</info>');
                exit();
            }
            $stop = new Process($location.'/bin/neo4j stop');
            $output->writeln('<info>Stopping already running database on port '.$port.'..</info>');
            $stop->run(function ($type, $buffer) {
    if (Process::ERR === $type) {
        echo $buffer;
    } else {
        echo $buffer;
    }
});
        sleep(2);
        } else {
            $output->writeln('<info>No running databases on port '.$port.'</info>');
        }
        $whereToStart = $ilocation.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'neo4j start-no-wait';
        $start = new Process($whereToStart);
        $output->writeln('<info>Starting the '.$name.' database..</info>');
        $start->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                echo $buffer;
            } else {
                echo $buffer;
            }
        });
        try {
            sleep(10);
            $client->head('http://localhost:'.$port);
        } catch (RequestException $e) {
            $output->writeln('<error>Could not start the "'.$name.'" database</error>');
            $output->writeln('<error>See more info in the log file located in "'.$ilocation.'/data/log/console.log"</error>');
            $output->writeln('<error>'.$e->getMessage().'</error>');
            exit();
        }
        $output->writeln('<info>Database '.$name.' successfully started on port '.$port.'</info>');

    }

    protected function writeStartMessage(OutputInterface $output)
    {
        $output->writeln('-----------------------');
        $output->writeln('<comment>NeoToolkit Version '.$this->appVersion.'</comment>');
        $output->writeln('<comment>This is a development version, do not use in production environment</comment>');
        $output->writeln('-----------------------');

    }
}
