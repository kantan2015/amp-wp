<?php

abstract class AMP_Base_Sanitizer {
	protected $DEFAULT_ARGS = array();

	protected $dom;
	protected $args;
	protected $did_convert_elements = false;

	public function __construct( $dom, $args = array() ) {
		$this->dom = $dom;
		$this->args = array_merge( $this->DEFAULT_ARGS, $args );
	}

	abstract public function sanitize( $amp_attributes = array() );

	public function get_scripts() {
		return array();
	}

	/**
	 * This is our workaround to enforce max sizing with layout=responsive.
	 *
	 * We want elements to not grow beyond their width and shrink to fill the screen on viewports smaller than their width.
	 *
	 * See https://github.com/ampproject/amphtml/issues/1280#issuecomment-171533526
	 * See https://github.com/Automattic/amp-wp/issues/101
	 */
	public function enforce_sizes_attribute( $attributes ) {
		if ( isset( $attributes['sizes'] ) ) {
			return $attributes;
		}

		if ( ! isset( $attributes['width'], $attributes['height'] ) ) {
			return $attributes;
		}

		$max_width = $attributes['width'];
		if ( isset( $this->args['content_max_width'] ) && $max_width >= $this->args['content_max_width'] ) {
			$max_width = $this->args['content_max_width'];
		}

		$attributes['sizes'] = sprintf( '(min-width: %1$dpx) %1$dpx, 100vw', absint( $max_width ) );

		$class = 'amp-wp-enforced-sizes';
		if ( isset( $attributes['class'] ) ) {
			$attributes['class'] .= ' ' . $class;
		} else {
			$attributes['class'] = $class;
		}

		return $attributes;
	}
}
