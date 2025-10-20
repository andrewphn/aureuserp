import { chromium } from '@playwright/test';
import fs from 'fs';

(async () => {
    const browser = await chromium.launch();
    const page = await browser.newPage();

    await page.goto('http://aureuserp.test/admin/projects/projects/1/annotate-pdf-v2?pdf=1&page=1');
    await page.waitForLoadState('networkidle');
    
    const html = await page.content();
    fs.writeFileSync('page-html-output.html', html);
    console.log('HTML saved to page-html-output.html');
    console.log('HTML length:', html.length, 'bytes');
    
    // Check for key elements
    console.log('Contains x-filament-panels::page:', html.includes('x-filament-panels::page'));
    console.log('Contains pdf-annotation-viewer:', html.includes('pdf-annotation-viewer'));
    console.log('Contains @vite:', html.includes('@vite'));
    console.log('Contains annotations.js:', html.includes('annotations.js'));
    
    await browser.close();
})();
