# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added
- [#8] `tests/Integration/PoolIntegrationTest.php` runs `executeQuery()` and `fetchScalar()`
  against a real MySQL, covering a scalar result, a string result, an empty result and a query
  error. The suite skips itself when `MYSQL_TEST_URL` is not set, so `composer test` still needs
  no database.
- [#8] CI job `integration` starts a MySQL 8.4 service, sets `MYSQL_TEST_URL` and runs
  `composer test-integration`. It is part of `ci-ok`.
- [#8] `composer test-integration` runs only the `integration` suite; `phpunit.xml` now has the
  suites `unit` and `integration`.
- [#5] A README with installation, configuration and usage, and the steps that start the example
  application and open a page that runs queries through the bundle.
- [#18] CI job `example app`: installs `example/` and boots its kernel with `php bin/console about`. It runs for code changes (including Dependabot PRs for `/example`) and is part of `ci-ok`.
- [#9] `require` declares the Symfony components the bundle actually uses (`symfony/config`,
  `symfony/dependency-injection`, `symfony/http-kernel`) as `^6.4 || ^7.4 || ^8.1`. The
  supported Symfony range was not declared anywhere before. `symfony/framework-bundle` in
  `require-dev` uses the same range.
- [#9] `tests/MysqlAsyncBundleTest.php` boots the bundle in a minimal kernel
  (`tests/TestKernel.php`, `framework.test: true`), compiles the container and checks the
  pool services, the `Pool` alias and invalid configuration. It needs no database.

### Fixed
- [#8] `PoolFactoryTest::testCreatesPoolFromUrl()` only asserted that a `Pool` came back, so it
  passed with the host, port, user, password, database, charset or any query option parsed
  wrongly. A data provider now checks every parsed `MysqlConfig` value, the defaults when options
  are omitted, and that the database is the first path segment.
- [#22] `AGENTS.md` claimed that `bin/pick-issue.sh` is skipped by `shellcheck` and that it is a
  byte-identical copy of the shared script. `bin/lint.sh` checks every tracked shell script, and
  the copy in this repository is not identical to `.github/standard/pick-issue.sh`. The sentence
  now says what is true.
- [#5] `example/compose.yaml` defined a PostgreSQL service, while the sample application uses
  MySQL through both Doctrine and this bundle. It now defines MySQL, with a healthcheck and a
  published `${MYSQL_PORT:-3306}` port so the application can reach it from the host.
- [#5] The example pointed at a hard-coded Docker bridge IP (`172.17.0.2`) in `example/.env` and
  in `example/config/services.yaml`, so it could never connect. The connection details are now
  `MYSQL_*` variables, `DATABASE_URL` is derived from them, and the bundle reads `%env(MYSQL_URL)%`.
- [#5] Removed the `Amp\Mysql\MysqlConfig` service from `example/config/services.yaml`. Nothing
  injected it: the bundle builds its own `MysqlConfig` in `PoolFactory`, and the class is `final`
  with a required `$host` argument, so the definition only carried the wrong host.
- [#9] The bundle registered its own `MysqlAsyncExtension` under the `mysql_async` alias in
  `build()`, overwriting the `BundleExtension` that `AbstractBundle` provides. As a result
  `configure()` and `src/config/configuration.php` were never used and the configuration was
  never validated: an unknown option, an empty url or an unknown pool name were all accepted
  silently. Without any `mysql_async` configuration `load()` returned early, so no pool
  service and no `Pool` alias were registered at all and autowiring failed with a bare
  `ServiceNotFoundException`. `MysqlAsyncBundle::loadExtension()` now registers the pools and
  the config tree validates the input.
- [#9] Each pool service was registered with class `PoolFactory` while `PoolFactory::create()`
  returns a `Pool`. Nothing noticed, because `CheckTypeDeclarationsPass` skips definitions
  that have a factory; `debug:container` reported the wrong class.
- [#9] `composer.json` declared `"php": ">=8.1"` while CI only tested PHP 8.3 and 8.4, so the
  floor was never verified. The floor is now `>=8.3` (PHPUnit 12 needs it, and the tested
  matrix starts there) and PHPStan follows with `phpVersion: 80300`.
- [#7] `PoolFactory::create()` reported `Invalid charset value` for an invalid `collate` and for an
  invalid `key` as well, so the message named the wrong option. Each option now names itself:
  `Invalid collate value`, `Invalid key value`.
- [#7] `Pool` validated `maxConnections` and `idleTimeout` only in `getPool()`, that is on the
  first `executeQuery()`. A bad configuration therefore passed container build and failed at
  runtime. Both are validated in the constructor now, and the existing tests expect the exception
  when the pool is created.
- [#7] `Result` typed the awaited value as `Amp\Mysql\Internal\MysqlPooledResult`, an amphp class
  marked `@internal` that may change in any release. `src/` no longer references any
  `Amp\...\Internal\...` class; `Result` uses the public `MysqlResult` interface.

### Changed
- [#7] The local variable `$poll` in `Pool::executeQuery()` is renamed to `$pool`.
- [#10] `bin/lint.sh` no longer exports the deprecated `PHP_CS_FIXER_IGNORE_ENV`. The flag is now
  set the documented way, with `->setUnsupportedPhpVersionAllowed(true)` in
  `.php-cs-fixer.dist.php`.
- [#9] The CI test matrix covers the maintained Symfony lines (6.4 LTS, 7.4 LTS and 8.1)
  instead of the end-of-life 7.0, 7.1 and 7.2. Symfony 8.1 needs PHP 8.4.1 or newer, so PHP 8.3 is
  excluded from that one cell.
- [#9] `tests/` has an `autoload-dev` PSR-4 mapping, so a test class can use another test
  class without PHPUnit having to include the file first.

### Removed
- [#9] `CrazyGoat\MysqlAsyncBundle\MysqlAsyncExtension`. With `AbstractBundle` the bundle
  itself is the extension; the class was only referenced by the redundant registration above.

### Security
- [#6] The sample application in `example/` moves from Symfony 7.2 (end of life) to Symfony 7.4 LTS, and
  `example/composer.lock` is updated (twig/twig 3.30.0, Symfony 7.4.20). This closes the 23 Dependabot
  alerts for that lock file. `doctrine/doctrine-bundle` is pinned to `^2.19.1`, because 3.x needs PHP 8.4
  and drops the `use_savepoints` option the example uses.
- [#6] `.github/dependabot.yml` also watches `/example`, so the example lock file gets update PRs.

## [0.1.0] - 2026-10-02

First release: a Symfony bundle that provides an `amphp/mysql` 3 connection pool (`Pool`, `PoolFactory`) configured through the bundle configuration.

### Added
- [#2] `bin/lint.sh` (composer validate and audit, php-cs-fixer, Rector, PHPStan, shellcheck; `--fix` applies fixes) and
  `phpstan/phpstan` in `require-dev`. `composer lint` and `composer lint-fix` call the script.
- [#3] PHPUnit tests for `PoolFactory` and the `Pool` argument checks (`tests/`).
- [#3] Development process documentation: `docs/workflow.md`, `docs/release-workflow.md`,
  `AGENTS.md`, worktree scripts and `pick-issue.sh` under `bin/`, issue forms, a pull request
  template and Dependabot configuration for Composer and GitHub Actions.
- [#3] `release.yaml` workflow: pushing a `v*` tag creates the GitHub Release with the notes taken
  from the matching `CHANGELOG.md` section.

### Changed
- [#3] CI: documentation-only pull requests run only the fast docs checks; the `ci-ok` job
  aggregates the results. CI also runs on pushes to `main`. The lint job runs only
  `bin/lint.sh`, and the test matrix is limited to PHP 8.3 and 8.4 because PHPUnit 12 needs PHP 8.3.
- [#3] `composer.json` declares `"php": ">=8.1"` (the floor of `amphp/mysql` 3), and PHPStan checks
  against PHP 8.1.
- [#3] `src/config/configuration.php`: removed a redundant `assert()` flagged by Rector; no behaviour change.

### Fixed
- [#3] `Pool` threw `http\Exception\InvalidArgumentException` (from the PECL http extension) for
  invalid pool settings; it now throws `\InvalidArgumentException`.
- [#3] CI used a non-existing `matrix.php-versions` value, so the PHP version was never set.
- [#3] The `var/` cache directory is no longer committed and is gitignored.
