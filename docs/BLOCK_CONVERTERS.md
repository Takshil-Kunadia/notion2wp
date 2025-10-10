# Block Converter System

## Overview

The Notion2WP plugin uses a modular block converter system to transform Notion blocks into WordPress Gutenberg blocks. This architecture provides flexibility, maintainability, and extensibility.

## Architecture

### Components

1. **Block_Converter_Interface** (`interface-block-converter.php`)
   - Defines the contract for all block converters
   - Methods: `supports()`, `convert()`, `get_priority()`

2. **Abstract_Block_Converter** (`abstract-block-converter.php`)
   - Base class with common functionality
   - Provides utility methods for rich text conversion, color handling, and child block processing
   - All converters extend this class

3. **Block_Registry** (`class-block-registry.php`)
   - Singleton that manages all block converters
   - Handles converter registration and priority sorting
   - Routes blocks to the appropriate converter

4. **Individual Converters** (`converters/`)
   - Each Notion block type has its own converter class
   - Implements the conversion logic for that specific block type

## Block Converters

### Currently Implemented

| Notion Block Type | Converter Class | Gutenberg Block |
|-------------------|----------------|-----------------|
| paragraph | `Paragraph_Converter` | `core/paragraph` |
| heading_1/2/3 | `Heading_Converter` | `core/heading` |
| bulleted_list_item | `List_Converter` | `core/list` |
| numbered_list_item | `List_Converter` | `core/list` |
| quote | `Quote_Converter` | `core/quote` |
| code | `Code_Converter` | `core/code` |
| image | `Image_Converter` | `core/image` |
| divider | `Divider_Converter` | `core/separator` |
| callout | `Callout_Converter` | `core/group` |
| toggle | `Toggle_Converter` | `core/details` |
| to_do | `Todo_Converter` | `core/list` |
| table | `Table_Converter` | `core/table` |
| bookmark | `Bookmark_Converter` | `core/embed` |
| embed | `Embed_Converter` | `core/embed` |
| file | `File_Converter` | `core/file` |
| video | `Video_Converter` | `core/video` or `core/embed` |
| audio | `Audio_Converter` | `core/audio` |
| (unsupported) | `Unsupported_Converter` | HTML comment |

## How It Works

### 1. Registration

When the `Block_Registry` is instantiated, it automatically registers all converters:

```php
$registry = Block_Registry::get_instance();
// All converters are now registered
```

### 2. Conversion Flow

```
Notion Block
    ↓
Block_Registry::convert_block()
    ↓
Find matching converter (highest priority first)
    ↓
Converter::supports() - Check if converter handles this block
    ↓
Converter::convert() - Transform to Gutenberg format
    ↓
Process children recursively (if any)
    ↓
Gutenberg Block HTML
```

### 3. Nested Blocks

Nested blocks (children) are handled automatically:

```php
protected function process_children( $children, $context = [] ) {
    $registry = Block_Registry::get_instance();
    $html = '';

    foreach ( $children as $child_block ) {
        $html .= $registry->convert_block( $child_block, $context );
    }

    return $html;
}
```

## Creating a New Converter

### Step 1: Create the Converter Class

Create a new file in `includes/blocks/converters/`:

```php
<?php
namespace Notion2WP\Blocks\Converters;

use Notion2WP\Blocks\Abstract_Block_Converter;

defined( 'ABSPATH' ) || exit;

class My_Block_Converter extends Abstract_Block_Converter {

    public function supports( $block ) {
        return isset( $block['type'] ) && 'my_block_type' === $block['type'];
    }

    public function convert( $block, $context = [] ) {
        $block_data = $block['my_block_type'] ?? [];
        $rich_text  = $block_data['rich_text'] ?? [];

        // Convert rich text to HTML
        $content = $this->rich_text_to_html( $rich_text );

        // Build HTML
        $html = '<div>' . $content . '</div>';

        // Process children if present
        if ( ! empty( $block['children'] ) ) {
            $html .= $this->process_children( $block['children'], $context );
        }

        // Wrap in Gutenberg block format
        return $this->wrap_gutenberg_block( 'core/my-block', $html );
    }
}
```

### Step 2: Register the Converter

Add to `Block_Registry::register_default_converters()`:

```php
require_once __DIR__ . '/converters/class-my-block-converter.php';
$this->register( new Converters\My_Block_Converter() );
```

## Utility Methods

### Rich Text Conversion

```php
// Get plain text only
$text = $this->extract_plain_text( $rich_text );

// Get HTML with formatting (bold, italic, links, etc.)
$html = $this->rich_text_to_html( $rich_text );
```

### Color Handling

```php
$color_class = $this->get_color_class( 'blue' );
// Returns: 'has-blue-color'
```


### Color Mapping
```php
$wp_color =  $this->map_color( 'blue_background' );
// Returns: '#CCE4F9'
```

### Gutenberg Block Wrapping

```php
$html = $this->wrap_gutenberg_block(
    'core/paragraph',
    '<p>Content</p>',
    [ 'className' => 'my-class' ]
);
// Returns: <!-- wp:core/paragraph {"className":"my-class"} -->
// <p>Content</p>
// <!-- /wp:core/paragraph -->
```

## Block Types with Children

The following Notion block types support child blocks:

- Bulleted list item
- Callout
- Heading (when `is_toggleable` = true)
- Numbered list item
- Paragraph
- Quote
- Toggle
- To do
- Table

Always check for `$block['children']` and process them recursively.

### Special Case: List Grouping

**Problem:** Notion returns each list item as a separate block, but Gutenberg expects consecutive list items to be grouped in a single list block.

**Solution:** The `Block_Registry::group_list_items()` method automatically groups consecutive list items before conversion.

#### How List Grouping Works

1. **Detection Phase** - `Block_Registry::convert_blocks()` calls `group_list_items()` to scan for consecutive list items

2. **Grouping Phase** - Consecutive items of the same type are collected:
   ```php
   // Input: Individual Notion blocks
   [
       { "type": "bulleted_list_item", "bulleted_list_item": {...} },
       { "type": "bulleted_list_item", "bulleted_list_item": {...} },
       { "type": "bulleted_list_item", "bulleted_list_item": {...} }
   ]
   
   // Output: Grouped list block
   {
       "type": "bulleted_list_item",
       "is_grouped": true,
       "list_items": [
           { "type": "bulleted_list_item", ... },
           { "type": "bulleted_list_item", ... },
           { "type": "bulleted_list_item", ... }
       ]
   }
   ```

3. **Conversion Phase** - `List_Converter` detects grouped lists and creates a single `<ul>` or `<ol>` with multiple `<li>` elements:
   ```html
   <!-- Gutenberg: core/list -->
   <ul>
       <li>First item</li>
       <li>Second item</li>
       <li>Third item</li>
   </ul>
   ```

4. **Nested Lists** - Individual list items with children are handled separately (not grouped), allowing for proper nesting:
   ```html
   <ul>
       <li>Parent item
           <ul>
               <li>Nested item 1</li>
               <li>Nested item 2</li>
           </ul>
       </li>
   </ul>
   ```

#### Example: Complex List Structure

**Notion API Response:**
```json
[
    { "type": "bulleted_list_item", "bulleted_list_item": { "rich_text": [{"text": {"content": "Item 1"}}] } },
    { "type": "bulleted_list_item", "bulleted_list_item": { "rich_text": [{"text": {"content": "Item 2"}}], "children": [...] } },
    { "type": "bulleted_list_item", "bulleted_list_item": { "rich_text": [{"text": {"content": "Item 3"}}] } },
    { "type": "paragraph", "paragraph": { "rich_text": [...] } },
    { "type": "numbered_list_item", "numbered_list_item": { "rich_text": [{"text": {"content": "Numbered 1"}}] } },
    { "type": "numbered_list_item", "numbered_list_item": { "rich_text": [{"text": {"content": "Numbered 2"}}] } }
]
```

**Converted Output:**
```html
<!-- core/list (bulleted) -->
<ul>
    <li>Item 1</li>
    <li>Item 2
        <!-- Nested children here -->
    </li>
    <li>Item 3</li>
</ul>
<!-- /core/list -->

<!-- core/paragraph -->
<p>Paragraph text</p>
<!-- /core/paragraph -->

<!-- core/list (numbered) -->
<ol>
    <li>Numbered 1</li>
    <li>Numbered 2</li>
</ol>
<!-- /core/list -->
```

## Testing a Converter

1. Create a test Notion page with the specific block type
2. Use the Import UI to import the page
3. Verify the WordPress post contains the correct Gutenberg blocks
4. Check the post in the block editor to ensure proper rendering

## Best Practices

1. **Keep converters simple** - Each converter should handle one block type
2. **Use utility methods** - Leverage the base class methods for common tasks
3. **Handle edge cases** - Check for empty content, missing properties, etc.
4. **Preserve formatting** - Use `rich_text_to_html()` to maintain text styles
5. **Process children** - Always handle nested blocks when supported
6. **Escape output** - Use WordPress escaping functions (`esc_html()`, `esc_url()`, etc.)
7. **Add attributes** - Include relevant Gutenberg block attributes for better editing

## Future Enhancements

Potential improvements to the block converter system:

1. **Media Download** - Automatically download and import images/files to WordPress media library
2. **Advanced Block Mapping** - Map Notion blocks to custom Gutenberg blocks
3. **Conversion Settings** - Allow users to configure conversion preferences
4. **Block Caching** - Cache converted blocks for performance
5. **Conversion Hooks** - Add filters/actions for customization
6. **Column Layouts** - Support for Notion's column_list and column blocks
7. **Synced Blocks** - Handle Notion's synced block feature
8. **Equation Blocks** - Support for mathematical equations (KaTeX)
9. **PDF Blocks** - Embed PDF files
10. **Breadcrumb Blocks** - Navigation breadcrumbs

## Troubleshooting

### Block not converting

1. Check if converter is registered in `Block_Registry`
2. Verify `supports()` method returns true for the block
3. Add error logging in `convert()` method
4. Check for PHP errors in debug log

### Children not rendering

1. Ensure `process_children()` is called
2. Verify `has_children` property is true in Notion block
3. Check if child blocks are fetched by `Notion_Client`

### Formatting lost

1. Use `rich_text_to_html()` instead of `extract_plain_text()`
2. Check annotation handling in base class
3. Verify escaping isn't stripping HTML tags
