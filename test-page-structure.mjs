import { chromium } from '@playwright/test';

const browser = await chromium.launch({ headless: false });
const page = await browser.newPage();

// Login
console.log('🔐 Logging in...');
await page.goto('http://aureuserp.test/admin/login');
await page.fill('input[type="email"]', 'info@tcswoodwork.com');
await page.fill('input[type="password"]', 'Lola2024!');
await page.click('button[type="submit"]');
await page.waitForLoadState('networkidle');
console.log('✅ Logged in successfully\n');

// Navigate to annotation page
console.log('📍 Navigating to PDF annotation page...');
await page.goto('http://aureuserp.test/admin/projects/resources/projects/9/pdf-documents/1/pages/2/annotate');
await page.waitForLoadState('networkidle');
await page.waitForTimeout(2000);
console.log('✅ Page loaded\n');

// Get the page structure
console.log('🔍 Analyzing page structure...\n');
const structure = await page.evaluate(() => {
    const main = document.querySelector('main');
    if (!main) return { error: 'No main element found' };

    const getStructure = (el, depth = 0) => {
        const classes = Array.from(el.classList);
        const hasWFull = classes.includes('w-full');
        const hasPadding = classes.some(c => c.startsWith('pb-'));

        return {
            tag: el.tagName,
            classes: classes.join(' '),
            hasWFull,
            hasPadding,
            paddingClass: classes.find(c => c.startsWith('pb-')) || 'none',
            depth
        };
    };

    // Find all divs with w-full class
    const wFullDivs = Array.from(main.querySelectorAll('div.w-full'));
    return {
        totalWFullDivs: wFullDivs.length,
        divs: wFullDivs.map(div => getStructure(div))
    };
});

console.log('Page structure:', JSON.stringify(structure, null, 2));

// Also check the raw HTML of the first few divs
const rawHTML = await page.evaluate(() => {
    const main = document.querySelector('main');
    if (!main) return 'No main element';

    const firstDiv = main.querySelector('div');
    if (!firstDiv) return 'No div in main';

    return firstDiv.outerHTML.substring(0, 500);
});

console.log('\n📄 First div HTML (first 500 chars):\n', rawHTML);

await browser.close();
