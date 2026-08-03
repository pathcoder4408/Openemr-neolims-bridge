#!/usr/bin/env bash
set -Eeuo pipefail
OPENEMR_ROOT="${OPENEMR_ROOT:-/var/www/openemr}"
SITE="${OPENEMR_SITE:-default}"
MODULE="$OPENEMR_ROOT/interface/modules/custom_modules/openemr-neolims-bridge"

sudo tee /etc/systemd/system/openemr-neolims-worker.service >/dev/null <<EOF
[Unit]
Description=OpenEMR NeoLIMS workflow worker
After=network.target mariadb.service php8.3-fpm.service

[Service]
Type=oneshot
User=www-data
Group=www-data
Environment=OPENEMR_ROOT=$OPENEMR_ROOT
Environment=OPENEMR_SITE=$SITE
ExecStart=/usr/bin/php $MODULE/bin/neolims-worker.php 25
Nice=10
EOF

sudo tee /etc/systemd/system/openemr-neolims-worker.timer >/dev/null <<'EOF'
[Unit]
Description=Run OpenEMR NeoLIMS workflow worker every minute

[Timer]
OnBootSec=2min
OnUnitActiveSec=1min
AccuracySec=10s
Persistent=true

[Install]
WantedBy=timers.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable --now openemr-neolims-worker.timer
sudo systemctl status openemr-neolims-worker.timer --no-pager
