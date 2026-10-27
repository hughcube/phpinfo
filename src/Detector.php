<?php

namespace Hughcube\PhpInfo;

use Hughcube\PhpInfo\Support\Buffer;

class Detector
{
    /**
     * @var array
     */
    private const HTML_SAPIS = [
        'fpm-fcgi',
        'apache2handler',
        'apache2filter',
        'apache',
        'cgi-fcgi',
        'cgi',
        'cli-server',
        'litespeed',
        'lsapi',
        'nsapi',
        'phttpd',
        'pi3web',
        'roxen',
        'thttpd',
        'tux',
        'webjames',
        'frankenphp',
    ];

    /**
     * @var array
     */
    private const TEXT_SAPIS = [
        'cli',
        'phpdbg',
        'embed',
    ];

    /**
     * @var bool|null
     */
    private static $cachedIsHtml = null;

    /**
     * @return bool
     */
    public static function isNativeHtml()
    {
        if (self::$cachedIsHtml !== null) {
            return self::$cachedIsHtml;
        }

        $sapi = PHP_SAPI;
        if (in_array($sapi, self::HTML_SAPIS, true)) {
            return self::$cachedIsHtml = true;
        }

        if (in_array($sapi, self::TEXT_SAPIS, true)) {
            return self::$cachedIsHtml = false;
        }

        return self::$cachedIsHtml = self::probe();
    }

    /**
     * @return bool
     */
    private static function probe()
    {
        if (!function_exists('phpinfo')) {
            return false;
        }

        $head = Buffer::capture(function () {
            phpinfo(INFO_GENERAL);
        });

        if ($head === '') {
            return false;
        }

        return strncmp(ltrim($head), '<', 1) === 0;
    }

    /**
     * @return void
     */
    public static function reset()
    {
        self::$cachedIsHtml = null;
    }
}
