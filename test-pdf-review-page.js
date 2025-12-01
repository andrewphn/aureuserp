import { chromium } from 'playwright';

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: '/Users/andrewphan/Library/Caches/ms-playwright/chromium-1193/chrome-mac/Chromium.app/Contents/MacOS/Chromium'
  });
  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 }
  });
  const page = await context.newPage();

  try {
    console.log('Navigating to PDF review page...');
    await page.goto('http://aureuserp.test/admin/project/projects/18/pdf-review?pdf=19', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // Check if redirected to login
    const currentUrl = page.url();
    console.log('Current URL:', currentUrl);

    if (currentUrl.includes('/login')) {
      console.log('Login required, authenticating...');

      // Wait for login form
      await page.waitForSelector('input[type="email"]', { timeout: 5000 });

      // Fill login form
      await page.fill('input[type="email"]', 'info@tcswoodwork.com');
      await page.fill('input[type="password"]', 'Lola2024!');

      // Click login button and wait for navigation
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 15000 }),
        page.click('button[type="submit"]')
      ]);

      console.log('Login successful, navigating to PDF review page...');

      // Navigate to the target page
      await page.goto('http://aureuserp.test/admin/project/projects/18/pdf-review?pdf=19', {
        waitUntil: 'networkidle',
        timeout: 30000
      });

      console.log('Arrived at PDF review page');
    }

    // Wait for page content to load
    await page.waitForTimeout(2000);

    // Take full page screenshot
    console.log('Taking screenshot...');
    await page.screenshot({
      path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-page-fixed.png',
      fullPage: true
    });

    console.log('Screenshot saved successfully!');

    // Analyze page structure
    const pageAnalysis = await page.evaluate(() => {
      const analysis = {
        hasWizardSteps: false,
        hasCollapsibleSections: false,
        mainSections: [],
        scrollHeight: document.documentElement.scrollHeight,
        viewportHeight: window.innerHeight,
        pageTitle: document.title
      };

      // Check for wizard steps
      const wizardSteps = document.querySelectorAll('[class*="wizard-step"], [class*="step-"]');
      analysis.hasWizardSteps = wizardSteps.length > 0;

      // Check for collapsible sections
      const collapsibles = document.querySelectorAll('[x-data*="collapse"], details, [class*="collaps"]');
      analysis.hasCollapsibleSections = collapsibles.length > 0;

      // Get main sections
      const sections = document.querySelectorAll('section, [class*="section"], .card, [class*="panel"]');
      sections.forEach((section, index) => {
        const heading = section.querySelector('h1, h2, h3, h4, h5, h6');
        analysis.mainSections.push({
          index,
          heading: heading ? heading.textContent.trim() : 'No heading',
          class: section.className,
          height: section.offsetHeight
        });
      });

      return analysis;
    });

    console.log('\n=== PAGE ANALYSIS ===');
    console.log('Page Title:', pageAnalysis.pageTitle);
    console.log('Scroll Height:', pageAnalysis.scrollHeight, 'px');
    console.log('Viewport Height:', pageAnalysis.viewportHeight, 'px');
    console.log('Requires Scrolling:', pageAnalysis.scrollHeight > pageAnalysis.viewportHeight ? 'YES' : 'NO');
    console.log('Has Wizard Steps:', pageAnalysis.hasWizardSteps ? 'YES' : 'NO');
    console.log('Has Collapsible Sections:', pageAnalysis.hasCollapsibleSections ? 'YES' : 'NO');
    console.log('\nMain Sections Found:', pageAnalysis.mainSections.length);
    pageAnalysis.mainSections.forEach(section => {
      console.log(`  - ${section.heading} (${section.height}px)`);
    });

  } catch (error) {
    console.error('Error:', error.message);

    // Take error screenshot
    await page.screenshot({
      path: '/Users/andrewphan/tcsadmin/aureuserp/.playwright-mcp/pdf-review-page-error.png',
      fullPage: true
    });
  } finally {
    await browser.close();
  }
})();
