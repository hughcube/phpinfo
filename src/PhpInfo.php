<?php

namespace Hughcube\PhpInfo;

use Hughcube\PhpInfo\Parser\CliParser;
use Hughcube\PhpInfo\Renderer\HtmlRenderer;
use Hughcube\PhpInfo\Support\Buffer;
use Hughcube\PhpInfo\Themes\ThemeRegistry;

class PhpInfo
{
    /**
     * Ability 1: Check if the current environment natively outputs HTML.
     *
     * @return bool
     */
    public static function supportsNativeHtml()
    {
        return Detector::isNativeHtml();
    }

    /**
     * Ability 2: Adaptive output.
     * If running in a native HTML environment (e.g. FPM, Apache, cli-server) and no
     * custom theme version is forced, captures and returns native HTML directly.
     * Otherwise (e.g. CLI, Swoole, Workerman), restores and renders 1:1 HTML.
     *
     * @param int|null $what
     * @param string|null $themeVersion
     * @return string
     */
    public static function html($what = null, $themeVersion = null)
    {
        if (self::supportsNativeHtml() && $themeVersion === null) {
            return Buffer::capture(function () use ($what) {
                phpinfo($what !== null ? $what : INFO_ALL);
            });
        }

        return self::forceRender($what, $themeVersion);
    }

    /**
     * Ability 3: Force rendering via PHP engine.
     *
     * @param int|null $what
     * @param string|null $themeVersion
     * @param bool $fragmentOnly
     * @return string
     */
    public static function forceRender($what = null, $themeVersion = null, $fragmentOnly = false)
    {
        $rawText = Buffer::capture(function () use ($what) {
            phpinfo($what !== null ? $what : INFO_ALL);
        });

        $blocks = (new CliParser())->parse($rawText);
        $theme = ThemeRegistry::resolve($themeVersion);

        return (new HtmlRenderer($theme))->render($blocks, $fragmentOnly);
    }

    /**
     * Render and emit directly with Content-Type header.
     *
     * @param int|null $what
     * @param string|null $themeVersion
     * @return void
     */
    public static function render($what = null, $themeVersion = null)
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('X-PhpInfo-Mode: ' . (self::supportsNativeHtml() && $themeVersion === null ? 'native' : 'restored'));
        }

        echo self::html($what, $themeVersion);
    }

    /**
     * Register additional extension header keys for <th> rendering.
     *
     * @param array $keys
     * @return void
     */
    public static function addHeaderKeys(array $keys)
    {
        CliParser::addHeaderKeys($keys);
    }
}
