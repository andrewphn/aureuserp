#!/usr/bin/env node

/**
 * Helper Function Index Generator
 * Automatically extracts and documents all helper functions from manager files
 *
 * Usage: node generate-helpers-index.js
 */

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const MANAGERS_DIR = __dirname;
const OUTPUT_FILE = path.join(MANAGERS_DIR, 'HELPERS-INDEX.md');

// Manager display names and descriptions
const MANAGER_INFO = {
    'annotation-manager': {
        name: 'Annotation Manager',
        description: 'Handles annotation loading, transformation, and editing operations'
    },
    'autocomplete-manager': {
        name: 'Autocomplete Manager',
        description: 'Provides search functionality for rooms and locations'
    },
    'coordinate-transform': {
        name: 'Coordinate Transform',
        description: 'Converts between PDF coordinates and screen coordinates'
    },
    'drawing-system': {
        name: 'Drawing System',
        description: 'Handles interactive drawing of annotations and context management'
    },
    'entity-reference-manager': {
        name: 'Entity Reference Manager',
        description: 'Manages entity relationships and references'
    },
    'filter-system': {
        name: 'Filter System',
        description: 'Filters annotations by type, view, room, location, etc.'
    },
    'hierarchy-detection-manager': {
        name: 'Hierarchy Detection Manager',
        description: 'Detects and validates hierarchical relationships'
    },
    'isolation-mode-manager': {
        name: 'Isolation Mode Manager',
        description: 'Illustrator-style focus mode for hierarchical editing'
    },
    'navigation-manager': {
        name: 'Navigation Manager',
        description: 'Handles PDF page navigation'
    },
    'pdf-manager': {
        name: 'PDF Manager',
        description: 'Manages PDF rendering and page observation'
    },
    'resize-move-system': {
        name: 'Resize Move System',
        description: 'Handles annotation resizing and moving operations'
    },
    'state-manager': {
        name: 'State Manager',
        description: 'Initializes and manages component state'
    },
    'tree-manager': {
        name: 'Tree Manager',
        description: 'Builds and manages hierarchical tree structure'
    },
    'undo-redo-manager': {
        name: 'Undo Redo Manager',
        description: 'Manages undo/redo history stack'
    },
    'view-type-manager': {
        name: 'View Type Manager',
        description: 'Manages view types (plan, elevation, section, detail)'
    },
    'visibility-toggle-manager': {
        name: 'Visibility Toggle Manager',
        description: 'Controls visibility of rooms, locations, and annotations'
    },
    'zoom-manager': {
        name: 'Zoom Manager',
        description: 'Handles zoom operations and calculations'
    }
};

/**
 * Extract functions from a JavaScript file
 */
function extractFunctions(filePath) {
    const content = fs.readFileSync(filePath, 'utf-8');
    const lines = content.split('\n');
    const functions = [];

    let currentComment = null;
    let commentLines = [];

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const trimmed = line.trim();

        // Capture JSDoc comments
        if (trimmed.startsWith('/**')) {
            commentLines = [trimmed];
        } else if (commentLines.length > 0 && !trimmed.startsWith('*/')) {
            commentLines.push(trimmed);
        } else if (trimmed.startsWith('*/')) {
            commentLines.push(trimmed);
            currentComment = parseJSDoc(commentLines.join('\n'));
            commentLines = [];
        }

        // Match function declarations
        const exportMatch = trimmed.match(/^export\s+function\s+(\w+)\s*\(/);
        const functionMatch = trimmed.match(/^function\s+(\w+)\s*\(/);

        if (exportMatch || functionMatch) {
            const name = exportMatch ? exportMatch[1] : functionMatch[1];
            const isExported = !!exportMatch;

            // Extract parameters
            const paramsMatch = line.match(/\(([^)]*)\)/);
            const params = paramsMatch ? paramsMatch[1].trim() : '';

            functions.push({
                name,
                line: i + 1,
                isExported,
                params,
                comment: currentComment,
                fullLine: trimmed
            });

            currentComment = null;
        }
    }

    return functions;
}

/**
 * Parse JSDoc comment
 */
function parseJSDoc(comment) {
    const lines = comment.split('\n').map(l => l.trim().replace(/^\*\s*/, ''));

    let purpose = '';
    const params = [];
    let returns = '';

    let currentSection = 'description';

    for (const line of lines) {
        if (line.startsWith('@param')) {
            currentSection = 'params';
            const match = line.match(/@param\s+(?:\{([^}]+)\}\s+)?(\w+)(?:\s+-\s+(.+))?/);
            if (match) {
                params.push({
                    name: match[2],
                    type: match[1] || 'any',
                    description: match[3] || ''
                });
            }
        } else if (line.startsWith('@returns') || line.startsWith('@return')) {
            currentSection = 'returns';
            const match = line.match(/@returns?\s+(?:\{([^}]+)\}\s+)?(.+)?/);
            if (match) {
                returns = match[2] || match[1] || 'void';
            }
        } else if (line.startsWith('@purpose')) {
            currentSection = 'purpose';
            purpose = line.replace('@purpose', '').trim();
        } else if (!line.startsWith('/**') && !line.startsWith('*/') && !line.startsWith('@')) {
            if (currentSection === 'description' && line) {
                purpose += (purpose ? ' ' : '') + line;
            }
        }
    }

    return { purpose, params, returns };
}

/**
 * Generate markdown for a manager section
 */
function generateManagerSection(managerKey, functions) {
    const info = MANAGER_INFO[managerKey] || {
        name: managerKey.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' '),
        description: ''
    };

    const exported = functions.filter(f => f.isExported);
    const internal = functions.filter(f => !f.isExported);

    let md = `\n## ${info.name}\n\n`;
    md += `**File:** \`${managerKey}.js\`\n`;
    if (info.description) {
        md += `**Description:** ${info.description}\n`;
    }
    md += '\n';

    if (exported.length > 0) {
        md += '### Exported Functions\n\n';

        for (const func of exported) {
            md += `#### \`${func.name}(${func.params})\`\n`;
            md += `**Line:** ${func.line}\n`;

            if (func.comment?.purpose) {
                md += `**Purpose:** ${func.comment.purpose}\n`;
            }

            if (func.comment?.params?.length > 0) {
                md += `**Parameters:**\n`;
                for (const param of func.comment.params) {
                    md += `- \`${param.name}\` (${param.type})`;
                    if (param.description) {
                        md += ` - ${param.description}`;
                    }
                    md += '\n';
                }
            }

            if (func.comment?.returns) {
                md += `**Returns:** ${func.comment.returns}\n`;
            }

            md += '\n';
        }
    }

    if (internal.length > 0) {
        md += '### Internal Functions\n\n';

        for (const func of internal) {
            md += `#### \`${func.name}(${func.params})\`\n`;
            md += `**Line:** ${func.line}\n`;

            if (func.comment?.purpose) {
                md += `**Purpose:** ${func.comment.purpose}\n`;
            }

            md += '\n';
        }
    }

    return md;
}

/**
 * Generate table of contents
 */
function generateTOC(managers) {
    let toc = '## 📋 Table of Contents\n\n';

    for (const managerKey of managers) {
        const info = MANAGER_INFO[managerKey] || {
            name: managerKey.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
        };
        const anchor = info.name.toLowerCase().replace(/\s+/g, '-');
        toc += `- [${info.name}](#${anchor})\n`;
    }

    toc += '\n---\n';

    return toc;
}

/**
 * Main execution
 */
function main() {
    console.log('🔍 Scanning manager files...');

    // Get all JS files in managers directory (excluding this script)
    const files = fs.readdirSync(MANAGERS_DIR)
        .filter(f => f.endsWith('.js') && f !== 'generate-helpers-index.js')
        .sort();

    console.log(`📄 Found ${files.length} manager files`);

    // Extract functions from each file
    const allFunctions = {};
    let totalFunctions = 0;

    for (const file of files) {
        const filePath = path.join(MANAGERS_DIR, file);
        const managerKey = file.replace('.js', '');
        const functions = extractFunctions(filePath);

        allFunctions[managerKey] = functions;
        totalFunctions += functions.length;

        console.log(`  ✓ ${file}: ${functions.length} functions (${functions.filter(f => f.isExported).length} exported)`);
    }

    console.log(`\n📊 Total functions found: ${totalFunctions}`);
    console.log('📝 Generating markdown...');

    // Generate markdown
    let markdown = '# PDF Viewer Manager Helper Functions Index\n\n';
    markdown += '**Automatically Generated Quick Reference Guide**\n';
    markdown += `**Last Updated:** ${new Date().toISOString().split('T')[0]}\n`;
    markdown += `**Total Functions:** ${totalFunctions}\n`;
    markdown += `**Managers:** ${files.length}\n\n`;
    markdown += '> 💡 This file is auto-generated. Run `node generate-helpers-index.js` to update.\n\n';
    markdown += '---\n\n';

    // Add table of contents
    markdown += generateTOC(Object.keys(allFunctions));

    // Add each manager section
    for (const [managerKey, functions] of Object.entries(allFunctions)) {
        markdown += generateManagerSection(managerKey, functions);
    }

    // Add footer
    markdown += '\n---\n\n';
    markdown += '## 🔧 Common Patterns\n\n';
    markdown += '### Name Lookup Helpers\n\n';
    markdown += 'Multiple managers implement these lookup helpers:\n\n';
    markdown += '```javascript\n';
    markdown += '// Pattern: Get entity name by ID\n';
    markdown += 'getRoomNameById(roomId, state)\n';
    markdown += 'getLocationNameById(locationId, state)\n';
    markdown += 'getCabinetRunNameById(cabinetRunId, state)\n';
    markdown += '```\n\n';
    markdown += '**Found in:** annotation-manager, drawing-system, isolation-mode-manager, tree-manager, filter-system\n\n';
    markdown += '---\n\n';
    markdown += '**Document Generated By:** Helper Index Generator Script\n';
    markdown += '**Script:** `generate-helpers-index.js`\n';
    markdown += '**Architecture:** Manager Pattern with Functional Exports\n';
    markdown += '**Framework:** Alpine.js v3 + Vite\n';

    // Write to file
    fs.writeFileSync(OUTPUT_FILE, markdown, 'utf-8');

    console.log(`\n✅ Index generated successfully!`);
    console.log(`📄 Output: ${OUTPUT_FILE}`);
    console.log(`\n📖 View the index: cat ${OUTPUT_FILE}`);
}

// Run the script
try {
    main();
} catch (error) {
    console.error('❌ Error generating index:', error.message);
    console.error(error.stack);
    process.exit(1);
}
