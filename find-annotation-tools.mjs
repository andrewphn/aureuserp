#!/usr/bin/env node

/**
 * Find and test the annotation drawing tools
 */

import { chromium } from 'playwright';

const BASE_URL = 'http://aureuserp.test';
const PROJECT_ID = 1;
const PDF_ID = 1;

async function main() {
    console.log('╔══════════════════════════════════════════════════════╗');
    console.log('║  FINDING ANNOTATION DRAWING TOOLS                     ║');
    console.log('╚══════════════════════════════════════════════════════╝\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 1000
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    try {
        // Login
        console.log('🔐 Logging in...');
        await page.goto(`${BASE_URL}/admin/login`);
        await page.waitForLoadState('networkidle');

        if (page.url().includes('/login')) {
            await page.locator('input[type="email"]').first().fill('info@tcswoodwork.com');
            await page.locator('input[type="password"]').first().fill('Lola2024!');
            await page.click('button:has-text("Sign in")');
            await page.waitForLoadState('networkidle');
            await page.waitForTimeout(2000);
        }
        console.log('✓ Logged in\n');

        // Navigate to annotation viewer
        console.log('📄 Opening annotation viewer...');
        await page.goto(`${BASE_URL}/admin/project/projects/${PROJECT_ID}/pdf-review?pdf=${PDF_ID}`);
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(5000);
        console.log('✓ Viewer opened\n');

        // Take initial screenshot
        console.log('📸 Taking initial screenshot...');
        await page.screenshot({ path: 'annotation-interface-full.png', fullPage: true });
        console.log('   Saved: annotation-interface-full.png\n');

        // Look for common annotation tool patterns
        console.log('🔍 Searching for annotation tools...\n');

        const toolPatterns = [
            { name: 'Rectangle', selectors: ['button:has-text("Rectangle")', '[title*="Rectangle"]', '[aria-label*="Rectangle"]', 'button[data-tool="rectangle"]'] },
            { name: 'Circle', selectors: ['button:has-text("Circle")', '[title*="Circle"]', '[aria-label*="Circle"]', 'button[data-tool="circle"]'] },
            { name: 'Arrow', selectors: ['button:has-text("Arrow")', '[title*="Arrow"]', '[aria-label*="Arrow"]'] },
            { name: 'Text', selectors: ['button:has-text("Text")', '[title*="Text"]', '[aria-label*="Text"]'] },
            { name: 'Annotate', selectors: ['button:has-text("Annotate")', '[title*="Annotate"]'] },
        ];

        let foundTools = [];

        for (const tool of toolPatterns) {
            for (const selector of tool.selectors) {
                const count = await page.locator(selector).count();
                if (count > 0) {
                    console.log(`✓ Found ${tool.name} tool: ${selector} (${count} matches)`);
                    foundTools.push({ name: tool.name, selector, count });
                    break;
                }
            }
        }

        if (foundTools.length === 0) {
            console.log('❌ No standard annotation tools found\n');
            console.log('Looking for ANY buttons on the page...\n');

            // Get all visible buttons
            const allButtons = await page.locator('button:visible').all();
            console.log(`Found ${allButtons.length} visible buttons total\n`);

            console.log('First 30 buttons:');
            for (let i = 0; i < Math.min(30, allButtons.length); i++) {
                const btn = allButtons[i];
                const text = await btn.textContent();
                const title = await btn.getAttribute('title');
                const ariaLabel = await btn.getAttribute('aria-label');
                const className = await btn.getAttribute('class');

                const display = text || title || ariaLabel || `[class: ${className?.substring(0, 30)}...]`;
                console.log(`   ${i + 1}. ${display}`);
            }

            console.log('\n📸 Taking detailed screenshot for analysis...');
            await page.screenshot({ path: 'no-tools-found.png', fullPage: true });
            console.log('   Saved: no-tools-found.png\n');

        } else {
            console.log(`\n✅ Found ${foundTools.length} annotation tools!\n`);

            // Try to click the first tool
            console.log('Testing first tool: ' + foundTools[0].name);
            console.log('Clicking: ' + foundTools[0].selector + '\n');

            try {
                await page.locator(foundTools[0].selector).first().click();
                await page.waitForTimeout(1000);
                console.log('✓ Tool clicked successfully\n');

                // Take screenshot after clicking
                await page.screenshot({ path: 'tool-activated.png', fullPage: false });
                console.log('📸 Screenshot after click: tool-activated.png\n');

            } catch (err) {
                console.log('❌ Error clicking tool:', err.message + '\n');
            }
        }

        // Check for canvas elements (PDF might be rendered on canvas)
        const canvasCount = await page.locator('canvas').count();
        console.log(`\n📊 Found ${canvasCount} canvas elements`);

        if (canvasCount > 0) {
            console.log('Canvas elements detected - PDF is likely rendered\n');
        }

        console.log('='.repeat(60));
        console.log('\nBrowser staying open for manual inspection.');
        console.log('Check the screenshots and try clicking tools manually.\n');
        console.log('Press Ctrl+C when done.\n');

        // Keep browser open
        await page.waitForTimeout(3600000);

    } catch (error) {
        console.error('\n❌ Error:', error.message);
        await page.screenshot({ path: 'error-finding-tools.png', fullPage: true });
    } finally {
        await browser.close();
    }
}

main();
