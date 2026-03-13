#!/usr/bin/env bash
#
# Quick test suite for Maid of Threads
# Runs: PHP lint → Frontend build (no Docker or Playwright required)
#
# Usage: npm run test:quick  (or bash scripts/test-quick.sh)
#

set -euo pipefail

PASS=0
FAIL=0
ERRORS=""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

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
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo " Maid of Threads — Quick Checks"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# ──────────────────────────────────────────────
# 1. PHP syntax check
# Uses local php if available, otherwise Docker container
# ──────────────────────────────────────────────
echo -e "${YELLOW}[1/2] PHP Syntax Validation${NC}"

PHP_ERRORS=0

# Determine PHP lint method
if command -v php > /dev/null 2>&1; then
    PHP_LINT="php -l"
    PHP_LINT_PREFIX=""
elif docker compose -f docker/docker-compose.yml ps -q web > /dev/null 2>&1; then
    CONTAINER=$(docker compose -f docker/docker-compose.yml ps -q web 2>/dev/null)
    if [ -n "$CONTAINER" ]; then
        PHP_LINT="docker exec $CONTAINER php -l"
        PHP_LINT_PREFIX="/var/www/html/"
    else
        echo -e "  ${YELLOW}⚠${NC} No PHP available locally or in Docker — skipping syntax check"
        PHP_LINT=""
    fi
else
    echo -e "  ${YELLOW}⚠${NC} No PHP available locally or in Docker — skipping syntax check"
    PHP_LINT=""
fi

if [ -n "${PHP_LINT:-}" ]; then
    for f in site/templates/*.php; do
        TARGET="${PHP_LINT_PREFIX}${f}"
        if $PHP_LINT "$TARGET" > /dev/null 2>&1; then
            : # silent on success
        else
            fail "Syntax error in $f"
            $PHP_LINT "$TARGET" 2>&1 | tail -1
            PHP_ERRORS=$((PHP_ERRORS + 1))
        fi
    done

    if [ $PHP_ERRORS -eq 0 ]; then
        COUNT=$(ls -1 site/templates/*.php | wc -l | tr -d ' ')
        pass "All $COUNT PHP files pass syntax check"
    fi
fi

echo ""

# ──────────────────────────────────────────────
# 2. Frontend build
# ──────────────────────────────────────────────
echo -e "${YELLOW}[2/2] Frontend Build${NC}"

if npm run build --silent 2>/dev/null; then
    pass "npm run build succeeded"
else
    fail "npm run build failed"
fi

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
    echo -e "${RED}✗ Fix failures before continuing${NC}"
    exit 1
else
    echo ""
    echo -e "${GREEN}✓ Quick checks passed${NC}"
    exit 0
fi
