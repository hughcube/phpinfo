<h1 align="center">hughcube/phpinfo</h1>

<p align="center">
    Restore <code>phpinfo()</code> plain text output in CLI, Swoole, Workerman, and RoadRunner into <b>1:1 pixel-perfect native HTML</b> across PHP versions.
</p>

<p align="center">
    <a href="https://github.com/hughcube/phpinfo/actions"><img src="https://github.com/hughcube/phpinfo/workflows/Test/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/v/hughcube/phpinfo.svg" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/php-v/hughcube/phpinfo.svg" alt="PHP Version Support"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/dt/hughcube/phpinfo.svg" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/hughcube/phpinfo"><img src="https://img.shields.io/packagist/l/hughcube/phpinfo.svg" alt="License"></a>
</p>

<p align="center">
    <b>English</b> | <a href="README.zh-CN.md">简体中文</a>
</p>

---

## 💡 Why This Package?

In PHP core (`ext/standard/info.c`), whether `phpinfo()` outputs formatted HTML or plain text is dictated by an internal SAPI structure member: `sapi_module.phpinfo_as_text`:
* **Web SAPIs (FPM, Apache mod_php, cli-server)**: `phpinfo_as_text = 0` (outputs full HTML tables with official CSS styling and inline logos).
* **CLI SAPIs (CLI, Swoole HTTP server, Workerman, RoadRunner)**: `phpinfo_as_text = 1` (outputs plain text `key => value` lists).

When building microservices or APIs with Swoole, Workerman, or Laravel Octane, developers often expose an internal `/phpinfo` management endpoint. However, calling `phpinfo()` in these environments outputs unreadable, unstyled raw text.

**`hughcube/phpinfo`** solves this by capturing the CLI output stream and parsing it through an in-memory AST state machine, reassembling it into the **exact 1:1 pixel-perfect native HTML** matching the active runtime PHP version.

---

## 🌟 Key Features

* **⚡ Auto Environment Detection**: Intelligently senses if the current SAPI natively outputs HTML (e.g. FPM, Apache).
* **🔄 Adaptive Zero-Overhead Output**:
  * Under FPM / Apache / cli-server: passes through native HTML directly (100% byte fidelity, zero overhead).
  * Under Swoole / Workerman / RoadRunner / CLI: captures and reconstructs 1:1 styled HTML.
* **🎨 Version-Accurate Theme Engine**:
  * Faithfully reproduces official PHP stylesheets from PHP 5.3 up to PHP 8.5+.
  * Supports PHP 8.2+ native Dark Mode (`prefers-color-scheme: dark`), Sticky Table Headers, and official Base64-encoded PHP & Zend logos.
* **🛡️ 100% Pure Memory AST Parser**:
  * No subprocesses, no `proc_open`/`exec` calls, completely immune to `disable_functions` restrictions.
* **🧪 Broad Compatibility**: Tested and verified across all PHP versions: `[5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3, 8.4, 8.5]`.

---

## 📦 Installation

```bash
composer require hughcube/phpinfo
```

---

## 🚀 Quick Start

### 1. Adaptive HTML Output (Recommended)

```php
use Hughcube\PhpInfo\PhpInfo;

// Automatically detects environment: passes through native HTML on FPM, restores 1:1 HTML on Swoole/CLI
$html = PhpInfo::html();
```

### 2. Swoole HTTP Server

```php
use Hughcube\PhpInfo\PhpInfo;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;

$server = new Server('0.0.0.0', 9501);

$server->on('request', function (Request $request, Response $response) {
    if ($request->server['request_uri'] === '/phpinfo') {
        $response->header('Content-Type', 'text/html; charset=utf-8');
        $response->end(PhpInfo::html());
        return;
    }

    $response->end('OK');
});

$server->start();
```

### 3. Workerman / Webman

```php
use Hughcube\PhpInfo\PhpInfo;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

$worker->onMessage = function (TcpConnection $connection, Request $request) {
    if ($request->path() === '/phpinfo') {
        $connection->send(new Response(200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ], PhpInfo::html()));
        return;
    }
};
```

### 4. Advanced Options

```php
use Hughcube\PhpInfo\PhpInfo;

// Check if current runtime natively outputs HTML
if (PhpInfo::supportsNativeHtml()) {
    // FPM / Apache
}

// Force pure PHP parsing & rendering (even in FPM)
$html = PhpInfo::forceRender();

// Force rendering with specific version style (e.g., PHP 8.2 Dark Mode)
$darkHtml = PhpInfo::forceRender(INFO_ALL, '8.2');

// Render HTML fragment only (<table> tables without <html>, <head>, <body> tags)
$tablesOnly = PhpInfo::forceRender(INFO_ALL, null, true);
```

---

## 📊 Compatibility Matrix

| PHP Version | Theme Applied | Dark Mode | Sticky Headers | Official Logos |
| :---: | :---: | :---: | :---: | :---: |
| **< 5.6** | PHP 5.6 Theme | ❌ | ❌ | ✅ |
| **5.6 ~ 7.0** | PHP 5.6 Theme | ❌ | ❌ | ✅ |
| **7.0 ~ 7.4** | PHP 7.0 Theme | ❌ | ❌ | ✅ |
| **7.4 ~ 8.2** | PHP 7.4 Theme | ❌ | ❌ | ✅ |
| **8.2 ~ 8.4** | PHP 8.2 Theme | ✅ | ❌ | ✅ |
| **8.4 ~ 8.5** | PHP 8.4 Theme | ✅ | ✅ | ✅ |
| **> 8.5** | PHP 8.5 Theme | ✅ | ✅ | ✅ |

---

## 🧪 Testing

```bash
composer test
```

---

## 📄 License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
