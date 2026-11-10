<?php
/**
 * پاک‌سازی داده‌های افزونه هنگام حذف.
 *
 * @package DailyBalePost
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$dsb_options = array(
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

foreach ( $dsb_options as $dsb_option ) {
	delete_option( $dsb_option );
}

delete_transient( 'dsb_fallback_guard' );

wp_clear_scheduled_hook( 'dsb_tick' );
wp_clear_scheduled_hook( 'dsb_daily_event' );
