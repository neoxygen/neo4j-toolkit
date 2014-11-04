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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Neoxygen\NeoToolkit\Factory;

/**
 * This command provides information about NeoToolkit.
 */
class ListCommand extends Command
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
            ->setName('db:list')
            ->setDescription('List all the Neo4j instances registered in NeoToolkit')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeStartMessage($output);
        $config = Factory::getConfig();
        if (empty($config['instances'])) {
            $output->writeln('<info>No Neo4j instances registered</info>');
        } else {
            $table = $this->getHelper('table');
            $table
                ->setHeaders(array('NAME', 'VERSION', 'HTTP_PORT', 'LOCATION'))
                ;

            foreach ($config['instances'] as $name => $props) {
                $table->addRow(
                    array(
                        $name, $props['version'], $props['http_port'], $props['location']
                    )
                );
            }
            $table->render($output);
        }
    }

    protected function writeStartMessage(OutputInterface $output)
    {
        $output->writeln('-----------------------');
        $output->writeln('<comment>NeoToolkit Version '.$this->appVersion.'</comment>');
        $output->writeln('<comment>This is a development version, do not use in production environment</comment>');
        $output->writeln('-----------------------');

    }
}
