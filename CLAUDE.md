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
1. `sync_guidelines.php --check` — fails if any `CLAUDE.md` has drifted from this file
2. `check_test_classes.php` — fails on a duplicate test class name (all packages share the `Tests\` namespace, so a collision is a fatal error in the aggregated run, not a test failure)
3. `phpstan analyse` — static analysis, level 9, config: `phpstan.neon`
4. `php-cs-fixer fix` — auto-fixes style (`@PSR12` + `@PHP83Migration` + strict rules)
   *(Note: `@PHP85Migration` does not exist yet in php-cs-fixer; `@PHP83Migration` is the highest available and is used intentionally even though the project targets PHP 8.5)*
5. `phpunit` — all tests with coverage

Individual commands when needed:
```
composer analyse             # PHPStan only
composer cs                  # CS Fixer only
composer test                # PHPUnit only
composer guidelines:check    # CLAUDE.md drift only
composer test-classes:check  # duplicate test class names only
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
- Concrete classes are `final` — extend behavior through composition, not inheritance. Exception-hierarchy base classes (e.g. `EzPhpException`, `HttpException`, `CacheException`) are one carve-out, since they exist specifically to be extended. A documented template-method-style base class (e.g. `Mailable`, meant to be configured via constructor-time subclassing) is the other — the owning module's `CLAUDE.md` must record it under Design Decisions.

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
| `.env.example` | environment variable defaults (copy to `.env` on first run) |
| `docker-compose.yml` | Docker Compose service definition (always `container_name: ez-php-<name>-app`) |
| `docker/app/Dockerfile` | module Docker image (`FROM au9500/php:8.5`) |
| `docker/app/container-start.sh` | container entrypoint: `composer install` → `sleep infinity` |
| `docker/app/php.ini` | PHP ini overrides (`memory_limit`, `display_errors`, `xdebug.mode`) |
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

**Do not edit part 1 by hand.** It is generated from `CODING_GUIDELINES.md` by
`sync_guidelines.php` at the project root:

```
php sync_guidelines.php            # rewrite every out-of-sync CLAUDE.md
php sync_guidelines.php --check    # report drift, exit 1 if any (CI / pre-commit)
```

Edit `CODING_GUIDELINES.md`, then run the script — it replaces everything before the
`# Package:` / `# Directory:` / `# Project:` heading and preserves the hand-written
section below it byte-for-byte. Editing a single copy only creates drift; before this
script existed, all 40 copies had diverged.

### 3 — Scaffolding a new module

`make_module.php` at the project root writes the required-file set and the monorepo
wiring in one step, wrapping `docker-init` for the Docker subset:

```
composer module:make <name> -- --description="..."
php make_module.php <name> --description="..." --services=mysql,redis
```

`<name>` is the kebab-case package name; the namespace is derived as
`EzPhp\<PascalCase>` unless `--namespace=` overrides it (`bignum` → `BigNum` and
`opcache` → `OPCache` are existing exceptions the guess gets wrong).

To bring in a module whose code already lives in its own repository instead of
generating a fresh skeleton, pass `--repo=` with a git URL:

```
php make_module.php <name> --repo=<git-url> [--namespace=Foo]
```

This runs `git submodule add <url> modules/<name>` instead of writing package
files, then applies the same monorepo wiring below. It is mutually exclusive
with `--services` and `--description` — a submodule brings its own Docker
scaffold (if any) and its own `composer.json` description. A minimal `CLAUDE.md`
stub is written only if the submodule doesn't already ship one, so
`composer guidelines:sync` has a `# Package:` heading to anchor part 1 against.

It writes `modules/<name>/` and registers the module in the four places the monorepo
needs it — root `composer.json` (`autoload.psr-4`), `phpstan.neon`, `phpunit.xml`
(test suite **and** coverage source), and `packages.sh` (alphabetical position).

Two things stay manual on purpose:

- **`CLAUDE.md` part 1** — only the `# Package:` section is generated. Run
  `composer guidelines:sync` afterwards; baking a guidelines copy into the generator
  would recreate the drift the sync script exists to prevent.
- **The host-port table below** (`--services` only) — editing it marks all ~40
  `CLAUDE.md` copies as drifted at once, so the next `composer full` would fail for
  a brand-new module. The generator prints which ports to claim instead.

### 4 — Docker scaffold

Run from the new module root (requires `"ez-php/docker": "^2.0"` in `require-dev`):

```
vendor/bin/docker-init
```

This copies `Dockerfile`, `docker-compose.yml`, `.env.example`, `start.sh`, and `docker/` into the module, replacing `{{MODULE_NAME}}` placeholders. Existing files are never overwritten.

Pass `--services` to merge MySQL/Redis/Meilisearch service definitions directly into `docker-compose.yml` and uncomment the matching sections in `.env.example`, instead of adapting them by hand afterward:

```
vendor/bin/docker-init --services=mysql
vendor/bin/docker-init --services=redis
vendor/bin/docker-init --services=meilisearch
vendor/bin/docker-init --services=mysql,redis
```

After scaffolding:

1. Adapt `docker-compose.yml` — add or remove services (MySQL, Redis, Meilisearch) as needed
2. Adapt `.env.example` — fill in connection defaults matching the services above
3. Assign a unique host port for each exposed service (see table below)

**Allocated host ports:**

| Package | `DB_HOST_PORT` (MySQL) | Redis host port | `MEILISEARCH_PORT` |
|---|---|---|---|
| root (`ez-php-project`) | 3306 | 6379 (`REDIS_PORT`) | 7700 |
| `ez-php/framework` | 3307 | — | — |
| `ez-php/orm` | 3309 | — | — |
| `ez-php/cache` | — | 6380 (`REDIS_HOST_PORT`) | — |
| `ez-php/queue` | 3310 | 6381 (`REDIS_HOST_PORT`) | — |
| `ez-php/rate-limiter` | — | 6382 (`REDIS_HOST_PORT`) | — |
| `ez-php/search` | — | — | 7701 |
| **next free** | **3311** | **6383** | **7702** |

Only set a port for services the module actually uses. Modules without external services need no port config.

> The `MEILISEARCH_PORT` column is the **host** port. Inside a Compose network the service is always reachable at `http://meilisearch:7700` regardless of the host mapping — only publish-side ports need to be unique.

> The "Redis host port" column is likewise the **host**-published port. `ez-php/cache`, `ez-php/queue`, and `ez-php/rate-limiter` map it through a separate `REDIS_HOST_PORT` env var in `docker-compose.yml`, keeping `REDIS_PORT` fixed at `6379` for in-container connections (the app container always reaches Redis at `redis:6379` over the Compose network, regardless of the host mapping) — the root project is the one exception, since it has no host/container split and uses `REDIS_PORT` for both.

### 5 — Monorepo scripts

`packages.sh` at the project root is the **central package registry**. Both `push_all.sh` and `update_all.sh` source it — the package list lives in exactly one place.

When adding a new module, add `"$ROOT/modules/<name>"` to the `PACKAGES` array in `packages.sh` in **alphabetical order** among the other `modules/*` entries (before `framework`, `ez-php`, and the root entry at the end).

---

# Package: ez-php/logging

Structured logging module with pluggable drivers, a static `Log` facade, JSON formatting, minimum-level filtering, request context injection, and automatic exception logging via a decorator on `ExceptionHandler`.

---

## Source Structure

```
src/
├── LoggerInterface.php            — contract: log(), debug(), info(), warning(), error(), critical()
├── LogLevel.php                   — backed enum (string): DEBUG|INFO|WARNING|ERROR|CRITICAL; severity(), isAtLeast(), fromString()
├── FileDriver.php                 — appends to daily-rotated files; creates directory on demand
├── StdoutDriver.php               — debug/info/warning → stdout (echo), error/critical → stderr (fwrite)
├── NullDriver.php                 — no-op: discards all log entries silently
├── JsonDriver.php                 — decorator: serialises each entry as JSON, forwards to inner driver
├── StackDriver.php                — decorator: fans a single call out to multiple inner drivers
├── MinLevelDriver.php             — decorator: drops entries below a configured minimum severity
├── ContextualLogger.php           — decorator: merges a fixed context array into every log call
├── RequestContextMiddleware.php   — middleware: injects request_id/ip/method/path into Log for each request
├── Log.php                        — static facade; delegates to an injected LoggerInterface singleton
├── LoggingExceptionHandler.php    — decorator: report() logs at error level (with file/line) and delegates; render() only delegates
└── LogServiceProvider.php         — binds LoggerInterface (config-driven), wraps ExceptionHandler, wires Log

tests/
├── TestCase.php                        — base PHPUnit test case
├── SpyLogger.php                       — reusable test helper: captures all log calls in a public array
├── OrderTracker.php                    — reusable test helper: records call order across multiple spies
├── OrderedSpyLogger.php                — reusable test helper: spy that records calls into a shared OrderTracker
├── LogLevelTest.php                    — covers LogLevel enum: severity ordering, isAtLeast, fromString
├── NullDriverTest.php                  — covers NullDriver: no output for any level
├── StdoutDriverTest.php                — covers StdoutDriver: stdout for info levels, stderr for error levels
├── FileDriverTest.php                  — covers FileDriver: creates file, appends entries, formats correctly
├── JsonDriverTest.php                  — covers JsonDriver: correct JSON structure, forwards to inner driver
├── StackDriverTest.php                 — covers StackDriver: fans out to all drivers in order
├── MinLevelDriverTest.php              — covers MinLevelDriver: drops entries below min, passes at/above
├── ContextualLoggerTest.php            — covers ContextualLogger: fixed context merged into every call
├── RequestContextMiddlewareTest.php    — covers RequestContextMiddleware: sets/restores logger, injects context
├── LogTest.php                         — covers Log facade: setLogger, resetLogger, all level delegates
├── LoggingExceptionHandlerTest.php     — covers decorator: report() logs then delegates, render() does not log, returns inner response
├── LogServiceProviderTest.php          — covers provider: binds LoggerInterface, wraps ExceptionHandler
├── ApplicationTestCase.php             — full-bootstrap test base (requires Docker DB)
└── DatabaseTestCase.php                — database-aware test base
```

---

## Key Classes and Responsibilities

### LoggerInterface (`src/LoggerInterface.php`)

The single contract all drivers implement. Modelled after PSR-3 but without the PSR-3 dependency.

```php
public function log(LogLevel $level, string $message, array $context = []): void;
public function debug(string $message, array $context = []): void;
public function info(string $message, array $context = []): void;
public function warning(string $message, array $context = []): void;
public function error(string $message, array $context = []): void;
public function critical(string $message, array $context = []): void;
```

The convenience methods (`debug()`, `info()`, etc.) exist so callers never need to pass a level manually.

---

### LogLevel (`src/LogLevel.php`)

Backed enum (`string`) with five cases: `DEBUG`, `INFO`, `WARNING`, `ERROR`, `CRITICAL`.

- `severity(): int` — numeric weight (DEBUG=0 … CRITICAL=4) for comparison
- `isAtLeast(LogLevel $min): bool` — returns `true` when this level ≥ `$min`
- `fromString(string $value): self` — named wrapper around the native `from()` for explicit usage
- `all(): list<self>` — all cases in ascending severity order

---

### FileDriver (`src/FileDriver.php`)

Appends to `{path}/app-YYYY-MM-DD.log`. The date suffix rotates the file automatically at midnight.

Line format:
```
[2026-03-15 12:00:00] INFO: message {"key":"value"}
```
Context is JSON-encoded and appended only when non-empty. The log directory is created (`0755`, recursive) on first write.

---

### StdoutDriver (`src/StdoutDriver.php`)

Writes to stdout via `echo` for `debug`, `info`, `warning` levels — capturable by `ob_start()` in tests. `error` and `critical` write to STDERR via `fwrite(STDERR, ...)`.

---

### NullDriver (`src/NullDriver.php`)

All methods are no-ops. Used in tests and when logging is intentionally disabled.

---

### JsonDriver (`src/JsonDriver.php`)

Decorator that serialises each log entry as a single JSON line and forwards it to an inner driver:

```json
{"timestamp":"2026-03-21T12:00:00+00:00","level":"info","message":"tick.event","context":{"user_id":5}}
```

Combine with `FileDriver` or `StdoutDriver` via `logging.json_inner` to get structured production logs.

---

### StackDriver (`src/StackDriver.php`)

Decorator that fans a single log call out to multiple inner loggers in order. Useful for writing to file and stdout simultaneously. Configure the driver list in `config/logging.php → 'stack'`.

---

### MinLevelDriver (`src/MinLevelDriver.php`)

Decorator that silently drops entries below a configured minimum severity. Entries at or above the minimum are forwarded unchanged. Wired automatically by `LogServiceProvider` when `logging.min_level` is non-empty.

---

### ContextualLogger (`src/ContextualLogger.php`)

Decorator that merges a fixed `array<string, mixed>` into the context of every log call. Used by `RequestContextMiddleware` to attach `request_id`, `ip`, `method`, and `path` to all entries produced during a single HTTP request.

---

### RequestContextMiddleware (`src/RequestContextMiddleware.php`)

Implements `MiddlewareInterface`. On each request:
1. Builds a context array: `request_id` (random hex), `ip`, `method`, `path`, optional `user_id` (via injected `Closure`)
2. Wraps the current logger in a `ContextualLogger` with that context
3. Calls `Log::setLogger($contextual)` so all log calls during the request carry the context
4. Restores the original logger in `finally` — always runs, even on exception

Register as global middleware:
```php
$app->middleware(RequestContextMiddleware::class);
```

---

### Log (`src/Log.php`)

Static facade. Holds a `LoggerInterface|null` singleton. All static methods throw `RuntimeException` if called before `setLogger()` or after `resetLogger()`. `LogServiceProvider` calls `Log::setLogger()` in `boot()`.

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

Decorator around `ExceptionHandlerInterface`.

- `report()`: calls `LoggerInterface::error()` with the exception message and context `['exception' => $e::class, 'code' => $e->getCode(), 'file' => $e->getFile(), 'line' => $e->getLine()]`, then delegates to the inner handler's `report()`
- `render()`: delegates to the inner handler and returns its response unchanged — **no logging**

Logging lives in `report()` because the kernel calls `report()` and `render()` separately (and only `report()` when a streamed response fails after its headers were sent). Logging in both would record every exception twice.

---

### LogServiceProvider (`src/LogServiceProvider.php`)

**`register()`:**
Binds `LoggerInterface` lazily. Reads `logging.driver` from `Config` at resolution time:

| `logging.driver` | Driver built |
|---|---|
| `'stdout'` | `StdoutDriver` |
| `'null'` | `NullDriver` |
| `'json'` | `JsonDriver` wrapping `logging.json_inner` sub-driver (default: `StdoutDriver`) |
| `'stack'` | `StackDriver` from the `logging.stack` array of driver names |
| anything else / missing | `FileDriver` with `logging.path` (fallback: `sys_get_temp_dir()/ez-php-logs`) |

After building the driver, if `logging.min_level` is a non-empty valid `LogLevel` string, the driver is wrapped in `MinLevelDriver`.

**`boot()`:**
- Resolves `ExceptionHandlerInterface` and `LoggerInterface`, wraps the handler in `LoggingExceptionHandler`, and re-binds it via `instance()`
- Calls `Log::setLogger()` to wire the static facade

---

## Design Decisions and Constraints

- **No PSR-3 dependency** — The `LoggerInterface` is structurally compatible with PSR-3 but avoids pulling in the package.
- **`LogLevel` is a backed enum, not a class with constants** — Enables `LogLevel::from()` / `LogLevel::tryFrom()` for safe string parsing, and `severity()` / `isAtLeast()` for ordered comparisons without a lookup table.
- **`StdoutDriver` uses `echo` for stdout** — `fwrite(STDOUT, ...)` bypasses PHP's output buffer, making tests impossible without process-level capture. `echo` is captured by `ob_start()`.
- **`FileDriver` creates the log directory on demand** — No provisioning step needed on first use.
- **Daily rotation via filename** — The `YYYY-MM-DD` suffix rotates the log at midnight without a cron job or logrotate.
- **Decorators over inheritance** — `JsonDriver`, `MinLevelDriver`, `ContextualLogger` are all decorators. They compose independently; any combination is valid without subclassing.
- **`ContextualLogger` merges, not replaces** — Per-call context always wins (`array_merge($fixed, $perCall)` is wrong; actual implementation is `array_merge($this->context, $context)` so fixed context is the base and per-call context overrides).
- **`RequestContextMiddleware` restores logger in `finally`** — Guarantees the base logger is always restored, even when a middleware or controller throws.
- **`Log::setLogger()` throws on uninitialized use** — Fail-fast prevents silent log loss when the provider is not registered.
- **Re-binding `ExceptionHandlerInterface` in `boot()`** — Uses `instance()` (not `bind()`) so the wrapper is stored as a resolved singleton. Safe because `ExceptionHandlerInterface` is not resolved until `Application::handle()`.

---

## Testing Approach

- **No infrastructure required** — All tests run in-process. `FileDriver` tests write to a temp directory (created in `setUp`, deleted in `tearDown`). `LogServiceProviderTest` requires Docker DB (extends `DatabaseTestCase`).
- **`ob_start()` / `ob_get_clean()`** — Used in `StdoutDriverTest` and `NullDriverTest` to capture stdout.
- **Spy helpers** — `SpyLogger` captures all log calls in a public `$logged` array. `OrderedSpyLogger` + `OrderTracker` verify fan-out order in `StackDriverTest`. Public properties are required (vs. reference-backed privates) because PHPStan level 9 flags `property.onlyWritten` on private properties that are only assigned.
- **`Log::resetLogger()`** — Must be called in both `setUp()` and `tearDown()` in any test that touches the `Log` facade. Omitting it leaks state between tests.
- **`#[UsesClass]` required** — `beStrictAboutCoverageMetadata=true` is set. Declare all indirectly used classes. Do **not** add `#[UsesClass(LoggerInterface::class)]` — interfaces are not valid coverage targets and trigger a PHPUnit warning.
- **`LogServiceProviderTest` extends `DatabaseTestCase`** — `Application::bootstrap()` loads `DatabaseServiceProvider`, which requires a real DB. Run inside the monorepo Docker environment.

---

## What Does NOT Belong Here

| Concern | Where it belongs |
|---|---|
| Log rotation daemon / logrotate config | Infrastructure / deployment |
| Async log shipping (ELK, Datadog, etc.) | Application layer or a future `ez-php/log-transport` package |
| Structured log querying / searching | External tooling (Grafana Loki, etc.) |
| PSR-3 compatibility shim | Application layer — implement a thin adapter if PSR-3 is required |
| Database query logging | `ez-php/orm` module (optional query log decorator) |
