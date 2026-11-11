<?php
/**
 * Plugin Name:       کانال‌یار — انتشار خودکار
 * Plugin Name (en):  Kanalyar
 * Plugin URI:        https://github.com/Yeganenorouzi/kanalyar
 * Description:       انتشار خودکار محتوای سایت و اعلان وضعیت سفارش ووکامرس در کانال‌های بله، تلگرام و ایتا — با قوانین مستقل، زمان‌بندی مقاوم و پایش سلامت.
 * Version:           6.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Yegane Norouzi
 * Author URI:        https://github.com/Yeganenorouzi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kanalyar
 * Domain Path:       /languages
 *
 * @package Kanalyar
 */

defined( 'ABSPATH' ) || exit;

define( 'KY_VERSION', '6.1.0' );
define( 'KY_FILE', __FILE__ );
define( 'KY_PATH', plugin_dir_path( __FILE__ ) );
define( 'KY_URL', plugin_dir_url( __FILE__ ) );
define( 'KY_BASENAME', plugin_basename( __FILE__ ) );

require_once KY_PATH . 'includes/class-ky-plugin.php';

register_activation_hook( __FILE__, array( 'KY_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'KY_Plugin', 'deactivate' ) );

/**
 * نقطه‌ی ورود افزونه.
 *
 * @return KY_Plugin
 */
function kanalyar() {
	return KY_Plugin::instance();
}

kanalyar();
