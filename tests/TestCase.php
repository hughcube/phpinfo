<?php

namespace Hughcube\PhpInfo\Tests;

if (!class_exists('PHPUnit\Framework\TestCase') && class_exists('PHPUnit_Framework_TestCase')) {
    class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\Framework\TestCase');
}

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cross-version compatible string assertion
     */
    public static function assertStrContains($needle, $haystack, $message = '')
    {
        if (method_exists(parent::class, 'assertStringContainsString')) {
            parent::assertStringContainsString($needle, $haystack, $message);
        } else {
            parent::assertContains($needle, $haystack, $message);
        }
    }

    /**
     * Cross-version compatible string not contains assertion
     */
    public static function assertStrNotContains($needle, $haystack, $message = '')
    {
        if (method_exists(parent::class, 'assertStringNotContainsString')) {
            parent::assertStringNotContainsString($needle, $haystack, $message);
        } else {
            parent::assertNotContains($needle, $haystack, $message);
        }
    }
}
