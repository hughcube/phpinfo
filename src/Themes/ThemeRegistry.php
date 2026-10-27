<?php

namespace Hughcube\PhpInfo\Themes;

class ThemeRegistry
{
    /**
     * @var array<string, ThemeInterface>
     */
    private static $themes = [];

    /**
     * @param string|null $version
     * @return ThemeInterface
     */
    public static function resolve($version = null)
    {
        if ($version === null || $version === '') {
            $version = PHP_VERSION;
        }

        // Exact match in registered instances
        if (isset(self::$themes[$version])) {
            return self::$themes[$version];
        }

        // Resolve by version comparison
        if (version_compare($version, '8.4', '>=')) {
            return self::getOrCreate('8.4', Php84Theme::class);
        }

        if (version_compare($version, '8.2', '>=')) {
            return self::getOrCreate('8.2', Php82Theme::class);
        }

        if (version_compare($version, '7.4', '>=')) {
            return self::getOrCreate('7.4', Php74Theme::class);
        }

        if (version_compare($version, '7.0', '>=')) {
            return self::getOrCreate('7.0', Php70Theme::class);
        }

        if (version_compare($version, '5.5', '>=')) {
            return self::getOrCreate('5.6', Php56Theme::class);
        }

        return self::getOrCreate('5.3', Php53Theme::class);
    }

    /**
     * @param string $key
     * @param string $class
     * @return ThemeInterface
     */
    private static function getOrCreate($key, $class)
    {
        if (!isset(self::$themes[$key])) {
            self::$themes[$key] = new $class();
        }

        return self::$themes[$key];
    }

    /**
     * @param string $name
     * @param ThemeInterface $theme
     * @return void
     */
    public static function register($name, ThemeInterface $theme)
    {
        self::$themes[$name] = $theme;
    }
}
