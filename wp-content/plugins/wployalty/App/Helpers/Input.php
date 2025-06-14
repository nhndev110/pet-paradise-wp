<?php
/**
 * @author      Wployalty (Alagesan)
 * @license     http://www.gnu.org/licenses/gpl-2.0.html
 * @link        https://www.wployalty.net
 * */

namespace Wlr\App\Helpers;
defined( 'ABSPATH' ) or die;

use Exception;

class Input {
	/**
	 * Character set
	 *
	 * Will be overridden by the constructor.
	 *
	 * @var    string
	 */
	public $charset = 'UTF-8';
	/**
	 * IP address of the current user
	 *
	 * @var    string
	 */
	protected $ip_address = false;
	/**
	 * Allow GET array flag
	 *
	 * If set to FALSE, then $_GET will be set to an empty array.
	 *
	 * @var    bool
	 */
	protected $_allow_get_array = true;
	/**
	 * Standardize new lines flag
	 *
	 * If set to TRUE, then newlines are standardized.
	 *
	 * @var    bool
	 */
	protected $_standardize_newlines;
	/**
	 * Enable XSS flag
	 *
	 * Determines whether the XSS filter is always active when
	 * GET, POST or COOKIE data is encountered.
	 * Set automatically based on config setting.
	 *
	 * @var    bool
	 */
	protected $_enable_xss = true;
	/**
	 * List of all HTTP request headers
	 *
	 * @var array
	 */
	protected $headers = array();
	/**
	 * Raw input stream data
	 *
	 * Holds a cache of php://input contents
	 *
	 * @var    string
	 */
	protected $_raw_input_stream;
	/**
	 * List of never allowed strings
	 *
	 * @var    array
	 */
	protected $_never_allowed_str = array(
		'document.cookie'   => '[removed]',
		'(document).cookie' => '[removed]',
		'document.write'    => '[removed]',
		'(document).write'  => '[removed]',
		'.parentNode'       => '[removed]',
		'.innerHTML'        => '[removed]',
		'-moz-binding'      => '[removed]',
		'<!--'              => '&lt;!--',
		'-->'               => '--&gt;',
		'<![CDATA['         => '&lt;![CDATA[',
		'<comment>'         => '&lt;comment&gt;',
		'<%'                => '&lt;&#37;'
	);
	/**
	 * List of never allowed regex replacements
	 *
	 * @var    array
	 */
	protected $_never_allowed_regex = array(
		'javascript\s*:',
		'(\(?document\)?|\(?window\)?(\.document)?)\.(location|on\w*)',
		'expression\s*(\(|&\#40;)', // CSS and IE
		'vbscript\s*:', // IE, surprise!
		'wscript\s*:', // IE
		'jscript\s*:', // IE
		'vbs\s*:', // IE
		'Redirect\s+30\d',
		"([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
	);
	/**
	 * XSS Hash
	 *
	 * Random Hash for protecting URLs.
	 *
	 * @var    string
	 */
	protected $_xss_hash;
	/**
	 * Parsed input stream data
	 *
	 * Parsed from php://input at runtime
	 *
	 * @see    CI_Input::input_stream()
	 * @var    array
	 */
	protected $_input_stream;
	protected $security;
	protected $uni;

	/**
	 * Clean Keys
	 *
	 * @param $str
	 * @param bool $fatal
	 *
	 * @return bool
	 */
	protected function _clean_input_keys( $str, $fatal = true ) {
		return $str;
	}

	/**
	 * Clean Input Data
	 *
	 * @param $str
	 *
	 * @return array|string|string[]|null
	 */
	protected function _clean_input_data( $str ) {
		if ( is_array( $str ) ) {
			$new_array = array();
			foreach ( array_keys( $str ) as $key ) {
				$new_array[ $this->_clean_input_keys( $key ) ] = $this->_clean_input_data( $str[ $key ] );
			}

			return $new_array;
		}
		if ( is_object( $str ) ) {
			return $str;
		}
		/* We strip slashes if magic quotes is on to keep things consistent

		   NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and
				 it will probably not exist in future versions at all.
		*/
		// Remove control characters
		$str = $this->remove_invisible_characters( $str, false );
		// Standardize newlines if needed
		if ( $this->_standardize_newlines === true ) {
			return preg_replace( '/(?:\r\n|[\r\n])/', PHP_EOL, $str );
		}

		return $str;
	}

	/**
	 * Remove Invisible Characters
	 *
	 * @param $str
	 * @param bool $url_encoded
	 *
	 * @return string|string[]|null
	 */
	function remove_invisible_characters( $str, $url_encoded = true ) {
		if ( is_null( $str ) ) {
			return $str;
		}
		$non_displayables = array();
		// every control character except newline (dec 10),
		// carriage return (dec 13) and horizontal tab (dec 09)
		if ( $url_encoded ) {
			$non_displayables[] = '/%0[0-8bcef]/i';    // url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/i';    // url encoded 16-31
			$non_displayables[] = '/%7f/i';    // url encoded 127
		}
		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';    // 00-08, 11, 12, 14-31, 127
		do {
			$str = preg_replace( $non_displayables, '', $str, - 1, $count );
		} while ( $count );

		return $str;
	}

	/**
	 * Fetch an item from POST data with fallback to GET
	 *
	 * @param $index
	 * @param null $xss_clean
	 * @param null $default
	 *
	 * @return mixed
	 */
	function post_get( $index, $default = null, $xss_clean = null ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		return isset( $_POST[ $index ] )
			? $this->post( $index, $default, $xss_clean )
			: $this->get( $index, $default, $xss_clean );
	}

	/**
	 * Fetch an item from the POST array
	 *
	 * @param null $index
	 * @param null $default
	 * @param null $xss_clean
	 *
	 * @return mixed
	 */
	function post( $index = null, $default = null, $xss_clean = null ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Missing
		return $this->_fetch_from_array( $_POST, $index, $default, $xss_clean );
	}

	/**
	 * Fetch from array
	 *
	 * @param $array
	 * @param null $index
	 * @param null $default
	 * @param null $xss_clean
	 *
	 * @return array|string|null
	 */
	protected function _fetch_from_array( &$array, $index = null, $default = null, $xss_clean = null ) {
		is_bool( $xss_clean ) or $xss_clean = $this->_enable_xss;
		// If $index is NULL, it means that the whole $array is requested
		$index = ( ! isset( $index ) || is_null( $index ) ) ? array_keys( $array ) : $index;
		// allow fetching multiple keys at once
		if ( is_array( $index ) ) {
			$output = array();
			foreach ( $index as $key ) {
				$output[ $key ] = $this->_fetch_from_array( $array, $key, $default, $xss_clean );
			}

			return $output;
		}
		if ( isset( $array[ $index ] ) ) {
			$value = $array[ $index ];
		} elseif ( ( $count = preg_match_all( '/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches ) ) > 1 ) // Does the index contain array notation
		{
			$value = $array;
			for ( $i = 0; $i < $count; $i ++ ) {
				$key = trim( $matches[0][ $i ], '[]' );
				if ( $key === '' ) // Empty notation will return the value as array
				{
					break;
				}
				if ( isset( $value[ $key ] ) ) {
					$value = $value[ $key ];
				} else {
					return null;
				}
			}
		} else {
			return $default;
		}

		return ( $xss_clean === true ) ? $this->xss_clean( $value ) : $value;
	}

	/**
	 * XSS Clean
	 *
	 * @param $str
	 * @param bool $is_image
	 *
	 * @return array|bool|string|string[]|null
	 */
	function xss_clean( $str, $is_image = false ) {
		// Is the string an array?
		if ( is_array( $str ) ) {
			foreach ( $str as $key => &$value ) {
				$str[ $key ] = $this->xss_clean( $value );
			}

			return $str;
		}
		if ( is_object( $str ) ) {
			return $str;
		}
		// Remove Invisible Characters
		$str = $this->remove_invisible_characters( $str );
		if ( stripos( $str, '%' ) !== false ) {
			do {
				$oldstr = $str;
				$str    = rawurldecode( $str );
				$str    = preg_replace_callback( '#%(?:\s*[0-9a-f]){2,}#i', array( $this, '_urldecodespaces' ), $str );
			} while ( $oldstr !== $str );
			unset( $oldstr );
		}
		$str = preg_replace_callback( "/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si", array(
			$this,
			'_convert_attribute'
		), $str );
		$str = preg_replace_callback( '/<\w+.*/si', array( $this, '_decode_entity' ), $str );
		// Remove Invisible Characters Again!
		$str = $this->remove_invisible_characters( $str );
		$str = str_replace( "\t", ' ', $str );
		// Capture converted string for later comparison
		$converted_string = $str;
		// Remove Strings that are never allowed
		$str = $this->_do_never_allowed( $str );
		if ( $is_image === true ) {
			$str = preg_replace( '/<\?(php)/i', '&lt;?\\1', $str );
		} else {
			$str = str_replace( array( '<?', '?' . '>' ), array( '&lt;?', '?&gt;' ), $str );
		}
		$words = array(
			'javascript',
			'expression',
			'vbscript',
			'jscript',
			'wscript',
			'vbs',
			'script',
			'base64',
			'applet',
			'alert',
			'document',
			'write',
			'cookie',
			'window',
			'confirm',
			'prompt',
			'eval'
		);
		foreach ( $words as $word ) {
			$word = implode( '\s*', str_split( $word ) ) . '\s*';
			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback( '#(' . substr( $word, 0, - 3 ) . ')(\W)#is', array(
				$this,
				'_compact_exploded_words'
			), $str );
		}
		do {
			$original = $str;
			if ( preg_match( '/<a/i', $str ) ) {
				$str = preg_replace_callback( '#<a(?:rea)?[^a-z0-9>]+([^>]*?)(?:>|$)#si', array(
					$this,
					'_js_link_removal'
				), $str );
			}
			if ( preg_match( '/<img/i', $str ) ) {
				$str = preg_replace_callback( '#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', array(
					$this,
					'_js_img_removal'
				), $str );
			}
			if ( preg_match( '/script|xss/i', $str ) ) {
				$str = preg_replace( '#</*(?:script|xss).*?>#si', '[removed]', $str );
			}
		} while ( $original !== $str );
		unset( $original );
		$pattern = '#'
		           . '<((?<slash>/*\s*)((?<tagName>[a-z0-9]+)(?=[^a-z0-9]|$)|.+)' // tag start and name, followed by a non-tag character
		           . '[^\s\042\047a-z0-9>/=]*' // a valid attribute character immediately after the tag would count as a separator
		           // optional attributes
		           . '(?<attributes>(?:[\s\042\047/=]*' // non-attribute characters, excluding > (tag close) for obvious reasons
		           . '[^\s\042\047>/=]+' // attribute characters
		           // optional attribute-value
		           . '(?:\s*=' // attribute-value separator
		           . '(?:[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*))' // single, double or non-quoted value
		           . ')?' // end optional attribute-value group
		           . ')*)' // end optional attributes group
		           . '[^>]*)(?<closeTag>\>)?#isS';
		do {
			$old_str = $str;
			$str     = preg_replace_callback( $pattern, array( $this, '_sanitize_naughty_html' ), $str );
		} while ( $old_str !== $str );
		unset( $old_str );
		$str = preg_replace(
			'#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si',
			'\\1\\2&#40;\\3&#41;',
			$str
		);
		$str = preg_replace(
			'#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)`(.*?)`#si',
			'\\1\\2&#96;\\3&#96;',
			$str
		);
		$str = $this->_do_never_allowed( $str );
		if ( $is_image === true ) {
			return ( $str === $converted_string );
		}

		return $str;
	}

	/**
	 * Do Never Allowed
	 *
	 * @param $str
	 *
	 * @return mixed|string|string[]|null
	 */
	protected function _do_never_allowed( $str ) {
		$str = str_replace( array_keys( $this->_never_allowed_str ), $this->_never_allowed_str, $str );
		foreach ( $this->_never_allowed_regex as $regex ) {
			$str = preg_replace( '#' . $regex . '#is', '[removed]', $str );
		}

		return $str;
	}

	/**
	 * Fetch an item from the GET array
	 *
	 * @param null $index
	 * @param null $default
	 * @param null $xss_clean
	 *
	 * @return mixed
	 */
	function get( $index = null, $default = null, $xss_clean = null ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return $this->_fetch_from_array( $_GET, $index, $default, $xss_clean );
	}

	/**
	 * Fetch an item from GET data with fallback to POST
	 *
	 * @param $index
	 * @param null $xss_clean
	 * @param null $default
	 *
	 * @return mixed
	 */
	function get_post( $index, $default = null, $xss_clean = null ) {
		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $index ] )
			? $this->get( $index, $default, $xss_clean )
			: $this->post( $index, $default, $xss_clean );
	}

	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @param null $index
	 * @param null $default
	 * @param null $xss_clean
	 *
	 * @return mixed
	 */
	function cookie( $index = null, $default = null, $xss_clean = null ) {
		return $this->_fetch_from_array( $_COOKIE, $index, $default, $xss_clean );
	}

	/**
	 * Fetch User Agent string
	 *
	 * @param null $xss_clean
	 * @param null $default
	 *
	 * @return mixed
	 */
	function user_agent( $xss_clean = null, $default = null ) {
		return $this->_fetch_from_array( $_SERVER, 'HTTP_USER_AGENT', $default, $xss_clean );
	}

	/**
	 * Get Request Header
	 *
	 * @param $index
	 * @param bool $xss_clean
	 *
	 * @return string|null
	 */
	function get_request_header( $index, $xss_clean = false ) {
		static $headers;
		if ( ! isset( $headers ) ) {
			empty( $this->headers ) && $this->request_headers();
			foreach ( $this->headers as $key => $value ) {
				$headers[ strtolower( $key ) ] = $value;
			}
		}
		$index = strtolower( $index );
		if ( ! isset( $headers[ $index ] ) ) {
			return null;
		}

		return ( $xss_clean === true )
			? $this->xss_clean( $headers[ $index ] )
			: $headers[ $index ];
	}

	/**
	 * Request Headers
	 *
	 * @param bool $xss_clean
	 *
	 * @return mixed
	 */
	function request_headers( $xss_clean = false ) {
		// If header is already defined, return it immediately
		if ( ! empty( $this->headers ) ) {
			return $this->_fetch_from_array( $this->headers, null, null, $xss_clean );
		}
		// In Apache, you can simply call apache_request_headers()
		if ( function_exists( 'apache_request_headers' ) ) {
			$this->headers = apache_request_headers();
		} else {
			isset( $_SERVER['CONTENT_TYPE'] ) && $this->headers['Content-Type'] = sanitize_text_field( wp_unslash( $_SERVER['CONTENT_TYPE'] ) );
			foreach ( $_SERVER as $key => $val ) {
				if ( sscanf( $key, 'HTTP_%s', $header ) === 1 ) {
					// take SOME_HEADER and turn it into Some-Header
					$header                   = str_replace( '_', ' ', strtolower( $header ) );
					$header                   = str_replace( ' ', '-', ucwords( $header ) );
					$this->headers[ $header ] = isset( $_SERVER[ $key ] ) ? $_SERVER[ $key ] : '';//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				}
			}
		}

		return $this->_fetch_from_array( $this->headers, null, null, $xss_clean );
	}

	/**
	 * Get Request Method
	 *
	 * @param bool $upper
	 *
	 * @return string
	 */
	function method( $upper = false ) {
		return ( $upper )
			? strtoupper( $this->server( 'REQUEST_METHOD' ) )
			: strtolower( $this->server( 'REQUEST_METHOD' ) );
	}

	/**
	 * Fetch an item from the SERVER array
	 *
	 * @param $index
	 * @param null $xss_clean
	 * @param null $default
	 *
	 * @return mixed
	 */
	function server( $index, $default = null, $xss_clean = null ) {
		return $this->_fetch_from_array( $_SERVER, $index, $default, $xss_clean );
	}

	/**
	 * Allows read access to protected properties
	 *
	 * @param $name
	 *
	 * @return false|string
	 */
	function __get( $name ) {
		if ( $name === 'raw_input_stream' ) {
			isset( $this->_raw_input_stream ) or $this->_raw_input_stream = file_get_contents( 'php://input' );

			return $this->_raw_input_stream;
		} elseif ( $name === 'ip_address' ) {
			return $this->ip_address;
		}
	}

	/**
	 * URL-decode taking spaces into account
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	protected function _urldecodespaces( $matches ) {
		$input    = $matches[0];
		$nospaces = preg_replace( '#\s+#', '', $input );

		return ( $nospaces === $input )
			? $input
			: rawurldecode( $nospaces );
	}

	/**
	 * HTML Entity Decode Callback
	 *
	 * @param $match
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function _decode_entity( $match ) {
		// Protect GET variables in URLs
		// 901119URL5918AMP18930PROTECT8198
		$match = preg_replace( '|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i', $this->xss_hash() . '\\1=\\2', $match[0] );

		// Decode, then un-protect URL GET vars
		return str_replace(
			$this->xss_hash(),
			'&',
			$this->entity_decode( $match, $this->charset )
		);
	}

	/**
	 * Generates the XSS hash if needed and returns it.
	 * @return mixed|string
	 * @throws Exception
	 */
	function xss_hash() {
		if ( $this->_xss_hash === null ) {
			$rand            = $this->get_random_bytes( 16 );
			$this->_xss_hash = ( $rand === false )
				? md5( uniqid( wp_rand(), true ) )
				: bin2hex( $rand );
		}

		return $this->_xss_hash;
	}

	/**
	 * Get random bytes
	 *
	 * @param $length
	 *
	 * @return bool|string|void
	 * @throws Exception
	 */
	function get_random_bytes( $length ) {
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( empty( $length ) or ! ctype_digit( (string) $length ) ) {
			return false;
		}
		if ( function_exists( 'random_bytes' ) ) {
			try {
				// The cast is required to avoid TypeError
				return random_bytes( (int) $length );
			} catch ( \Exception $e ) {
				// If random_bytes() can't do the job, we can't either ...
				// There's no point in using fallbacks.
				return false;
			}
		}
		if ( $wp_filesystem->exists( '/dev/urandom' ) ) {
			$output = $wp_filesystem->get_contents( '/dev/urandom', false, $length );
			if ( $output !== false ) {
				return $output;
			}
		}
		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return openssl_random_pseudo_bytes( $length );
		}

		return false;
	}

	/**
	 * Determines if the current version of PHP is equal to or greater than the supplied value
	 *
	 * @param $version
	 *
	 * @return mixed
	 */
	function is_php( $version ) {
		static $_is_php;
		$version = (string) $version;
		if ( ! isset( $_is_php[ $version ] ) ) {
			$_is_php[ $version ] = version_compare( PHP_VERSION, $version, '>=' );
		}

		return $_is_php[ $version ];
	}

	/**
	 * HTML Entities Decode
	 *
	 * @param $str
	 * @param null $charset
	 *
	 * @return mixed|string
	 */
	function entity_decode( $str, $charset = null ) {
		if ( strpos( $str, '&' ) === false ) {
			return $str;
		}
		static $_entities;
		isset( $charset ) or $charset = $this->charset;
		$flag = $this->is_php( '5.4' )
			? ENT_COMPAT | ENT_HTML5
			: ENT_COMPAT;
		if ( ! isset( $_entities ) ) {
			$_entities = array_map( 'strtolower', get_html_translation_table( HTML_ENTITIES, $flag, $charset ) );
			// If we're not on PHP 5.4+, add the possibly dangerous HTML 5
			// entities to the array manually
			if ( $flag === ENT_COMPAT ) {
				$_entities[':']  = '&colon;';
				$_entities['(']  = '&lpar;';
				$_entities[')']  = '&rpar;';
				$_entities["\n"] = '&NewLine;';
				$_entities["\t"] = '&Tab;';
			}
		}
		do {
			$str_compare = $str;
			// Decode standard entities, avoiding false positives
			if ( preg_match_all( '/&[a-z]{2,}(?![a-z;])/i', $str, $matches ) ) {
				$replace = array();
				$matches = array_unique( array_map( 'strtolower', $matches[0] ) );
				foreach ( $matches as &$match ) {
					if ( ( $char = array_search( $match . ';', $_entities, true ) ) !== false ) {
						$replace[ $match ] = $char;
					}
				}
				$str = str_replace( array_keys( $replace ), array_values( $replace ), $str );
			}
			// Decode numeric & UTF16 two byte entities
			$str = html_entity_decode(
				preg_replace( '/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str ),
				$flag,
				$charset
			);
			if ( $flag === ENT_COMPAT ) {
				$str = str_replace( array_values( $_entities ), array_keys( $_entities ), $str );
			}
		} while ( $str_compare !== $str );

		return $str;
	}

	/**
	 * Compact Exploded Words
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	protected function _compact_exploded_words( $matches ) {
		return preg_replace( '/\s+/s', '', $matches[1] ) . $matches[2];
	}

	/**
	 * JS Link Removal
	 *
	 * Callback function for xss_clean() to sanitize links
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on link-heavy strings
	 *
	 * @param array
	 *
	 * @return  string
	 */
	protected function _js_link_removal( $match ) {
		return str_replace(
			$match[1],
			preg_replace(
				'#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
				'',
				$this->_filter_attributes( str_replace( array( '<', '>' ), '', $match[1] ) )
			),
			$match[0]
		);
	}

	/**
	 * Filter Attributes
	 *
	 * Filters tag attributes for consistency and safety
	 *
	 * @param string
	 *
	 * @return  string
	 */
	protected function _filter_attributes( $str ) {
		$out = '';
		if ( preg_match_all( '#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches ) ) {
			foreach ( $matches[0] as $match ) {
				$out .= preg_replace( "#/\*.*?\*/#s", '', $match );
			}
		}

		return $out;
	}

	/**
	 * JS Image Removal
	 *
	 * Callback function for xss_clean() to sanitize image tags
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on image tag heavy strings
	 *
	 * @param array
	 *
	 * @return  string
	 */
	protected function _js_img_removal( $match ) {
		return str_replace(
			$match[1],
			preg_replace(
				'#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
				'',
				$this->_filter_attributes( str_replace( array( '<', '>' ), '', $match[1] ) )
			),
			$match[0]
		);
	}

	/**
	 * Sanitize Naughty HTML
	 *
	 * @param $matches
	 *
	 * @return string
	 */
	protected function _sanitize_naughty_html( $matches ) {
		static $naughty_tags = array(
			'alert',
			'area',
			'prompt',
			'confirm',
			'applet',
			'audio',
			'basefont',
			'base',
			'behavior',
			'bgsound',
			'blink',
			'body',
			'embed',
			'expression',
			'form',
			'frameset',
			'frame',
			'head',
			'html',
			'ilayer',
			'iframe',
			'input',
			'button',
			'select',
			'isindex',
			'layer',
			'link',
			'meta',
			'keygen',
			'object',
			'plaintext',
			'style',
			'script',
			'textarea',
			'title',
			'math',
			'video',
			'svg',
			'xml',
			'xss'
		);
		static $evil_attributes = array(
			'on\w+',
			'style',
			'xmlns',
			'formaction',
			'form',
			'xlink:href',
			'FSCommand',
			'seekSegmentTime'
		);
		// First, escape unclosed tags
		if ( empty( $matches['closeTag'] ) ) {
			return '&lt;' . $matches[1];
		} // Is the element that we caught naughty? If so, escape it
		elseif ( in_array( strtolower( $matches['tagName'] ), $naughty_tags, true ) ) {
			return '&lt;' . $matches[1] . '&gt;';
		} // For other tags, see if their attributes are "evil" and strip those
		elseif ( isset( $matches['attributes'] ) ) {
			// We'll store the already filtered attributes here
			$attributes = array();
			// Attribute-catching pattern
			$attributes_pattern = '#'
			                      . '(?<name>[^\s\042\047>/=]+)' // attribute characters
			                      // optional attribute-value
			                      . '(?:\s*=(?<value>[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*)))' // attribute-value separator
			                      . '#i';
			// Blacklist pattern for evil attribute names
			$is_evil_pattern = '#^(' . implode( '|', $evil_attributes ) . ')$#i';
			// Each iteration filters a single attribute
			do {
				$matches['attributes'] = preg_replace( '#^[^a-z]+#i', '', $matches['attributes'] );
				if ( ! preg_match( $attributes_pattern, $matches['attributes'], $attribute, PREG_OFFSET_CAPTURE ) ) {
					// No (valid) attribute found? Discard everything else inside the tag
					break;
				}
				if (
					// Is it indeed an "evil" attribute?
					preg_match( $is_evil_pattern, $attribute['name'][0] )
					// Or does it have an equals sign, but no value and not quoted? Strip that too!
					or ( trim( $attribute['value'][0] ) === '' )
				) {
					$attributes[] = 'xss=removed';
				} else {
					$attributes[] = $attribute[0][0];
				}
				$matches['attributes'] = substr( $matches['attributes'], $attribute[0][1] + strlen( $attribute[0][0] ) );
			} while ( $matches['attributes'] !== '' );
			$attributes = empty( $attributes )
				? ''
				: ' ' . implode( ' ', $attributes );

			return '<' . $matches['slash'] . $matches['tagName'] . $attributes . '>';
		}

		return $matches[0];
	}

	/**
	 * Attribute Conversion
	 *
	 * @param $match
	 *
	 * @return mixed
	 */
	protected function _convert_attribute( $match ) {
		return str_replace( array( '>', '<', '\\' ), array( '&gt;', '&lt;', '\\\\' ), $match[0] );
	}
}
