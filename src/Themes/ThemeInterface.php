<?php

namespace Hughcube\PhpInfo\Themes;

interface ThemeInterface
{
    /**
     * @return string
     */
    public function getVersion();

    /**
     * @return string
     */
    public function getCss();

    /**
     * @param string $phpVersion
     * @return string
     */
    public function renderTitle($phpVersion);

    /**
     * @param string $moduleName
     * @return string
     */
    public function renderModuleAnchor($moduleName);

    /**
     * @param string $version
     * @return string
     */
    public function renderBanner($version);

    /**
     * @param string $body
     * @return string
     */
    public function renderZendBox($body);

    /**
     * @return string
     */
    public function renderHr();

    /**
     * @param string $text
     * @return string
     */
    public function renderH1($text);

    /**
     * @param string $text
     * @return string
     */
    public function renderH2($text);

    /**
     * @param array $paragraphs
     * @return string
     */
    public function renderLicense(array $paragraphs);

    /**
     * @param string $which 'PHP' or 'Zend'
     * @return string
     */
    public function getLogo($which);
}
