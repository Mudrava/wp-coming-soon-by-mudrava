# Coming Soon by Mudrava

A lightweight, developer-friendly WordPress plugin for displaying a customizable Coming Soon / Maintenance Mode page.

## Features

- **Block Editor Integration** — Edit your Coming Soon page using the full Gutenberg block editor
- **Countdown Timer Block** — Built-in `mudrava/countdown` block with configurable target date, units, labels, and expired message
- **Background Customization** — Color, image (with size/position controls), blur effect, and color overlay
- **SEO Friendly** — Proper 503 status code, Retry-After header, customizable page title and meta description
- **Access Control** — Bypass by user role, IP whitelist, or shareable preview link
- **Launch Date** — Optional auto-deactivation when the launch date passes
- **Social Links** — Configurable social media links with custom labels
- **Responsive** — Mobile-first design with `clamp()` typography
- **Theme Override** — Place `mudrava-coming-soon/coming-soon.php` in your theme to customize the template
- **Auto-Updates** — Automatic updates from GitHub Releases
- **Developer Hooks** — Filters for template path, access control, and more

## Requirements

- WordPress 6.2+
- PHP 8.0+

## Installation

1. Download the latest release ZIP from the [Releases page](https://github.com/Mudrava/wp-coming-soon-by-mudrava/releases)
2. Upload via **Plugins → Add New → Upload Plugin**
3. Activate the plugin
4. Navigate to **Coming Soon** in the admin menu

## Usage

### Basic Setup

1. Go to **Coming Soon** in the WordPress admin sidebar
2. Toggle **Enable Coming Soon Mode**
3. Configure your page title and meta description in the **SEO** section
4. Click **Save Settings**
5. Click **Edit Page Content** to customize the Coming Soon page in the block editor

### Countdown Timer

Insert the **Countdown Timer** block in the block editor:

- Set a target date via the block inspector
- Toggle individual units (days, hours, minutes, seconds)
- Customize labels for each unit
- Set an expired message

### Background

In **Coming Soon → Advanced → Background**:

- Set a background color
- Upload a background image
- Configure image size (cover/contain/auto) and position (9 presets)
- Add blur effect (0–20px)
- Add color overlay with adjustable opacity

### Access Control

In **Coming Soon → Access**:

- **Bypass Roles**: Choose which logged-in user roles can see the live site
- **IP Whitelist**: Add IP addresses or CIDR ranges that bypass the Coming Soon page
- **Preview Link**: Share a tokenized URL for external review

### Launch Date

In **Coming Soon → General → Launch Date**:

- Toggle "Use Launch Date" on/off
- When enabled, set a date and Coming Soon mode auto-deactivates after it passes

## Developer Reference

### Filters

```php
// Override the access decision
add_filter('mudrava_coming_soon_access', function (bool $show, array $settings): bool {
    // Custom logic here
    return $show;
}, 10, 2);

// Override the template file
add_filter('mudrava_coming_soon_template', function (string $template, array $vars): string {
    return $template;
}, 10, 2);
```

### Theme Template Override

Copy `templates/coming-soon.php` to your theme at:

```
your-theme/mudrava-coming-soon/coming-soon.php
```

### CSS Custom Properties

The template uses these CSS custom properties:

| Property              | Description              |
| --------------------- | ------------------------ |
| `--mcs-bg-color`      | Background color         |
| `--mcs-bg-image`      | Background image URL     |
| `--mcs-bg-size`       | Background size          |
| `--mcs-bg-position`   | Background position      |
| `--mcs-overlay`       | Overlay rgba value       |
| `--mcs-bg-blur`       | Backdrop blur amount     |

## Development

### Prerequisites

- Node.js 18+
- npm 9+

### Setup

```bash
npm install
```

### Build

```bash
npm run build
```

### Watch (development)

```bash
npm start
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-or-later

## Credits

Built by [Mudrava](https://mudrava.com/en/).
