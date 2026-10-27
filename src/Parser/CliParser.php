<?php

namespace Hughcube\PhpInfo\Parser;

class CliParser
{
    /**
     * @var array
     */
    const SECTIONS = [
        'Configuration',
        'Additional Modules',
        'Environment',
        'PHP Variables',
        'PHP Credits',
        'PHP License',
    ];

    /**
     * @var array
     */
    const HEADER_KEYS = [
        'FFI support',
        'pcntl support',
        'PDO support',
        'Phar: PHP Archive support',
        'Readline Support',
        'sodium support',
        'SPL support',
        'ZLib Support',
    ];

    /**
     * @var array<string, string>
     */
    private static $extraHeaderKeys = [];

    /**
     * @param array $keys
     * @return void
     */
    public static function addHeaderKeys(array $keys)
    {
        foreach ($keys as $k) {
            self::$extraHeaderKeys[$k] = $k;
        }
    }

    /**
     * @param string $text
     * @return array<int, array<string, mixed>>
     */
    public function parse($text)
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $n = count($lines);
        $i = 0;

        if ($n > 0 && rtrim((string) $lines[0]) === 'phpinfo()') {
            $i = 1;
        }

        $blocks = [];
        $table = null;
        $section = 'general';
        $phpVersion = null;
        $lastWasKv = false;
        $continuationActive = false;
        $pendingValueNewline = false;

        $flush = function () use (&$table, &$blocks) {
            if ($table !== null) {
                if ($table['rows'] !== [] || $table['header'] !== null) {
                    $blocks[] = $table;
                }
                $table = null;
            }
        };

        while ($i < $n) {
            $line = rtrim((string) $lines[$i], "\r");
            $i++;

            if ($line === '') {
                if ($lastWasKv) {
                    $flush();
                } elseif ($continuationActive) {
                    $pendingValueNewline = true;
                }
                $lastWasKv = false;
                $continuationActive = false;
                continue;
            }

            // Separator <hr />
            if (preg_match('/^\s*_{20,}\s*$/', $line)) {
                $flush();
                $lastWasKv = false;
                $blocks[] = ['t' => 'hr'];
                continue;
            }

            // Zend Engine box
            if (strncmp($line, 'This program makes use of the Zend', 33) === 0) {
                $flush();
                $body = '';
                while ($i < $n && trim((string) $lines[$i]) !== '') {
                    $body .= ($body === '' ? '' : "\n") . rtrim((string) $lines[$i], "\r");
                    $i++;
                }
                $blanks = 0;
                while ($i + $blanks < $n && trim((string) $lines[$i + $blanks]) === '') {
                    $blanks++;
                }
                if ($blanks >= 2) {
                    $body .= "\n";
                }
                $lastWasKv = false;
                $blocks[] = ['t' => 'zendbox', 'text' => $body];
                continue;
            }

            // Credits centered header
            if ($section === 'credits' && preg_match('/^\s{2,}(\S.*?\S)\s{2,}$/u', $line, $m) && strlen($line) >= 40) {
                $flush();
                $lastWasKv = false;
                $title = trim($m[1]);
                $table = ['t' => 'table', 'kind' => 'kv', 'header' => null, 'rows' => [], 'colspan' => $title];
                if ($i < $n) {
                    $next = rtrim((string) $lines[$i], "\r");
                    $centered = preg_match('/^\s{2,}(\S.*?\S)\s{2,}$/u', $next) && strlen($next) >= 40;
                    if (trim($next) !== '' && strpos($next, ' => ') === false && !$centered && !in_array($next, self::SECTIONS, true)) {
                        $table['rows'][] = [$next];
                        $i++;
                    }
                }
                continue;
            }

            // Key => value
            if (strpos($line, ' => ') !== false) {
                if ($pendingValueNewline && $table !== null && $table['rows'] !== []) {
                    $ri = count($table['rows']) - 1;
                    $ci = count($table['rows'][$ri]) - 1;
                    $table['rows'][$ri][$ci] .= "\n";
                }
                $pendingValueNewline = false;

                if (preg_match('/^(.*?) => (Array.*)$/', $line, $mm)) {
                    $key = $mm[1];
                    $val = $mm[2];
                    while ($i < $n && !preg_match('/^\)\s*$/', (string) $lines[$i])) {
                        $val .= "\n" . rtrim((string) $lines[$i], "\r");
                        $i++;
                    }
                    if ($i < $n) {
                        $val .= "\n" . rtrim((string) $lines[$i], "\r");
                        $i++;
                    }
                    $cols = [$key, $val];
                } else {
                    $cols = explode(' => ', $line);
                }

                // INI header
                if (count($cols) === 3 && $cols[0] === 'Directive' && $cols[1] === 'Local Value' && $cols[2] === 'Master Value') {
                    $pendingValueNewline = false;
                    $flush();
                    $table = ['t' => 'table', 'kind' => 'ini', 'header' => $cols, 'rows' => []];
                    continue;
                }

                // Variables header
                if (count($cols) === 2 && $cols[0] === 'Variable' && $cols[1] === 'Value') {
                    $pendingValueNewline = false;
                    $flush();
                    $table = ['t' => 'table', 'kind' => $section === 'PHP Variables' ? 'vars' : 'kv', 'header' => $cols, 'rows' => []];
                    continue;
                }

                // Credits 2-column header
                if ($table !== null && $table['rows'] === [] && $table['header'] === null
                    && count($cols) === 2 && in_array($cols[0], ['Contribution', 'Module'], true)) {
                    $table['header'] = $cols;
                    continue;
                }

                // Special header keys
                if (count($cols) === 2 && $this->isHeaderKey($cols[0])) {
                    $flush();
                    $table = ['t' => 'table', 'kind' => 'kv', 'header' => $cols, 'rows' => []];
                    $lastWasKv = false;
                    continue;
                }

                if ($table === null) {
                    $table = ['t' => 'table', 'kind' => 'kv', 'header' => null, 'rows' => []];
                }
                $table['rows'][] = $cols;
                $lastWasKv = true;
                $continuationActive = false;
                continue;
            }

            // Continuation lines
            if (($lastWasKv || $continuationActive) && $table !== null && $table['rows'] !== []) {
                $ri = count($table['rows']) - 1;
                $ci = count($table['rows'][$ri]) - 1;
                $table['rows'][$ri][$ci] .= "\n" . $line;
                $lastWasKv = false;
                $continuationActive = true;
                continue;
            }

            // Non kv line
            $flush();
            $lastWasKv = false;

            // SECTION()
            if (in_array($line, self::SECTIONS, true)) {
                if ($line === 'Configuration' || $line === 'PHP Credits') {
                    $blocks[] = ['t' => 'h1', 'text' => $line];
                } else {
                    $blocks[] = ['t' => 'h2', 'text' => $line];
                }
                if ($line === 'PHP License') {
                    $flush();
                    $blocks[] = ['t' => 'license'];
                }
                $section = $line === 'PHP Credits' ? 'credits'
                    : ($line === 'PHP License' ? 'license'
                    : ($line === 'Additional Modules' ? 'additional' : $line));
                continue;
            }

            if ($section === 'license') {
                continue;
            }

            if ($section === 'additional' && $line === 'Module Name') {
                $table = ['t' => 'table', 'kind' => 'modules', 'header' => ['Module Name'], 'rows' => []];
                continue;
            }

            if ($section === 'additional') {
                if ($table === null) {
                    $table = ['t' => 'table', 'kind' => 'modules', 'header' => null, 'rows' => []];
                }
                $table['rows'][] = [$line];
                continue;
            }

            if ($section === 'credits') {
                $value = '';
                if ($i < $n) {
                    $next = rtrim((string) $lines[$i], "\r");
                    $isCentered = preg_match('/^\s{2,}(\S.*?\S)\s{2,}$/u', $next) && strlen($next) >= 40;
                    if (trim($next) !== '' && strpos($next, ' => ') === false && !$isCentered && !in_array($next, self::SECTIONS, true)) {
                        $value = $next;
                        $i++;
                    }
                }
                $blocks[] = ['t' => 'credit1', 'title' => $line, 'value' => $value];
                continue;
            }

            if ($section === 'Configuration') {
                if ($this->looksLikeProse($line)) {
                    $body = $line;
                    while ($i < $n && trim((string) $lines[$i]) !== '' && strpos((string) $lines[$i], ' => ') === false
                        && !in_array(trim((string) $lines[$i]), self::SECTIONS, true)) {
                        $body .= "\n" . rtrim((string) $lines[$i], "\r");
                        $i++;
                    }
                    $blocks[] = strpos($body, "\n") === false
                        ? ['t' => 'header1', 'text' => $body]
                        : ['t' => 'box', 'text' => $body];
                    continue;
                }
                $blocks[] = ['t' => 'module', 'name' => $line];
                continue;
            }

            $blocks[] = ['t' => 'box', 'text' => $line];
        }

        $flush();

        // Extract PHP Version to banner block
        $out = [];
        $hasBanner = false;
        foreach ($blocks as $b) {
            $type = isset($b['t']) ? $b['t'] : '';
            $firstCol = isset($b['rows'][0][0]) ? $b['rows'][0][0] : null;
            if ($phpVersion === null && $type === 'table' && $firstCol === 'PHP Version') {
                $phpVersion = $b['rows'][0][1];
                $out[] = ['t' => 'banner', 'version' => $phpVersion];
                $hasBanner = true;
                $b['rows'] = array_slice($b['rows'], 1);
                if ($b['rows'] !== []) {
                    $out[] = $b;
                }
                continue;
            }
            if ($type === 'banner') {
                $hasBanner = true;
            }
            $out[] = $b;
        }

        if (!$hasBanner) {
            array_unshift($out, ['t' => 'banner', 'version' => PHP_VERSION]);
        }

        return $out;
    }

    /**
     * @param string $line
     * @return bool
     */
    private function looksLikeProse($line)
    {
        if (strlen($line) > 55) {
            return true;
        }

        return (bool) preg_match('/\.\s*$/u', $line) && strpos($line, ' ') !== false;
    }

    /**
     * @param string $key
     * @return bool
     */
    private function isHeaderKey($key)
    {
        return in_array($key, self::HEADER_KEYS, true) || isset(self::$extraHeaderKeys[$key]);
    }
}
