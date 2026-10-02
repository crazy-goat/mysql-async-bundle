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
| `example/` | Sample Symfony application (own `composer.json`, `compose.yaml`) |
| `bin/` | Lint script, worktree scripts and `pick-issue.sh` |

## Commands

PHP 8.3+ (PHPUnit 12 needs it) and Composer.

```bash
composer install

composer lint            # runs bin/lint.sh (check only)
composer lint-fix        # runs bin/lint.sh --fix, then checks again
composer test            # PHPUnit
```

`bin/lint.sh` runs php-cs-fixer (dry run), Rector (dry run), PHPStan (level max) and
`shellcheck` (plus `hadolint` when the repository has Dockerfiles). It runs every step and fails
if any step failed. `shellcheck` and `hadolint` must be installed. `bin/pick-issue.sh` is a
byte-identical copy of the shared script and is skipped by `shellcheck`.

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
(PHP 8.3 and 8.4 against Symfony 6.4 to 7.2) run for code changes. `ci-ok` aggregates them and is
the only required check. The CI `lint` job only runs `bin/lint.sh`.
Tagging `vX.Y.Z` runs `.github/workflows/release.yaml`.

## Notes

- `var/` holds PHPUnit and php-cs-fixer caches and is gitignored.
- `src/config/configuration.php` is excluded from PHPStan (it is loaded by the Symfony
  config loader with a closure).
