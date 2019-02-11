<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2f68d23dd97857b5db6a3ca15c219d79
{
    public static $files = array (
        '6c200413eed8aeea54dbaf934a31b127' => __DIR__ . '/..' . '/weglot/simplehtmldom/src/simple_html_dom.php',
    );

    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Weglot\\' => 7,
        ),
        'P' => 
        array (
            'Psr\\Cache\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Weglot\\' => 
        array (
            0 => __DIR__ . '/..' . '/weglot/weglot-php/src',
        ),
        'Psr\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/cache/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'W' => 
        array (
            'WGSimpleHtmlDom' => 
            array (
                0 => __DIR__ . '/..' . '/weglot/simplehtmldom/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2f68d23dd97857b5db6a3ca15c219d79::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2f68d23dd97857b5db6a3ca15c219d79::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit2f68d23dd97857b5db6a3ca15c219d79::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
