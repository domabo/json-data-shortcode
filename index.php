<?php
/*
Plugin Name: JSON Data Shortcode
Description: Load data via JSON and display it in a page or post - even if the browser has Javascript disabled
Version: 1.4
Revision Date: 09/03/2014
Requires at least: WP 3.3
Tested up to: WP 3.9.1
License: Example: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
Author: David Dean, Domabo
Author URI: http://www.generalthreat.com/
*/

define( 'DD_JSON_KEY_REGEX', '/\{([^\}]+)\}/' );

/** How long (in seconds) to cache retrieved data - can be overridden with the 'lifetime' parameter */
define( 'DD_JSON_DEFAULT_LIFETIME', 60 * 5 ); // default = 5 minutes

class DD_JSON_Shortcode {
	
	var $sources = array();
	
	function DD_JSON_Shortcode() {
		/** Enable logging with WP Debug Logger */
		$GLOBALS['wp_log_plugins'][] = 'json_shortcode';
	}

	function do_shortcode( $attrs, $content = null ) {

		if( ! function_exists( 'json_encode' ) ) {
			require_once ABSPATH . 'wp-includes/compat.php';
		}
		
		$params = shortcode_atts(
			array(	'src' => '', 'name' => '', 'key' => '', 'array' => '','list' => '','items' => '','honor' => '', 'arraystrip' => '', 'arraykeyvalue' => '', 'lifetime' => DD_JSON_DEFAULT_LIFETIME ),
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
		
		$params['src'] = html_entity_decode( $params['src'] );
		
		if( ! $data = get_transient( 'json_' . md5( $params['src'] ) ) ) {
			$this->debug( sprintf( __( 'Cached data was not found.  Fetching JSON data from: %s', 'json-shortcode' ), $params['src'] ) );
			$data = json_decode( $this->fetch_file( $params['src'] ) );
			set_transient( 'json_' . md5( $params['src'] ), $data, $params['lifetime'] );
		}
		
			if( ! empty( $params['items'] )) {
			return $this->parse_items(  $data );
		}
		
		if( ! empty( $params['honor'] )) {
			return $this->parse_honor(  $data );
		}
		
		if( ! empty( $params['list'] )  && ! empty( $params['key'] )) {
			return $this->parse_list(  $data , $params['key']);
		}
	
	
		if( ! empty( $params['array'] )  && ! empty( $params['key'] ) ) {
			return $this->parse_array( $params['array'], $params['key'], $data );
		}
		
		if( ! empty( $params['arraystrip'] )  && ! empty( $params['key'] ) ) {
			return $this->parse_arraystrip( $params['arraystrip'], $params['key'], $data );
		}
	
		
		if( ! empty( $params['arraykeyvalue'] )  && ! empty( $params['key'] ) ) {
			return $this->parse_arraykeyvalue( $params['arraykeyvalue'], $params['key'], $data );
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
	 * Recurse through provided object to locate specified selector and key
	 * @param string $selector string containing the key name in JS object notation - i.e. "object.member"
	 * @param string $key string containing the key name 
	 * @param object $data object containing all received JSON data, or a subset during recursion
	 * @return mixed the value retrieved from the specified key or a string on error
	 */
	function parse_array($selector, $key, $data ) {
		
		$array = $this->parse_key($selector, $data);
		
		foreach ($array as $item) {
	        	if( isset( $item->$key ) )
		        return $item->$key;
		        }
		return $this->debug( sprintf( __( 'Selected array-key: %s was not found.', 'json-shortcode' ), $key ) );
	}
	
	/**
	 * Recurse through provided object 
	 * @param object $data object containing all received JSON data, or a subset during recursion
	 * @return mixed the value retrieved from the specified key or a string on error
	 */
	function parse_items($data ) {
		
		$retvalue = "<ul style='list-style-type: none;'>";
			
	      		        
	        $array = $data->results->Captains;
		
		
		foreach ($array as $item) {
			$name = $item->item->text;
			$href= $item->item->href;
			$value = $item->value;
			
			if ($name == "Guy Barnard")
			{
			   $name = "Partners via Guy Barnard";
			   $valueint = intval(str_replace("$", "",str_replace(",", "", $value)));
			   $value = number_format($valueint - 900);
			   $retvalue .= "<li><a href='" . $href ."'>Guy & Sarah Barnard" . " $" . "1,000" . "</a></li>";
			    $retvalue .= "<li><a href='" . $href ."'>" . $name . " $" . $value . "</a></li>";
			}
			else
			{
			$retvalue .= "<li><a href='" . $href ."'>" . $name . " " . $value . "</a></li>";
			}
	         }
	
		$array = $data->results->Individuals;
		
		
		foreach ($array as $item) {
			$name = $item->item->text;
			$href= $item->item->href;
			$value = $item->value;
			if ($name == "Sarah Barnard")
			{
				
			} else
			{
				$retvalue .= "<li><a href='" . $href ."'>" . $name . " " . $value . "</a></li>";
			}
	        		        }
	        		        
	        $array = $data->results->Team;
		
		
		foreach ($array as $item) {
			$name = $item->item->text;
			$href= $item->item->href;
			$value = $item->value;
			$retvalue .= "<li><a href='" . $href ."'>" . $name . " " . $value . "</a></li>";
	        		        }
	        		        
	        $array = $data->results->HonorRoll;
	        $retvalue .= "<ul>";
		
		foreach ($array as $item) {
			$name = $item->item;
			$name .= " " . $item->description;
			$value = $item->value;
			$retvalue .= "<li><a href='#'>" . $name . " " . $value . "</a></li>";
	        		        }
		$retvalue .= "</ul>";
	  
	       $retvalue .= "</ul>";
	       return $retvalue;
	 }
	 
	 	/**
	 * Recurse through provided object
	 * @param object $data object containing all received JSON data, or a subset during recursion
	 * @return mixed the value retrieved from the specified key or a string on error
	 */
	function parse_honor($data ) {
		
		$retvalue = "<ul>";
	      		       
	        $array = $data->results->HonorRoll;
		$array2 = $data->results->collection2;
		$count = 0;
	
		foreach ($array as $item) {
			$name = $item->item;
			$value = $item->value;
			$description = $array2[$count]->description;
			$count = $count +1;
			
			if ($name == "From Guy Barnard")
			{
			}
			else
			{
			$retvalue .= "<li><a href='" . "#" ."'>" . $name . " " . $description . " " . $value . "</a></li>";
			}
	         }
	
	  
	       $retvalue .= "</ul>";
	       return $retvalue;
	 }
	 
	 	/**
	 * Recurse through provided object
	 * @param object $data object containing all received JSON data, or a subset during recursion
	 * @return mixed the value retrieved from the specified key or a string on error
	 */
	function parse_list($data, $key) {
		
		$retvalue = "<ul>";
	      		       
	        $array = $data->results->$key;
	
		foreach ($array as $item) {
			$name = $item->item->text;
			$href = $item->item->href;
			$retvalue .= "<li><a href='" . $href . "'>" . $name . "</a></li>";
	         }
	
	       $retvalue .= "</ul>";
	       return $retvalue;
	 }
	
		/**
	 * Recurse through provided object to locate specified selector and key
	 * @param string $selector string containing the key name in JS object notation - i.e. "object.member"
	 * @param string $key string containing the key name 
	 * @param object $data object containing all received JSON data, or a subset during recursion
	 * @return mixed the value retrieved from the specified key or a string on error
	 */
	function parse_arraystrip($selector, $key, $data ) {
		
		$array = $this->parse_key($selector, $data);
		
		foreach ($array as $item) {
	        	if( isset( $item->$key ) )
	        	return str_replace("$", "",str_replace(",", "", $item->$key));
		        }
		return $this->debug( sprintf( __( 'Selected array-key: %s was not found.', 'json-shortcode' ), $key ) );
	}
	

	
		/**
	 * Recurse through provided object to locate specified selector and key
	 * @param string $selector string containing the key name in JS object notation - i.e. "object.member"
	 * @param string $key string containing the key name 
	 * @param object $data object containing all received JSON data, or a subset during recursion
	 * @return mixed the value retrieved from the specified key or a string on error
	 */
	function parse_arraykeyvalue($selector, $key, $data ) {
		
		$array = $this->parse_key($selector, $data);
		
		foreach ($array as $item) {
			
			if( isset( $item['Key'] ) )
				{
	        		if( $item['Key'] == $key  )
			  		return $item['Value'];
			  	}
			}
		return $this->debug( sprintf( __( 'Selected array-key: %s was not found.', 'json-shortcode' ), $key ) );
	}
	
	/**
	 * Get the file requested in $uri
	 */
	function fetch_file( $uri ) {
		
		$result = wp_remote_get( $uri );
		
		if( is_wp_error( $result ) ) {
			$this->debug( sprintf( __( 'HTTP request returned an error: %s (%s).', 'json-shortcode' ), $result->get_error_message(), $result->get_error_code() ) );
			return $result->get_error_message();
		}
		
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
	
	/**
	 * Add a test page to the 'Tools' menu
	 */
	public function add_admin_page() {
		
		$page = add_management_page(
			__('JSON Shortcode Diagnostics', 'json-shortcode'), 
			__('JSON Shortcode Test', 'json-shortcode'), 
			'manage_options', 
			'json_data_test', 
			array( &$this, 'admin_page_diagnostic' )
		);
		
	}
	
	public function admin_page_diagnostic() {
		
		?>
		<h1><?php _e('JSON Data Shortcode Test Form','json-shortcode'); ?></h1>
		<p>
			<?php _e( 'This form will let you try out your JSON shortcode before dropping it into a post.','json-shortcode'); ?>
			<?php _e( 'Here are two quick examples you can use to validate that the plugin is working:', 'json-shortcode' ); ?>
		</p>
		<ul>
			<li><code>[json src="http://ip.jsontest.com/"]My server's IP address is: {ip}[/json]</code></li>
			<li><code>[json src="http://date.jsontest.com/" key="date"][/json]</code></li>
		</ul>
		<form name="json-diagnostic" id="json-diagnostic" method="GET" action="">
			<textarea name="" id="json-test-string" placeholder="" style="width: 90%"></textarea>
			<?php submit_button( 'Test this JSON shortcode', 'primary', 'do-json-test' ); ?>
		</form>
		<div class="" style="border: 1px solid #ccc; width: 90%; padding: 5px;" id="json-result">
			<?php _e( 'The output of the shortcode(s) entered above will appear here.', 'json-shortcode' ); ?>
		</div>
		<script type="text/javascript">
			jQuery(document).ready(function($) {
				
				$('#do-json-test').on(
					'click',
					function() {
						
						$('#json-result').html( "<?php _e('Loading...','json-shortcode' ) ?>" );
						
						var data = {
							'action': 'json_diagnostic',
							'json-text': $('#json-test-string').val()
						};
						
						$.post(ajaxurl, data, function(response) {
							
							if( 'undefined' == typeof(response.result) ) {
								$('#json-result').html( "<?php _e( 'There was an error processing the supplied text.', 'json-shortcode' ) ?>" );
							} else if( ! response.matched ) {
								$('#json-result').html( "<?php _e( 'The [json] shortcode was not found in the supplied text.', 'json-shortcode' ) ?>" );
							} else if( '' == response.result ) {
								$('#json-result').html( "<?php _e( 'The [json] shortcode was found, but no output was generated. Did you specify a key?', 'json-shortcode' ) ?>" );
							} else {
								$('#json-result').html( response.result );
							}
							
						});
						
						return false;
					}
				);
				
			});
		</script>
		<?php
		
	}
	
	public function do_ajax_diagnostic() {
		
		$text = stripslashes( $_REQUEST['json-text'] );
		$result_text = array();
		$matched = false;
		
		/* Manually step through shortcode processing, 
		   sending the processed result to do_shortcode, above */
		
		$regex = get_shortcode_regex();
		$shortcodes = preg_match_all( '/' . $regex . '/s', $text, $matches, PREG_SET_ORDER );
		
		foreach( $matches as $key => $match ) {
			
			if( 'json' == $match[2] || 'json' == $match[3] ) {
				
				$result_text[] = do_shortcode_tag( $match );
				$matched = true;
				
			}
			
		}
		
		header( 'Content-Type: application/json' );
		
		// Echo result
		echo json_encode(
			array(
				'matched' => $matched,
				'result'  => implode( "<br>\n", $result_text )
			)
		);
		die();
		
	}
	
}

$json_shortcode = new DD_JSON_Shortcode();
add_shortcode( 'json', array( &$json_shortcode, 'do_shortcode' ) );

add_action( 'admin_menu', array( &$json_shortcode, 'add_admin_page' ) );
add_action( 'wp_ajax_json_diagnostic', array( &$json_shortcode, 'do_ajax_diagnostic' ) );

?>
