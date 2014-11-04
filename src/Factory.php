<?php

namespace Neoxygen\NeoToolkit;

use Symfony\Component\Yaml\Yaml;

class Factory
{
    public static function getHomeDir()
    {
        if (!getenv('HOME')) {
            throw new \RuntimeException('The "HOME" environment variable must be accessible for NeoToolkit to work');
        }
        $home = rtrim(getenv('HOME'), '/') . '/.neotoolkit';

        return $home;
    }

    public static function getCacheDir()
    {
        $home = self::getHomeDir();
        $cache = $home . DIRECTORY_SEPARATOR . 'cache';

        return $cache;
    }

    public static function getCachedVersionDir($version, $type = 'community')
    {
        $cache = self::getCacheDir();
        $vcache = $cache . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $version;

        return $vcache;
    }

    public static function getConfigFile()
    {
        $home = self::getHomeDir();

        return $home.DIRECTORY_SEPARATOR.'config';
    }

    public static function getConfig()
    {
        $file = self::getConfigFile();

        if (!file_exists($file)) {
            $config = self::createConfigFile();

            return $config;
        }
        $config = Yaml::parse($file);

        return $config;
    }

    public static function setConfig(array $config)
    {
        $file = self::getConfigFile();
        $dump = Yaml::dump($config, 4 , 2);
        file_put_contents($file, $dump);

        return $config;
    }

    public static function createConfigFile()
    {
        $default = [
            'instances' => []
        ];
        return self::setConfig($default);
    }
}