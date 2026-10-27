<?php

namespace Hughcube\PhpInfo\Themes;

class Php82Theme extends Php74Theme
{
    public function getVersion()
    {
        return '8.2';
    }

    public function getCss()
    {
        return $this->loadCss('php82.css');
    }

    public function renderModuleAnchor($moduleName)
    {
        $anchor = strtolower(urlencode($moduleName));
        return sprintf("<h2><a name=\"module_%s\" href=\"#module_%s\">%s</a></h2>\n", $anchor, $anchor, $moduleName);
    }
}
