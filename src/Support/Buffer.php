<?php

namespace Hughcube\PhpInfo\Support;

class Buffer
{
    /**
     * @param callable $callback
     * @return string
     */
    public static function capture($callback)
    {
        $level = ob_get_level();
        ob_start();

        try {
            $callback();
            return (string) ob_get_clean();
        } finally {
            while (ob_get_level() > $level) {
                @ob_end_clean();
            }
        }
    }
}
