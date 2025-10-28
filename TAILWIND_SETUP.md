# Tailwind CSS Installation Guide

## Installation Complete! ✅

Tailwind CSS v3 has been successfully installed in your project.

## Files Created:
- `tailwind.config.js` - Tailwind configuration
- `assets/css/input.css` - Source CSS file with Tailwind directives
- `assets/css/output.css` - Compiled CSS (generated automatically)
- `package.json` - Updated with build scripts

## Available NPM Commands:

### Build CSS (for production):
```bash
npm run build:css
```
This compiles and minifies your Tailwind CSS.

### Watch CSS (for development):
```bash
npm run watch:css
```
or
```bash
npm run dev
```
This automatically rebuilds CSS when you make changes.

## Usage in Your PHP Files:

Replace the CDN link with the compiled CSS:
```html
<link rel="stylesheet" href="../assets/css/output.css">
```

## Custom Tailwind Classes Available:

The `input.css` file includes custom component classes:
- `.btn-primary` - Primary button style
- `.input-field` - Form input style
- `.card` - Card container
- `.alert`, `.alert-error`, `.alert-success`, `.alert-warning`, `.alert-info` - Alert messages

## Customization:

### Update Colors:
Edit `tailwind.config.js` to change primary/secondary colors.

### Add Custom Styles:
Edit `assets/css/input.css` and run `npm run build:css`.

## Development Workflow:

1. Run `npm run dev` in terminal (keeps watching for changes)
2. Edit your PHP files with Tailwind classes
3. Edit `assets/css/input.css` for custom styles
4. Changes automatically compile!

## Production:

Before deploying, run:
```bash
npm run build:css
```

This creates an optimized, minified CSS file.

## Note:
- `output.css` is in `.gitignore` (will be built on deployment)
- Remember to run `npm install` after cloning the repo
- Commit `input.css` and `tailwind.config.js` to version control
