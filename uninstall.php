<?php
/**
 * پاک‌سازی داده‌های افزونه هنگام حذف.
 *
 * @package Kanalyar
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/*
 * اگر نسخه‌ی دیگری از همین افزونه (با نام پوشه‌ی قدیمی یا جدید) هنوز
 * روی سایت هست، داده‌ها مشترک‌اند و نباید پاک شوند؛ وگرنه حذف نسخه‌ی
 * قدیمی بعد از نصب نسخه‌ی تازه، تنظیمات را از بین می‌برد.
 */
$ky_siblings = array(
	WP_PLUGIN_DIR . '/kanalyar/kanalyar.php',
	WP_PLUGIN_DIR . '/daily-bale-post/daily-bale-post.php',
	WP_PLUGIN_DIR . '/daily-bale-post/kanalyar.php',
);

foreach ( $ky_siblings as $ky_sibling ) {
	if ( $ky_sibling !== WP_UNINSTALL_PLUGIN && file_exists( $ky_sibling ) ) {
		return;
	}
}

$ky_options = array(
	// آپشن‌های نسخه‌ی ۴.
	'dsb_settings',
	'dsb_version',
	'dsb_log',
	'dsb_recent',
	'dsb_today',
	'dsb_run_lock',
	'dsb_last_tick',
	'dsb_last_cron_tick',
	'dsb_scheduled_at',
	'dsb_last_alert',
	'dsb_installed_at',
	'dsb_destinations',
	'dsb_rules',
	'dsb_orders',
	// آپشن‌های نسخه‌های پیش از ۴.
	'dsb_token',
	'dsb_channel',
	'dsb_phone',
	'dsb_hour',
	'dsb_minute',
	'dsb_enabled',
	'dsb_categories',
	'dsb_avoid',
	'dsb_instock',
	'dsb_days',
	'dsb_show_price',
);

foreach ( $ky_options as $ky_option ) {
	delete_option( $ky_option );
}

// آپشن‌های پویا: وضعیت امروز و تاریخچه‌ی هر قانون.
global $wpdb;

$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE 'dsb\_today\_%' OR option_name LIKE 'dsb\_recent\_%'"
);

delete_transient( 'dsb_fallback_guard' );

wp_clear_scheduled_hook( 'dsb_tick' );
wp_clear_scheduled_hook( 'dsb_daily_event' );
