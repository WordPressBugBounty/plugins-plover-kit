<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita4a6d183ce1253ce2dd355793690dd38
{
    public static $files = array (
        '09e163e8d401dbacee2ed31861685b99' => __DIR__ . '/../..' . '/src/helpers.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Plover\\Kit\\' => 11,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Plover\\Kit\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita4a6d183ce1253ce2dd355793690dd38::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita4a6d183ce1253ce2dd355793690dd38::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInita4a6d183ce1253ce2dd355793690dd38::$classMap;

        }, null, ClassLoader::class);
    }
}
