<?php
/**
 * Media Collector.
 *
 * Collects placeholder-to-URL mappings during block conversion for deferred media downloads.
 *
 * @package Notion2WP
 */

namespace Notion2WP\Media;

defined( 'ABSPATH' ) || exit;

/**
 * Singleton class that registers Notion-hosted media URLs during block conversion
 * and returns deterministic placeholder tokens for later resolution.
 */
class Media_Collector {

	/**
	 * Singleton instance.
	 *
	 * @var Media_Collector|null
	 */
	private static $instance = null;

	/**
	 * Collected media manifest.
	 *
	 * Maps placeholder hash to media metadata.
	 *
	 * @var array<string, array{url: string, block_type: string}>
	 */
	private $manifest = [];

	/**
	 * Get singleton instance.
	 *
	 * @return Media_Collector
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor to enforce singleton pattern.
	 */
	private function __construct() {}

	/**
	 * Register a Notion-hosted URL and return a placeholder token.
	 *
	 * The placeholder token is deterministic: the same underlying file always
	 * produces the same token, enabling deduplication within a single page.
	 *
	 * @param string $notion_url  The Notion-hosted media URL.
	 * @param string $block_type  The block type (e.g., 'image', 'video').
	 * @return string Placeholder token in the format {{notion_media:<hash>}}.
	 */
	public function register( $notion_url, $block_type ) {
		$normalized_url = self::normalize_notion_url( $notion_url );
		$hash           = substr( hash( 'sha256', $normalized_url ), 0, 16 );
		$token          = '{{notion_media:' . $hash . '}}';

		if ( ! isset( $this->manifest[ $hash ] ) ) {
			$this->manifest[ $hash ] = [
				'url'        => $notion_url,
				'block_type' => $block_type,
			];
		}

		return $token;
	}

	/**
	 * Get the full media manifest.
	 *
	 * @return array<string, array{url: string, block_type: string}>
	 */
	public function get_manifest() {
		return $this->manifest;
	}

	/**
	 * Reset the manifest between page imports.
	 *
	 * @return void
	 */
	public function reset() {
		$this->manifest = [];
	}

	/**
	 * Normalize a Notion-hosted URL by stripping query parameters.
	 *
	 * Notion S3 URLs include signed query parameters (X-Amz-Signature, etc.)
	 * that change on every API call. Stripping them ensures the same underlying
	 * file always produces the same hash for deduplication.
	 *
	 * @param string $url The Notion-hosted URL.
	 * @return string The normalized URL (path only, no query string).
	 */
	public static function normalize_notion_url( $url ) {
		$parsed = wp_parse_url( $url );

		$scheme = $parsed['scheme'] ?? 'https';
		$host   = $parsed['host'] ?? '';
		$path   = $parsed['path'] ?? '';

		return sprintf( '%s://%s%s', $scheme, $host, $path );
	}
}
