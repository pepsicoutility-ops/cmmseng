# PWA Icons Placeholder

This directory should contain the following PWA icons:

## Required Icons

1. **pwa-icon-192.png** (192x192 pixels)
   - Standard PWA icon
   - Used for app installation and shortcuts

2. **pwa-icon-512.png** (512x512 pixels)
   - High-resolution PWA icon
   - Used for splash screens and app stores

3. **badge-icon.png** (96x96 pixels)
   - Notification badge icon
   - Monochrome icon for Android notifications

4. **shortcut-wo.png** (96x96 pixels)
   - Shortcut icon for "Create Work Order"
   - Used in PWA shortcuts menu

5. **screenshot-mobile.png** (390x844 pixels)
   - App screenshot for PWA store listing
   - Portrait orientation mobile screenshot

## How to Create Icons

### Option 1: Use PepsiCo Logo
Copy your existing PepsiCo logo and resize to required dimensions:
- pepsico-logo.png → resize to 192x192, 512x512

### Option 2: Generate from Existing Logo
```bash
# Install ImageMagick (if not already installed)
# Then resize your logo:

convert pepsico-logo.png -resize 192x192 pwa-icon-192.png
convert pepsico-logo.png -resize 512x512 pwa-icon-512.png
convert pepsico-logo.png -resize 96x96 badge-icon.png
convert pepsico-logo.png -resize 96x96 shortcut-wo.png
```

### Option 3: Use Online PWA Icon Generator
1. Visit: https://www.pwabuilder.com/imageGenerator
2. Upload PepsiCo logo
3. Download generated icon pack
4. Extract to this directory

## Current Status

⚠️ **Placeholder icons needed** - Replace these with actual PepsiCo branding

For now, you can:
1. Copy `public/images/pepsico-logo.png` (if exists) multiple times
2. Rename to match required filenames above
3. PWA will work with any square PNG images

The app will function without these icons, but installation prompts may show generic icons until proper icons are added.
