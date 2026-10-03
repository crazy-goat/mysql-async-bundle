# AGENTS.md

Project commands and specifics for mysql-async-bundle, a Symfony bundle that wraps
`amphp/mysql` (async MySQL pool). The development process (issue, worktree, review, PR,
merge) is in [docs/workflow.md](docs/workflow.md), the release process in
[docs/release-workflow.md](docs/release-workflow.md). The default branch is `main`.

Everything is written in English (code, comments, docs, commits, issues).

## Layout

| Path | Content |
|---|---|
| `src/` | Bundle, namespace `CrazyGoat\MysqlAsyncBundle\` |
| `src/Connection/` | `Pool`, `PoolFactory`, `Result` |
| `tests/` | PHPUnit tests, they need no database |
| `tests/TestKernel.php` | Minimal kernel the functional tests boot (`framework.test: true`) |
| `tests/Integration/` | Tests against a real MySQL, skipped unless `MYSQL_TEST_URL` is set |
| `example/` | Sample Symfony application (own `composer.json`, `compose.yaml`) |
| `bin/` | Lint script, worktree scripts and `pick-issue.sh` |

## Commands

PHP 8.3+ (PHPUnit 12 needs it) and Composer.

```bash
composer install

composer lint            # runs bin/lint.sh (check only)
composer lint-fix        # runs bin/lint.sh --fix, then checks again
composer test            # PHPUnit, integration tests skipped without MYSQL_TEST_URL
composer test-integration # only tests/Integration, needs MYSQL_TEST_URL
```

`tests/Integration/PoolIntegrationTest.php` needs a MySQL server and skips itself when
`MYSQL_TEST_URL` is unset or empty. To run it locally, start any MySQL and point the variable at
it, for example with the compose file in `example/`:

```bash
MYSQL_TEST_URL='mysql://root:root@127.0.0.1:3306/test' composer test-integration
```

amphp needs a running event loop for the socket. PHPUnit does not provide one, so the test drives
it itself: `async()` starts the fiber and `Revolt\EventLoop::run()` takes **no callback**, it only
runs the loop until the fiber is done. `EventLoop::run($closure)` silently ignores the closure.

`bin/lint.sh` runs php-cs-fixer (dry run), Rector (dry run), PHPStan (level max) and
`shellcheck` (plus `hadolint` when the repository has Dockerfiles). It runs every step and fails
if any step failed. `shellcheck` and `hadolint` must be installed. `bin/pick-issue.sh` is a copy
of the shared script, so do not edit it here; sync it instead. `bin/lint.sh` runs `shellcheck`
on every tracked shell script, that copy included.

Run `composer lint-fix` and then `composer lint` before committing. Push only when
`composer lint` and `composer test` pass.

## Docker

The library needs no containers. `example/compose.yaml` only defines a database service for
the sample application and publishes no host ports. If you add published ports, use
`"${NAME_PORT:-N}:N"` and no `container_name`, so that worktrees can run side by side.
`bin/worktree.sh` writes the free ports and `COMPOSE_PROJECT_NAME` to `.env.worktree`;
`bin/worktree-teardown.sh` stops the stack of the worktree and refuses to run without
`COMPOSE_PROJECT_NAME`.

## CI

`.github/workflows/tests.yaml` runs `changes` and `docs` always. `lint` and `tests`
(PHP 8.3 and 8.4 against the maintained Symfony lines: 6.4 LTS, 7.4 LTS and 8.1) run for
code changes. The matrix rewrites every `symfony/*` constraint in `composer.json` with `sed`,
so `require`, `require-dev` and the matrix must stay in sync. The `example` job
installs `example/` (`composer install`) and boots its kernel (`php bin/console about`), so
Dependabot PRs for `/example` are checked too. `ci-ok` aggregates them and is
the only required check. The CI `lint` job only runs `bin/lint.sh`. The `integration` job
starts a MySQL 8.4 service, sets `MYSQL_TEST_URL` and runs `composer test-integration`.
Tagging `vX.Y.Z` runs `.github/workflows/release.yaml`.

## Notes

- `var/` holds PHPUnit and php-cs-fixer caches and is gitignored.
- `src/config/configuration.php` is excluded from PHPStan (it is loaded by the Symfony
  config loader with a closure).
