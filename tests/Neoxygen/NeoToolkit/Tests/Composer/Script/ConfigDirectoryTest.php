<?php

namespace Neoxygen\NeoToolkit\Tests\Composer\Script;

use Composer\IO\ConsoleIO;
use Composer\Script\Event;
use Neoxygen\NeoToolkit\Composer\Script\ConfigDirectory;
use Neoxygen\NeoToolkit\Factory;
use org\bovigo\vfs\vfsStream;

class ConfigDirectoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OutputInterface
     */
    protected $outputMock;

    /**
     * @var Event
     */
    protected $event;

    /**
     * @var  vfsStreamDirectory
     */
    protected $home;

    protected function setUp()
    {
        parent::setUp();

        // Set up Composer environment
        $composerMock = $this->getMock('Composer\Composer');
        $inputMock = $this->getMock('Symfony\Component\Console\Input\InputInterface');
        $this->outputMock = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $helperMock = $this->getMock('Symfony\Component\Console\Helper\HelperSet');
        $consoleIO = new ConsoleIO($inputMock, $this->outputMock, $helperMock);

        $this->event = new Event('post-install-cmd', $composerMock, $consoleIO, true);

        $root = vfsStream::setup('homedir');
        $this->home = vfsStream::url('homedir');
        putenv("HOME={$this->home}");
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function testCreateConfigDirectoryCreatedIfNotExists()
    {
        $home = Factory::getHomeDir();
        $this->assertFileNotExists($home); 

        $this->outputMock->expects($this->once())
            ->method('write')
            ->with(sprintf("'%s' created.", $home));

        ConfigDirectory::create($this->event);

        $this->assertFileExists($home); 
        $this->assertEquals(40755, decoct(fileperms($home)));
    }

    public function testCreateConfigDirectoryNotCreatedIfExists()
    {
        $home = Factory::getHomeDir();
        mkdir($home, 0755);
        $this->assertFileExists($home); 

        $this->outputMock->expects($this->never())
            ->method('write');

        ConfigDirectory::create($this->event);
    }
}
