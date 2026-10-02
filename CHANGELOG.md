# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added
- [#18] CI job `example app`: installs `example/` and boots its kernel with `php bin/console about`. It runs for code changes (including Dependabot PRs for `/example`) and is part of `ci-ok`.

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
