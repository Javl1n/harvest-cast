import sharp from 'sharp';
import toIco from 'to-ico';
import { readFileSync, writeFileSync } from 'fs';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

const publicDir = join(__dirname, '..', 'public');
const svgPath = join(publicDir, 'favicon.svg');
const icoPath = join(publicDir, 'favicon.ico');
const appleTouchPath = join(publicDir, 'apple-touch-icon.png');

async function generateFavicon() {
  try {
    const svgBuffer = readFileSync(svgPath);

    // Generate PNG sizes for ICO (16x16, 32x32, 48x48)
    const sizes = [16, 32, 48];
    const pngBuffers = await Promise.all(
      sizes.map(size =>
        sharp(svgBuffer)
          .resize(size, size)
          .png()
          .toBuffer()
      )
    );

    // Generate ICO file
    const icoBuffer = await toIco(pngBuffers);
    writeFileSync(icoPath, icoBuffer);
    console.log('✓ Generated favicon.ico');

    // Generate Apple Touch Icon (180x180)
    await sharp(svgBuffer)
      .resize(180, 180)
      .png()
      .toFile(appleTouchPath);
    console.log('✓ Generated apple-touch-icon.png');

    console.log('\nFavicon generation complete!');
  } catch (error) {
    console.error('Error generating favicon:', error);
    process.exit(1);
  }
}

generateFavicon();
