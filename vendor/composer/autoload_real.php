<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitbce5f3c6ac578af660f49a0b41498d66
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        spl_autoload_register(array('ComposerAutoloaderInitbce5f3c6ac578af660f49a0b41498d66', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitbce5f3c6ac578af660f49a0b41498d66', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitbce5f3c6ac578af660f49a0b41498d66::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
