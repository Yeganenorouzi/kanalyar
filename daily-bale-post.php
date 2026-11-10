<?php
/**
 * Plugin Name:       Daily Bale Post
 * Plugin URI:        https://github.com/Yeganenorouzi/kanalyar
 * Description:       ارسال خودکار روزانه‌ی یک محصول ووکامرس به کانال بله — با زمان‌بندی مقاوم، تلاش مجدد، ارسال مطمئن تصویر و پایش سلامت.
 * Version:           4.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Yegane Norouzi
 * Author URI:        https://github.com/Yeganenorouzi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       daily-bale-post
 * Domain Path:       /languages
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

define( 'DSB_VERSION', '4.1.0' );
define( 'DSB_FILE', __FILE__ );
define( 'DSB_PATH', plugin_dir_path( __FILE__ ) );
define( 'DSB_URL', plugin_dir_url( __FILE__ ) );
define( 'DSB_BASENAME', plugin_basename( __FILE__ ) );

require_once DSB_PATH . 'includes/class-dsb-plugin.php';

register_activation_hook( __FILE__, array( 'DSB_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DSB_Plugin', 'deactivate' ) );

/**
 * نقطه‌ی ورود افزونه.
 *
 * @return DSB_Plugin
 */
function dsb() {
	return DSB_Plugin::instance();
}

dsb();
