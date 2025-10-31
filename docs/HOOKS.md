# Filters and Actions Reference

This document provides a comprehensive reference for all WordPress filters and actions available in the Sync Content From Notion.
These hooks allow developers to extend and customize the plugin's functionality.

## Table of Contents

- [Filters](#filters)
  - [Capabilities](#capabilities-filters)
  - [API Client](#api-client-filters)
  - [Block Conversion](#block-conversion-filters)
  - [Import Process](#import-process-filters)
- [Actions](#actions)
  - [Import Hooks](#import-hooks)
- [Usage Examples](#usage-examples)

---

## Filters

### Capabilities Filters

#### `sync_content_from_notion_update_role_capabilities`

Filters the roles before updating capabilities.

**Since:** 1.0.0

**Parameters:**
- `$new_roles` (array) - Array of role names that should have the capability.

**Location:** `includes/admin/class-capabilities.php`

**Example:**
```php
add_filter( 'sync_content_from_notion_update_role_capabilities', function( $new_roles ) {
    // Always include 'editor' role
    if ( ! in_array( 'editor', $new_roles, true ) ) {
        $new_roles[] = 'editor';
    }
    return $new_roles;
} );
```

---

### API Client Filters

#### `sync_content_from_notion_notion_api_version`

Filters the Notion API version used for requests.

**Since:** 1.0.0

**Parameters:**
- `$version` (string) - The Notion API version (default: `2022-06-28`).

**Location:** `includes/api-client/class-notion-client.php`

**Example:**
```php
add_filter( 'sync_content_from_notion_notion_api_version', function( $version ) {
    // Use a different API version
    return '2023-01-15';
} );
```

---

### Block Conversion Filters

#### `sync_content_from_notion_converted_block`

Filters the converted block HTML output.

**Since:** 1.0.0

**Parameters:**
- `$html` (string) - Converted HTML output.
- `$block` (array) - Notion block data.
- `$converter` (Block_Converter_Interface) - The converter instance used.
- `$context` (array) - Conversion context.

**Location:** `includes/blocks/class-block-registry.php`

**Example:**
```php
add_filter( 'sync_content_from_notion_converted_block', function( $html, $block, $converter, $context ) {
    // Add custom wrapper to all paragraph blocks
    if ( $block['type'] === 'paragraph' ) {
        $html = '<div class="custom-paragraph">' . $html . '</div>';
    }
    return $html;
}, 10, 4 );
```

---

#### `sync_content_from_notion_unsupported_block_output`

Filters the output for unsupported Notion block types.

**Since:** 1.0.0

**Parameters:**
- `$output` (string) - The default output HTML (comment by default).
- `$block` (array) - The Notion block data.
- `$type` (string) - The block type.
- `$context` (array) - The conversion context.

**Location:** `includes/blocks/class-block-registry.php`

**Example:**
```php
add_filter( 'sync_content_from_notion_unsupported_block_output', function( $output, $block, $type, $context ) {
    // Provide custom fallback for unsupported blocks
    return sprintf(
        '<div class="notion-unsupported-block" data-type="%s">
            <p>This block type (%s) is not yet supported.</p>
        </div>',
        esc_attr( $type ),
        esc_html( $type )
    );
}, 10, 4 );
```

---

#### `sync_content_from_notion_groupable_list_items`

Filters the list of groupable block types for list consolidation.

**Since:** 1.0.0

**Parameters:**
- `$groupable_items` (array) - Array of block types that should be grouped together (default: `['bulleted_list_item', 'numbered_list_item', 'to_do']`).

**Location:** `includes/blocks/class-block-registry.php`

**Example:**
```php
add_filter( 'sync_content_from_notion_groupable_list_items', function( $groupable_items ) {
    // Add custom list type to grouping logic
    $groupable_items[] = 'custom_list_item';
    return $groupable_items;
} );
```

---

### Import Process Filters

#### `sync_content_from_notion_page_data`

Filters the Notion page data before processing.

**Since:** 1.0.0

**Parameters:**
- `$page` (array) - Notion page data from API.
- `$page_id` (string) - Notion page ID.

**Location:** `includes/importer/class-importer-controller.php`

**Example:**
```php
add_filter( 'sync_content_from_notion_page_data', function( $page, $page_id ) {
    // Modify page properties before import
    if ( isset( $page['properties']['Status'] ) ) {
        // Convert status to custom format
        $page['properties']['Status']['custom'] = true;
    }
    return $page;
}, 10, 2 );
```

---

#### `sync_content_from_notion_page_blocks`

Filters the Notion blocks before conversion to WordPress content.

**Since:** 1.0.0

**Parameters:**
- `$blocks` (array) - Array of Notion blocks.
- `$page_id` (string) - Notion page ID.
- `$page` (array) - Notion page data.

**Location:** `includes/importer/class-importer-controller.php`

**Example:**
```php
add_filter( 'sync_content_from_notion_page_blocks', function( $blocks, $page_id, $page ) {
    // Filter out certain block types
    return array_filter( $blocks, function( $block ) {
        return $block['type'] !== 'divider';
    } );
}, 10, 3 );
```

---

## Actions

### Import Hooks

#### `sync_content_from_notion_after_import_page`

Fires after successfully importing a Notion page.

**Since:** 1.0.0

**Parameters:**
- `$post_id` (int) - WordPress post ID of the imported page.
- `$page_id` (string) - Notion page ID.
- `$page` (array) - Notion page data.
- `$blocks` (array) - Notion blocks array.

**Location:** `includes/importer/class-importer-controller.php`

**Example:**
```php
add_action( 'sync_content_from_notion_after_import_page', function( $post_id, $page_id, $page, $blocks ) {
    // Send notification after import
    wp_mail(
        get_option( 'admin_email' ),
        'New Page Imported',
        sprintf( 'Page "%s" has been imported as post #%d', get_the_title( $post_id ), $post_id )
    );

    // Add custom post meta
    update_post_meta( $post_id, '_notion_block_count', count( $blocks ) );
}, 10, 4 );
```



## Best Practices

### Filter Priority

- Use priority `10` (default) for most filters
- Use priority `5` for filters that should run early
- Use priority `15` or higher for filters that should run after other modifications

---