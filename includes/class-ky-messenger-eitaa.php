<?php
/**
 * کلاینت پیام‌رسان ایتا.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * ایتا، از طریق سرویس eitaayar.ir.
 *
 * برخلاف بله و تلگرام، API ایتا فقط ارسال است: نه دکمه‌ی شیشه‌ای دارد،
 * نه قالب‌بندی مارک‌داون. برای همین متن ساده فرستاده می‌شود و نشانی
 * مطلب در خود متن می‌آید، نه روی دکمه.
 *
 * توکن را از بخش API در eitaayar.ir می‌گیرید؛ این توکن با توکن
 * BotFather فرق دارد.
 */
class KY_Messenger_Eitaa extends KY_Messenger {

	/**
	 * ریشه‌ی API ایتا.
	 *
	 * @return string
	 */
	public function endpoint() {
		/**
		 * بازنویسی نشانی API ایتا.
		 *
		 * @param string $endpoint نشانی پیش‌فرض.
		 */
		return (string) apply_filters( 'ky_eitaa_endpoint', 'https://eitaayar.ir/api/' );
	}

	/**
	 * شناسه‌ی ماشینی.
	 *
	 * @return string
	 */
	public function platform() {
		return 'eitaa';
	}

	/**
	 * نام نمایشی.
	 *
	 * @return string
	 */
	public function label() {
		return __( 'ایتا', 'kanalyar' );
	}

	/**
	 * ایتا قالب‌بندی مارک‌داون ندارد.
	 *
	 * @return bool
	 */
	public function supports_markdown() {
		return false;
	}

	/**
	 * ایتا دکمه‌ی شیشه‌ای ندارد.
	 *
	 * @return bool
	 */
	public function supports_buttons() {
		return false;
	}

	/**
	 * بررسی سلامت اتصال.
	 *
	 * ایتا متد getMe ندارد؛ تنها راه مطمئن، یک ارسال واقعی است.
	 * پس اینجا فقط تنظیم‌بودن توکن و کانال را گزارش می‌کنیم.
	 *
	 * @return array
	 */
	public function check() {
		if ( ! $this->is_configured() ) {
			return array(
				'ok'    => false,
				'error' => __( 'توکن یا آیدی کانال تنظیم نشده است.', 'kanalyar' ),
			);
		}

		return array(
			'ok'     => true,
			'result' => array(
				'first_name' => __( 'ایتا — صحت توکن فقط با ارسال واقعی مشخص می‌شود', 'kanalyar' ),
			),
		);
	}

	/**
	 * ارسال پیام متنی.
	 *
	 * @param string     $text         متن پیام.
	 * @param array|null $reply_markup نادیده گرفته می‌شود.
	 * @return array
	 */
	public function send_message( $text, $reply_markup = null ) {
		$payload = array(
			'chat_id' => $this->chat_id,
			'text'    => $this->trim_to( $text, self::MAX_TEXT ),
		);

		if ( $this->silent ) {
			$payload['disable_notification'] = 'true';
		}

		if ( $this->pin ) {
			$payload['pin'] = '1';
		}

		return $this->request_json( 'sendMessage', $payload );
	}

	/**
	 * ایتا تصویر را از روی لینک نمی‌گیرد؛ مستقیم به آپلود می‌رویم.
	 *
	 * @param string     $photo_url    نشانی تصویر.
	 * @param string     $caption      کپشن.
	 * @param array|null $reply_markup نادیده گرفته می‌شود.
	 * @return array
	 */
	public function send_photo_by_url( $photo_url, $caption, $reply_markup = null ) {
		return array(
			'ok'    => false,
			'error' => __( 'ایتا ارسال تصویر با لینک را پشتیبانی نمی‌کند.', 'kanalyar' ),
		);
	}

	/**
	 * ارسال فایل با آپلود مستقیم.
	 *
	 * @param string     $file_path    مسیر فایل روی سرور.
	 * @param string     $caption      کپشن.
	 * @param array|null $reply_markup نادیده گرفته می‌شود.
	 * @return array
	 */
	public function send_photo_upload( $file_path, $caption, $reply_markup = null ) {
		if ( ! $file_path || ! is_readable( $file_path ) ) {
			return array(
				'ok'    => false,
				'error' => __( 'فایل تصویر روی سرور خوانده نشد.', 'kanalyar' ),
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$bits = file_get_contents( $file_path );

		if ( false === $bits || '' === $bits ) {
			return array(
				'ok'    => false,
				'error' => __( 'فایل تصویر خالی است.', 'kanalyar' ),
			);
		}

		$fields = array(
			'chat_id' => $this->chat_id,
			'caption' => $this->trim_to( $caption, self::MAX_CAPTION ),
		);

		if ( $this->silent ) {
			$fields['disable_notification'] = 'true';
		}

		if ( $this->pin ) {
			$fields['pin'] = '1';
		}

		$type = wp_check_filetype( $file_path );

		$file = array(
			'name'  => basename( $file_path ),
			'type'  => $type['type'] ? $type['type'] : 'application/octet-stream',
			'bits'  => $bits,
			'field' => 'file',
		);

		return $this->request_multipart( 'sendFile', $fields, $file );
	}
}
