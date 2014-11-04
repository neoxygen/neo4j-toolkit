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
class AboutCommand extends Command
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
            ->setName('about')
            ->setDescription('NeoToolkit Help.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = Factory::getConfig();
        
        $output->writeln(sprintf("\n<info>NeoToolkit</info> <comment>%s</comment>", $this->appVersion));
        $output->writeln("=====================");

        $output->writeln(" The NeoToolkit is a set of CLI commands to help you manage your Neo4j databases.\n");

        $output->writeln("Available commands");
        $output->writeln("------------------");

        $output->writeln("   <info>db:list </info>              List the registered Neo4j instances");

        $output->writeln("   <info>db:new    <db-name></info>   Creates a new Neo4j database in the given directory");
        $output->writeln("                              Example: <comment>$ neo db:new socialgraph</comment>\n");

        $output->writeln("   <info>db:switch <db-name></info>   Switch the running instance to the specificed db name");
        $output->writeln("                              Example: <comment>$ neo db:switch recograph</comment>\n");
    }
}
