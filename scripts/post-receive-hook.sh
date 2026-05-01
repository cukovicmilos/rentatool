#!/bin/bash
# =============================================================================
# RentATool - Post-Receive Hook
# =============================================================================
# Pokrece se nakon git push na serveru
# =============================================================================

GIT_DIR="/var/www/rentatool/.git"
APP_DIR="/var/www/rentatool"
LOG_FILE="/var/www/rentatool/temp/deploy.log"
LOCK_FILE="/var/www/rentatool/temp/deploy.lock"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Prevent concurrent deployments using file lock
exec 200>"$LOCK_FILE"
if ! flock -n 200; then
    log "Another deployment is already running. Skipping."
    exit 0
fi

cd "$APP_DIR" || exit 1

log "========================================="
log "Post-receive hook triggered"
log "Running as: $(whoami)"
log "========================================="

# Git has already updated the working tree before this hook runs
# No need for git checkout -f main here

# Reload PHP-FPM
log "Reloading PHP-FPM..."
if systemctl is-active php-fpm > /dev/null 2>&1; then
    systemctl reload php-fpm 2>&1 | tee -a "$LOG_FILE" || log "Could not reload PHP-FPM"
fi

# Wait for PHP-FPM to stabilize after reload
log "Waiting for PHP-FPM to stabilize..."
sleep 2

# Quick health check before running full smoke test
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "https://rentatool.in.rs/" 2>/dev/null || echo "000")
if [ "$HEALTH_CHECK" != "200" ]; then
    log "Health check failed (HTTP $HEALTH_CHECK), waiting 3 more seconds..."
    sleep 3
fi

# Run smoke test
log "Running smoke test..."
if [ -f "$APP_DIR/scripts/smoke-test.sh" ]; then
    if bash "$APP_DIR/scripts/smoke-test.sh" >> "$LOG_FILE" 2>&1; then
        log "Smoke test PASSED"
    else
        log "Smoke test FAILED - Check deployment!"
        
        # Optional: Notify via Telegram if configured
        TELEGRAM_TOKEN=$(grep "^TELEGRAM_BOT_TOKEN=" "$APP_DIR/.env" 2>/dev/null | cut -d'=' -f2)
        TELEGRAM_CHAT_ID=$(grep "^TELEGRAM_CHAT_ID=" "$APP_DIR/.env" 2>/dev/null | cut -d'=' -f2)
        
        if [ -n "$TELEGRAM_TOKEN" ] && [ -n "$TELEGRAM_CHAT_ID" ]; then
            curl -s -X POST "https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage" \
                -d "chat_id=${TELEGRAM_CHAT_ID}" \
                -d "text=⚠️ Deploy failed! Smoke test not passed. Check server." \
                2>&1 | tee -a "$LOG_FILE"
        fi
        exit 1
    fi
else
    log "Smoke test script not found - skipping"
fi

log "Deployment complete!"
exit 0
