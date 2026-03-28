# Changelog

All notable changes to `ez-php/logging` are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

---

## [v1.2.0] — 2026-03-28

### Changed
- `LoggingExceptionHandler::render()` — second parameter type changed from `Request` to `RequestInterface`, in line with the updated `ExceptionHandlerInterface` contract
- `RequestContextMiddleware::handle()` — first parameter type changed from `Request` to `RequestInterface`
- Updated `ez-php/contracts` and `ez-php/http` dependency constraints to `^1.2`

---

## [v1.0.1] — 2026-03-25

### Changed
- Tightened all `ez-php/*` dependency constraints from `"*"` to `"^1.0"` for predictable resolution

---

## [v1.0.0] — 2026-03-24

### Added
- `Logger` — PSR-inspired logger with `debug()`, `info()`, `notice()`, `warning()`, `error()`, `critical()`, `alert()`, and `emergency()` methods
- `FileDriver` — appends structured log lines to a configurable file path with timestamps and log levels
- `StdoutDriver` — writes log lines to `STDOUT`; suitable for container environments
- `NullDriver` — silently discards all log messages; useful in testing
- `LoggingExceptionHandler` — decorator around `ExceptionHandlerInterface` that logs every unhandled `Throwable` before delegating rendering
- `LoggingServiceProvider` — resolves the configured driver from environment and binds it as `LoggerInterface`
- `LoggingException` for driver initialization failures
