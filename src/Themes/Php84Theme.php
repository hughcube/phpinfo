<?php

namespace Hughcube\PhpInfo\Themes;

class Php84Theme extends Php82Theme
{
    public function getVersion()
    {
        return '8.4';
    }

    public function getCss()
    {
        return $this->loadCss('php84.css');
    }
}
