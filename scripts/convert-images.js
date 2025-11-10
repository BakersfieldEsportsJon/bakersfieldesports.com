const sharp = require('sharp');
const fs = require('fs').promises;
const path = require('path');

const imageExtensions = ['.jpg', '.jpeg', '.png', '.gif'];

async function convertToWebP(filePath) {
    try {
        const ext = path.extname(filePath);
        if (!imageExtensions.includes(ext.toLowerCase())) {
            return;
        }

        const webpPath = filePath.replace(ext, '.webp');
        
        // Skip if WebP version already exists
        try {
            await fs.access(webpPath);
            console.log(`WebP already exists for ${filePath}`);
            return;
        } catch (err) {
            // File doesn't exist, proceed with conversion
        }

        await sharp(filePath)
            .webp({ quality: 80 })
            .toFile(webpPath);

        // Also optimize the original
        const optimizedOriginal = await sharp(filePath)
            .jpeg({ quality: 85, progressive: true })
            .png({ quality: 85, progressive: true })
            .toBuffer();

        await fs.writeFile(filePath, optimizedOriginal);
        
        console.log(`Converted and optimized: ${filePath}`);
    } catch (error) {
        console.error(`Error processing ${filePath}:`, error);
    }
}

async function processDirectory(directory) {
    try {
        const entries = await fs.readdir(directory, { withFileTypes: true });

        for (const entry of entries) {
            const fullPath = path.join(directory, entry.name);
            
            if (entry.isDirectory()) {
                await processDirectory(fullPath);
            } else {
                await convertToWebP(fullPath);
            }
        }
    } catch (error) {
        console.error(`Error processing directory ${directory}:`, error);
    }
}

// Start processing from the images directory
const imagesDir = path.join(__dirname, '..', 'images');
processDirectory(imagesDir)
    .then(() => console.log('Image conversion complete'))
    .catch(console.error);
