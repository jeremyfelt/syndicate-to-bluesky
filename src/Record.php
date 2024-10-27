<?php
/**
 * Manage the creation of a record to be sent to Bluesky.
 *
 * @package syndicate-to-bluesky
 */

namespace SyndicateToBluesky;

/**
 * Manage the creation of a record to be sent to Bluesky.
 */
class Record {

	/**
	 * The post from which the record is created.
	 *
	 * @var \WP_Post
	 */
	public $post;

	/**
	 * The record data to be sent to Bluesky.
	 *
	 * @var array{
	 *     '$type': string,
	 *     'createdAt': string,
	 *     'text': string,
	 *     'facets': array<array-key, array{
	 *         features: array<array-key, array{
	 *             uri: string,
	 *             '$type': string
	 *         }>,
	 *         index: array{
	 *             byteStart: int,
	 *             byteEnd: int
	 *         }
	 *     }>
	 * }
	 */
	public $record;

	/**
	 * Constructor.
	 *
	 * @param \WP_Post $post The post.
	 */
	public function __construct( $post ) {
		$this->post = $post;

		$time = strtotime( $post->post_date_gmt );
		$time = $time ? $time : time();

		$this->record = [
			'$type'     => 'app.bsky.feed.post',
			'createdAt' => gmdate( 'c', $time ),
			'text'      => '',
			'facets'    => [],
		];

		$this->set_type();
		$this->set_text();
		$this->set_facets();
	}

	/**
	 * Get the record.
	 *
	 * @return array{
	 *     '$type': string,
	 *     'createdAt': string,
	 *     'text': string,
	 *     'facets': array<array-key, array{
	 *         features: array<array-key, array{
	 *             uri: string,
	 *             '$type': string
	 *         }>,
	 *         index: array{
	 *             byteStart: int,
	 *             byteEnd: int
	 *         }
	 *     }>
	 * } The record.
	 */
	public function get(): array {
		return $this->record;
	}

	/**
	 * Set the type of the record.
	 *
	 * @return void
	 */
	protected function set_type(): void {
		$this->record['$type'] = apply_filters( 'syndicate_to_bluesky_record_type', 'app.bsky.feed.post', $this->post );
	}

	/**
	 * Set the text of the record.
	 *
	 * @return void
	 */
	protected function set_text(): void {
		$default = get_the_title( $this->post ) . ' ' . get_permalink( $this->post );

		$this->record['text'] = apply_filters( 'syndicate_to_bluesky_record_text', $default, $this->post );
	}

	/**
	 * Set the facets of the record.
	 */
	protected function set_facets(): void {
		$permalink = get_permalink( $this->post );

		// Find the byte start and end of the permalink in the text, if it exists.
		$byte_start = strpos( $this->record['text'], $permalink );
		$byte_end   = $byte_start + strlen( $permalink );

		$this->record['facets'] = [];

		// If the permalink is found in the text, add it as a facet.
		if ( false !== $byte_start ) {
			$this->record['facets'][] = array(
				'features' => array(
					array(
						'uri'   => $permalink,
						'$type' => 'app.bsky.richtext.facet#link',
					),
				),
				'index'    => array(
					'byteStart' => $byte_start,
					'byteEnd'   => $byte_end,
				),
			);
		}

		$this->record['facets'] = apply_filters( 'syndicate_to_bluesky_record_facets', $this->record['facets'], $this->post );
	}
}
