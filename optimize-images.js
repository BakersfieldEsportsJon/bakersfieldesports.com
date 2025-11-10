#!/usr/bin/env node

/**
 * Image Optimization Script
 * Converts images to WebP and AVIF formats for better performance
 *
 * Usage: node optimize-images.js [directory]
 * Example: node optimize-images.js images/
 */

const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

// Configuration
const config = {
    quality: {
        webp: 85,
        avif: 80,
        jpeg: 85,
        png: 90
    },
    sizes: {
        thumbnail: 300,
        small: 640,
        medium: 1024,
        large: 1920,
        original: null // Keep original size
    },
    extensions: ['.jpg', '.jpeg', '.png'],
    skipDirs: ['node_modules', 'vendor', '.git', 'min']
};

/**
 * Get all image files in a directory recursively
 * @param {string} dir - Directory to search
 * @param {Array} fileList - Accumulated file list
 * @returns {Array} List of image files
 */
function getImageFiles(dir, fileList = []) {
    const files = fs.readdirSync(dir);

    files.forEach(file => {
        const filePath = path.join(dir, file);
        const stat = fs.statSync(filePath);

        if (stat.isDirectory()) {
            // Skip certain directories
            if (!config.skipDirs.includes(file)) {
                getImageFiles(filePath, fileList);
            }
        } else {
            const ext = path.extname(file).toLowerCase();
            if (config.extensions.includes(ext)) {
                fileList.push(filePath);
            }
        }
    });

    return fileList;
}

/**
 * Convert image to WebP format
 * @param {string} inputPath - Input image path
 * @param {string} outputPath - Output WebP path
 * @param {number} quality - Quality (0-100)
 */
async function convertToWebP(inputPath, outputPath, quality = 85) {
    try {
        await sharp(inputPath)
            .webp({ quality, effort: 6 })
            .toFile(outputPath);
        return true;
    } catch (error) {
        console.error(`Error converting ${inputPath} to WebP:`, error.message);
        return false;
    }
}

/**
 * Convert image to AVIF format
 * @param {string} inputPath - Input image path
 * @param {string} outputPath - Output AVIF path
 * @param {number} quality - Quality (0-100)
 */
async function convertToAVIF(inputPath, outputPath, quality = 80) {
    try {
        await sharp(inputPath)
            .avif({ quality, effort: 6 })
            .toFile(outputPath);
        return true;
    } catch (error) {
        console.error(`Error converting ${inputPath} to AVIF:`, error.message);
        return false;
    }
}

/**
 * Create responsive image sizes
 * @param {string} inputPath - Input image path
 * @param {string} baseName - Base name for output files
 * @param {string} ext - File extension
 */
async function createResponsiveSizes(inputPath, baseName, ext) {
    const metadata = await sharp(inputPath).metadata();
    const originalWidth = metadata.width;

    for (const [sizeName, width] of Object.entries(config.sizes)) {
        if (width === null || width >= originalWidth) continue;

        const outputPath = baseName + `-${sizeName}` + ext;

        try {
            await sharp(inputPath)
                .resize(width, null, { withoutEnlargement: true })
                .toFile(outputPath);
        } catch (error) {
            console.error(`Error creating ${sizeName} size for ${inputPath}:`, error.message);
        }
    }
}

/**
 * Optimize a single image
 * @param {string} imagePath - Path to image
 */
async function optimizeImage(imagePath) {
    const ext = path.extname(imagePath);
    const baseName = imagePath.slice(0, -ext.length);
    const webpPath = baseName + '.webp';
    const avifPath = baseName + '.avif';

    console.log(`Processing: ${imagePath}`);

    // Check if WebP already exists
    if (!fs.existsSync(webpPath)) {
        const webpSuccess = await convertToWebP(imagePath, webpPath, config.quality.webp);
        if (webpSuccess) {
            const originalSize = fs.statSync(imagePath).size;
            const webpSize = fs.statSync(webpPath).size;
            const savings = ((1 - (webpSize / originalSize)) * 100).toFixed(1);
            console.log(`  ✓ WebP created (${savings}% smaller)`);
        }
    } else {
        console.log(`  - WebP exists, skipping`);
    }

    // Check if AVIF already exists
    if (!fs.existsSync(avifPath)) {
        const avifSuccess = await convertToAVIF(imagePath, avifPath, config.quality.avif);
        if (avifSuccess) {
            const originalSize = fs.statSync(imagePath).size;
            const avifSize = fs.statSync(avifPath).size;
            const savings = ((1 - (avifSize / originalSize)) * 100).toFixed(1);
            console.log(`  ✓ AVIF created (${savings}% smaller)`);
        }
    } else {
        console.log(`  - AVIF exists, skipping`);
    }
}

/**
 * Main optimization function
 * @param {string} targetDir - Directory to optimize
 */
async function optimizeImages(targetDir = 'images') {
    console.log(`\n🖼️  Image Optimization Starting...\n`);
    console.log(`Target directory: ${targetDir}`);
    console.log(`Quality settings: WebP=${config.quality.webp}, AVIF=${config.quality.avif}\n`);

    if (!fs.existsSync(targetDir)) {
        console.error(`❌ Directory not found: ${targetDir}`);
        process.exit(1);
    }

    const imageFiles = getImageFiles(targetDir);
    console.log(`Found ${imageFiles.length} images to process\n`);

    let processed = 0;
    let errors = 0;

    for (const imagePath of imageFiles) {
        try {
            await optimizeImage(imagePath);
            processed++;
        } catch (error) {
            console.error(`❌ Error processing ${imagePath}:`, error.message);
            errors++;
        }
    }

    console.log(`\n✅ Optimization complete!`);
    console.log(`   Processed: ${processed}`);
    console.log(`   Errors: ${errors}`);
    console.log(`\n💡 Tip: Use <picture> elements to serve optimized images:`);
    console.log(`   <picture>`);
    console.log(`     <source srcset="image.avif" type="image/avif">`);
    console.log(`     <source srcset="image.webp" type="image/webp">`);
    console.log(`     <img src="image.jpg" alt="Description">`);
    console.log(`   </picture>\n`);
}

// Run optimization
const targetDir = process.argv[2] || 'images';
optimizeImages(targetDir).catch(error => {
    console.error('Fatal error:', error);
    process.exit(1);
});
