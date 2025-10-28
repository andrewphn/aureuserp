import { chromium } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 900 }
  });
  const page = await context.newPage();

  try {
    console.log('🔐 Logging in...');
    await page.goto('http://aureuserp.test/admin/login');
    await page.fill('input[type="email"]', 'info@tcswoodwork.com');
    await page.fill('input[type="password"]', 'Lola2024!');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    console.log('✅ Logged in');

    console.log('📄 Navigating to project page...');
    await page.goto('http://aureuserp.test/admin/project/projects/9/edit');
    await page.waitForLoadState('networkidle', { timeout: 15000 });
    console.log('✅ Page loaded');

    await page.waitForTimeout(2000);

    // Expand footer
    const footer = await page.locator('.fi-section').first();
    await footer.click();
    await page.waitForTimeout(1000);

    // Inspect the full DOM structure and styles
    const structure = await page.evaluate(() => {
      const section = document.querySelector('.fi-section');
      if (!section) return null;

      const getStyles = (el) => {
        const styles = window.getComputedStyle(el);
        return {
          padding: styles.padding,
          paddingBottom: styles.paddingBottom,
          margin: styles.margin,
          marginBottom: styles.marginBottom,
          className: el.className,
          tagName: el.tagName
        };
      };

      return {
        section: getStyles(section),
        parent: section.parentElement ? getStyles(section.parentElement) : null,
        grandparent: section.parentElement?.parentElement ? getStyles(section.parentElement.parentElement) : null,
        sectionHTML: section.outerHTML.substring(0, 500)
      };
    });

    console.log('🔍 DOM Structure Analysis:');
    console.log(JSON.stringify(structure, null, 2));

  } catch (error) {
    console.error('❌ Error:', error.message);
    await page.screenshot({ path: 'footer-structure-error.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();
