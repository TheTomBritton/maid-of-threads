#!/usr/bin/env bash
#
# Full test suite for Maid of Threads
# Runs: Docker check → PHP lint → Frontend build → Playwright e2e tests
#
# Usage: npm run test  (or bash scripts/test.sh)
#

set -euo pipefail

PASS=0
FAIL=0
ERRORS=""

# Colours
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No colour

pass() {
    PASS=$((PASS + 1))
    echo -e "  ${GREEN}✓${NC} $1"
}

fail() {
    FAIL=$((FAIL + 1))
    ERRORS="${ERRORS}\n  • $1"
    echo -e "  ${RED}✗${NC} $1"
}

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo " Maid of Threads — Full Test Suite"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# ──────────────────────────────────────────────
# 1. Docker environment check
# ──────────────────────────────────────────────
echo -e "${YELLOW}[1/4] Docker Environment${NC}"

if docker compose -f docker/docker-compose.yml ps --format '{{.State}}' 2>/dev/null | grep -q 'running'; then
    pass "Docker containers running"
else
    fail "Docker containers not running — start with: cd docker && docker compose up -d"
    echo ""
    echo -e "${RED}Cannot continue without Docker. Aborting.${NC}"
    exit 1
fi

# Check site responds
if curl -sf -o /dev/null http://localhost:8080/ 2>/dev/null; then
    pass "Site responds at http://localhost:8080"
else
    fail "Site not responding at http://localhost:8080"
    echo ""
    echo -e "${RED}Cannot continue without site responding. Aborting.${NC}"
    exit 1
fi

echo ""

# ──────────────────────────────────────────────
# 2. PHP syntax check (via Docker since PHP runs in container)
# ──────────────────────────────────────────────
echo -e "${YELLOW}[2/4] PHP Syntax Validation${NC}"

PHP_ERRORS=0
CONTAINER=$(docker compose -f docker/docker-compose.yml ps -q web 2>/dev/null)
for f in site/templates/*.php; do
    CONTAINER_PATH="/var/www/html/$f"
    if docker exec "$CONTAINER" php -l "$CONTAINER_PATH" > /dev/null 2>&1; then
        : # silent on success
    else
        fail "Syntax error in $f"
        docker exec "$CONTAINER" php -l "$CONTAINER_PATH" 2>&1 | tail -1
        PHP_ERRORS=$((PHP_ERRORS + 1))
    fi
done

if [ $PHP_ERRORS -eq 0 ]; then
    COUNT=$(ls -1 site/templates/*.php | wc -l | tr -d ' ')
    pass "All $COUNT PHP files pass syntax check"
fi

echo ""

# ──────────────────────────────────────────────
# 3. Frontend build
# ──────────────────────────────────────────────
echo -e "${YELLOW}[3/4] Frontend Build${NC}"

if npm run build --silent 2>/dev/null; then
    pass "npm run build succeeded"
else
    fail "npm run build failed"
fi

# Verify output files
if [ -s site/assets/dist/app.css ]; then
    pass "app.css exists and is non-empty"
else
    fail "app.css missing or empty"
fi

if [ -s site/assets/dist/app.js ]; then
    pass "app.js exists and is non-empty"
else
    fail "app.js missing or empty"
fi

echo ""

# ──────────────────────────────────────────────
# 4. Playwright e2e tests
# ──────────────────────────────────────────────
echo -e "${YELLOW}[4/4] Playwright E2E Tests${NC}"

if npx playwright test --config=tests/playwright.config.js 2>&1; then
    pass "All Playwright tests passed"
else
    fail "Playwright tests failed (see output above)"
fi

echo ""

# ──────────────────────────────────────────────
# Summary
# ──────────────────────────────────────────────
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo " Results: ${GREEN}${PASS} passed${NC}, ${RED}${FAIL} failed${NC}"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ $FAIL -gt 0 ]; then
    echo ""
    echo -e "${RED}FAILURES:${NC}"
    echo -e "$ERRORS"
    echo ""
    echo -e "${RED}✗ DO NOT PUSH — fix failures first${NC}"
    exit 1
else
    echo ""
    echo -e "${GREEN}✓ All checks passed — safe to push${NC}"
    exit 0
fi
