<?php
/**
 * Notion Page Property Handler.
 *
 * Handles extraction and conversion of Notion page properties to WordPress format.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Importer;

defined( 'ABSPATH' ) || exit;

/**
 * Page Property Handler class for managing Notion page properties.
 */
class Page_Property_Handler {

	/**
	 * Extract title from Notion page.
	 *
	 * @param array $page Notion page object.
	 * @return string Page title.
	 */
	public function extract_title( $page ) {
		// For pages with properties.
		if ( 'page' === $page['object'] && ! empty( $page['properties'] ) ) {
			foreach ( $page['properties'] as $property ) {
				if ( isset( $property['type'] ) && 'title' === $property['type'] ) {
					return $this->extract_rich_text( $property['title'] ?? [] );
				}
			}
		}

		// For databases.
		if ( ( 'data_source' === $page['object'] || 'database' === $page['object'] ) && ! empty( $page['title'] ) ) {
			return $this->extract_rich_text( $page['title'] );
		}

		return __( '(Untitled)', 'notion2wp' );
	}

	/**
	 * Extract description from Notion page/database.
	 *
	 * @param array $page Notion page/database object.
	 * @return string Description text.
	 */
	public function extract_description( $page ) {
		if ( ! empty( $page['description'] ) ) {
			return $this->extract_rich_text( $page['description'] );
		}
		return '';
	}

	/**
	 * Extract plain text from Notion rich text array.
	 *
	 * @param array $rich_text Rich text array from Notion.
	 * @return string Plain text.
	 */
	public function extract_rich_text( $rich_text ) {
		if ( ! is_array( $rich_text ) ) {
			return '';
		}

		$text = '';
		foreach ( $rich_text as $text_item ) {
			if ( isset( $text_item['plain_text'] ) ) {
				$text .= $text_item['plain_text'];
			}
		}

		return $text;
	}

	/**
	 * Extract URL from Notion file object.
	 *
	 * Handles external, Notion-hosted, and API-uploaded files.
	 *
	 * @param array $file Notion file object.
	 * @return string|null File URL or null if not found.
	 */
	public static function extract_file_url( $file ) {
		if ( empty( $file['type'] ) ) {
			return null;
		}

		$type = $file['type'];

		switch ( $type ) {
			case 'external':
				// Externally hosted file.
				return $file['external']['url'] ?? null;

			case 'file':
				// Notion-hosted file with temporary URL (valid for 1 hour).
				return $file['file']['url'] ?? null;

			case 'file_upload':
				// File uploaded via API - requires additional handling.
				// For now, return null as this requires additional API call.
				return null;

			default:
				return null;
		}
	}

	/**
	 * Handle page cover and set as WordPress featured image.
	 *
	 * @param int   $post_id WordPress post ID.
	 * @param array $cover Notion cover file object.
	 * @return int|false Attachment ID on success, false on failure.
	 */
	public function handle_cover_image( $post_id, $cover ) {
		if ( empty( $cover ) || ! is_array( $cover ) ) {
			return false;
		}

		$cover_url = $this->extract_file_url( $cover );

		if ( empty( $cover_url ) ) {
			return false;
		}

		// Check if image already exists for this post.
		$existing_attachment_id = get_post_thumbnail_id( $post_id );
		$existing_cover_url     = get_post_meta( $existing_attachment_id, '_notion_cover_url', true );

		// If the same cover URL, don't re-download.
		if ( $existing_attachment_id && $existing_cover_url === $cover_url ) {
			return $existing_attachment_id;
		}

		// Download and attach the image.
		$attachment_id = $this->download_and_attach_media( $cover_url, $post_id, 'cover' );

		if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
			// Store the original Notion cover URL for future comparison.
			update_post_meta( $attachment_id, '_notion_cover_url', $cover_url );
			update_post_meta( $attachment_id, '_notion_media_type', 'cover' );

			// Set as featured image.
			set_post_thumbnail( $post_id, $attachment_id );

			return $attachment_id;
		}

		return false;
	}

	/**
	 * Handle page icon.
	 *
	 * Icons can be emoji or file objects. For now, we store it as post meta.
	 *
	 * @param int   $post_id WordPress post ID.
	 * @param array $icon Notion icon object.
	 * @return bool Success status.
	 */
	public function handle_icon( $post_id, $icon ) {
		if ( empty( $icon ) || ! is_array( $icon ) ) {
			return false;
		}

		$icon_type = $icon['type'] ?? '';

		switch ( $icon_type ) {
			case 'emoji':
				// Store emoji as post meta.
				$emoji = $icon['emoji'] ?? '';
				if ( $emoji ) {
					update_post_meta( $post_id, '_notion_icon_emoji', $emoji );
					update_post_meta( $post_id, '_notion_icon_type', 'emoji' );
					return true;
				}
				break;

			case 'external':
			case 'file':
			case 'file_upload':
				// Store icon URL as post meta.
				$icon_url = $this->extract_file_url( $icon );
				if ( $icon_url ) {
					update_post_meta( $post_id, '_notion_icon_url', $icon_url );
					update_post_meta( $post_id, '_notion_icon_type', 'file' );
					return true;
				}
				break;
		}

		return false;
	}

	/**
	 * Download media from URL and attach to WordPress post.
	 *
	 * @param string $media_url Media URL.
	 * @param int    $post_id WordPress post ID.
	 * @param string $context Context for the media (cover, icon, content).
	 * @return int|\WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public function download_and_attach_media( $media_url, $post_id, $context = 'content' ) {
		// Require WordPress media functions.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download media to temp file.
		$temp_file = download_url( $media_url );

		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Get the file name and extension from the URL.
		$filename = basename( wp_parse_url( $media_url, PHP_URL_PATH ) );

		// Clean filename and ensure it has an extension.
		$filename = $this->sanitize_filename( $filename, $context );

		// Prepare file array.
		$file_array = [
			'name'     => $filename,
			'tmp_name' => $temp_file,
		];

		// Upload to WordPress media library.
		$attachment_id = media_handle_sideload( $file_array, $post_id );

		// Clean up temp file.
		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Store context as meta.
		update_post_meta( $attachment_id, '_notion_import_context', $context );

		return $attachment_id;
	}

	/**
	 * Sanitize and generate filename for media.
	 *
	 * @param string $filename Original filename.
	 * @param string $context Media context.
	 * @return string Sanitized filename.
	 */
	private function sanitize_filename( $filename, $context = 'media' ) {
		// Remove query strings and URL fragments.
		$filename = preg_replace( '/[?#].*/', '', $filename );

		// Check if filename has a valid extension.
		$valid_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'mov', 'pdf' ];
		$has_extension    = false;

		foreach ( $valid_extensions as $ext ) {
			if ( preg_match( '/\.' . $ext . '$/i', $filename ) ) {
				$has_extension = true;
				break;
			}
		}

		// If no valid extension, generate a filename.
		if ( ! $has_extension || empty( $filename ) ) {
			$filename = 'notion-' . $context . '-' . wp_generate_password( 12, false ) . '.jpg';
		}

		// Sanitize the filename.
		$filename = sanitize_file_name( $filename );

		return $filename;
	}

	/**
	 * Extract page metadata (dates, archive status, etc.).
	 *
	 * @param array $page Notion page object.
	 * @return array Page metadata.
	 */
	public function extract_metadata( $page ) {
		$metadata = [
			'created_time'     => $page['created_time'] ?? null,
			'last_edited_time' => $page['last_edited_time'] ?? null,
			'archived'         => $page['archived'] ?? false,
			'in_trash'         => $page['in_trash'] ?? false,
			'url'              => $page['url'] ?? '',
			'public_url'       => $page['public_url'] ?? null,
		];

		// Extract created by / last edited by user info if needed.
		if ( ! empty( $page['created_by'] ) ) {
			$metadata['created_by'] = $page['created_by'];
		}

		if ( ! empty( $page['last_edited_by'] ) ) {
			$metadata['last_edited_by'] = $page['last_edited_by'];
		}

		return $metadata;
	}

	/**
	 * Store page metadata as WordPress post meta.
	 *
	 * @param int   $post_id WordPress post ID.
	 * @param array $metadata Page metadata.
	 * @return bool Success status.
	 */
	public function store_metadata( $post_id, $metadata ) {
		foreach ( $metadata as $key => $value ) {
			if ( null !== $value && '' !== $value ) {
				update_post_meta( $post_id, '_notion_' . $key, maybe_serialize( $value ) );
			}
		}

		return true;
	}

	/**
	 * Get database property names and types.
	 *
	 * @param array $database Notion database object.
	 * @return array Array of property info.
	 */
	public function get_database_properties( $database ) {
		$properties = [];

		if ( ! empty( $database['properties'] ) ) {
			foreach ( $database['properties'] as $name => $property ) {
				$properties[] = [
					'name' => $name,
					'type' => $property['type'] ?? '',
					'id'   => $property['id'] ?? '',
				];
			}
		}

		return $properties;
	}
}
