<?php

namespace Hughcube\PhpInfo\Themes;

abstract class AbstractTheme implements ThemeInterface
{
    /**
     * @var array|null
     */
    protected static $logos = null;

    /**
     * @var array
     */
    protected static $cssCache = [];

    /**
     * @param string $cssFile
     * @return string
     */
    protected function loadCss($cssFile)
    {
        if (!isset(self::$cssCache[$cssFile])) {
            $path = dirname(dirname(__DIR__)) . '/resources/css/' . $cssFile;
            self::$cssCache[$cssFile] = is_file($path) ? file_get_contents($path) : '';
        }

        return self::$cssCache[$cssFile];
    }

    /**
     * @param string $which
     * @return string
     */
    public function getLogo($which)
    {
        if (self::$logos === null) {
            $path = dirname(dirname(__DIR__)) . '/resources/logos/logos.php';
            self::$logos = is_file($path) ? (array) require $path : [];
        }

        return isset(self::$logos[$which]) ? self::$logos[$which] : '';
    }

    /**
     * @param string $version
     * @return string
     */
    public function renderBanner($version)
    {
        return "<table>\n<tr class=\"h\"><td>\n"
            . "<a href=\"https://www.php.net/\"><img border=\"0\" src=\"" . $this->getLogo('PHP')
            . "\" alt=\"PHP logo\" /></a>"
            . sprintf("<h1 class=\"p\">PHP Version %s</h1>\n", $version)
            . "</td></tr>\n</table>\n";
    }

    /**
     * @param string $body
     * @return string
     */
    public function renderZendBox($body)
    {
        return "<table>\n<tr class=\"v\"><td>\n"
            . "<a href=\"https://www.zend.com/\"><img border=\"0\" src=\"" . $this->getLogo('Zend')
            . "\" alt=\"Zend logo\" /></a>\n"
            . "This program makes use of the Zend Scripting Language Engine:<br />"
            . $this->zendHtmlPuts($body)
            . "</td></tr>\n</table>\n";
    }

    /**
     * @return string
     */
    public function renderHr()
    {
        return "<hr />\n";
    }

    /**
     * @param string $text
     * @return string
     */
    public function renderH1($text)
    {
        return sprintf("<h1>%s</h1>\n", $text);
    }

    /**
     * @param string $text
     * @return string
     */
    public function renderH2($text)
    {
        return sprintf("<h2>%s</h2>\n", $text);
    }

    /**
     * @param array $paragraphs
     * @return string
     */
    public function renderLicense(array $paragraphs)
    {
        $html = "<table>\n<tr class=\"v\"><td>\n";
        foreach ($paragraphs as $p) {
            $html .= "<p>\n" . $p . "\n</p>\n";
        }
        $html .= "</td></tr>\n</table>\n";

        return $html;
    }

    /**
     * @param string $s
     * @return string
     */
    protected function zendHtmlPuts($s)
    {
        return str_replace([' ', "\n"], ['&nbsp;', '<br />'], $s);
    }
}
