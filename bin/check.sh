#!/bin/bash

set -e

BOLD='\033[1m'
CYAN='\033[36m'
GREEN='\033[32m'
YELLOW='\033[33m'
MAGENTA='\033[35m'
DIM='\033[2m'
RESET='\033[0m'

STEP_NUM=0
TOTAL_STEPS=5

step() {
    STEP_NUM=$((STEP_NUM + 1))
    echo ""
    echo -e "${DIM}┌──────────────────────────────────────────────┐${RESET}"
    echo -e "${DIM}│${RESET} ${BOLD}${CYAN}[${STEP_NUM}/${TOTAL_STEPS}]${RESET} ${BOLD}$1${RESET}"
    echo -e "${DIM}└──────────────────────────────────────────────┘${RESET}"
}

ok() {
    echo -e "${GREEN}${BOLD}  [OK]${RESET} $1"
}

echo -e "${MAGENTA}${BOLD}"
cat << "EOF"
   ___ _  _ ___ ___ _  __
  / __| || | __/ __| |/ /
 | (__| __ | _| (__| ' 
  \___|_||_|___\___|_|\_\

EOF
echo -e "${RESET}${DIM}  php-minimal · quality gate${RESET}"

step "Regenerating optimized autoloader"
./bin/autoload.sh
ok "Autoloader regenerated"

step "Running code style fixer"
./bin/cs-fix.sh
ok "Code style fixed"

step "Running static analysis"
./bin/analyse.sh
ok "Static analysis passed"

step "Running tests"
./bin/test.sh
ok "Tests passed"

step "Generating coverage report"
./bin/coverage.sh
ok "Coverage report generated"

echo ""
echo -e "${GREEN}${BOLD}"
cat << "EOF"
  ✓✓✓ ALL CHECKS PASSED ✓✓✓
EOF
echo -e "${RESET}"