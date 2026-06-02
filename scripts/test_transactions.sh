#!/usr/bin/env bash

# Test script: top up account by +100, then attempt 10 parallel charges of -30.
# Expected result: 3 charges succeed (3x30=90 <= 100), remaining 7 fail with insufficient funds.
#
# Usage:
#   ./scripts/test_transactions.sh [USER_ID] [BASE_URL]
#
# Defaults:
#   USER_ID  = 1
#   BASE_URL = http://localhost:8080/api
#
# Compatible with: macOS, Linux, Git Bash (Windows), WSL

set -euo pipefail

USER_ID="${1:-1}"
BASE_URL="${2:-http://localhost:8080/api}"
ENDPOINT="${BASE_URL}/accounts/${USER_ID}/transactions"

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Pretty-print JSON if python3 is available, otherwise print raw
json_pretty() {
    if command -v python3 &>/dev/null; then
        python3 -m json.tool 2>/dev/null || cat
    else
        cat
    fi
}

# Extract response body (all lines except the last HTTP status line)
extract_body() {
    sed '$d'
}

echo -e "${YELLOW}=== PowerDivision — Transaction stress test ===${NC}"
echo "Endpoint : ${ENDPOINT}"
echo "User ID  : ${USER_ID}"
echo ""

# ---------------------------------------------------------------------------
# Step 1: top up account by +100
# ---------------------------------------------------------------------------
echo -e "${YELLOW}[1/2] Topping up account by +100...${NC}"

TOP_UP_RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${ENDPOINT}" \
    -H "Content-Type: application/json" \
    -d '{"amount": 100}')

TOP_UP_BODY=$(echo "${TOP_UP_RESPONSE}" | extract_body)
TOP_UP_STATUS=$(echo "${TOP_UP_RESPONSE}" | tail -n 1)

if [ "${TOP_UP_STATUS}" -eq 200 ]; then
    echo -e "${GREEN}  Top-up OK (HTTP ${TOP_UP_STATUS})${NC}"
    echo "${TOP_UP_BODY}" | json_pretty
else
    echo -e "${RED}  Top-up FAILED (HTTP ${TOP_UP_STATUS})${NC}"
    echo "${TOP_UP_BODY}" | json_pretty
    exit 1
fi

echo ""

# ---------------------------------------------------------------------------
# Step 2: 10 parallel charges of -30
# ---------------------------------------------------------------------------
echo -e "${YELLOW}[2/2] Sending 10 parallel charges of -30...${NC}"
echo "  (only 3 should succeed, 7 should fail with insufficient_funds)"
echo ""

PIDS=()
TMPDIR_RESULTS=$(mktemp -d)

for i in $(seq 1 10); do
    (
        RESPONSE=$(curl -s -w "\n%{http_code}" -X POST "${ENDPOINT}" \
            -H "Content-Type: application/json" \
            -d '{"amount": -30}')

        BODY=$(echo "${RESPONSE}" | sed '$d')
        STATUS=$(echo "${RESPONSE}" | tail -n 1)

        echo "${STATUS}|${BODY}" > "${TMPDIR_RESULTS}/result_${i}"
    ) &
    PIDS+=($!)
done

# Wait for all background jobs
for PID in "${PIDS[@]}"; do
    wait "${PID}"
done

echo ""

SUCCESS=0
FAIL=0

for i in $(seq 1 10); do
    RESULT=$(cat "${TMPDIR_RESULTS}/result_${i}")
    STATUS=$(echo "${RESULT}" | cut -d'|' -f1)
    BODY=$(echo "${RESULT}" | cut -d'|' -f2-)

    if [ "${STATUS}" -eq 200 ]; then
        SUCCESS=$((SUCCESS + 1))
        echo -e "  Request #${i}: ${GREEN}OK (HTTP ${STATUS})${NC}"
        echo "${BODY}" | json_pretty
    else
        FAIL=$((FAIL + 1))
        echo -e "  Request #${i}: ${RED}FAILED (HTTP ${STATUS})${NC}"
        echo "${BODY}" | json_pretty
    fi
done

rm -rf "${TMPDIR_RESULTS}"

echo ""
echo -e "${YELLOW}=== Summary ===${NC}"
echo -e "  Succeeded : ${GREEN}${SUCCESS}${NC}"
echo -e "  Failed    : ${RED}${FAIL}${NC}"
