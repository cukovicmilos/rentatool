#!/bin/bash
# =============================================================================
# RentATool - Deployment Script
# =============================================================================
# Usage:
#   bash deploy.sh
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="/var/www/rentatool"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_info() { echo -e "${YELLOW}[DEPLOY]${NC} $1"; }
log_ok()   { echo -e "${GREEN}[OK]${NC} $1"; }
log_fail() { echo -e "${RED}[FAIL]${NC} $1"; }

cd "$APP_DIR"

log_info "Starting deployment..."

# 1. Git pull
log_info "Git pull..."
git pull origin main 2>/dev/null || git pull 2>/dev/null || log_info "No remote or already up to date"

# 2. Clear PHP opcache if using PHP-FPM
log_info "Clearing PHP opcache..."
if command -v php-fpm &> /dev/null; then
    # Try to signal PHP-FPM to reload
    if [ -S /run/php/php-fpm.sock ]; then
        sudo systemctl reload php-fpm 2>/dev/null || log_info "Could not reload PHP-FPM"
    fi
fi

# 3. Run smoke test
log_info "Running smoke test..."
if [ -f "$SCRIPT_DIR/scripts/smoke-test.sh" ]; then
    bash "$SCRIPT_DIR/scripts/smoke-test.sh"
    if [ $? -eq 0 ]; then
        log_ok "Smoke test passed - deployment successful"
    else
        log_fail "Smoke test failed - check deployment"
        exit 1
    fi
else
    log_info "Smoke test script not found - skipping"
fi

log_ok "Deployment complete!"

# Test deploy: 2026-05-01 - smoke test verification successful
