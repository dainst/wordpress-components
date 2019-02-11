<?php

namespace WeglotWP\Third\NinjaForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Weglot\Client\Api\WordCollection;
use Weglot\Client\Api\WordEntry;
use Weglot\Client\Api\Enum\WordType;
use Weglot\Client\Client;
use Weglot\Client\Endpoint\Translate;
use Weglot\Client\Api\TranslateEntry;
use Weglot\Client\Api\Enum\BotType;

use WeglotWP\Helpers\Helper_Json_Inline_Weglot;



/**
 * Ninja_Translate_Json_Weglot
 *
 * @since 2.5.0
 */
class Ninja_Translate_Json_Weglot {
	protected function translate_entries( $all_words ) {
		// TranslateEntry
		$params = [
			'language_from' => weglot_get_original_language(),
			'language_to'   => weglot_get_current_language(),
			'request_url'   => weglot_get_current_full_url(),
			'bot'           => BotType::HUMAN,
		];

		$translate = new TranslateEntry( $params );

		$word_collection = $translate->getInputWords();
		foreach ( $all_words as $value ) {
			$value = Helper_Json_Inline_Weglot::format_for_api( $value );
			$word_collection->addOne( new WordEntry( $value, WordType::TEXT ) );
		}

		$client    = new Client( weglot_get_option( 'api_key' ) );
		$translate = new Translate( $translate, $client );

		return $translate->handle();
	}

	/**
	 * @since 2.5.0
	 *
	 * @param string $content
	 * @return array
	 */
	protected function translate_i18n( $content ) {
		if ( ! apply_filters( 'weglot_ninja_forms_translate_i18n', false ) ) {
			return $content;
		}

		preg_match( '#nfi18n(.*?);#', $content, $match );

		if ( ! isset( $match[1] ) ) {
			return $content;
		}

		$regex = apply_filters( 'weglot_ninja_forms_translate_i18n', '#(title|changeEmailErrorMsg|changeDateErrorMsg|confirmFieldErrorMsg|fieldNumberNumMinError|fieldNumberNumMaxError|fieldNumberIncrementBy|fieldTextareaRTEInsertLink|fieldTextareaRTEInsertMedia|fieldTextareaRTESelectAFile|formErrorsCorrectErrors|validateRequiredField|honeypotHoneypotError|fileUploadOldCodeFileUploadInProgress|previousMonth|nextMonth|fieldsMarkedRequired|fileUploadOldCodeFileUpload)":"(.*?)",#' );
		preg_match_all( $regex, $match[1], $all );

		if ( empty( $all[2] ) ) {
			return $content;
		}

		$object = $this->translate_entries( $all[2] );

		foreach ( $object->getInputWords() as $key => $input_word ) {
			$from_input_encoding    = apply_filters( 'weglot_ninja_translate_need_json_encode', true );
			$to_output_encoding     = apply_filters( 'weglot_ninja_translate_need_json_encode', true );
			$from_input             = $input_word->getWord();
			$to_output              = $object->getOutputWords()[ $key ]->getWord();

			if ( '' === $from_input ) {
				continue;
			}

			if ( $from_input_encoding ) {
				$from_input          = Helper_Json_Inline_Weglot::need_json_encode_api( $from_input );
			}
			if ( $to_output_encoding ) {
				$to_output          = Helper_Json_Inline_Weglot::need_json_encode_api( $to_output );
			}

			$content    = str_replace( '"' . $from_input . '"', '"' . $to_output . '"', $content );
		}

		return $content;
	}

	/**
	 * @since 2.5.0
	 * @param string $content
	 * @return array
	 */
	protected function translate_form_settings( $content ) {
		$regex = apply_filters( 'weglot_ninja_forms_match_form_settings', '#form\.settings=(.*?);#' );
		preg_match( $regex, $content, $match );
		if ( ! isset( $match[1] ) ) {
			return $content;
		}

		$regex = apply_filters( 'weglot_ninja_forms_translate_form_settings', '#(title|changeEmailErrorMsg|changeDateErrorMsg|confirmFieldErrorMsg|fieldNumberNumMinError|fieldNumberNumMaxError|fieldNumberIncrementBy|fieldTextareaRTEInsertLink|fieldTextareaRTEInsertMedia|fieldTextareaRTESelectAFile|formErrorsCorrectErrors|validateRequiredField|honeypotHoneypotError|fileUploadOldCodeFileUploadInProgress|previousMonth|nextMonth|fieldsMarkedRequired)":"(.*?)",#' );
		preg_match_all( $regex, $match[1], $all );

		if ( empty( $all[2] ) ) {
			return $content;
		}

		$object = $this->translate_entries( $all[2] );

		foreach ( $object->getInputWords() as $key => $input_word ) {
			$from_input_encoding = apply_filters( 'weglot_ninja_translate_need_json_encode', true );
			$to_output_encoding  = apply_filters( 'weglot_ninja_translate_need_json_encode', true );
			$from_input          = $input_word->getWord();
			$to_output           = $object->getOutputWords()[ $key ]->getWord();
			if ( '' === $from_input ) {
				continue;
			}

			if ( $from_input_encoding ) {
				$from_input          = Helper_Json_Inline_Weglot::need_json_encode_api( $from_input );
			}
			if ( $to_output_encoding ) {
				$to_output          = Helper_Json_Inline_Weglot::need_json_encode_api( $to_output );
			}

			$content    = str_replace( '"' . $from_input . '"', '"' . $to_output . '"', $content );
		}

		return $content;
	}


	/**
	 * @since 2.5.0
	 * @param string $content
	 * @return array
	 */
	protected function translate_form_fields( $content ) {
		$regex = apply_filters( 'weglot_ninja_forms_match_form_fields', '#form\.fields=(.*?);#' );
		preg_match( $regex, $content, $match );

		if ( ! isset( $match[1] ) ) {
			return $content;
		}

		$regex = apply_filters( 'weglot_ninja_forms_translate_form_fields', '#(label|help_text|value)":"(.*?)",#' );
		preg_match_all( $regex, $match[1], $all );
		if ( empty( $all[1] ) ) {
			return $content;
		}

		$object = $this->translate_entries( $all[2] );

		foreach ( $object->getInputWords() as $key => $input_word ) {
			$from_input_encoding = apply_filters( 'weglot_ninja_translate_need_json_encode', true );
			$to_output_encoding  = apply_filters( 'weglot_ninja_translate_need_json_encode', true );
			$from_input          = $input_word->getWord();
			$to_output           = $object->getOutputWords()[ $key ]->getWord();
			if ( '' === $from_input ) {
				continue;
			}

			if ( $from_input_encoding ) {
				$from_input          = Helper_Json_Inline_Weglot::need_json_encode_api( $from_input );
			}
			if ( $to_output_encoding ) {
				$to_output          = Helper_Json_Inline_Weglot::need_json_encode_api( $to_output );
			}

			$content    = str_replace( '"' . $from_input . '"', '"' . $to_output . '"', $content );
		}

		return $content;
	}

	/**
	 * @since 2.5.0
	 * @param string $content
	 * @return string
	 */
	public function translate_words( $content ) {
		$content = $this->translate_form_settings( $content );
		$content = $this->translate_form_fields( $content );
		$content = $this->translate_i18n( $content );

		return $content;
	}
}
