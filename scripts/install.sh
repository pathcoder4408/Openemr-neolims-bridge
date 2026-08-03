#!/usr/bin/env bash
set -Eeuo pipefail
OPENEMR_ROOT="${OPENEMR_ROOT:-/var/www/openemr}"
MODULE="openemr-neolims-bridge"
SOURCE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET="$OPENEMR_ROOT/interface/modules/custom_modules/$MODULE"

sudo mkdir -p "$TARGET"
sudo rsync -a --delete --exclude='.git/' "$SOURCE/" "$TARGET/"
sudo chown -R www-data:www-data "$TARGET"
sudo find "$TARGET" -type d -exec chmod 0755 {} \;
sudo find "$TARGET" -type f -exec chmod 0644 {} \;

echo "Installed to $TARGET"
echo "Next: OpenEMR -> Modules -> Manage Modules -> install and enable."
