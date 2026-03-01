# GNUBoard 5 Frontend Migration Guide

## Overview
This document outlines the steps to develop and preview the modernized frontend for GNUBoard 5.

## Project Structure
- **Root Directory**: Contains the GNUBoard 5 installation.
- **src/**: Source code for the modern frontend (SCSS, JS modules).
- **theme/preview_modern/**: The new theme directory that consumes the built assets.
- **preview/**: A separate entry point for viewing the modern theme without affecting the live site.
- **vite.config.js**: Configuration for the build system.

## How to Develop

### 1. Install Dependencies
```bash
npm install
```

### 2. Start Development Server
```bash
npm run dev
```
This will start the Vite development server. Note that for PHP integration in dev mode, you might need to adjust `theme/preview_modern/head.sub.php` to point to the localhost URL (currently configured for production build).

### 3. Build for Preview
```bash
npm run build
```
This compiles the assets from `src/` into `theme/preview_modern/dist/`.

## How to Preview

1.  **Build the assets** using `npm run build`.
2.  **Access the preview site**:
    Navigate to `http://your-domain.com/preview/`
    
    This entry point forces the theme to be `preview_modern`, allowing you to test the new design while the main site (`/index.php`) continues to serve the default theme.

## Backend Integration
The `preview/index.php` file includes the original backend logic (`../index.php`), ensuring that all data, sessions, and board functionality remain active and consistent with the live site.

## Next Steps
- Migrate jQuery scripts to Vanilla JS in `src/scripts/`.
- Modularize CSS files into `src/styles/`.
- Use `theme/preview_modern/` PHP files to modify HTML structure as needed.
