# Browser Tests with Playwright

This directory contains browser-based end-to-end tests for the AureusERP PDF viewer functionality using Playwright.

## Setup

Install Playwright browsers:

```bash
npm run playwright:install
```

## Running Tests

```bash
# Run all browser tests (headless)
npm run test:browser

# Run tests with browser UI visible
npm run test:browser:headed

# Run tests in specific browser
npm run test:browser:chrome
npm run test:browser:firefox
npm run test:browser:webkit

# Debug tests interactively
npm run test:browser:debug

# Open Playwright UI mode
npm run test:browser:ui
```

## Test Structure

- `fixtures/` - Reusable test fixtures for authentication and data setup
- `helpers/` - Helper functions for PDF viewer interactions
- `reports/` - Test execution reports (HTML, JSON)
- `*.spec.ts` - Test files

## Writing Tests

```typescript
import { test, expect } from './fixtures/test-fixtures';
import { createPdfViewerHelpers } from './helpers/pdf-viewer-helpers';

test('should create annotation', async ({ pdfViewerPage }) => {
  const helpers = createPdfViewerHelpers(pdfViewerPage);

  await helpers.waitForViewerReady();

  const annotationId = await helpers.createAnnotation('TextAnnotation', {
    x: 100,
    y: 200,
  });

  expect(annotationId).toBeTruthy();
});
```

## Configuration

Browser and test settings are configured in `playwright.config.ts` at the project root.

## Environment Variables

- `APP_URL` - Base URL for the application (default: http://localhost)
- `TEST_USER_EMAIL` - Test user email for authentication
- `TEST_USER_PASSWORD` - Test user password

## CI/CD Integration

Tests are configured to run in CI with retries and video/screenshot capture on failure.
