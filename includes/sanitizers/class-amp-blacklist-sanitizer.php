<?php

require_once( dirname( __FILE__ ) . '/class-amp-base-sanitizer.php' );

/**
 * Strips blacklisted tags and attributes from content.
 *
 * See following for blacklist:
 *     https://github.com/ampproject/amphtml/blob/master/spec/amp-html-format.md#html-tags
 */
class AMP_Blacklist_Sanitizer extends AMP_Base_Sanitizer {
	public function sanitize( $amp_attributes = array() ) {
		$blacklisted_tags = $this->get_blacklisted_tags();
		$blacklisted_attributes = $this->get_blacklisted_attributes();
		$blacklisted_protocols = $this->get_blacklisted_protocols();
		$ignore_tags = $this->get_ignore_tags();

		$body = $this->dom->getElementsByTagName( 'body' )->item( 0 );
		$this->strip_tags( $body, $blacklisted_tags );
		$this->strip_attributes_recursive( $body, $blacklisted_attributes, $blacklisted_protocols, $ignore_tags);
	}

	private function strip_attributes_recursive( $node, $bad_attributes, $bad_protocols, $ignore_tags) {
		if ( $node->nodeType !== XML_ELEMENT_NODE ) {
			return;
		}

		if ( $node->hasAttributes() ) {
			$length = $node->attributes->length;
			for( $i = $length - 1; $i >= 0; $i--) {
				$attribute = $node->attributes->item($i);
				$attribute_name = strtolower( $attribute->name );
				if ( in_array( $attribute_name, $bad_attributes ) ) {
					$node->removeAttribute( $attribute_name );
					continue;
				}

				// on* attributes (like onclick) are a special case
				if ( 0 === stripos( $attribute_name, 'on' ) ) {
					$node->removeAttribute( $attribute_name );
					continue;
				}

				if ( 'href' === $attribute_name ) {
					$protocol = strtok( $attribute->value, ':' );
					if ( in_array( $protocol, $bad_protocols ) ) {
						$node->removeAttribute( $attribute_name );
						continue;
					}
				}
				if (in_array( $node->nodeName, $ignore_tags )) {
					continue;
				}
				if ( 'width' === $attribute_name ||'height' === $attribute_name) {
					$node->removeAttribute( $attribute_name );
					continue;
				}
			}
		}
		foreach ( $node->childNodes as $child_node ) {
			$this->strip_attributes_recursive( $child_node, $bad_attributes, $bad_protocols, $ignore_tags);
		}
	}

	private function strip_tags( $node, $tags ) {
		foreach ( $tags as $tag_name ) {
			$elements = $node->getElementsByTagName( $tag_name );
			if ( $elements->length ) {
				foreach ( $elements as $element ) {
					$element->parentNode->removeChild( $element );
				}
			}
		}
	}

	private function get_blacklisted_protocols() {
		return array(
			'javascript',
		);
	}

	private function get_blacklisted_tags() {
		return array(
			'script',
			'noscript',
			'style',
			'frame',
			'frameset',
			'object',
			'param',
			'applet',
			'form',
			'input',
			'button',
			'textarea',
			'select',
			'option',
			'link',
			'meta',

			// These are converted into amp-* versions
			//'img',
			//'video',
			//'audio',
			//'iframe',
		);
	}

	private function get_ignore_tags() {
		return array(
			'img',
			'video',
			'audio',
			'iframe',
			'amp-ad',
			'amp-img',
			'amp-video',
			'amp-audio',
			'amp-iframe',
		);
	}

	private function get_blacklisted_attributes() {
		return array(
			'style',
			'border',
			'align',
			'valign',
			'bgcolor',
			'cellspacing',
			'cellpadding',
		);
	}
}
