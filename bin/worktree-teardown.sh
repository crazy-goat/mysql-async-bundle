#!/usr/bin/env bash
# Stop and remove the Docker stack of this worktree (example/compose.yaml).
# Called by bin/worktree-done.sh before the worktree is removed.
set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

if [[ -f .env.worktree ]]; then
  set -a
  # shellcheck disable=SC1091
  . ./.env.worktree
  set +a
else
  unset COMPOSE_PROJECT_NAME
fi

if [[ -z "${COMPOSE_PROJECT_NAME:-}" ]]; then
  echo "COMPOSE_PROJECT_NAME is empty (no .env.worktree); refusing to stop the stacks of another checkout." >&2
  exit 1
fi

echo "docker compose down"
(cd example && docker compose down -v --remove-orphans) || true
