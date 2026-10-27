<?php

namespace Hughcube\PhpInfo\Themes;

class Php53Theme extends AbstractTheme
{
    public function getVersion()
    {
        return '5.3';
    }

    public function getCss()
    {
        return $this->loadCss('php53.css');
    }

    public function renderTitle($phpVersion)
    {
        return "<title>phpinfo()</title>";
    }

    public function renderModuleAnchor($moduleName)
    {
        return sprintf("<h2><a name=\"module_%s\">%s</a></h2>\n", $moduleName, $moduleName);
    }
}
