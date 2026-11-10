<?php
/**
 * کلاینت HTTP پیام‌رسان بله.
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * ارسال درخواست به Bot API بله، با تلاش مجدد و پشتیبانی از آپلود فایل.
 */
class DSB_Bale_API {

	/**
	 * ریشه‌ی API.
	 */
	const ENDPOINT = 'https://tapi.bale.ai/bot';

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
	private $token;

	/**
	 * شناسه‌ی کانال.
	 *
	 * @var string
	 */
	private $channel;

	/**
	 * تعداد تلاش مجدد در خطاهای گذرا.
	 *
	 * @var int
	 */
	private $retries;

	/**
	 * سازنده.
	 *
	 * @param string $token   توکن بات.
	 * @param string $channel شناسه‌ی کانال.
	 * @param int    $retries تعداد تلاش مجدد.
	 */
	public function __construct( $token, $channel, $retries = 2 ) {
		$this->token   = trim( (string) $token );
		$this->channel = trim( (string) $channel );
		$this->retries = max( 0, (int) $retries );
	}

	/**
	 * آیا توکن و کانال تنظیم شده‌اند.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->token && '' !== $this->channel;
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
			'chat_id'    => $this->channel,
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
			'chat_id'    => $this->channel,
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
	 * وقتی بله نتواند لینک تصویر را از سایت بگیرد
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
				'error' => __( 'فایل تصویر روی سرور خوانده نشد.', 'daily-bale-post' ),
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$bits = file_get_contents( $file_path );

		if ( false === $bits || '' === $bits ) {
			return array(
				'ok'    => false,
				'error' => __( 'فایل تصویر خالی است.', 'daily-bale-post' ),
			);
		}

		$fields = array(
			'chat_id'    => $this->channel,
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
	private function request_json( $method, array $payload ) {
		if ( ! $this->is_configured() ) {
			return array(
				'ok'    => false,
				'error' => __( 'توکن یا آیدی کانال تنظیم نشده است.', 'daily-bale-post' ),
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
	private function request_multipart( $method, array $fields, array $file ) {
		if ( ! $this->is_configured() ) {
			return array(
				'ok'    => false,
				'error' => __( 'توکن یا آیدی کانال تنظیم نشده است.', 'daily-bale-post' ),
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
	private function dispatch( $method, array $args ) {
		$url      = self::ENDPOINT . $this->token . '/' . $method;
		$attempts = $this->retries + 1;
		$last     = array(
			'ok'    => false,
			'error' => __( 'پاسخی از بله دریافت نشد.', 'daily-bale-post' ),
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
	private function trim_to( $text, $limit ) {
		$text = (string) $text;

		if ( mb_strlen( $text ) <= $limit ) {
			return $text;
		}

		return rtrim( mb_substr( $text, 0, $limit - 1 ) ) . '…';
	}

	/**
	 * استخراج پیام خطای خوانا از پاسخ بله.
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

		return __( 'خطای نامشخص', 'daily-bale-post' );
	}
}
