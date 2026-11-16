/**
 * رفتارهای کوچک صفحه‌ی تنظیمات.
 */
( function () {
	'use strict';

	/**
	 * درج متن در جای نشانگر متنی.
	 *
	 * @param {HTMLTextAreaElement} field  فیلد.
	 * @param {string}              insert متن.
	 */
	function insertAtCursor( field, insert ) {
		var start = field.selectionStart;
		var end   = field.selectionEnd;

		if ( typeof start !== 'number' ) {
			field.value += insert;
			return;
		}

		field.value = field.value.slice( 0, start ) + insert + field.value.slice( end );
		field.selectionStart = start + insert.length;
		field.selectionEnd   = field.selectionStart;
	}

	/**
	 * کارت کانال خاموش، کم‌رنگ می‌شود.
	 *
	 * فیلدها غیرفعال نمی‌شوند تا مقدارشان (مثل توکن) با ذخیره پاک نشود.
	 *
	 * @param {HTMLInputElement} toggle تیک فعال‌سازی کانال.
	 */
	function syncChannel( toggle ) {
		var card = toggle.closest( '.dsb-channel' );

		if ( card ) {
			card.classList.toggle( 'is-off', ! toggle.checked );
		}
	}

	document.addEventListener( 'click', function ( event ) {
		var closeButton = event.target.closest( '.dsb-notice-close' );

		if ( closeButton ) {
			var notice = closeButton.closest( '.dsb-notice' );

			if ( notice ) {
				notice.remove();
			}

			return;
		}

		var chip = event.target.closest( '.dsb-chip' );

		if ( chip ) {
			var chipTarget = document.getElementById( chip.getAttribute( 'data-target' ) );

			if ( chipTarget ) {
				chipTarget.focus();
				insertAtCursor( chipTarget, chip.getAttribute( 'data-token' ) );
			}

			return;
		}

		var reset = event.target.closest( '.dsb-template-reset' );

		if ( reset ) {
			var resetTarget = document.getElementById( reset.getAttribute( 'data-target' ) );

			if ( resetTarget ) {
				resetTarget.value = reset.getAttribute( 'data-default' );
				resetTarget.focus();
			}
		}
	} );

	document.addEventListener( 'change', function ( event ) {
		var toggle = event.target.closest( '.dsb-channel-toggle' );

		if ( toggle ) {
			syncChannel( toggle );
		}
	} );
}() );
