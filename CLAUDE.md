# Coding Guidelines

Applies to the entire ez-php project — framework core, all modules, and the application template.

---

## Environment

- PHP **8.5**, Composer for dependency management
- All project based commands run **inside Docker** — never directly on the host

```
docker compose exec app <command>
```

Container name: `ez-php-app`, service name: `app`.

---

## Quality Suite

Run after every change:

```
docker compose exec app composer full
```

Executes in order:
1. `phpstan analyse` — static analysis, level 9, config: `phpstan.neon`
2. `php-cs-fixer fix` — auto-fixes style (`@PSR12` + `@PHP83Migration` + strict rules)
3. `phpunit` — all tests with coverage

Individual commands when needed:
```
composer analyse   # PHPStan only
composer cs        # CS Fixer only
composer test      # PHPUnit only
```

**PHPStan:** never suppress with `@phpstan-ignore-line` — always fix the root cause.

---

## Coding Standards

- `declare(strict_types=1)` at the top of every PHP file
- Typed properties, parameters, and return values — avoid `mixed`
- PHPDoc on every class and public method
- One responsibility per class — keep classes small and focused
- Constructor injection — no service locator pattern
- No global state unless intentional and documented

**Naming:**

| Thing | Convention |
|---|---|
| Classes / Interfaces | `PascalCase` |
| Methods / variables | `camelCase` |
| Constants | `UPPER_CASE` |
| Files | Match class name exactly |

**Principles:** SOLID · KISS · DRY · YAGNI

---

## Workflow & Behavior

- Write tests **before or alongside** production code (test-first)
- Read and understand the relevant code before making any changes
- Modify the minimal number of files necessary
- Keep implementations small — if it feels big, it likely belongs in a separate module
- No hidden magic — everything must be explicit and traceable
- No large abstractions without clear necessity
- No heavy dependencies — check if PHP stdlib suffices first
- Respect module boundaries — don't reach across packages
- Keep the framework core small — what belongs in a module stays there
- Document architectural reasoning for non-obvious design decisions
- Do not change public APIs unless necessary
- Prefer composition over inheritance — no premature abstractions

---

## New Modules & CLAUDE.md Files

### 1 — Required files

Every module under `modules/<name>/` must have:

| File | Purpose |
|---|---|
| `composer.json` | package definition, deps, autoload |
| `phpstan.neon` | static analysis config, level 9 |
| `phpunit.xml` | test suite config |
| `.php-cs-fixer.php` | code style config |
| `.gitignore` | ignore `vendor/`, `.env`, cache |
| `.github/workflows/ci.yml` | standalone CI pipeline |
| `README.md` | public documentation |
| `tests/TestCase.php` | base test case for the module |
| `start.sh` | convenience script: copy `.env`, bring up Docker, wait for services, exec shell |
| `CLAUDE.md` | see section 2 below |

### 2 — CLAUDE.md structure

Every module `CLAUDE.md` must follow this exact structure:

1. **Full content of `CODING_GUIDELINES.md`, verbatim** — copy it as-is, do not summarize or shorten
2. A `---` separator
3. `# Package: ez-php/<name>` (or `# Directory: <name>` for non-package directories)
4. Module-specific section covering:
   - Source structure — file tree with one-line description per file
   - Key classes and their responsibilities
   - Design decisions and constraints
   - Testing approach and infrastructure requirements (MySQL, Redis, etc.)
   - What does **not** belong in this module

### 3 — Docker scaffold

Run from the new module root (requires `"ez-php/docker": "0.*"` in `require-dev`):

```
vendor/bin/docker-init
```

This copies `Dockerfile`, `docker-compose.yml`, `.env.example`, `start.sh`, and `docker/` into the module, replacing `{{MODULE_NAME}}` placeholders. Existing files are never overwritten.

After scaffolding:

1. Adapt `docker-compose.yml` — add or remove services (MySQL, Redis) as needed
2. Adapt `.env.example` — fill in connection defaults matching the services above
3. Assign a unique host port for each exposed service (see table below)

**Allocated host ports:**

| Package | `DB_HOST_PORT` (MySQL) | `REDIS_PORT` |
|---|---|---|
| root (`ez-php-project`) | 3306 | 6379 |
| `ez-php/framework` | 3307 | — |
| `ez-php/orm` | 3309 | — |
| `ez-php/cache` | — | 6380 |
| **next free** | **3310** | **6381** |

Only set a port for services the module actually uses. Modules without external services need no port config.

---

# Package: ez-php/logging

Structured logging module with pluggable drivers, a static `Log` facade, and automatic exception logging via a decorator on `ExceptionHandler`.

---

## Source Structure

```
src/
├── LoggerInterface.php           — contract: log(), debug(), info(), warning(), error(), critical()
├── LogLevel.php                  — string constants: DEBUG, INFO, WARNING, ERROR, CRITICAL + all()
├── FileDriver.php                — appends to daily-rotated files; creates directory on demand
├── StdoutDriver.php              — debug/info/warning → stdout (echo), error/critical → stderr (fwrite)
├── NullDriver.php                — no-op: discards all log entries silently
├── Log.php                       — static facade; delegates to an injected LoggerInterface singleton
├── LoggingExceptionHandler.php   — decorator: logs at error level, then delegates to inner ExceptionHandler
└── LogServiceProvider.php        — binds LoggerInterface (config-driven), wraps ExceptionHandler, wires Log

tests/
├── TestCase.php                       — base PHPUnit test case
├── LogLevelTest.php                   — covers LogLevel constants and all()
├── NullDriverTest.php                 — covers NullDriver: no output for any level
├── StdoutDriverTest.php               — covers StdoutDriver: stdout for info levels, stderr for error levels
├── FileDriverTest.php                 — covers FileDriver: creates file, appends entries, formats correctly
├── LogTest.php                        — covers Log facade: setLogger, resetLogger, all level delegates
├── LoggingExceptionHandlerTest.php    — covers decorator: logs before delegating, returns inner response
└── LogServiceProviderTest.php         — covers provider: binds LoggerInterface, wraps ExceptionHandler
```

---

## Key Classes and Responsibilities

### LoggerInterface (`src/LoggerInterface.php`)

The single contract all drivers implement. Modelled after PSR-3 but without the PSR-3 dependency.

```php
public function log(string $level, string $message, array $context = []): void;
public function debug(string $message, array $context = []): void;
public function info(string $message, array $context = []): void;
public function warning(string $message, array $context = []): void;
public function error(string $message, array $context = []): void;
public function critical(string $message, array $context = []): void;
```

The convenience methods (`debug()`, `info()`, etc.) exist so callers never need to pass a level string manually.

---

### LogLevel (`src/LogLevel.php`)

Five string constants — `DEBUG`, `INFO`, `WARNING`, `ERROR`, `CRITICAL` — plus `all(): list<string>` for iteration. Used by `StdoutDriver` to decide whether to write to stdout or stderr.

---

### FileDriver (`src/FileDriver.php`)

Appends to `{path}/app-YYYY-MM-DD.log`. The date suffix is computed on each `log()` call, so the file rotates automatically at midnight without any external scheduler.

Line format:
```
[2026-03-15 12:00:00] INFO: message {"key":"value"}
```
Context is JSON-encoded and appended only when non-empty. The log directory is created (with `0755`, recursive) if it does not exist, so no manual provisioning is needed on first use.

---

### StdoutDriver (`src/StdoutDriver.php`)

Writes to stdout via `echo` for `debug`, `info`, `warning` levels — which makes output capturable by `ob_start()` in tests. `error` and `critical` write to STDERR via `fwrite(STDERR, ...)` — these are not captured by output buffering; tests assert that nothing appears on stdout for those levels.

---

### NullDriver (`src/NullDriver.php`)

All methods are no-ops. Used in tests that exercise components which require a logger but must not produce any output. Also useful as the driver when logging is intentionally disabled.

---

### Log (`src/Log.php`)

Static facade. Holds a `LoggerInterface|null` singleton. All static methods throw `RuntimeException` if called before `setLogger()` or after `resetLogger()`. The `LogServiceProvider` calls `Log::setLogger()` in `boot()`.

| Static method | Delegates to |
|---|---|
| `Log::debug($msg, $ctx)` | `LoggerInterface::debug()` |
| `Log::info($msg, $ctx)` | `LoggerInterface::info()` |
| `Log::warning($msg, $ctx)` | `LoggerInterface::warning()` |
| `Log::error($msg, $ctx)` | `LoggerInterface::error()` |
| `Log::critical($msg, $ctx)` | `LoggerInterface::critical()` |
| `Log::log($level, $msg, $ctx)` | `LoggerInterface::log()` |
| `Log::setLogger($logger)` | Sets the singleton |
| `Log::resetLogger()` | Clears the singleton (call in test tearDown) |

---

### LoggingExceptionHandler (`src/LoggingExceptionHandler.php`)

Decorator around `ExceptionHandler`. On `render()`:
1. Calls `LoggerInterface::error()` with the exception message and context `['exception' => get_class($e), 'code' => $e->getCode()]`
2. Delegates to the inner `ExceptionHandler::render()` and returns its response

The logging always happens **before** the inner handler renders. The inner response is returned unchanged.

---

### LogServiceProvider (`src/LogServiceProvider.php`)

**`register()`:**
- Binds `LoggerInterface` lazily. Reads `logging.driver` from `Config` at resolution time:
  - `'stdout'` → `StdoutDriver`
  - `'null'` → `NullDriver`
  - anything else (including missing config) → `FileDriver` with path from `logging.path` or `{basePath}/storage/logs`
- Re-binds `ExceptionHandler` to `LoggingExceptionHandler(DefaultExceptionHandler, LoggerInterface)`

**`boot()`:**
- Calls `Log::setLogger($app->make(LoggerInterface::class))` to wire the static facade

The `ExceptionHandler` re-binding in `register()` safely overrides the core binding because `ExceptionHandler` is not resolved until `Application::handle()` — well after all providers have booted.

---

## Design Decisions and Constraints

- **No PSR-3 dependency** — PSR-3 adds a Composer dependency for an interface we can define in 10 lines. The `LoggerInterface` is structurally compatible with PSR-3 but avoids pulling in the package.
- **`StdoutDriver` uses `echo` for stdout** — `fwrite(STDOUT, ...)` bypasses PHP's output buffer, making tests impossible without process-level capture. `echo` is captured by `ob_start()`, so tests can assert on the formatted output directly.
- **`FileDriver` creates the log directory on demand** — No provisioning step needed. First write creates `{path}/` with `0755` permissions.
- **Daily rotation via filename** — The `YYYY-MM-DD` suffix in the filename rotates the log at midnight without a cron job, logrotate, or any external tool.
- **`Log::setLogger()` throws on uninitialized use** — Calling any `Log::*` method before `setLogger()` throws `RuntimeException`. Fail-fast is preferable to silent null discards, which would make missing provider registration invisible.
- **`LoggingExceptionHandler` is a decorator, not a subclass** — Inheritance would couple the logging behaviour to a specific `ExceptionHandler` implementation. The decorator works with any inner handler and is swappable independently.
- **Re-binding `ExceptionHandler` in `register()`** — Safe because `ExceptionHandler` is only resolved during `handle()`, long after all providers finish booting. The container's lazy binding ensures `LoggingExceptionHandler` wraps the last-bound `DefaultExceptionHandler`.

---

## Testing Approach

- **No infrastructure required** — All tests run in-process. `FileDriver` tests write to a temp directory (created in `setUp`, deleted in `tearDown`).
- **`ob_start()` / `ob_get_clean()`** — Used in `StdoutDriverTest` and `NullDriverTest` to capture stdout. `error` and `critical` in `StdoutDriver` write to STDERR (not captured); those tests assert stdout is empty.
- **Spy pattern** — Anonymous classes with public array properties (e.g., `public array $logged = []`) are used instead of reference-backed private properties. PHPStan can reason about public properties; reference-backed private properties trigger `property.onlyWritten`.
- **`Log::resetLogger()`** — Must be called in both `setUp()` and `tearDown()` in any test that touches the `Log` facade. Omitting it leaks logger state between tests.
- **`#[UsesClass]` required** — `beStrictAboutCoverageMetadata=true` is set in the module-level `phpunit.xml`. Declare all indirectly used classes. **Do not** add `#[UsesClass(LoggerInterface::class)]` — interfaces are not valid coverage targets and trigger a PHPUnit warning.
- **`LogServiceProviderTest` extends `DatabaseTestCase`** — `Application::bootstrap()` loads core providers including `DatabaseServiceProvider`, which requires a real database. Run these tests inside the monorepo's Docker environment, not standalone.

---

## What Does NOT Belong Here

| Concern | Where it belongs |
|---|---|
| Log rotation daemon / logrotate config | Infrastructure / deployment |
| Async log shipping (to ELK, Datadog, etc.) | Application layer or a future `ez-php/log-transport` package |
| Structured log querying / searching | External tooling (Grafana Loki, etc.) |
| Request-level log context (request ID, user ID) | Middleware in the application that calls `Log::setContext()` — not implemented here |
| PSR-3 compatibility shim | Application layer — implement a thin adapter if PSR-3 is required |
| Database query logging | `ez-php/orm` module (optional query log decorator) |

