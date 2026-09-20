# ez-php/logging

Logging module for the [ez-php framework](https://github.com/ez-php/framework) — structured logging with pluggable drivers, minimum-level filtering, JSON formatting, request context injection, and automatic exception logging.

[![CI](https://github.com/ez-php/logging/actions/workflows/ci.yml/badge.svg)](https://github.com/ez-php/logging/actions/workflows/ci.yml)

## Requirements

- PHP 8.5+
- ez-php/framework 0.*

## Installation

```bash
composer require ez-php/logging
```

## Setup

Register the service provider:

```php
$app->register(\EzPhp\Logging\LogServiceProvider::class);
```

Configure in `config/logging.php`:

```php
return [
    'driver'    => getenv('LOG_DRIVER') ?: 'file', // file | stdout | null | json | stack
    'path'      => getenv('LOG_PATH') ?: 'storage/logs',
    'min_level' => getenv('LOG_MIN_LEVEL') ?: '',  // DEBUG|INFO|WARNING|ERROR|CRITICAL — empty = all
    'json_inner'=> getenv('LOG_JSON_INNER') ?: 'stdout', // inner driver when driver=json
    'stack'     => ['file', 'stdout'],              // used when driver=stack
];
```

## Usage

```php
use EzPhp\Logging\Log;
use EzPhp\Logging\LogLevel;

Log::debug('Cache miss', ['key' => 'users.all']);
Log::info('User registered', ['id' => 42]);
Log::warning('Rate limit approaching', ['remaining' => 5]);
Log::error('Payment failed', ['order' => 'ORD-99']);
Log::critical('Database unreachable');

// Generic level dispatch
Log::log(LogLevel::INFO, 'Something happened');
```

## Drivers

| Driver | Description |
|---|---|
| `file` | Appends to `{path}/app-YYYY-MM-DD.log` (daily rotation) |
| `stdout` | `debug`/`info`/`warning` → stdout · `error`/`critical` → stderr |
| `null` | Discards all entries — useful in tests |
| `json` | Decorator: serialises entries as JSON and forwards to an inner driver |
| `stack` | Decorator: fans each call out to multiple inner drivers simultaneously |

Wrap any driver with a minimum-level filter by setting `LOG_MIN_LEVEL`:

```dotenv
LOG_DRIVER=file
LOG_MIN_LEVEL=WARNING   # drops DEBUG and INFO entries
```

## JSON structured logging

```dotenv
LOG_DRIVER=json
LOG_JSON_INNER=stdout
```

Each entry is written as a single JSON line:

```json
{"timestamp":"2026-03-21T12:00:00+00:00","level":"info","message":"tick.event","context":{"user_id":5}}
```

## Stack driver

Write to multiple destinations simultaneously:

```dotenv
LOG_DRIVER=stack
```

```php
// config/logging.php
return [
    'driver' => 'stack',
    'stack'  => ['file', 'stdout'],
];
```

## Request context middleware

`RequestContextMiddleware` injects `request_id`, `ip`, `method`, and `path` into every log entry produced during a request:

```php
$app->middleware(\EzPhp\Logging\RequestContextMiddleware::class);
```

The middleware wraps the current logger in a `ContextualLogger` and restores the original logger in `finally`.

## Exception logging

`LogServiceProvider` automatically wraps the `ExceptionHandler` with `LoggingExceptionHandler`, so all unhandled exceptions are logged at `error` level (with exception class, code, file, and line) before the response is rendered. No extra configuration needed.

## Log format (file/stdout)

```
[2026-03-15 12:00:00] INFO: User registered {"id":42}
[2026-03-15 12:00:01] ERROR: Payment failed {"order":"ORD-99"}
```

## Classes

| Class | Description |
|---|---|
| `LoggerInterface` | Contract: `log()`, `debug()`, `info()`, `warning()`, `error()`, `critical()` |
| `LogLevel` | Backed enum: `DEBUG\|INFO\|WARNING\|ERROR\|CRITICAL`; `severity()`, `isAtLeast()` |
| `FileDriver` | Appends to daily-rotated log files; creates directory on demand |
| `StdoutDriver` | stdout for debug/info/warning, stderr for error/critical |
| `NullDriver` | No-op — discards all entries |
| `JsonDriver` | Decorator: serialises entries as JSON, forwards to inner driver |
| `StackDriver` | Decorator: fans calls out to a list of inner drivers |
| `MinLevelDriver` | Decorator: drops entries below a configured minimum severity |
| `ContextualLogger` | Decorator: merges a fixed context array into every log call |
| `RequestContextMiddleware` | Injects request_id/ip/method/path context for the duration of a request |
| `Log` | Static façade backed by a managed `LoggerInterface` singleton |
| `LoggingExceptionHandler` | Decorator: logs exceptions at error level, then delegates to inner handler |
| `LogServiceProvider` | Config-driven driver binding; wraps `ExceptionHandler`; wires `Log` façade |

## License

MIT — [Andreas Uretschnig](mailto:andreas.uretschnig@gmail.com)
