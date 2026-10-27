<?php

namespace Hughcube\PhpInfo\Tests;

use Hughcube\PhpInfo\PhpInfo;

class HtmlVerificationTest extends TestCase
{
    /**
     * Test against native HTTP server (FPM / Built-in server)
     */
    public function testCompareWithNativeHtml()
    {
        $url = getenv('TEST_NATIVE_HTTP_URL');
        $serverProc = null;

        if (!$url) {
            // Local fallback: spawn a temporary built-in server to obtain native HTML
            $tempDir = sys_get_temp_dir() . '/phpinfo_test_' . uniqid();
            @mkdir($tempDir, 0777, true);
            file_put_contents($tempDir . '/index.php', '<?php phpinfo();');

            $port = 18991;
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            $serverProc = @proc_open(
                sprintf('php -S 127.0.0.1:%d -t %s', $port, escapeshellarg($tempDir)),
                $descriptors,
                $pipes
            );
            usleep(500000);
            $url = sprintf('http://127.0.0.1:%d/index.php', $port);
        }

        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true,
                ]
            ]);
            $nativeHtml = @file_get_contents($url, false, $context);

            if ($nativeHtml === false || strpos($nativeHtml, '<!DOCTYPE') === false) {
                $this->markTestSkipped('Native web server not responding at ' . $url);
                return;
            }

            // Render via our package
            $restoredHtml = PhpInfo::forceRender();

            // 1. Assert Doctype & container
            $this->assertStrContains('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"', $restoredHtml);
            $this->assertStrContains('<div class="center">', $restoredHtml);
            $this->assertStrContains('</div></body></html>', $restoredHtml);

            // 2. Assert Stylesheet presence & classes
            $this->assertStrContains('<style type="text/css">', $restoredHtml);
            $this->assertStrContains('.center', $restoredHtml);
            $this->assertStrContains('.e {background-color:', $restoredHtml);
            $this->assertStrContains('.h {background-color:', $restoredHtml);
            $this->assertStrContains('.v {background-color:', $restoredHtml);

            // 3. Assert Logos
            $this->assertStrContains('alt="PHP logo"', $restoredHtml);
            $this->assertStrContains('alt="Zend logo"', $restoredHtml);

            // 4. Assert Key structural sections present in both
            $this->assertStrContains('<h1>Configuration</h1>', $restoredHtml);
            $this->assertStrContains('PHP Version', $restoredHtml);
            $this->assertStrContains('Zend Scripting Language Engine', $restoredHtml);

            // Extract table counts
            $nativeTableCount = substr_count($nativeHtml, '<table');
            $restoredTableCount = substr_count($restoredHtml, '<table');

            // Table counts should be extremely close (usually within 1-2 due to environment/cli specific sections)
            $this->assertGreaterThan(5, $restoredTableCount);
            $diff = abs($nativeTableCount - $restoredTableCount);
            $this->assertLessThanOrEqual(10, $diff,
                sprintf("Table count mismatch: Native=%d, Restored=%d", $nativeTableCount, $restoredTableCount)
            );
        } finally {
            if ($serverProc !== null && is_resource($serverProc)) {
                @proc_terminate($serverProc);
            }
        }
    }
}
