<?php

namespace Hughcube\PhpInfo\Themes;

class Php56Theme extends Php53Theme
{
    public function getVersion()
    {
        return '5.6';
    }

    public function getCss()
    {
        return $this->loadCss('php56.css');
    }
}
