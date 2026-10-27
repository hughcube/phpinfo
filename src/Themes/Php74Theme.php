<?php

namespace Hughcube\PhpInfo\Themes;

class Php74Theme extends Php70Theme
{
    public function getVersion()
    {
        return '7.4';
    }

    public function getCss()
    {
        return $this->loadCss('php74.css');
    }

    public function renderTitle($phpVersion)
    {
        return sprintf("<title>PHP %s - phpinfo()</title>", $phpVersion);
    }
}
