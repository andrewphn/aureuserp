#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const managersDir = 'plugins/webkul/projects/resources/js/components/pdf-viewer/managers';

// Get all manager files
const files = fs.readdirSync(managersDir)
    .filter(f => f.endsWith('.js'))
    .sort();

console.log(`📊 JSDoc Coverage Analysis for ${files.length} Manager Files\n`);
console.log('=' .repeat(80));

const results = [];
let totalFunctions = 0;
let totalDocumented = 0;
let totalUndocumented = 0;

files.forEach(file => {
    const filePath = path.join(managersDir, file);
    const content = fs.readFileSync(filePath, 'utf8');
    const lines = content.split('\n');

    const functions = [];
    const undocumented = [];

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];

        // Match exported function declarations
        const match = line.match(/^export (async )?function (\w+)/);
        if (match) {
            const funcName = match[2];

            // Check if previous lines have JSDoc
            let hasJSDoc = false;
            for (let j = i - 1; j >= Math.max(0, i - 10); j--) {
                if (lines[j].trim().match(/^\/\*\*/)) {
                    hasJSDoc = true;
                    break;
                }
                // Stop if we hit another function or significant code
                if (lines[j].match(/^(export |function |const |let |var )/)) {
                    break;
                }
            }

            functions.push({ name: funcName, line: i + 1, documented: hasJSDoc });
            if (!hasJSDoc) {
                undocumented.push({ name: funcName, line: i + 1 });
            }
        }
    }

    totalFunctions += functions.length;
    totalDocumented += functions.filter(f => f.documented).length;
    totalUndocumented += undocumented.length;

    results.push({
        file,
        functions: functions.length,
        documented: functions.filter(f => f.documented).length,
        undocumented: undocumented.length,
        coverage: functions.length > 0 ?
            ((functions.filter(f => f.documented).length / functions.length) * 100).toFixed(1) :
            '0.0',
        undocumentedList: undocumented
    });
});

// Sort by coverage (worst first)
results.sort((a, b) => parseFloat(a.coverage) - parseFloat(b.coverage));

// Print summary table
console.log('\n📋 Summary by File (sorted by coverage, worst first):\n');
console.log('File'.padEnd(40) + 'Functions'.padEnd(12) + 'Documented'.padEnd(14) + 'Coverage');
console.log('-'.repeat(80));

results.forEach(r => {
    const status = parseFloat(r.coverage) === 100 ? '✅' :
                   parseFloat(r.coverage) >= 80 ? '⚠️' : '❌';
    console.log(
        `${status} ${r.file.padEnd(37)}${String(r.functions).padEnd(12)}${String(r.documented).padEnd(14)}${r.coverage}%`
    );
});

console.log('-'.repeat(80));
console.log(`Total`.padEnd(40) +
            `${totalFunctions}`.padEnd(12) +
            `${totalDocumented}`.padEnd(14) +
            `${((totalDocumented / totalFunctions) * 100).toFixed(1)}%`);

// Print detailed undocumented functions
console.log('\n\n📝 Undocumented Functions by File:\n');
console.log('='.repeat(80));

results.forEach(r => {
    if (r.undocumented > 0) {
        console.log(`\n${r.file} (${r.undocumented} undocumented):`);
        r.undocumentedList.forEach(f => {
            console.log(`  - ${f.name.padEnd(35)} (line ${f.line})`);
        });
    }
});

// Print recommendations
console.log('\n\n💡 Recommendations:\n');
console.log('='.repeat(80));

const highPriority = results.filter(r => parseFloat(r.coverage) < 50 && r.functions > 5);
const mediumPriority = results.filter(r => parseFloat(r.coverage) >= 50 && parseFloat(r.coverage) < 80 && r.functions > 3);
const lowPriority = results.filter(r => parseFloat(r.coverage) >= 80 && parseFloat(r.coverage) < 100);

if (highPriority.length > 0) {
    console.log('\n🔴 HIGH PRIORITY (coverage < 50%, 5+ functions):');
    highPriority.forEach(r => {
        console.log(`   - ${r.file} (${r.coverage}% coverage, ${r.undocumented} undocumented)`);
    });
}

if (mediumPriority.length > 0) {
    console.log('\n🟡 MEDIUM PRIORITY (coverage 50-80%, 3+ functions):');
    mediumPriority.forEach(r => {
        console.log(`   - ${r.file} (${r.coverage}% coverage, ${r.undocumented} undocumented)`);
    });
}

if (lowPriority.length > 0) {
    console.log('\n🟢 LOW PRIORITY (coverage 80-99%):');
    lowPriority.forEach(r => {
        console.log(`   - ${r.file} (${r.coverage}% coverage, ${r.undocumented} undocumented)`);
    });
}

const perfect = results.filter(r => parseFloat(r.coverage) === 100);
if (perfect.length > 0) {
    console.log('\n✅ PERFECT COVERAGE (100%):');
    perfect.forEach(r => {
        console.log(`   - ${r.file}`);
    });
}

console.log('\n' + '='.repeat(80));
console.log(`\n📊 Overall Coverage: ${((totalDocumented / totalFunctions) * 100).toFixed(1)}% (${totalDocumented}/${totalFunctions} functions documented)\n`);
