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

// Check for bottom padding
console.log('🔍 Checking for bottom padding...');
const paddingInfo = await page.evaluate(() => {
    const mainContainer = document.querySelector('.w-full.pb-32');
    if (!mainContainer) {
        return { found: false };
    }

    const styles = window.getComputedStyle(mainContainer);
    return {
        found: true,
        paddingBottom: styles.paddingBottom,
        height: styles.height
    };
});

console.log('Container info:', paddingInfo);

if (paddingInfo.found) {
    console.log('✅ Bottom padding is applied:', paddingInfo.paddingBottom);
} else {
    console.log('❌ Container with pb-32 class not found');
}

// Check if global footer overlaps save button
console.log('\n🔍 Checking for footer overlap...');
const overlapInfo = await page.evaluate(() => {
    const footer = document.querySelector('[x-data*="contextFooterGlobal"]');
    const saveButton = document.querySelector('button[x-bind*="saveAnnotation"]');

    if (!footer || !saveButton) {
        return {
            footerFound: !!footer,
            saveButtonFound: !!saveButton
        };
    }

    const footerRect = footer.getBoundingClientRect();
    const buttonRect = saveButton.getBoundingClientRect();

    const overlaps = !(footerRect.bottom < buttonRect.top ||
                       footerRect.top > buttonRect.bottom);

    return {
        footerFound: true,
        saveButtonFound: true,
        overlaps: overlaps,
        footerTop: footerRect.top,
        footerBottom: footerRect.bottom,
        buttonTop: buttonRect.top,
        buttonBottom: buttonRect.bottom
    };
});

console.log('Overlap check:', overlapInfo);

if (overlapInfo.overlaps) {
    console.log('❌ Footer is overlapping the save button!');
} else {
    console.log('✅ No overlap detected - footer and save button are properly separated');
}

// Take screenshot
await page.screenshot({ path: 'footer-padding-test.png', fullPage: true });
console.log('\n📸 Screenshot saved: footer-padding-test.png');

await browser.close();
