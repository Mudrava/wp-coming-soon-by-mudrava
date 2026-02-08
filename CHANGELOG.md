# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-02-08

### Added

- Initial release
- Coming Soon / Maintenance Mode with 503 status and Retry-After header
- Block editor-powered page content via custom post type
- Countdown Timer Gutenberg block (`mudrava/countdown`)
  - Configurable target date, visible units, custom labels, expired message
  - CSS Grid layout with responsive 2-column fallback
  - Vanilla JS frontend hydration
- Settings page (React + @wordpress/components)
  - General tab: enable/disable, SEO, retry-after (hours/days), launch date toggle
  - Access tab: bypass roles, IP whitelist (CIDR support), preview link
  - Advanced tab: background (color, image, size, position, blur, overlay), social links with labels, custom CSS, admin bar toggle
- Background image controls: size, position, blur, overlay colour/opacity
- Social links with platform selection (Facebook, X, Instagram, LinkedIn, YouTube, TikTok, Pinterest, GitHub, Telegram, Custom) and custom labels
- Launch Date with optional toggle — auto-deactivation when date passes
- Responsive typography with `clamp()` for headings and paragraphs
- Credit footer linking to mudrava.com
- Preview token system with secure cookie-based persistence
- Admin bar "Coming Soon: ON" indicator
- Dashboard/plugins admin notice
- Theme template override support
- Auto-updates via GitHub Releases API
- Full i18n support with text domain `wp-coming-soon-by-mudrava`
