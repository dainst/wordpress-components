<?php

namespace WeglotWP\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 2.4.0
 */
abstract class Helper_Keys_Json_Weglot {
	/**
	 * @var array
	 */
	protected static $keys = [
		'contact-form-7' => [
			'message'
		],
	];

	/**
	 * @since 2.4.0
	 * @return array
	 */
	public static function get_keys() {
		return apply_filters( 'weglot_keys_translate_json', self::$keys );
	}

	/**
	 * @since 2.4.0
	 * @param string $key
	 * @return bool
	 */
	public static function translate_key_for_path( $key ) {
		$keys_translate = self::get_keys();
		$path           = weglot_get_rest_current_url_path();

		foreach ( $keys_translate as $key_translate => $value) {
			if ( empty( $key_translate ) || strpos( $path, $key_translate ) === false) {
				continue;
			}

			if ( in_array( $key, $keys_translate[ $key_translate ], true ) ) {
				return true;
			}
		}

		return false;
	}
}
