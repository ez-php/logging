# ez-php/logging

Logging module for the [ez-php framework](https://github.com/ez-php/framework) — structured logging with file, stdout, and null drivers.

[![CI](https://github.com/ez-php/logger/actions/workflows/ci.yml/badge.svg)](https://github.com/ez-php/logger/actions/workflows/ci.yml)

## Requirements

- PHP 8.5+
- ez-php/framework ^0.1

## Installation

```bash
composer require ez-php/logging
```

## Setup

Register the service provider:

```php
$app->register(\EzPhp\Logging\LogServiceProvider::class);
```

Configure the driver in `config/logging.php`:

```php
return [
    'driver' => env('LOG_DRIVER', 'file'), // 'file' | 'stdout' | 'null'
    'path'   => env('LOG_PATH', 'storage/logs'),
];
```

## Usage

```php
use EzPhp\Logging\Log;

Log::debug('Cache miss', ['key' => 'users.all']);
Log::info('User registered', ['id' => 42]);
Log::warning('Rate limit approaching', ['remaining' => 5]);
Log::error('Payment failed', ['order' => 'ORD-99']);
Log::critical('Database unreachable');

// Generic level dispatch
Log::log('info', 'Something happened');
```

## Drivers

| Driver | Description |
|---|---|
| `file` | Appends to `storage/logs/app-YYYY-MM-DD.log` (daily rotation) |
| `stdout` | `debug`/`info`/`warning` → stdout · `error`/`critical` → stderr |
| `null` | Discards all entries — useful in tests |

## Exception Logging

`LogServiceProvider` automatically wraps the `ExceptionHandler` with `LoggingExceptionHandler`, so all unhandled exceptions are logged at `error` level before the response is rendered. No extra configuration needed.

## Log Format

```
[2026-03-15 12:00:00] INFO: User registered {"id":42}
[2026-03-15 12:00:01] ERROR: Payment failed {"order":"ORD-99"}
```

## License

MIT — [Andreas Uretschnig](mailto:andreas.uretschnig@gmail.com)
