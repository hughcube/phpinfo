<?php

namespace Hughcube\PhpInfo\Renderer;

use Hughcube\PhpInfo\Themes\ThemeInterface;

class HtmlRenderer
{
    /**
     * @var ThemeInterface
     */
    protected $theme;

    /**
     * @var array
     */
    const STREAM_HASH_KEYS = [
        'Registered PHP Streams',
        'Registered Stream Socket Transports',
        'Registered Stream Filters',
    ];

    /**
     * @var array
     */
    const CREDIT_COLSPAN = [
        'PHP Authors' => 2,
        'SAPI Modules' => 2,
        'Module Authors' => 2,
        'PHP Documentation' => 2,
        'Websites and Infrastructure team' => 2,
        'Debian Packaging' => 1,
    ];

    /**
     * @var array
     */
    const LICENSE = [
        'This program is free software; you can redistribute it and/or modify it under the terms of the PHP License as published by the PHP Group and included in the distribution in the file:  LICENSE',
        'This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.',
        'If you did not receive a copy of the PHP license, or have any questions about PHP licensing, please contact license@php.net.',
    ];

    /**
     * @param ThemeInterface $theme
     */
    public function __construct(ThemeInterface $theme)
    {
        $this->theme = $theme;
    }

    /**
     * @param array<int, array<string, mixed>> $blocks
     * @param bool $fragmentOnly
     * @return string
     */
    public function render(array $blocks, $fragmentOnly = false)
    {
        $phpVersion = PHP_VERSION;
        foreach ($blocks as $b) {
            $t = isset($b['t']) ? $b['t'] : '';
            if ($t === 'banner') {
                $phpVersion = $b['version'];
                break;
            }
        }

        $html = '';

        if (!$fragmentOnly) {
            $html .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"DTD/xhtml1-transitional.dtd\">\n";
            $html .= "<html xmlns=\"http://www.w3.org/1999/xhtml\"><head>\n";
            $html .= "<style type=\"text/css\">\n" . $this->theme->getCss() . "</style>\n";
            $html .= $this->theme->renderTitle($phpVersion);
            $html .= "<meta name=\"ROBOTS\" content=\"NOINDEX,NOFOLLOW,NOARCHIVE\" />";
            $html .= "</head>\n";
            $html .= "<body><div class=\"center\">\n";
        }

        foreach ($blocks as $b) {
            $html .= $this->renderBlock($b);
        }

        if (!$fragmentOnly) {
            $html .= "</div></body></html>";
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $b
     * @return string
     */
    protected function renderBlock(array $b)
    {
        switch ($b['t']) {
            case 'banner':
                return $this->theme->renderBanner((string) $b['version']);

            case 'hr':
                return $this->theme->renderHr();

            case 'h1':
                return $this->theme->renderH1((string) $b['text']);

            case 'h2':
                return $this->theme->renderH2((string) $b['text']);

            case 'module':
                return $this->theme->renderModuleAnchor((string) $b['name']);

            case 'zendbox':
                return $this->theme->renderZendBox((string) $b['text']);

            case 'header1':
                return "<table>\n<tr class=\"h\"><th>" . $this->nl2br((string) $b['text'])
                    . "</th></tr>\n</table>\n";

            case 'box':
                return "<table>\n<tr class=\"v\"><td>\n"
                    . $this->nl2br((string) $b['text'])
                    . "</td></tr>\n</table>\n";

            case 'credit1':
                $v = (string) $b['value'];
                return "<table>\n"
                    . sprintf("<tr class=\"h\"><th>%s</th></tr>\n", $this->esc($b['title']))
                    . "<tr><td class=\"e\">" . ($v === '' ? '<i>no value</i>' : $this->esc($v)) . " </td></tr>\n"
                    . "</table>\n";

            case 'table':
                return $this->renderTable($b);

            case 'license':
                return $this->theme->renderLicense(self::LICENSE);

            default:
                return '';
        }
    }

    /**
     * @param array<string, mixed> $t
     * @return string
     */
    protected function renderTable(array $t)
    {
        $h = "<table>\n";

        if (isset($t['colspan'])) {
            $cols = isset(self::CREDIT_COLSPAN[$t['colspan']])
                ? self::CREDIT_COLSPAN[$t['colspan']]
                : ($t['header'] !== null ? count($t['header']) : 1);
            $h .= sprintf("<tr class=\"h\"><th colspan=\"%d\">%s</th></tr>\n", $cols, $this->esc($t['colspan']));
        }

        if ($t['header'] !== null) {
            $h .= "<tr class=\"h\">";
            foreach ($t['header'] as $th) {
                $h .= "<th>" . $this->esc($th) . "</th>";
            }
            $h .= "</tr>\n";
        }

        $kind = isset($t['kind']) ? $t['kind'] : 'kv';
        foreach ($t['rows'] as $row) {
            if ($kind === 'vars') {
                $v = isset($row[1]) ? $row[1] : '';
                $h .= "<tr><td class=\"e\">" . $this->gpcseKey((string) $row[0]) . "</td><td class=\"v\">"
                    . $this->gpcseValue((string) $v) . "</td></tr>\n";
                continue;
            }

            if ($kind === 'ini') {
                $v1 = isset($row[1]) ? $row[1] : '';
                $v2 = isset($row[2]) ? $row[2] : '';
                $h .= "<tr><td class=\"e\">" . $this->esc($row[0]) . "</td><td class=\"v\">"
                    . $this->iniCell($row[0], $v1) . "</td><td class=\"v\">"
                    . $this->iniCell($row[0], $v2) . "</td></tr>\n";
                continue;
            }

            if ($kind === 'modules') {
                $h .= sprintf("<tr><td class=\"v\">%s</td></tr>\n", $this->esc($row[0]));
                continue;
            }

            if (count($row) === 1) {
                $h .= "<tr><td class=\"e\">" . $this->cell($row[0]) . " </td></tr>\n";
                continue;
            }

            if (count($row) === 3) {
                $h .= "<tr><td class=\"e\">" . $this->cell($row[0]) . " </td>"
                    . "<td class=\"v\">" . $this->cell($row[1]) . " </td>"
                    . "<td class=\"v\">" . $this->cell($row[2]) . " </td></tr>\n";
                continue;
            }

            if (in_array($row[0], self::STREAM_HASH_KEYS, true)) {
                $h .= "<tr><td class=\"e\">" . $this->esc($row[0]) . "</td><td class=\"v\">"
                    . $this->esc($row[1]) . "</td></tr>\n";
                continue;
            }

            if (strncmp((string) $row[1], 'Array', 5) === 0) {
                $h .= "<tr><td class=\"e\">" . $this->esc($row[0]) . " </td><td class=\"v\"><pre>"
                    . $this->esc($row[1]) . "</pre> </td></tr>\n";
                continue;
            }

            $v = isset($row[1]) ? $row[1] : '';
            $h .= "<tr><td class=\"e\">" . $this->cell($row[0]) . " </td>"
                . "<td class=\"v\">" . $this->cell($v) . " </td></tr>\n";
        }

        $h .= "</table>\n";
        return $h;
    }

    protected function iniCell($name, $v)
    {
        if ($v === 'no value' || trim($v) === '') {
            return '<i>no value</i>';
        }

        if (strncmp($name, 'highlight.', 10) === 0 && preg_match('/^#[0-9A-Fa-f]{6}$/', $v)) {
            return sprintf('<font style="color: %s">%s</font>', $v, $v);
        }

        return str_replace(' ', '&nbsp;', $this->esc($v));
    }

    protected function cell($v)
    {
        if ($v === null || trim($v) === '') {
            return '<i>no value</i>';
        }

        return $this->esc($v);
    }

    protected function gpcseKey($key)
    {
        if (preg_match('/^(\$(?:_[A-Z]+|[A-Za-z_][A-Za-z0-9_]*))\[\'(.*)\'\]$/s', $key, $m)) {
            return $m[1] . "['" . $this->esc($m[2]) . "']";
        }

        return $this->esc($key);
    }

    protected function gpcseValue($v)
    {
        if ($v === '') {
            return '<i>no value</i>';
        }

        if (strncmp($v, 'Array', 5) === 0) {
            return '<pre>' . $this->esc($v) . '</pre>';
        }

        return $this->esc($v);
    }

    protected function esc($s)
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    protected function nl2br($s)
    {
        return str_replace("\n", '<br />', $s);
    }
}
