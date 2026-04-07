#!/bin/bash
# =============================================================================
# RentATool - Post-Receive Hook
# =============================================================================
# Pokrece se nakon git push na serveru
# =============================================================================

GIT_DIR="/var/www/rentatool/.git"
APP_DIR="/var/www/rentatool"
LOG_FILE="/var/www/rentatool/temp/deploy.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

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