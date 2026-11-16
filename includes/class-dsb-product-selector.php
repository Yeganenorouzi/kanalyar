<?php
/**
 * انتخاب‌گر محصول (نام قدیمی، برای سازگاری با نسخه‌های پیش از ۴.۲).
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * از نسخه‌ی ۴.۲ انتخاب محتوا به DSB_Content_Selector منتقل شده تا
 * بتواند نوشته و نوع پست‌های سفارشی را هم انتخاب کند.
 *
 * @deprecated 4.2.0 به‌جای آن از DSB_Content_Selector استفاده کنید.
 */
class DSB_Product_Selector extends DSB_Content_Selector {
}
