<?php
/**
 * Media Handler.
 *
 * Downloads Notion-hosted media to WordPress Media Library and resolves placeholder tokens.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Media;

use Notion2WP\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Handles downloading, sideloading, deduplication, and URL resolution for Notion-hosted media files.
 */
class Media_Handler {

	/**
	 * Meta key used to track the original Notion source URL on attachments.
	 */
	const SOURCE_URL_META_KEY = '_notion_source_url';

	/**
	 * Meta key used to track the media context on attachments.
	 */
	const CONTEXT_META_KEY = '_notion_media_context';

	/**
	 * Legacy meta key used by cover image handler (backward compat).
	 */
	const LEGACY_COVER_URL_META_KEY = '_notion_cover_url';

	/**
	 * Default download timeout in seconds.
	 */
	const DEFAULT_TIMEOUT = 60;

	/**
	 * Resolve all placeholder tokens in post content by downloading media and replacing tokens with WordPress attachment URLs.
	 *
	 * Returns new content string - does not mutate the input.
	 *
	 * @param int    $post_id      WordPress post ID to attach media to.
	 * @param string $post_content Post content containing placeholder tokens.
	 * @param array  $manifest     Media manifest from Media_Collector.
	 * @return string Resolved post content with WordPress URLs.
	 */
	public function resolve_media_for_post( $post_id, $post_content, array $manifest ) {
		if ( empty( $manifest ) ) {
			return $post_content;
		}

		$resolved_content = $post_content;

		foreach ( $manifest as $hash => $entry ) {
			$token      = '{{notion_media:' . $hash . '}}';
			$notion_url = $entry['url'];
			$context    = $entry['block_type'];

			// Attempt to find an existing attachment for this URL (dedup).
			$attachment_id = $this->find_existing_attachment( $notion_url );

			if ( null === $attachment_id ) {
				// Download and sideload the media.
				$attachment_id = $this->download_and_sideload( $notion_url, $post_id, $context );
			}

			// Determine the replacement URL.
			if ( is_int( $attachment_id ) && $attachment_id > 0 ) {
				$replacement_url = wp_get_attachment_url( $attachment_id );

				if ( ! $replacement_url ) {
					$replacement_url = $notion_url;
					$this->log( sprintf( 'Could not get attachment URL for ID %d, falling back to Notion URL.', $attachment_id ) );
				}
			} else {
				// Download failed — fall back to the original Notion URL.
				$replacement_url = $notion_url;

				if ( is_wp_error( $attachment_id ) ) {
					$this->log( sprintf( 'Download failed for %s: %s', $notion_url, $attachment_id->get_error_message() ) );

					/**
					 * Fires when a media download fails.
					 *
					 * @since 1.1.0
					 *
					 * @param string    $notion_url The URL that failed to download.
					 * @param \WP_Error $error      The error that occurred.
					 * @param int       $post_id    The WordPress post ID.
					 */
					do_action( 'notion2wp_media_download_failed', $notion_url, $attachment_id, $post_id );
				}
			}

			// Replace all occurrences of the placeholder token with the resolved URL.
			$resolved_content = str_replace( $token, $replacement_url, $resolved_content );
		}

		// Safety net: if any placeholder tokens survive, replace with empty string and log.
		if ( false !== strpos( $resolved_content, '{{notion_media:' ) ) {
			$this->log( 'Unresolved placeholder tokens found in post content — removing them.' );
			$resolved_content = preg_replace( '/\{\{notion_media:[a-f0-9]{16}\}\}/', '', $resolved_content );
		}

		/**
		 * Filters post content after all media placeholders have been resolved.
		 *
		 * @since 1.1.0
		 *
		 * @param string $resolved_content The resolved post content.
		 * @param int    $post_id          The WordPress post ID.
		 * @param array  $manifest         The media manifest that was resolved.
		 */
		return apply_filters( 'notion2wp_resolved_media_content', $resolved_content, $post_id, $manifest );
	}

	/**
	 * Download a media file and sideload it into the WordPress Media Library.
	 *
	 * @param string $media_url The URL to download.
	 * @param int    $post_id   WordPress post ID to attach to.
	 * @param string $context   Media context (e.g., 'image', 'video', 'cover').
	 * @return int|\WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public function download_and_sideload( $media_url, $post_id, $context = 'content' ) {
		// Require WordPress media functions.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		/**
		 * Filters the download timeout for Notion media files.
		 *
		 * @since 1.1.0
		 *
		 * @param int $timeout Timeout in seconds.
		 */
		$timeout = apply_filters( 'notion2wp_media_download_timeout', self::DEFAULT_TIMEOUT );

		// Pre-check file size via HEAD request.
		$size_check = $this->check_file_size( $media_url, $timeout );

		if ( is_wp_error( $size_check ) ) {
			return $size_check;
		}

		$this->log( sprintf( 'Downloading media: %s (context: %s)', $media_url, $context ) );

		// Download to temp file.
		$temp_file = download_url( $media_url, $timeout );

		if ( is_wp_error( $temp_file ) ) {
			return $temp_file;
		}

		// Validate MIME type.
		$filetype = wp_check_filetype_and_ext( $temp_file, basename( wp_parse_url( $media_url, PHP_URL_PATH ) ) );

		if ( empty( $filetype['type'] ) ) {
			// Try to detect from file content.
			$filetype['type'] = mime_content_type( $temp_file );
		}

		$allowed_types = get_allowed_mime_types();

		if ( ! empty( $filetype['type'] ) && ! in_array( $filetype['type'], $allowed_types, true ) ) {
			wp_delete_file( $temp_file );
			return new \WP_Error(
				'invalid_mime_type',
				sprintf(
					/* translators: %s: MIME type */
					__( 'Media file has unsupported MIME type: %s', 'notion2wp' ),
					$filetype['type']
				)
			);
		}

		// Generate a clean filename.
		$filename = $this->sanitize_media_filename(
			basename( wp_parse_url( $media_url, PHP_URL_PATH ) ),
			$context
		);

		/**
		 * Filters the filename used when sideloading Notion media.
		 *
		 * @since 1.1.0
		 *
		 * @param string $filename The sanitized filename.
		 * @param string $media_url The original Notion URL.
		 * @param string $context  The media context.
		 */
		$filename = apply_filters( 'notion2wp_media_filename', $filename, $media_url, $context );

		$file_array = [
			'name'     => $filename,
			'tmp_name' => $temp_file,
		];

		// Sideload into WordPress Media Library.
		$attachment_id = media_handle_sideload( $file_array, $post_id );

		// Clean up temp file if it still exists.
		if ( file_exists( $temp_file ) ) {
			wp_delete_file( $temp_file );
		}

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		// Store source URL for deduplication and the media context.
		$normalized_url = Media_Collector::normalize_notion_url( $media_url );
		update_post_meta( $attachment_id, self::SOURCE_URL_META_KEY, $normalized_url );
		update_post_meta( $attachment_id, self::CONTEXT_META_KEY, $context );

		$this->log( sprintf( 'Sideloaded media as attachment #%d', $attachment_id ) );

		return $attachment_id;
	}

	/**
	 * Find an existing WordPress attachment by its original Notion source URL.
	 *
	 * Uses the normalized URL (without query params) for matching, so that
	 * the same underlying file is found even when Notion generates new signed URLs.
	 *
	 * @param string $notion_url The original Notion URL.
	 * @return int|null Attachment ID if found, null otherwise.
	 */
	public function find_existing_attachment( $notion_url ) {
		$normalized_url = Media_Collector::normalize_notion_url( $notion_url );

		$attachments = get_posts(
			[
				'post_type'      => 'attachment',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					'relation' => 'OR',
					[
						'key'   => self::SOURCE_URL_META_KEY,
						'value' => $normalized_url,
					],
					// Backward compat: check legacy cover URL meta key.
					[
						'key'   => self::LEGACY_COVER_URL_META_KEY,
						'value' => $normalized_url,
					],
				],
				'fields'         => 'ids',
			]
		);

		return ! empty( $attachments ) ? (int) $attachments[0] : null;
	}

	/**
	 * Pre-check file size via HEAD request.
	 *
	 * @param string $url     The URL to check.
	 * @param int    $timeout Request timeout in seconds.
	 * @return true|\WP_Error True if size is acceptable, WP_Error if too large.
	 */
	private function check_file_size( $url, $timeout ) {
		/**
		 * Filters the maximum file size allowed for Notion media downloads.
		 *
		 * @since 1.1.0
		 *
		 * @param int $max_bytes Maximum file size in bytes. Defaults to WP upload limit.
		 */
		$max_size = apply_filters( 'notion2wp_media_max_file_size', wp_max_upload_size() );

		$response = wp_remote_head(
			$url,
			[
				'timeout'   => min( $timeout, 10 ),
				'sslverify' => true,
			]
		);

		// If HEAD request fails, let the download attempt proceed.
		if ( is_wp_error( $response ) ) {
			return true;
		}

		$content_length = (int) wp_remote_retrieve_header( $response, 'content-length' );

		if ( $content_length > 0 && $content_length > $max_size ) {
			$this->log(
				sprintf(
					'Skipping download: file size %s exceeds limit %s for URL: %s',
					size_format( $content_length ),
					size_format( $max_size ),
					$url
				)
			);

			return new \WP_Error(
				'file_too_large',
				sprintf(
					/* translators: 1: File size, 2: Maximum allowed size */
					__( 'Media file size (%1$s) exceeds the maximum upload limit (%2$s).', 'notion2wp' ),
					size_format( $content_length ),
					size_format( $max_size )
				)
			);
		}

		return true;
	}

	/**
	 * Sanitize and generate a filename for media files.
	 *
	 * @param string $filename Original filename from URL path.
	 * @param string $context  Media context for fallback naming.
	 * @return string Sanitized filename.
	 */
	private function sanitize_media_filename( $filename, $context = 'media' ) {
		// Remove query strings and URL fragments.
		$filename = preg_replace( '/[?#].*/', '', $filename );

		// Check if filename has a valid extension.
		$valid_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'mov', 'mp3', 'ogg', 'wav', 'pdf', 'doc', 'docx', 'zip' ];
		$valid_extensions = apply_filters( 'notion2wp_media_valid_extensions', $valid_extensions, $context );
		$has_extension    = false;

		foreach ( $valid_extensions as $ext ) {
			if ( preg_match( '/\.' . $ext . '$/i', $filename ) ) {
				$has_extension = true;
				break;
			}
		}

		// If no valid extension, generate a filename.
		if ( ! $has_extension || empty( $filename ) ) {
			$extension = $this->get_default_extension( $context );
			$filename  = 'notion-' . $context . '-' . wp_generate_password( 12, false ) . '.' . $extension;
		}

		return sanitize_file_name( $filename );
	}

	/**
	 * Get default file extension based on media context.
	 *
	 * @param string $context Media context.
	 * @return string Default file extension.
	 */
	private function get_default_extension( $context ) {
		$extensions = [
			'image' => 'jpg',
			'cover' => 'jpg',
			'video' => 'mp4',
			'audio' => 'mp3',
			'file'  => 'pdf',
		];

		$extensions = apply_filters( 'notion2wp_media_default_extension', $extensions, $context );

		return $extensions[ $context ] ?? 'jpg';
	}

	/**
	 * Log a message when debug logging is enabled.
	 *
	 * @param string $message The message to log.
	 * @return void
	 */
	private function log( $message ) {
		if ( Settings::get_setting( 'enable_debug_logging', false ) ) {
			error_log( '[Notion2WP Media] ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}
}
