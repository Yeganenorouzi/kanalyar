/**
 * رفتارهای کوچک صفحه‌ی تنظیمات.
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.dsb-notice-close' );

		if ( ! button ) {
			return;
		}

		var notice = button.closest( '.dsb-notice' );

		if ( notice ) {
			notice.remove();
		}
	} );
}() );
