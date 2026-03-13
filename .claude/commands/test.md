# /test — Run Test Suite

Run the project test suite and report results.

## Options

If the user specifies "quick" or "fast", run `npm run test:quick` (PHP lint + build only, no Docker required).
If the user specifies "e2e" or "browser", run `npm run test:e2e` (Playwright tests only).
Otherwise, run `npm run test` (full suite — Docker must be running).

## Steps

1. Run the appropriate test command
2. Display the results clearly
3. If any tests fail, list the specific failures
4. State clearly whether the code is safe to push

## Important

- Never skip or ignore test failures
- If Docker is not running and the user requests the full suite, warn them and suggest `npm run test:quick` instead
- After reporting results, ask if the user wants to fix any failures
