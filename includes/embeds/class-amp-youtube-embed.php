<?php

require_once( dirname( __FILE__ ) . '/class-amp-base-embed-handler.php' );

// Much of this class is borrowed from Jetpack embeds
class AMP_YouTube_Embed_Handler extends AMP_Base_Embed_Handler {
	const SHORT_URL_HOST = 'youtu.be';
	const URL_PATTERN = '#https?://(?:www\.)?(?:youtube.com/(?:v/|e/|embed/|playlist|watch[/\#?])|youtu\.be/).*#i';

	private static $script_slug = 'amp-youtube';
	private static $script_src = 'https://cdn.ampproject.org/v0/amp-youtube-0.1.js';

	function register_embed() {
		wp_embed_register_handler( 'amp-youtube', self::URL_PATTERN, array( $this, 'oembed' ), -1 );
		add_shortcode( 'youtube', array( $this, 'shortcode' ) );
	}

	public function unregister_embed() {
		wp_embed_unregister_handler( 'amp-youtube', -1 );
		remove_shortcode( 'youtube' );
	}

	public function get_scripts() {
		if ( ! $this->did_convert_elements ) {
			return array();
		}

		return array( self::$script_slug => self::$script_src );
	}

	public function shortcode( $attr ) {
		$url = false;
		$video_id = false;
		if ( isset( $attr[0] ) ) {
			$url = ltrim( $attr[0] , '=' );
		} elseif ( function_exists ( 'shortcode_new_to_old_params' ) ) {
			$url = shortcode_new_to_old_params( $atts );
		}

		if ( empty( $url ) ) {
			return '';
		}

		$video_id = $this->get_video_id_from_url( $url );

		return $this->render( array(
			'url' => $url,
			'video_id' => $video_id,
		) );
	}

	public function oembed( $matches, $attr, $url, $rawattr ) {
		return $this->shortcode( array( $url ) );
	}

	public function render( $args ) {
		$args = wp_parse_args( $args, array(
			'video_id' => false,
		) );

		if ( empty( $args['video_id'] ) ) {
			return AMP_HTML_Utils::build_tag( 'a', array( 'href' => esc_url( $args['url'] ), 'class' => 'amp-wp-embed-fallback' ), esc_html( $args['url'] ) );
		}

		$this->did_convert_elements = true;

		return AMP_HTML_Utils::build_tag(
			'amp-youtube',
			wp_parse_args( array(
				'data-videoid' => $args['video_id'],
				'layout' => 'responsive',
			), $this->args )
		);
	}

	private function get_video_id_from_url( $url ) {
		$video_id = false;
		$parsed_url = parse_url( $url );

		if ( self::SHORT_URL_HOST === substr( $parsed_url['host'], -strlen( self::SHORT_URL_HOST ) ) ) {
			// youtu.be/{id}
			$parts = explode( '/', $parsed_url['path'] );
			if ( ! empty( $parts ) ) {
				$video_id = $parts[1];
			}
		} else {
			// ?v={id} or ?list={id}
			parse_str( $parsed_url['query'], $query_args );

			if ( isset( $query_args['v'] ) ) {
				$video_id = $query_args['v'];
			}
		}

		if ( empty( $video_id ) ) {
			// /(v|e|embed)/{id}
			$parts = explode( '/', $parsed_url['path'] );

			if ( in_array( $parts[1], array( 'v', 'e', 'embed' ) ) ) {
				$video_id = $parts[2];
			}
		}

		return $video_id;
	}
}
