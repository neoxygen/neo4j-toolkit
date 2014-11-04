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

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\Progress\Progress;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;
use Neoxygen\NeoToolkit\Factory;
use ZipArchive;

/**
 * This command provides information about NeoToolkit.
 */
class NewCommand extends Command
{
    private $appVersion;

    private $neo4jVersion;

    private $fs;

    public function __construct($appVersion, $neo4jVersion)
    {
        parent::__construct();

        $this->appVersion = $appVersion;
        $this->neo4jVersion = $neo4jVersion;
    }


    protected function configure()
    {
        $this
            ->setName('db:new')
            ->setDescription('Install a new Neo4j version in the given directory')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the directory where Neo4j must be installed')
            ->addArgument('version', InputArgument::OPTIONAL, 'The Neo4j version to be install (default to the latest stable', $this->neo4jVersion)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->fs = new Filesystem();
        $config = Factory::getConfig();
        $name = $input->getArgument('name');
        $v = null !== $input->getArgument('version') ? $input->getArgument('version') : $this->neo4jVersion;
        $this->writeStartMessage($output);
        if (array_key_exists($name, $config['instances'])) {
            $output->writeln('<error>'.sprintf('A Neo4j instance with name "%s" already exists', $name).'</error>');
            exit();
        }
        $dir = getcwd().DIRECTORY_SEPARATOR.$input->getArgument('name');
        $user = get_current_user();
        $homedir = '/Users'.DIRECTORY_SEPARATOR.$user;
        $cacheDir = $homedir.DIRECTORY_SEPARATOR.'.neotoolkit'.DIRECTORY_SEPARATOR.'cache';
        $versionCacheDir = $cacheDir.DIRECTORY_SEPARATOR.'community'.DIRECTORY_SEPARATOR.$v;
        $extractedDir = $cacheDir.DIRECTORY_SEPARATOR.'community'.DIRECTORY_SEPARATOR.'neo4j-community-'.$v;
        $fs = new Filesystem();

        $output->writeln('<info>Creating a new Neo4j database in "'.$dir.'"</info>');

        if (!is_dir($versionCacheDir)) {
            $this->fs->mkdir($cacheDir);
            $output->writeln('<info>Downloading Neo4j Version '.$v.'..</info>');
            $tarGZPath = $versionCacheDir.'.tar.gz';
            $this->download($tarGZPath, $v, $output);
            $cwd = getcwd();
            chdir($cacheDir.DIRECTORY_SEPARATOR.'community');
            exec('tar -xzf '.$v.'.tar.gz');
            chdir($cwd);
            $this->cleanUp($tarGZPath, $versionCacheDir, $extractedDir);
        } elseif (is_dir($versionCacheDir)) {
            $output->writeln('<info>Cache version '.$this->neo4jVersion.' found in "'.$versionCacheDir.'"');
            $process = new Process('cp -Rf '.$versionCacheDir.' '.$dir);
            $process->run();
        }

        $dialog = $this->getHelper('dialog');
        $WEBSERVER_HTTP_PORT = $dialog->ask(
            $output,
            '<question>http port for data, administrative and ui access ? [default: 7474]</question> : ',
            7474
        );
        $WEBSERVER_HTTPS_PORT = $dialog->ask(
            $output,
            '<question>https port for data, administrative and ui access ? [default: 7473]</question> : ',
            7473
        );
        if (null === $WEBSERVER_HTTP_PORT) {
            $WEBSERVER_HTTP_PORT = 7474;
        }
        $output->writeln('<info>Creating server properties file</info>');
        $file = file_get_contents(__DIR__.'/../Resources/config/server/neo4j-server.properties');
        $file = str_replace('$WEBSERVER_HTTP_PORT$', $WEBSERVER_HTTP_PORT, $file);
        $file = str_replace('$WEBSERVER_HTTPS_PORT$', $WEBSERVER_HTTPS_PORT, $file);
        $original = $dir.'/conf/neo4j-server.properties';
        $backup = $original.'.back';
        $backupConfig = new Process('mv '.$original.' '.$backup);
        @$backupConfig->run();
        $nf = new Process('touch '.$original);
        $nf->run();
        file_put_contents($dir.'/conf/neo4j-server.properties', $file);
        $output->writeln('<info>Installation completed</info>');
        $config['instances'][$name] = [
            'version' => $v,
            'location' => $dir,
            'http_port' => $WEBSERVER_HTTP_PORT
        ];
        Factory::setConfig($config);
        $start = $dialog->ask(
            $output,
            '<question>Do you want to start the database now ?</question> [default : no]',
            'no'
        );
        if ($start == 'yes') {
            $launchDir = $dir.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'neo4j start';
            $startdb = new Process($launchDir);
            $startdb->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    echo $buffer;
                } else {
                    echo $buffer;
                }
            });

            try {
                sleep(2);
                $client = new Client();
                $host = 'http://localhost:'.$WEBSERVER_HTTP_PORT.'/';
                echo $host;
                $client->get($host);
                $output->writeln('<info>Neo4j database running on localhost:'.$WEBSERVER_HTTP_PORT.'/</info>');
                $openIt = $dialog->ask(
                    $output,
                    '<question>Do you want to open the database web browser ? </question> [y/(n)] : ',
                    'n'
                );
                if ($openIt == 'y') {
                    $openDB = new Process('open http://localhost:'.$WEBSERVER_HTTP_PORT);
                    $openDB->run();
                }
            } catch (RequestException $e) {
                $output->writeln('<error>The database could not be launched, see the console.log file located in "'.$dir.'/data/log/console.log"</error>');
                $output->writeln('<error>'.$e->getMessage().'</error>');
            }
        }


    }

    protected function writeStartMessage(OutputInterface $output)
    {
        $output->writeln('-----------------------');
        $output->writeln('<comment>NeoToolkit Version '.$this->appVersion.'</comment>');
        $output->writeln('<comment>This is a development version, do not use in production environment</comment>');
        $output->writeln('-----------------------');

    }

    private function download($targetPath, $neoVersion, OutputInterface $output)
    {
        $progressBar = null;
        $downloadCallback = function ($size, $downloaded, $client, $request, Response $response) use ($output, &$progressBar) {
            // Don't initialize the progress bar for redirects as the size is much smaller
            if ($response->getStatusCode() >= 300) {
                return;
            }

            if (null === $progressBar) {
                ProgressBar::setPlaceholderFormatterDefinition('max', function (ProgressBar $bar) {
                    return $this->formatSize($bar->getMaxSteps());
                });
                ProgressBar::setPlaceholderFormatterDefinition('current', function (ProgressBar $bar) {
                    return str_pad($this->formatSize($bar->getStep()), 10, ' ', STR_PAD_LEFT);
                });
                $progressBar = new ProgressBar($output, $size);
                $progressBar->setRedrawFrequency(max(1, floor($size / 1000)));

                if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
                    $progressBar->setEmptyBarCharacter('░');
                    $progressBar->setProgressCharacter('▏');
                    $progressBar->setBarCharacter('▋');
                }

                $progressBar->setBarWidth(60);

                $progressBar->start();
            }

            $progressBar->setCurrent($downloaded);
        };

        $client = new Client();
        $client->getEmitter()->attach(new Progress(null, $downloadCallback));

        $response = $client->get('http://dist.neo4j.org/neo4j-community-'.$neoVersion.'-unix.tar.gz');
        $this->fs->dumpFile($targetPath, $response->getBody());

        if (null !== $progressBar) {
            $progressBar->finish();
            $output->writeln("\n");
        }

        return $this;
    }

    private function cleanUp($tarGZPath, $versionCacheDir, $extractedDir)
    {
        $this->fs->remove($tarGZPath);
        $this->fs->rename($extractedDir, $versionCacheDir);

        return $this;
    }

    private function formatSize($bytes)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = $bytes ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
