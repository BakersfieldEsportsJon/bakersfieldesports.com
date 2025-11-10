#!/usr/bin/env node

/**
 * CSS Build Script
 * Compiles and minifies CSS files for production
 *
 * Usage: node build-css.js
 */

const fs = require('fs');
const path = require('path');

// Simple CSS minifier (removes comments, whitespace, etc.)
function minifyCSS(css) {
    return css
        // Remove comments
        .replace(/\/\*[\s\S]*?\*\//g, '')
        // Remove whitespace around special characters
        .replace(/\s*([{}:;,>+~])\s*/g, '$1')
        // Remove extra whitespace
        .replace(/\s+/g, ' ')
        // Remove whitespace at start and end
        .trim();
}

// Resolve @import statements
function resolveImports(cssContent, basePath) {
    const importRegex = /@import\s+url\(['"]?([^'"]+)['"]?\);?/g;
    let match;
    let result = cssContent;

    while ((match = importRegex.exec(cssContent)) !== null) {
        const importPath = match[1];
        const fullPath = path.join(basePath, importPath);

        if (fs.existsSync(fullPath)) {
            const importedContent = fs.readFileSync(fullPath, 'utf8');
            // Recursively resolve imports in the imported file
            const resolvedContent = resolveImports(importedContent, path.dirname(fullPath));
            result = result.replace(match[0], resolvedContent);
        } else {
            console.warn(`Warning: Could not find imported file: ${fullPath}`);
        }
    }

    return result;
}

// Main build function
function buildCSS() {
    const cssDir = path.join(__dirname, 'css');
    const mainCSSPath = path.join(cssDir, 'main.css');
    const outputPath = path.join(cssDir, 'optimized.min.css');

    console.log('Building CSS...');

    try {
        // Read main.css
        let mainCSS = fs.readFileSync(mainCSSPath, 'utf8');

        // Resolve all @import statements
        console.log('Resolving imports...');
        mainCSS = resolveImports(mainCSS, cssDir);

        // Minify
        console.log('Minifying...');
        const minified = minifyCSS(mainCSS);

        // Write output
        fs.writeFileSync(outputPath, minified, 'utf8');

        const originalSize = Buffer.byteLength(mainCSS, 'utf8');
        const minifiedSize = Buffer.byteLength(minified, 'utf8');
        const savings = ((1 - (minifiedSize / originalSize)) * 100).toFixed(2);

        console.log('\n✓ CSS build complete!');
        console.log(`  Original size: ${(originalSize / 1024).toFixed(2)} KB`);
        console.log(`  Minified size: ${(minifiedSize / 1024).toFixed(2)} KB`);
        console.log(`  Savings: ${savings}%`);
        console.log(`  Output: ${outputPath}\n`);

    } catch (error) {
        console.error('Error building CSS:', error);
        process.exit(1);
    }
}

// Run build
buildCSS();
