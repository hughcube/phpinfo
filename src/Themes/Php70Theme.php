<?php

namespace Hughcube\PhpInfo\Themes;

class Php70Theme extends Php56Theme
{
    public function getVersion()
    {
        return '7.0';
    }

    public function getCss()
    {
        return $this->loadCss('php70.css');
    }

    public function renderModuleAnchor($moduleName)
    {
        $anchor = strtolower(urlencode($moduleName));
        return sprintf("<h2><a name=\"module_%s\">%s</a></h2>\n", $anchor, $moduleName);
    }
}
