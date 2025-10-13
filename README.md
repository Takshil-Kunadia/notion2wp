# Notion → WordPress Plugin

**Transform your Notion workspace into WordPress content seamlessly.**

[![WordPress](https://img.shields.io/badge/WordPress-6.6+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL--2.0-orange.svg)](LICENSE)

Notion2WP connects your Notion workspace with WordPress, allowing you to import pages and databases as WordPress posts with automatic conversion to Gutenberg blocks.

---

## 📋 Table of Contents

- [Description](#description)
- [Requirements](#requirements)
- [Installation](#installation)
- [Getting Started](#getting-started)
- [Features](#features)
- [External Services](#external-services)
- [User Roles & Permissions](#user-roles--permissions)
- [Supported Notion Blocks](#supported-notion-blocks)
- [Developer Documentation](#developer-documentation)
- [Frequently Asked Questions](#frequently-asked-questions)
- [Support](#support)
- [Contributing](#contributing)
- [License](#license)

---

## Description

Notion2WP bridges the gap between Notion's collaborative content creation and WordPress's powerful publishing platform. Content teams can now draft, review, and organize content in Notion, then import it directly into WordPress with a single click.

**What makes Notion2WP special:**

- **No manual copying** - Import entire pages and databases directly from your Notion workspace
- **Smart conversion** - Notion blocks are automatically converted to equivalent Gutenberg blocks
- **Role-based access** - Control which user roles can import content
- **Developer-friendly** - Extensible with custom hooks and filters

**Perfect for:**
- Content teams using Notion for collaboration
- Bloggers who prefer Notion's writing experience
- Agencies managing multiple WordPress sites
- Publishers importing content from external sources

---

## Requirements

Before installing Notion2WP, ensure your environment meets these requirements:

### WordPress Requirements
- **WordPress Version:** 6.6 or higher
- **PHP Version:** 7.4 or higher
- **WordPress Permissions:** User must have administrator access to configure the plugin

### Notion Requirements
- **Notion Account:** A Notion workspace
- **Integration Token:** You'll need to create a Notion Internal Integration (see [Getting Started](#getting-started))

---

## Getting Started

Follow these steps to connect Notion2WP to your Notion workspace:

### Step 1: Create a Notion Internal Integration

1. Visit [Notion Integrations](https://www.notion.so/profile/integrations)
2. Click **"+ New integration"**
3. Choose **"Internal integration"** as the type
4. Give it a name (e.g., "WordPress Import")
5. Select your workspace
6. Under **Capabilities**, enable:
   - ✅ Read content
   - ✅ Read comments (optional)
   - ✅ Read user information without email (optional)
7. Click **Submit** to create the integration
8. Copy the **Internal Integration Token** (starts with `secret_`)

> **⚠️ Important:** Keep your integration token secure. Never share it publicly or commit it to version control.

### Step 2: Connect Notion2WP

1. In WordPress, navigate to **Notion2WP → Settings Tab**
2. Paste your Integration Token
3. Click **Connect to Notion**
4. You should see a success message with connection details

### Step 3: Share Pages with Your Integration

For Notion2WP to access your pages:

1. Select the Notion page or database you want to import
2. Click **Import** in right corner of that item's row.
3. Repeat for all pages/databases you want to import.
4. Alternatively, you can bulk select all the pages/data sources and scroll to the bottom for bulk import.

---

## Features

### 🔐 Secure Authentication
- Uses Notion's official Internal Integration API
- Tokens stored securely in WordPress database
- Easy connection management (connect/disconnect)

### 📥 Flexible Import
- Import individual Notion pages
- Import entire databases with all their pages
- Bulk import multiple items at once

### 🎨 Smart Block Conversion
Notion blocks are automatically converted to WordPress Gutenberg blocks:
- Paragraphs → Paragraph blocks
- Headings (H1-H6) → Heading blocks
- Lists (bulleted/numbered) → List blocks
- Images → Image blocks
- Quotes → Quote blocks
- Code blocks → Code blocks
- And more (see [Supported Blocks](#supported-notion-blocks))

### 👥 Role Management
Administrators can control which user roles have access to:
- View the Notion2WP menu
- Import content from Notion

### 🔧 Developer Features
- Comprehensive WordPress hooks and filters
- Extensible block converter system
- Full documentation for customization

---

## External Services

### Notion API

Notion2WP communicates with the Notion API to fetch your workspace content.

**Service Details:**
- **Service Name:** Notion API
- **Service URL:** `https://api.notion.com/v1/`
- **API Version:** 2025-09-03
- **Purpose:** Retrieve pages, databases, and block content from your Notion workspace

**Data Transmitted:**
- Your Internal Integration Token (for authentication)
- Page/database IDs you request to import

**Data Received:**
- Page metadata (title, properties, timestamps)
- Block content (text, images, formatting)
- Database structure and entries

**Privacy & Security:**
- Communication uses HTTPS (encrypted)
- Only content you explicitly share with your integration is accessible
- No data is sent to any other third-party services
- Your integration token never leaves your WordPress server

- [Notion API Documentation](https://developers.notion.com/)

---

## User Roles & Permissions

### Custom Capability: `manage_notion2wp`

The plugin introduces a custom capability that controls access to import functionality.

**Default Configuration:**
- **Administrator** - Full access (cannot be removed)
- **Other roles** - No access by default

**Managing Permissions:**

Administrators can grant access to additional roles via **Notion2WP → Settings → Role Permissions**:

1. View all available WordPress roles(including custom roles)
2. Check/uncheck roles to grant/revoke access
3. Changes are saved immediately
4. Non-admin users will see only the Import tab (no Settings access)

**Technical Details:**
- Settings and authentication remain admin-only (`manage_options` capability)
- Import functionality uses `manage_notion2wp` capability
- Capabilities are removed on plugin deactivation

---

## Supported Notion Blocks

Notion2WP supports the following Notion block types with automatic conversion:

| Notion Block Type | WordPress Block | Notes |
|-------------------|-----------------|-------|
| Paragraph | `core/paragraph` | Full rich text support |
| Heading 1 | `core/heading` (level 1) | Maintains heading hierarchy |
| Heading 2 | `core/heading` (level 2) | |
| Heading 3 | `core/heading` (level 3) | |
| Bulleted List | `core/list` (unordered) | Nested lists supported |
| Numbered List | `core/list` (ordered) | Nested lists supported |
| To-Do | `core/list` with checkboxes | Checked state preserved |
| Toggle | `core/details` | Collapsible content |
| Quote | `core/quote` | Block quotes with attribution |
| Callout | `core/group` with notice styling | Icon and color preserved |
| Code Block | `core/code` | Language syntax highlighting |
| Image | `core/image` | External/uploaded images |
| Video | `core/embed` | YouTube, Vimeo, etc. |
| File | `core/file` | File attachments |
| Bookmark | `core/embed` | Rich link previews |
| Divider | `core/separator` | Horizontal rules |
| Table | `core/table` | Table structure preserved |

---

## Developer Documentation

### Hooks & Filters

Notion2WP provides extensive hooks for customization. See [`docs/HOOKS.md`](docs/HOOKS.md) for complete documentation.

---

## Support

### Documentation
- [Hooks Reference](docs/HOOKS.md)
- [Block Converters](docs/BLOCK_CONVERTERS.md)

### Getting Help
- **GitHub Issues:** [Report bugs or request features](https://github.com/Takshil-Kunadia/notion2wp/issues)
- **Support Forum:** [WordPress.org Plugin Support](https://wordpress.org/support/plugin/notion2wp/)
- **Website:** [notion2wp.framer.website](https://notion2wp.framer.website/)

## Contributing

We welcome contributions! Here's how you can help:

### Reporting Bugs
- Use GitHub Issues
- Include detailed reproduction steps
- Provide screenshots if applicable

### Suggesting Features
- Open a feature request on GitHub
- Explain the use case
- Consider if it fits the plugin's scope

### Code Contributions
1. Fork the repository
2. Create a feature branch
3. Follow WordPress Coding Standards
4. Write tests for new functionality
5. Submit a pull request

See [`CONTRIBUTING.md`](CONTRIBUTING.md) for detailed guidelines.

---

## License

Notion2WP is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE) file for full license text.

---
