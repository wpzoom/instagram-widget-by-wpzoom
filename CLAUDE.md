# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Build and Asset Management
- `npm run build` - Build production assets using webpack
- `npm run start` - Start development server with watch mode
- `npm run format` - Format code using WordPress standards

### Linting and Quality
- `npm run lint:css` - Lint CSS/SCSS files
- `npm run lint:js` - Lint JavaScript files
- `npm run lint:pkg-json` - Lint package.json
- `composer run phpcs` - Check PHP code standards (WordPress coding standards)
- `composer run phpcbf` - Fix PHP code standards automatically

### Testing
- `npm run test:unit` - Run JavaScript unit tests
- `npm run test:e2e` - Run end-to-end tests

## Architecture Overview

### Core Plugin Structure
This is a WordPress plugin for displaying Instagram feeds with both widget and Gutenberg block support.

**Main Entry Point**: `instagram-widget-by-wpzoom.php` - Plugin bootstrap file that loads all classes and registers the widget.

**Key Classes**:
- `Wpzoom_Instagram_Widget` - Legacy widget implementation (extends WP_Widget)
- `Wpzoom_Instagram_Block` - Gutenberg block implementation
- `Wpzoom_Instagram_Widget_API` - Handles Instagram API integration
- `Wpzoom_Instagram_Widget_Display` - Renders frontend display logic
- `Wpzoom_Instagram_Widget_Settings` - Admin settings management
- `Wpzoom_Instagram_General_Settings` - General plugin settings
- `Wpzoom_Instagram_Assets` - Asset management and enqueuing
- `Wpzoom_Instagram_Image_Uploader` - Image upload functionality
- `Wpzoom_Instagram_Email_Notification` - Email notification system

### Asset Pipeline
- **Source**: `src/` directory contains all source files
- **Build**: `dist/` directory contains compiled assets
- **Webpack Configuration**: Custom webpack config extending WordPress scripts
- **SCSS**: Uses Sass with glob imports for styling
- **JavaScript**: Separate builds for frontend/backend functionality

### Frontend/Backend Split
- **Frontend**: `src/scripts/frontend/` and `src/styles/frontend/`
- **Backend**: `src/scripts/backend/` and `src/styles/backend/`
- **Block Editor**: Separate JavaScript builds for Gutenberg blocks
- **Library Assets**: Third-party libraries in `src/scripts/library/` and `src/styles/library/`

### Elementor Integration
- **Location**: `elementor/` directory
- **Widget**: Custom Elementor widget for Instagram feed
- **Styling**: Dedicated CSS for Elementor integration

### Dependencies
- **PHP**: Uses DiDOM library for HTML parsing (via Composer)
- **JavaScript**: WordPress scripts and icons, jQuery types
- **Development**: WordPress coding standards, PHP CodeSniffer

### File Organization
- **Templates**: `templates/admin/` for admin interface templates
- **Languages**: `languages/` for internationalization
- **Assets**: `assets/backend/` for backend-specific assets
- **Vendor**: `vendor/` for Composer dependencies

## Development Notes

### Build Process
The webpack configuration creates separate bundles for different contexts:
- Frontend index, block, and preview scripts
- Backend index, block, and cron-dismiss scripts
- Corresponding CSS files with RTL support
- Copies images and library assets to dist directory

### WordPress Integration
- Follows WordPress coding standards (checked via PHPCS)
- Uses WordPress hooks and actions throughout
- Integrates with WordPress widget system and Gutenberg blocks
- Supports WordPress internationalization

### API Integration
The plugin connects to Instagram's API to fetch feed data. The API handling is abstracted into the `Wpzoom_Instagram_Widget_API` class.