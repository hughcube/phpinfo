<?php

namespace Hughcube\PhpInfo\Tests;

use Hughcube\PhpInfo\Detector;
use Hughcube\PhpInfo\PhpInfo;
use Hughcube\PhpInfo\Themes\Php56Theme;
use Hughcube\PhpInfo\Themes\Php70Theme;
use Hughcube\PhpInfo\Themes\Php74Theme;
use Hughcube\PhpInfo\Themes\Php82Theme;
use Hughcube\PhpInfo\Themes\Php84Theme;
use Hughcube\PhpInfo\Themes\Php85Theme;
use Hughcube\PhpInfo\Themes\ThemeRegistry;

class PhpInfoTest extends TestCase
{
    public function testSupportsNativeHtml()
    {
        $isHtml = PhpInfo::supportsNativeHtml();
        $this->assertTrue(is_bool($isHtml));

        if (PHP_SAPI === 'cli') {
            $this->assertFalse($isHtml);
        }
    }

    public function testAdaptiveHtml()
    {
        $html = PhpInfo::html();
        $this->assertNotEmpty($html);
        $this->assertStrContains('phpinfo()', $html);
        $this->assertStrContains('<table', $html);
        $this->assertStrContains('Configuration', $html);
    }

    public function testForceRender()
    {
        $html = PhpInfo::forceRender();
        $this->assertNotEmpty($html);
        $this->assertStrContains('<!DOCTYPE html', $html);
        $this->assertStrContains('<style type="text/css">', $html);
        $this->assertStrContains('PHP logo', $html);
        $this->assertStrContains('Zend logo', $html);
        $this->assertStrContains('</table>', $html);

        // Ensure real runtime values are present
        $this->assertStrContains(PHP_VERSION, $html);
        $this->assertStrContains(php_uname('s'), $html);
    }

    public function testForceRenderFragmentOnly()
    {
        $fragment = PhpInfo::forceRender(null, null, true);
        $this->assertNotEmpty($fragment);
        $this->assertStrNotContains('<!DOCTYPE html', $fragment);
        $this->assertStrNotContains('<style type="text/css">', $fragment);
        $this->assertStrContains('<table', $fragment);
    }

    public function testThemeResolution()
    {
        // Lower than 5.6 fallbacks to 5.6
        $this->assertInstanceOf(Php56Theme::class, ThemeRegistry::resolve('5.0.0'));
        $this->assertInstanceOf(Php56Theme::class, ThemeRegistry::resolve('5.3.0'));
        $this->assertInstanceOf(Php56Theme::class, ThemeRegistry::resolve('5.5.9'));
        $this->assertInstanceOf(Php56Theme::class, ThemeRegistry::resolve('5.6.40'));

        $this->assertInstanceOf(Php70Theme::class, ThemeRegistry::resolve('7.0.0'));
        $this->assertInstanceOf(Php70Theme::class, ThemeRegistry::resolve('7.1.0'));
        $this->assertInstanceOf(Php74Theme::class, ThemeRegistry::resolve('7.4.33'));
        $this->assertInstanceOf(Php82Theme::class, ThemeRegistry::resolve('8.2.0'));
        $this->assertInstanceOf(Php84Theme::class, ThemeRegistry::resolve('8.4.1'));

        // 8.5 and higher fallback to 8.5
        $this->assertInstanceOf(Php85Theme::class, ThemeRegistry::resolve('8.5.0'));
        $this->assertInstanceOf(Php85Theme::class, ThemeRegistry::resolve('8.6.0'));
        $this->assertInstanceOf(Php85Theme::class, ThemeRegistry::resolve('9.0.0'));
    }

    public function testCustomVersionRender()
    {
        $html82 = PhpInfo::forceRender(null, '8.2');
        $this->assertStrContains('prefers-color-scheme: dark', $html82);

        $html56 = PhpInfo::forceRender(null, '5.6');
        $this->assertStrNotContains('prefers-color-scheme: dark', $html56);
    }
}
