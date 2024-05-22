<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitd3656ca99b108a15b3069d6083685174
{
    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'postplanpro\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'postplanpro\\' => 
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
            $loader->prefixLengthsPsr4 = ComposerStaticInitd3656ca99b108a15b3069d6083685174::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitd3656ca99b108a15b3069d6083685174::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitd3656ca99b108a15b3069d6083685174::$classMap;

        }, null, ClassLoader::class);
    }
}