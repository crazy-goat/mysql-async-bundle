#!/usr/bin/env bash
# Install Composer dependencies in a fresh worktree.
# Called by bin/worktree.sh after a new worktree is created.
# No containers are started: the tests need no database, and the example app's
# compose stack (example/compose.yaml) is only for manual use.
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

composer install --no-interaction --prefer-dist
