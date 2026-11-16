<?php
/**
 * کلاینت بله (نام قدیمی، برای سازگاری با نسخه‌های پیش از ۴.۱).
 *
 * @package DailyBalePost
 */

defined( 'ABSPATH' ) || exit;

/**
 * از نسخه‌ی ۴.۱ منطق ارسال به DSB_Messenger منتقل شده تا هم بله و هم
 * تلگرام از یک کلاینت استفاده کنند. این کلاس فقط برای کدهایی نگه داشته
 * شده که به نام قدیمی اشاره می‌کنند.
 *
 * @deprecated 4.1.0 به‌جای آن از DSB_Messenger::make() استفاده کنید.
 */
class DSB_Bale_API extends DSB_Messenger_Bale {

	/**
	 * ریشه‌ی API بله (نام قدیمی ثابت).
	 */
	const ENDPOINT = 'https://tapi.bale.ai/bot';
}
