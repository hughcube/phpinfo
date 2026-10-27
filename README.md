<h1 align="center">hughcube/phpinfo</h1>

<p align="center">
    Restore <code>phpinfo()</code> plain text output in CLI / Swoole / Workerman / RoadRunner into <b>1:1 pixel-perfect native HTML</b> across PHP versions.
</p>

<p align="center">
    <a href="https://github.com/hughcube/phpinfo/actions"><img src="https://github.com/hughcube/phpinfo/workflows/Test/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/v/hughcube/phpinfo.svg" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/l/hughcube/phpinfo.svg" alt="License"></a>
</p>

---

## 💡 Why This Package?

In PHP core (`ext/standard/info.c`), whether `phpinfo()` outputs HTML or plain text is controlled by the internal SAPI flag `sapi_module.phpinfo_as_text`:
* **Web SAPIs (FPM, Apache, cli-server)**: `phpinfo_as_text = 0` (outputs full HTML tables with embedded CSS and logos).
* **CLI SAPIs (CLI, Swoole HTTP server, Workerman, RoadRunner)**: `phpinfo_as_text = 1` (outputs plain text `key => value`).

In Swoole or Workerman, calling `phpinfo()` in an HTTP handler produces raw unformatted text. This package reverse-engineers the CLI text stream through an AST state machine and reconstructs the **1:1 exact native HTML** matching the active PHP version.

---

## 🌟 Key Features

1. **Auto Environment Detection**: Automatically senses whether the current environment natively outputs HTML.
2. **Adaptive Output**:
   * If running under FPM / Apache / cli-server: passes through native HTML (zero overhead, 100% byte fidelity).
   * If running under Swoole / Workerman / CLI: restores 1:1 styled HTML.
3. **Multi-Version Theme Engine**:
   * Accurately reproduces version-specific styles and features across PHP 5.3 ~ 8.5+ (including PHP 8.2+ native Dark Mode, Sticky Headers, etc.).
4. **Zero Subprocess Overhead**:
   * 100% in-memory pure PHP parser. Immune to `disable_functions` restrictions.

---

## 📦 Installation

```bash
composer require hughcube/phpinfo
```

---

## 🚀 Quick Start

### 1. Adaptive Output (Recommended)

```php
use Hughcube\PhpInfo\PhpInfo;

// Automatically detect environment and return 1:1 HTML
$html = PhpInfo::html();
```

### 2. Swoole HTTP Server Integration

```php
use Hughcube\PhpInfo\PhpInfo;
use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$server = new Server('0.0.0.0', 9501);

$server->on('request', function (Request $request, Response $response) {
    if ($request->server['request_uri'] === '/phpinfo') {
        $response->header('Content-Type', 'text/html; charset=utf-8');
        $response->end(PhpInfo::html());
        return;
    }

    $response->end('Hello World');
});

$server->start();
```

### 3. Check Native HTML Support

```php
if (PhpInfo::supportsNativeHtml()) {
    // FPM / Apache environment
} else {
    // Swoole / CLI environment
}
```

### 4. Force Render & Specific Version Styling

```php
// Force pure PHP parser rendering
$html = PhpInfo::forceRender();

// Force rendering with PHP 8.2 Dark Mode theme
$darkHtml = PhpInfo::forceRender(INFO_ALL, '8.2');

// Render fragment only (<table> elements without <html><head> wrapper)
$tableHtml = PhpInfo::forceRender(INFO_ALL, null, true);
```

---

## 🧪 Testing

```bash
composer test
```

---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
