#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
find "$ROOT" -type f -name '*.php' -print0 |
while IFS= read -r -d '' file; do
  php -l "$file"
done
echo "PHP syntax validation passed."
