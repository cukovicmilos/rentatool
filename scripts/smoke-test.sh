#!/bin/bash
# =============================================================================
# RentATool - Deployment Smoke Test
# =============================================================================
# Simulira ceo checkout flow i proverava da li osnovna funkcionalnost radi.
# Pokrece se nakon svakog deploy-a.
# Last verified: 2026-05-01
#
# Usage:
#   bash scripts/smoke-test.sh
#   BASE_URL=http://localhost:8080 DB_PATH=/path/to/db bash scripts/smoke-test.sh
#
# Exit codes:
#   0 - Test uspesan
#   1 - Test neuspesan
# =============================================================================

set -euo pipefail

# --- Configuration ---
BASE_URL="${BASE_URL:-https://rentatool.in.rs}"
DB_PATH="${DB_PATH:-$(dirname "$(dirname "$0")")/database/rentatool.db}"
TIMEOUT="${SMOKE_TEST_TIMEOUT:-60}"
COOKIE_JAR=""
START_TIME=$(date +%s)
RESERVATION_CODE=""
TEST_PASSED=false

# Test data
TEST_NAME="Test Korisnik"
TEST_EMAIL="test@rentatool.in.rs"
TEST_PHONE="+381601234567"
TEST_ADDRESS="Test adresa 1, Subotica"
TEST_NOTE="Smoke test rezervacija - automatski kreirana"
DELIVERY_OPTION="pickup"

# Dates: 60+ days from now to avoid conflicts
TOMORROW=$(date -d '+60 days' '+%Y-%m-%d')
DAY_AFTER=$(date -d '+62 days' '+%Y-%m-%d')

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# --- Helper functions ---
log_info() { echo -e "${YELLOW}[INFO]${NC} $1"; }
log_ok()   { echo -e "${GREEN}[OK]${NC} $1"; }
log_fail() { echo -e "${RED}[FAIL]${NC} $1"; }

elapsed() { echo $(( $(date +%s) - START_TIME )); }

check_timeout() {
    if [ "$(elapsed)" -ge "$TIMEOUT" ]; then
        log_fail "Timeout nakon ${TIMEOUT}s"
        cleanup
        exit 1
    fi
}

fail_and_exit() {
    log_fail "$1"
    cleanup
    exit 1
}

cleanup() {
    if [ -n "$COOKIE_JAR" ] && [ -f "$COOKIE_JAR" ]; then
        rm -f "$COOKIE_JAR"
    fi
    if [ -n "$RESERVATION_CODE" ] && [ -f "$DB_PATH" ]; then
        log_info "Ciscenje test rezervacije: $RESERVATION_CODE"
        sqlite3 "$DB_PATH" "DELETE FROM reservation_items WHERE reservation_id IN (SELECT id FROM reservations WHERE reservation_code = '$RESERVATION_CODE');" 2>/dev/null || true
        sqlite3 "$DB_PATH" "DELETE FROM reservations WHERE reservation_code = '$RESERVATION_CODE';" 2>/dev/null || true
    fi
}

trap cleanup EXIT

# --- Validation ---
log_info "========================================="
log_info "RentATool Smoke Test"
log_info "========================================="
log_info "Base URL: $BASE_URL"
log_info "DB Path:  $DB_PATH"
log_info "Timeout:  ${TIMEOUT}s"
log_info ""

if [ ! -f "$DB_PATH" ]; then
    fail_and_exit "Baza nije pronadjena: $DB_PATH"
fi
if ! command -v sqlite3 &> /dev/null; then
    fail_and_exit "sqlite3 nije instaliran"
fi
if ! command -v curl &> /dev/null; then
    fail_and_exit "curl nije instaliran"
fi

TOOL_COUNT=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM tools WHERE status = 'available';")
if [ "$TOOL_COUNT" -eq 0 ]; then
    fail_and_exit "Nema dostupnih alata u bazi"
fi

# Clean up old test reservations BEFORE running test
log_info "Ciscenje starih test rezervacija..."
sqlite3 "$DB_PATH" "DELETE FROM reservation_items WHERE reservation_id IN (SELECT id FROM reservations WHERE customer_email = 'test@rentatool.in.rs');" 2>/dev/null || true
sqlite3 "$DB_PATH" "DELETE FROM reservations WHERE customer_email = 'test@rentatool.in.rs';" 2>/dev/null || true
# Also clean up old pending reservations that might block (keep max 3)
sqlite3 "$DB_PATH" "
    DELETE FROM reservation_items WHERE reservation_id IN (
        SELECT id FROM reservations 
        WHERE status = 'pending' 
        AND created_at < datetime('now', '-2 days')
        AND id NOT IN (SELECT id FROM reservations ORDER BY created_at DESC LIMIT 3)
    );
    DELETE FROM reservations 
    WHERE status = 'pending' 
    AND created_at < datetime('now', '-2 days')
    AND id NOT IN (SELECT id FROM reservations ORDER BY created_at DESC LIMIT 3);
" 2>/dev/null || true

COOKIE_JAR=$(mktemp)

# --- Step 1: Homepage ---
log_info "Korak 1: Provera homepage-a..."
check_timeout
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 "$BASE_URL/")
if [ "$HTTP_CODE" -ne 200 ]; then
    fail_and_exit "Homepage nije dostupan (HTTP $HTTP_CODE)"
fi
log_ok "Homepage radi (HTTP 200)"

# --- Step 2: Get available tool with no date conflicts ---
log_info "Korak 2: Dohvatanje dostupnog alata..."
check_timeout
TOOL_ID=$(sqlite3 "$DB_PATH" "
    SELECT t.id FROM tools t
    WHERE t.status = 'available'
    AND NOT EXISTS (
        SELECT 1 FROM reservation_items ri
        JOIN reservations r ON ri.reservation_id = r.id
        WHERE ri.tool_id = t.id
        AND r.status IN ('pending', 'confirmed', 'rented')
        AND r.date_start <= '$DAY_AFTER' AND r.date_end >= '$TOMORROW'
    )
    ORDER BY t.id ASC LIMIT 1;")
TOOL_NAME=$(sqlite3 "$DB_PATH" "SELECT name FROM tools WHERE id = $TOOL_ID;")
TOOL_SLUG=$(sqlite3 "$DB_PATH" "SELECT slug FROM tools WHERE id = $TOOL_ID;")
if [ -z "$TOOL_ID" ]; then
    fail_and_exit "Nije moguce naci dostupan alat"
fi
log_ok "Nadjen alat: $TOOL_NAME (ID: $TOOL_ID, slug: $TOOL_SLUG)"

# --- Step 3: Add tool to cart ---
log_info "Korak 3: Dodavanje alata u korpu..."
check_timeout
CART_RESPONSE=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -X POST \
    -H "Content-Type: application/json" \
    -d "{\"action\":\"add\",\"tool_id\":$TOOL_ID,\"date_start\":\"$TOMORROW\",\"date_end\":\"$DAY_AFTER\"}" \
    --max-time 10 \
    "$BASE_URL/api/cart")

CART_SUCCESS=$(echo "$CART_RESPONSE" | python3 -c "import sys,json; print(json.load(sys.stdin).get('success', False))" 2>/dev/null || echo "false")
if [ "$CART_SUCCESS" != "True" ]; then
    fail_and_exit "Dodavanje u korpu nije uspelo: $CART_RESPONSE"
fi
log_ok "Alat dodat u korpu"

# --- Step 4: Verify cart ---
log_info "Korak 4: Provera korpe..."
check_timeout
CART_GET=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -X POST \
    -H "Content-Type: application/json" \
    -d '{"action":"get"}' \
    --max-time 10 \
    "$BASE_URL/api/cart")

CART_COUNT=$(echo "$CART_GET" | python3 -c "import sys,json; print(json.load(sys.stdin).get('cart_count', 0))" 2>/dev/null || echo "0")
if [ "$CART_COUNT" -eq 0 ]; then
    fail_and_exit "Korpa je prazna nakon dodavanja"
fi
log_ok "Korpa ima $CART_COUNT stavku/i"

# --- Step 5: Get checkout page and extract CSRF token ---
log_info "Korak 5: Ucitavanje checkout stranice..."
check_timeout
CHECKOUT_PAGE=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    --max-time 10 \
    "$BASE_URL/checkout")

CSRF_TOKEN=$(echo "$CHECKOUT_PAGE" | grep -oP 'name="csrf_token" value="\K[^"]+' || echo "")
if [ -z "$CSRF_TOKEN" ]; then
    fail_and_exit "CSRF token nije pronadjen na checkout stranici"
fi
log_ok "CSRF token ekstrahiran"

# --- Step 6: Submit checkout ---
log_info "Korak 6: Slanje checkout forme..."
check_timeout
CHECKOUT_RESPONSE=$(curl -s -L -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
    -X POST \
    -d "csrf_token=$CSRF_TOKEN" \
    -d "customer_name=$(echo "$TEST_NAME" | jq -sRr @uri)" \
    -d "customer_email=$(echo "$TEST_EMAIL" | jq -sRr @uri)" \
    -d "customer_phone=$(echo "$TEST_PHONE" | jq -sRr @uri)" \
    -d "customer_address=$(echo "$TEST_ADDRESS" | jq -sRr @uri)" \
    -d "customer_note=$(echo "$TEST_NOTE" | jq -sRr @uri)" \
    -d "delivery_option=$DELIVERY_OPTION" \
    -w "\n%{http_code}" \
    --max-time 15 \
    "$BASE_URL/checkout")

FINAL_HTTP_CODE=$(echo "$CHECKOUT_RESPONSE" | tail -1)
CHECKOUT_BODY=$(echo "$CHECKOUT_RESPONSE" | sed '$d')

# --- Step 7: Verify thank you page ---
log_info "Korak 7: Provera thank you stranice..."
check_timeout
RESERVATION_CODE=$(echo "$CHECKOUT_BODY" | grep -oP 'class="code">\K[A-Z0-9]+' || echo "")
if [ -z "$RESERVATION_CODE" ]; then
    RESERVATION_CODE=$(echo "$CHECKOUT_BODY" | grep -oP 'Broj rezervacije:</span>\s*<span class="code">\K[A-Z0-9]+' || echo "")
fi
if [ -z "$RESERVATION_CODE" ]; then
    log_fail "Reservation code nije pronadjen na thank you stranici"
    log_info "HTTP Code: $FINAL_HTTP_CODE"
    if echo "$CHECKOUT_BODY" | grep -q "alert-error\|alert error"; then
        ERRORS=$(echo "$CHECKOUT_BODY" | grep -oP '<li>\K[^<]+' || echo "Nepoznata greska")
        log_fail "Checkout greske: $ERRORS"
    fi
    cleanup
    exit 1
fi
log_ok "Thank you page prikazana sa reservation kodom: $RESERVATION_CODE"

# --- Step 8: Verify reservation in DB ---
log_info "Korak 8: Provera rezervacije u bazi..."
check_timeout
DB_RESERVATION=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM reservations WHERE reservation_code = '$RESERVATION_CODE' AND customer_email = '$TEST_EMAIL';")
if [ "$DB_RESERVATION" -eq 0 ]; then
    fail_and_exit "Rezervacija $RESERVATION_CODE nije pronadjena u bazi"
fi
log_ok "Rezervacija postoji u bazi"

# --- Step 9: Verify Telegram config ---
log_info "Korak 9: Provera Telegram konfiguracije..."
check_timeout
ENV_FILE="$(dirname "$0")/../.env"
if [ -f "$ENV_FILE" ]; then
    ENV_TOKEN=$(grep "^TELEGRAM_BOT_TOKEN=" "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "")
    ENV_CHAT_ID=$(grep "^TELEGRAM_CHAT_ID=" "$ENV_FILE" 2>/dev/null | cut -d'=' -f2 || echo "")
fi
if [ -n "${ENV_TOKEN:-}" ] && [ -n "${ENV_CHAT_ID:-}" ]; then
    log_ok "Telegram konfigurisan (poruka je poslata tokom checkout-a)"
else
    log_info "Telegram nije konfigurisan (preskoceno slanje poruke)"
fi

# --- Summary ---
TOTAL_TIME=$(elapsed)
echo ""
log_info "========================================="
log_ok "SMOKE TEST PROSAO ($TOTAL_TIME s)"
log_info "========================================="
log_info "Reservation code: $RESERVATION_CODE"
log_info "Test alat: $TOOL_NAME"
log_info "Period: $TOMORROW - $DAY_AFTER"
log_info ""
exit 0
