<?php

namespace Neoxygen\NeoToolkit\Composer\Script;

use Composer\Script\Event;
use Neoxygen\NeoToolkit\Factory;
use Symfony\Component\Filesystem\Filesystem;

class ConfigDirectory
{
    public static function create(Event $event)
    {
        $home = Factory::getHomeDir();

        if (file_exists($home) === false) {
            $fs = new Filesystem();
            $fs->mkdir($home, 0755);
            $event->getIO()->write(sprintf("'%s' created.", $home));
        }
    }
}
