<?php
/**
 * مقصدهای ارسال (کانال‌های بله و تلگرام).
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * نگه‌داری فهرست کانال‌هایی که محتوا به آن‌ها می‌رود.
 */
class KY_Destinations {

	/**
	 * نام آپشن.
	 */
	const OPTION = 'dsb_destinations';

	/**
	 * کش درون‌درخواستی.
	 *
	 * @var array|null
	 */
	private $cache = null;

	/**
	 * ساختار پیش‌فرض یک مقصد.
	 *
	 * @return array
	 */
	public static function blank() {
		return array(
			'id'       => '',
			'label'    => '',
			'platform' => 'bale',
			'token'    => '',
			'chat_id'  => '',
			'enabled'  => 1,
		);
	}

	/**
	 * همه‌ی مقصدها.
	 *
	 * @return array[]
	 */
	public function all() {
		if ( null === $this->cache ) {
			$stored = get_option( self::OPTION, array() );
			$stored = is_array( $stored ) ? $stored : array();

			$this->cache = array();

			foreach ( $stored as $row ) {
				if ( is_array( $row ) && ! empty( $row['id'] ) ) {
					$this->cache[] = wp_parse_args( $row, self::blank() );
				}
			}
		}

		return $this->cache;
	}

	/**
	 * فقط مقصدهای فعال و پیکربندی‌شده.
	 *
	 * @return array[]
	 */
	public function active() {
		return array_values(
			array_filter(
				$this->all(),
				function ( $row ) {
					return ! empty( $row['enabled'] ) && '' !== $row['token'] && '' !== $row['chat_id'];
				}
			)
		);
	}

	/**
	 * یک مقصد بر اساس شناسه.
	 *
	 * @param string $id شناسه.
	 * @return array|null
	 */
	public function get( $id ) {
		foreach ( $this->all() as $row ) {
			if ( $row['id'] === $id ) {
				return $row;
			}
		}

		return null;
	}

	/**
	 * چند مقصد بر اساس فهرست شناسه، فقط فعال‌ها.
	 *
	 * @param array $ids شناسه‌ها.
	 * @return array[]
	 */
	public function pick( array $ids ) {
		$out = array();

		foreach ( $this->active() as $row ) {
			if ( in_array( $row['id'], $ids, true ) ) {
				$out[] = $row;
			}
		}

		return $out;
	}

	/**
	 * ذخیره‌ی فهرست مقصدها.
	 *
	 * @param array[] $rows مقصدهای پاک‌سازی‌شده.
	 * @return void
	 */
	public function save( array $rows ) {
		update_option( self::OPTION, array_values( $rows ), true );
		$this->cache = null;
	}

	/**
	 * ساخت کلاینت پیام‌رسان برای یک مقصد.
	 *
	 * @param array $destination مقصد.
	 * @param int   $retries     تلاش مجدد.
	 * @return KY_Messenger
	 */
	public static function messenger( array $destination, $retries = 2 ) {
		return KY_Messenger::make(
			$destination['platform'],
			$destination['token'],
			$destination['chat_id'],
			$retries
		);
	}

	/**
	 * پاک‌سازی ورودی فرم مقصدها.
	 *
	 * @param array $raw داده‌ی خام.
	 * @return array[]
	 */
	public function sanitize( array $raw ) {
		$platforms = KY_Messenger::platforms();
		$out       = array();
		$seen      = array();

		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$token   = sanitize_text_field( $row['token'] ?? '' );
			$chat_id = sanitize_text_field( $row['chat_id'] ?? '' );
			$label   = sanitize_text_field( $row['label'] ?? '' );

			// ردیف کاملاً خالی یعنی کاربر اضافه‌اش کرده و پرش نکرده.
			if ( '' === $token && '' === $chat_id && '' === $label ) {
				continue;
			}

			$id = sanitize_key( $row['id'] ?? '' );

			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = self::new_id();
			}

			$seen[ $id ] = true;

			$platform = sanitize_key( $row['platform'] ?? 'bale' );

			$out[] = array(
				'id'       => $id,
				'label'    => '' !== $label ? $label : $chat_id,
				'platform' => isset( $platforms[ $platform ] ) ? $platform : 'bale',
				'token'    => $token,
				'chat_id'  => $chat_id,
				'enabled'  => empty( $row['enabled'] ) ? 0 : 1,
			);
		}

		return $out;
	}

	/**
	 * ساخت شناسه‌ی یکتا.
	 *
	 * @return string
	 */
	public static function new_id() {
		return 'dst_' . substr( md5( uniqid( '', true ) ), 0, 10 );
	}
}
