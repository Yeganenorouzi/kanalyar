<?php
/**
 * موتور قالب پیام.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * جای‌گذاری جای‌نشان‌ها، با دو خروجی: مارک‌داون و متن ساده.
 *
 * پیام‌رسان‌هایی مثل بله و تلگرام قالب‌بندی می‌فهمند و ایتا نه؛ پس هر
 * پیام در هر دو شکل ساخته می‌شود و هر مقصد نسخه‌ی خودش را می‌گیرد.
 */
class KY_Template {

	/**
	 * جای‌گذاری جای‌نشان‌ها در یک قالب.
	 *
	 * @param string $template قالب.
	 * @param array  $vars     مقادیر، هر کدام آرایه‌ای با کلیدهای md و plain.
	 * @param bool   $markdown آیا خروجی مارک‌داون باشد.
	 * @return string
	 */
	public static function render( $template, array $vars, $markdown ) {
		$sentinels = array();
		$values    = array();
		$index     = 0;

		/*
		 * مقادیر ابتدا با یک نشانه‌ی میانی جایگزین می‌شوند تا پاک‌سازیِ
		 * نشانه‌های مارک‌داونِ قالب به محتوای خودِ مقادیر دست نزند.
		 * بدون این کار، زیرخطِ داخل هشتگ‌ها هم حذف می‌شد.
		 */
		foreach ( $vars as $key => $pair ) {
			$token = "\x00" . $index . "\x00";

			$sentinels[ $key ] = $token;
			$values[ $token ]  = $markdown ? $pair['md'] : $pair['plain'];
			$index++;
		}

		$text = strtr( (string) $template, $sentinels );

		if ( ! $markdown ) {
			// نشانه‌های مارک‌داونِ خودِ قالب در حالت ساده معنی ندارند.
			$text = preg_replace( '/[*_`]/u', '', $text );
		}

		$text = strtr( $text, $values );

		// جای‌نشان‌های خالی نباید خط سفید جا بگذارند.
		$text = preg_replace( "/[ \t]+\n/", "\n", $text );
		$text = preg_replace( "/\n{3,}/", "\n\n", $text );

		return trim( $text );
	}

	/**
	 * ساخت جفتِ «مارک‌داون / ساده» از یک مقدار پویا.
	 *
	 * نسخه‌ی مارک‌داون خنثی‌سازی می‌شود تا نشانه‌های داخل متن
	 * قالب‌بندی پیام را نشکنند.
	 *
	 * @param string $value مقدار.
	 * @return array{md:string,plain:string}
	 */
	public static function pair( $value ) {
		$value = (string) $value;

		return array(
			'md'    => self::escape( $value ),
			'plain' => $value,
		);
	}

	/**
	 * مقداری که خودش مارک‌داون دارد و نباید خنثی شود.
	 *
	 * @param string $md    نسخه‌ی مارک‌داون.
	 * @param string $plain نسخه‌ی ساده.
	 * @return array{md:string,plain:string}
	 */
	public static function raw( $md, $plain ) {
		return array(
			'md'    => (string) $md,
			'plain' => (string) $plain,
		);
	}

	/**
	 * خنثی‌کردن کاراکترهای مارک‌داون در متن پویا.
	 *
	 * بدون این کار، عنوانی که _ یا * دارد باعث خطای
	 * «can't parse entities» و ازدست‌رفتن کل پیام می‌شود.
	 *
	 * @param string $text متن.
	 * @return string
	 */
	public static function escape( $text ) {
		return str_replace(
			array( '\\', '*', '_', '`', '[', ']' ),
			array( '\\\\', '\*', '\_', '\`', '\[', '\]' ),
			(string) $text
		);
	}

	/**
	 * قالب‌بندی عدد.
	 *
	 * @param float $amount مبلغ.
	 * @return string
	 */
	public static function money( $amount ) {
		return number_format( (float) $amount, 0, '.', ',' );
	}
}
