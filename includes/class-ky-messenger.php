<?php
/**
 * پایه‌ی کلاینت پیام‌رسان‌ها.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * رفتار مشترک بله و تلگرام.
 *
 * هر دو از Bot API تلگرام پیروی می‌کنند و تفاوتشان عملاً فقط
 * نشانی سرور است؛ پس منطق ارسال، تلاش مجدد و آپلود فایل اینجا
 * یک‌بار نوشته شده و کلاس‌های فرزند فقط نشانی و نام را می‌دهند.
 */
abstract class KY_Messenger {

	/**
	 * بیشینه‌ی طول متن پیام.
	 */
	const MAX_TEXT = 4096;

	/**
	 * بیشینه‌ی طول کپشن تصویر.
	 */
	const MAX_CAPTION = 1024;

	/**
	 * توکن بات.
	 *
	 * @var string
	 */
	protected $token;

	/**
	 * شناسه‌ی کانال یا گروه.
	 *
	 * @var string
	 */
	protected $chat_id;

	/**
	 * تعداد تلاش مجدد در خطاهای گذرا.
	 *
	 * @var int
	 */
	protected $retries;

	/**
	 * سازنده.
	 *
	 * @param string $token   توکن بات.
	 * @param string $chat_id شناسه‌ی کانال.
	 * @param int    $retries تعداد تلاش مجدد.
	 */
	public function __construct( $token, $chat_id, $retries = 2 ) {
		$this->token   = trim( (string) $token );
		$this->chat_id = trim( (string) $chat_id );
		$this->retries = max( 0, (int) $retries );
	}

	/**
	 * ریشه‌ی API این پیام‌رسان.
	 *
	 * @return string
	 */
	abstract public function endpoint();

	/**
	 * شناسه‌ی ماشینی پیام‌رسان.
	 *
	 * @return string
	 */
	abstract public function platform();

	/**
	 * نام نمایشی پیام‌رسان.
	 *
	 * @return string
	 */
	abstract public function label();

	/**
	 * ساخت کلاینت بر اساس نام پلتفرم.
	 *
	 * @param string $platform bale یا telegram.
	 * @param string $token    توکن.
	 * @param string $chat_id  شناسه‌ی کانال.
	 * @param int    $retries  تلاش مجدد.
	 * @return KY_Messenger
	 */
	public static function make( $platform, $token, $chat_id, $retries = 2 ) {
		if ( 'telegram' === $platform ) {
			return new KY_Messenger_Telegram( $token, $chat_id, $retries );
		}

		return new KY_Messenger_Bale( $token, $chat_id, $retries );
	}

	/**
	 * فهرست پیام‌رسان‌های پشتیبانی‌شده.
	 *
	 * @return array<string,string>
	 */
	public static function platforms() {
		return array(
			'bale'     => __( 'بله', 'kanalyar' ),
			'telegram' => __( 'تلگرام', 'kanalyar' ),
		);
	}

	/**
	 * آیا توکن و کانال تنظیم شده‌اند.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->token && '' !== $this->chat_id;
	}

	/**
	 * بررسی سلامت اتصال.
	 *
	 * @return array پاسخ getMe.
	 */
	public function check() {
		return $this->dispatch(
			'getMe',
			array(
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( new stdClass() ),
				'timeout'     => 20,
				'data_format' => 'body',
			)
		);
	}

	/**
	 * ارسال پیام متنی.
	 *
	 * @param string     $text         متن پیام.
	 * @param array|null $reply_markup دکمه‌های شیشه‌ای.
	 * @return array
	 */
	public function send_message( $text, $reply_markup = null ) {
		$payload = array(
			'chat_id'    => $this->chat_id,
			'text'       => $this->trim_to( $text, self::MAX_TEXT ),
			'parse_mode' => 'Markdown',
		);

		if ( $reply_markup ) {
			$payload['reply_markup'] = $reply_markup;
		}

		return $this->request_json( 'sendMessage', $payload );
	}

	/**
	 * ارسال تصویر با لینک.
	 *
	 * @param string     $photo_url    نشانی تصویر.
	 * @param string     $caption      کپشن.
	 * @param array|null $reply_markup دکمه‌های شیشه‌ای.
	 * @return array
	 */
	public function send_photo_by_url( $photo_url, $caption, $reply_markup = null ) {
		$payload = array(
			'chat_id'    => $this->chat_id,
			'photo'      => $photo_url,
			'caption'    => $this->trim_to( $caption, self::MAX_CAPTION ),
			'parse_mode' => 'Markdown',
		);

		if ( $reply_markup ) {
			$payload['reply_markup'] = $reply_markup;
		}

		return $this->request_json( 'sendPhoto', $payload );
	}

	/**
	 * ارسال تصویر با آپلود مستقیم فایل (multipart).
	 *
	 * وقتی سرور پیام‌رسان نتواند لینک تصویر را از سایت بگیرد
	 * (خطای failed to get HTTP URL content) این مسیر جواب می‌دهد.
	 *
	 * @param string     $file_path    مسیر فایل روی سرور.
	 * @param string     $caption      کپشن.
	 * @param array|null $reply_markup دکمه‌های شیشه‌ای.
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
			'chat_id'    => $this->chat_id,
			'caption'    => $this->trim_to( $caption, self::MAX_CAPTION ),
			'parse_mode' => 'Markdown',
		);

		if ( $reply_markup ) {
			$fields['reply_markup'] = wp_json_encode( $reply_markup );
		}

		$type = wp_check_filetype( $file_path );

		$file = array(
			'name' => basename( $file_path ),
			'type' => $type['type'] ? $type['type'] : 'application/octet-stream',
			'bits' => $bits,
		);

		return $this->request_multipart( 'sendPhoto', $fields, $file );
	}

	/**
	 * درخواست JSON.
	 *
	 * @param string $method  متد API.
	 * @param array  $payload بدنه.
	 * @return array
	 */
	protected function request_json( $method, array $payload ) {
		if ( ! $this->is_configured() ) {
			return array(
				'ok'    => false,
				'error' => __( 'توکن یا آیدی کانال تنظیم نشده است.', 'kanalyar' ),
			);
		}

		return $this->dispatch(
			$method,
			array(
				'headers'     => array( 'Content-Type' => 'application/json' ),
				'body'        => wp_json_encode( $payload ),
				'timeout'     => 30,
				'data_format' => 'body',
			)
		);
	}

	/**
	 * درخواست multipart برای آپلود فایل.
	 *
	 * @param string $method متد API.
	 * @param array  $fields فیلدهای متنی.
	 * @param array  $file   آرایه‌ی name/type/bits.
	 * @return array
	 */
	protected function request_multipart( $method, array $fields, array $file ) {
		if ( ! $this->is_configured() ) {
			return array(
				'ok'    => false,
				'error' => __( 'توکن یا آیدی کانال تنظیم نشده است.', 'kanalyar' ),
			);
		}

		$boundary = wp_generate_password( 24, false );
		$eol      = "\r\n";
		$body     = '';

		foreach ( $fields as $name => $value ) {
			$body .= '--' . $boundary . $eol;
			$body .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
			$body .= $value . $eol;
		}

		$body .= '--' . $boundary . $eol;
		$body .= 'Content-Disposition: form-data; name="photo"; filename="' . $file['name'] . '"' . $eol;
		$body .= 'Content-Type: ' . $file['type'] . $eol . $eol;
		$body .= $file['bits'] . $eol;
		$body .= '--' . $boundary . '--' . $eol;

		return $this->dispatch(
			$method,
			array(
				'headers'     => array( 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ),
				'body'        => $body,
				'timeout'     => 60,
				'data_format' => 'body',
			)
		);
	}

	/**
	 * اجرای درخواست با تلاش مجدد روی خطاهای گذرا.
	 *
	 * خطاهای ۴xx دائمی‌اند و تکرار نمی‌شوند؛ قطعی شبکه و خطاهای ۵xx تکرار می‌شوند.
	 *
	 * @param string $method متد API.
	 * @param array  $args   آرگومان‌های wp_remote_post.
	 * @return array
	 */
	protected function dispatch( $method, array $args ) {
		$url      = $this->endpoint() . $this->token . '/' . $method;
		$attempts = $this->retries + 1;
		$last     = array(
			'ok'    => false,
			'error' => __( 'پاسخی دریافت نشد.', 'kanalyar' ),
		);

		for ( $attempt = 1; $attempt <= $attempts; $attempt++ ) {
			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				$last = array(
					'ok'    => false,
					'error' => $response->get_error_message(),
				);
			} else {
				$code = (int) wp_remote_retrieve_response_code( $response );
				$raw  = wp_remote_retrieve_body( $response );
				$body = json_decode( $raw, true );

				if ( is_array( $body ) && ! empty( $body['ok'] ) ) {
					return $body;
				}

				$last = is_array( $body )
					? array_merge( array( 'ok' => false ), $body )
					: array(
						'ok'  => false,
						'raw' => $raw,
					);

				$last['http_code'] = $code;

				// خطای سمت درخواست؛ تکرارش فایده‌ای ندارد.
				if ( $code >= 400 && $code < 500 ) {
					return $last;
				}
			}

			if ( $attempt < $attempts ) {
				sleep( 2 );
			}
		}

		return $last;
	}

	/**
	 * کوتاه‌کردن امن متن به حد مجاز.
	 *
	 * @param string $text  متن.
	 * @param int    $limit حد مجاز.
	 * @return string
	 */
	protected function trim_to( $text, $limit ) {
		$text = (string) $text;

		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}

		return rtrim( mb_substr( $text, 0, $limit - 1 ) ) . '…';
	}

	/**
	 * استخراج پیام خطای خوانا از پاسخ.
	 *
	 * @param array $result پاسخ.
	 * @return string
	 */
	public static function error_message( $result ) {
		if ( ! empty( $result['error'] ) ) {
			return (string) $result['error'];
		}

		if ( ! empty( $result['description'] ) ) {
			return (string) $result['description'];
		}

		if ( ! empty( $result['raw'] ) ) {
			return (string) $result['raw'];
		}

		return __( 'خطای نامشخص', 'kanalyar' );
	}
}
