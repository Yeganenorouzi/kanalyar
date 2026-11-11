<?php
/**
 * ساخت متن اعلان سفارش.
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

/**
 * تبدیل یک سفارش ووکامرس به پیام آماده‌ی ارسال.
 */
class KY_Order_Message {

	/**
	 * تنظیمات اعلان سفارش.
	 *
	 * @var array
	 */
	private $config;

	/**
	 * سازنده.
	 *
	 * @param array $config تنظیمات اعلان.
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/**
	 * ساخت پیام از یک سفارش.
	 *
	 * @param WC_Order $order  سفارش.
	 * @param string   $status وضعیت تازه.
	 * @param string   $from   وضعیت قبلی.
	 * @return array{order_number:string,text:string,plain:string,reply_markup:array|null}
	 */
	public function build( $order, $status, $from = '' ) {
		$template = trim( (string) ( $this->config['template'] ?? '' ) );

		if ( '' === $template ) {
			$template = KY_Orders::default_template();
		}

		$number = (string) $order->get_order_number();
		$link   = $order->get_edit_order_url();
		$vars   = $this->vars( $order, $status, $from, $number, $link );

		$markup = null;

		if ( $link ) {
			$markup = array(
				'inline_keyboard' => array(
					array(
						array(
							'text' => __( '🧾 مشاهده سفارش', 'kanalyar' ),
							'url'  => $link,
						),
					),
				),
			);
		}

		return array(
			'order_number' => $number,
			'text'         => KY_Template::render( $template, $vars, true ),
			'plain'        => KY_Template::render( $template, $vars, false ),
			'reply_markup' => $markup,
		);
	}

	/**
	 * ساخت مقادیر جای‌نشان‌ها.
	 *
	 * @param WC_Order $order  سفارش.
	 * @param string   $status وضعیت تازه.
	 * @param string   $from   وضعیت قبلی.
	 * @param string   $number شماره‌ی سفارش.
	 * @param string   $link   نشانی سفارش در پیشخوان.
	 * @return array
	 */
	private function vars( $order, $status, $from, $number, $link ) {
		$statuses = KY_Orders::statuses();

		$vars = array(
			'{order_number}'    => KY_Template::pair( $number ),
			'{status}'          => KY_Template::pair( $status ),
			'{status_label}'    => KY_Template::pair( $statuses[ $status ] ?? $status ),
			'{old_status}'      => KY_Template::pair( '' !== $from ? ( $statuses[ $from ] ?? $from ) : '' ),
			'{total}'           => KY_Template::pair( wp_strip_all_tags( $order->get_formatted_order_total() ) ),
			'{total_amount}'    => KY_Template::pair( KY_Template::money( $order->get_total() ) ),
			'{payment_method}'  => KY_Template::pair( $order->get_payment_method_title() ),
			'{shipping_method}' => KY_Template::pair( wp_strip_all_tags( $order->get_shipping_method() ) ),
			'{note}'            => KY_Template::pair( $order->get_customer_note() ),
			'{date}'            => KY_Template::pair( $this->order_date( $order ) ),
			'{site}'            => KY_Template::pair( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ),
			'{admin_link}'      => KY_Template::pair( (string) $link ),
		);

		$items = $this->items( $order );

		$vars['{items}']       = KY_Template::pair( $items['text'] );
		$vars['{items_count}'] = KY_Template::pair( (string) $items['count'] );

		return array_merge( $vars, $this->customer_vars( $order ) );
	}

	/**
	 * مقادیر مربوط به مشتری.
	 *
	 * اگر «اطلاعات مشتری» خاموش باشد، همه خالی برمی‌گردند تا نام و
	 * تلفن و نشانی وارد کانال نشوند.
	 *
	 * @param WC_Order $order سفارش.
	 * @return array
	 */
	private function customer_vars( $order ) {
		$keys = array( '{customer}', '{customer_name}', '{customer_phone}', '{customer_email}', '{address}' );

		$empty = array();

		foreach ( $keys as $key ) {
			$empty[ $key ] = KY_Template::pair( '' );
		}

		if ( empty( $this->config['customer_info'] ) ) {
			return $empty;
		}

		$name = trim( $order->get_formatted_billing_full_name() );
		$tel  = (string) $order->get_billing_phone();
		$mail = (string) $order->get_billing_email();

		$address = trim( wp_strip_all_tags( str_replace( '<br/>', '، ', $order->get_formatted_shipping_address() ) ) );

		if ( '' === $address ) {
			$address = trim( wp_strip_all_tags( str_replace( '<br/>', '، ', $order->get_formatted_billing_address() ) ) );
		}

		$empty['{customer_name}']  = KY_Template::pair( $name );
		$empty['{customer_phone}'] = KY_Template::pair( $tel );
		$empty['{customer_email}'] = KY_Template::pair( $mail );
		$empty['{address}']        = KY_Template::pair( $address );

		$lines = array();

		if ( '' !== $name ) {
			$lines[] = '👤 ' . $name;
		}

		if ( '' !== $tel ) {
			$lines[] = '📞 ' . $tel;
		}

		if ( '' !== $address ) {
			$lines[] = '📍 ' . $address;
		}

		$block = implode( "\n", $lines );

		$empty['{customer}'] = KY_Template::raw( KY_Template::escape( $block ), $block );

		return $empty;
	}

	/**
	 * فهرست کالاهای سفارش.
	 *
	 * @param WC_Order $order سفارش.
	 * @return array{text:string,count:int}
	 */
	private function items( $order ) {
		$lines = array();
		$count = 0;

		foreach ( $order->get_items() as $item ) {
			$qty     = (int) $item->get_quantity();
			$count  += $qty;
			$lines[] = '• ' . $item->get_name() . ' × ' . $qty;

			// فهرست خیلی بلند، پیام را از حد مجاز رد می‌کند.
			if ( count( $lines ) >= 20 ) {
				$lines[] = '…';
				break;
			}
		}

		return array(
			'text'  => implode( "\n", $lines ),
			'count' => $count,
		);
	}

	/**
	 * تاریخ سفارش به وقت سایت.
	 *
	 * @param WC_Order $order سفارش.
	 * @return string
	 */
	private function order_date( $order ) {
		$date = $order->get_date_created();

		return $date ? $date->date_i18n( 'Y-m-d H:i' ) : '';
	}
}
