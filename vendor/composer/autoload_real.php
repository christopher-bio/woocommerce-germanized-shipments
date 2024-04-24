<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit5fcc11e7cbdafffe3948bfac6c026f4e
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

        spl_autoload_register(array('ComposerAutoloaderInit5fcc11e7cbdafffe3948bfac6c026f4e', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit5fcc11e7cbdafffe3948bfac6c026f4e', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit5fcc11e7cbdafffe3948bfac6c026f4e::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
