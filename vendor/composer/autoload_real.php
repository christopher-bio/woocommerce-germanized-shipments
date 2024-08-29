<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit07c3a333fa5de311c5e4d8ab8dda9457
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

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit07c3a333fa5de311c5e4d8ab8dda9457', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit07c3a333fa5de311c5e4d8ab8dda9457', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit07c3a333fa5de311c5e4d8ab8dda9457::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
