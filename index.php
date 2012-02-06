<?php
/*
Plugin Name: JSON Data Shortcode
Description: Load data via JSON and display it in a page or post - even if the browser has Javascript disabled
Version: 1.0
Requires at least: WP 3.0
Tested up to: WP 3.3.1
License: Example: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
Author: David Dean
Author URI: http://www.generalthreat.com/
*/

define( 'DD_JSON_KEY_REGEX', '/\{([^\}]+)\}/' );

/** How long (in seconds) to cache retrieved data - can be overridden with the 'lifetime' parameter */
define( 'DD_JSON_DEFAULT_LIFETIME', 60 * 30 ); // default = 30 minutes

class DD_JSON_Shortcode {
	
	var $json;
	var $sources = array();
	
	function DD_JSON_Shortcode() {
		/** Enable logging with WP Debug Logger */
		$GLOBALS['wp_log_plugins'][] = 'json_shortcode';
	}

	function do_shortcode( $attrs, $content = null ) {

		if( ! class_exists( 'Services_JSON' ) ) {
			require_once ABSPATH . 'wp-includes/class-json.php';
		}
		
		if( ! is_object( $this->json ) ) {
			$this->json = new Services_JSON();
		}
	
		$params = shortcode_atts(
			array(	'src'	=> '',	'name'	=> '',	'key'	=> '', 'lifetime'	=> DD_JSON_DEFAULT_LIFETIME	),
			$attrs
		);
		
		if( ! empty( $params['name'] ) && ! empty( $params['src'] ) ) {
			$this->sources[$params['name']] = $params['src'];
		}
		
		
		if( empty( $params['src'] ) ) {
			if( ! empty( $params['name'] ) && array_key_exists( $params['name'], $this->sources ) ) {
				$params['src'] = $this->sources[$params['name']];
			} else {
				return $this->debug( __( 'Must pass a source URI as "src"', 'json-shortcode' ) );
			}
		}

		if( empty( $params['key'] ) && is_null( $content ) ) {
			return $this->debug(__('Must pass either a key to output or content to format','json-shortcode'));
		}

		if( ! $data = get_transient( 'json_' . md5( $params['src'] ) ) ) {
			$this->debug( sprintf( __( 'Cached data was not found.  Fetching JSON data from: %s', 'json-shortcode' ), $params['src'] ) );
			$data = $this->json->decode( $this->fetch_file( $params['src'] ) );
			set_transient( 'json_' . md5( $params['src'] ), $data, $params['lifetime'] );
		}
		
		if( ! empty( $params['key'] ) ) {
			return $this->parse_key( $params['key'], $data );
		}
		
		if( ! is_null( $content ) && preg_match_all( DD_JSON_KEY_REGEX, $content, $keys ) ) {
			foreach( $keys[1] as $index => $key ) {
				$content = str_replace( $keys[0][$index], $this->parse_key( $key, $data ), $content );
			}
			return $content;
		}
		
	}
	
	/**
	 * Recurse through provided object to locate specified key
	 * @param string $key string containing the key name in JS object notation - i.e. "object.member"
	 * @param object $data object containing all received JSON data, or a subset during recursion
	 * @return mixed the value retrieved from the specified key or a string on error
	 */
	function parse_key( $key, $data ) {

		$parts = explode( '.', $key );
		if( count( $parts ) == 1 ) {
			if( ! isset( $data->$parts[0] ) )
				return $this->debug( sprintf( __( 'Selected key: %s was not found.', 'json-shortcode' ), $parts[0] ) );
			return $data->$parts[0];
		}
		$param = array_shift( $parts );
		
		if( ! isset( $data->$param ) )
			return $this->debug( sprintf( __( 'Selected key: %s was not found.', 'json-shortcode' ), $param ) );
		return $this->parse_key( implode( '.', $parts ), $data->$param );
	}
	
	/**
	 * Get the file requested in $uri
	 */
	function fetch_file( $uri ) {
		
		$result = wp_remote_get( $uri );
		if( $result['response']['code'] != '200' ) {
			$this->debug( sprintf( __( 'Server responded with: %s (%d). Data may not be usable.', 'json-shortcode' ), $result['response']['message'], $result['response']['code'] ) );
		}
		return $result['body'];
	}
	
	/**
	 * Handle debugging output
	 */
	function debug( $message ) {
		if( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			if( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY != FALSE ) {
				printf( __( 'JSON Data Shortcode Error: %s', 'json-shortcode' ), $message );
			} else {
				error_log( sprintf( __( 'JSON Data Shortcode Error: %s', 'json-shortcode' ), $message ) );
			}
			return $message;
		}
		if( defined( 'WP_DEBUG_LOG' ) ) {
			$GLOBALS['wp_log']['json_shortcode'][] = sprintf( __( 'JSON Data Shortcode Error: %s', 'json-shortcode' ), $message );
		}
		/** Be quiet unless debugging is on */
		return '';
	}
	
}

$json_shortcode = new DD_JSON_Shortcode();
add_shortcode( 'json', array( &$json_shortcode, 'do_shortcode' ) );

?>