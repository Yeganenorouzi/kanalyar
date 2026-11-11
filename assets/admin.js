/**
 * رفتارهای صفحه‌ی تنظیمات: بستن اطلاعیه و افزودن/حذف ردیف‌های تکرارشونده.
 */
( function () {
	'use strict';

	/**
	 * بستن اطلاعیه.
	 */
	document.addEventListener( 'click', function ( event ) {
		var close = event.target.closest( '.ky-notice-close' );

		if ( close ) {
			var notice = close.closest( '.ky-notice' );

			if ( notice ) {
				notice.remove();
			}
		}
	} );

	/**
	 * شماره‌ی ردیف بعدی یک گروه.
	 *
	 * @param {Element} group ظرف ردیف‌ها.
	 * @return {number} شماره‌ی آزاد بعدی.
	 */
	function nextIndex( group ) {
		var max = -1;

		group.querySelectorAll( '.ky-item' ).forEach( function ( item ) {
			var i = parseInt( item.getAttribute( 'data-index' ), 10 );

			if ( ! isNaN( i ) && i > max ) {
				max = i;
			}
		} );

		return max + 1;
	}

	/**
	 * عوض‌کردن شماره‌ی ردیف در نام فیلدها.
	 *
	 * ky[rules][0][label]  =>  ky[rules][3][label]
	 *
	 * @param {Element} item  ردیف.
	 * @param {string}  group نام گروه.
	 * @param {number}  index شماره‌ی تازه.
	 */
	function reindex( item, group, index ) {
		var pattern = new RegExp( '(ky\\[' + group + '\\]\\[)\\d+(\\])' );

		item.querySelectorAll( '[name]' ).forEach( function ( field ) {
			field.name = field.name.replace( pattern, '$1' + index + '$2' );
		} );

		item.setAttribute( 'data-index', index );
	}

	/**
	 * خالی‌کردن مقادیر یک ردیف تازه.
	 *
	 * @param {Element} item ردیف.
	 */
	function clearValues( item ) {
		item.querySelectorAll( 'input, select, textarea' ).forEach( function ( field ) {
			if ( 'checkbox' === field.type || 'radio' === field.type ) {
				// روزهای هفته و کلید «فعال» به‌صورت پیش‌فرض تیک‌خورده بمانند.
				field.checked = field.closest( '.ky-days' ) !== null || field.closest( '.ky-switch' ) !== null;
				return;
			}

			if ( 'hidden' === field.type ) {
				field.value = '';
				return;
			}

			if ( 'SELECT' === field.tagName ) {
				if ( field.multiple ) {
					Array.prototype.forEach.call( field.options, function ( option ) {
						option.selected = false;
					} );
				} else {
					field.selectedIndex = 0;
				}
				return;
			}

			if ( 'number' !== field.type ) {
				field.value = '';
			}
		} );

		var title = item.querySelector( '.ky-item-title' );

		if ( title ) {
			title.textContent = title.getAttribute( 'data-new-label' ) || '…';
		}

		// بخش‌هایی که فقط برای ردیف ذخیره‌شده معنی دارند.
		var foot = item.querySelector( '.ky-item-foot' );

		if ( foot ) {
			foot.remove();
		}

		var status = item.querySelector( '.ky-rule-status' );

		if ( status ) {
			status.remove();
		}
	}

	/**
	 * افزودن ردیف تازه.
	 */
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.ky-add' );

		if ( ! button ) {
			return;
		}

		event.preventDefault();

		var name  = button.getAttribute( 'data-repeat-target' );
		var group = document.querySelector( '[data-repeat="' + name + '"]' );

		if ( ! group ) {
			return;
		}

		var items = group.querySelectorAll( '.ky-item' );

		if ( ! items.length ) {
			return;
		}

		var clone = items[ items.length - 1 ].cloneNode( true );

		reindex( clone, name, nextIndex( group ) );
		clearValues( clone );
		group.appendChild( clone );

		clone.scrollIntoView( { behavior: 'smooth', block: 'center' } );

		var first = clone.querySelector( 'input[type="text"]' );

		if ( first ) {
			first.focus();
		}
	} );

	/**
	 * حذف ردیف.
	 */
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.ky-remove' );

		if ( ! button ) {
			return;
		}

		event.preventDefault();

		var item  = button.closest( '.ky-item' );
		var group = button.closest( '.ky-repeat' );

		if ( ! item || ! group ) {
			return;
		}

		if ( ! window.confirm( button.getAttribute( 'data-confirm' ) || 'حذف شود؟' ) ) {
			return;
		}

		// آخرین ردیف پاک نمی‌شود، فقط خالی می‌شود، تا فرم همیشه یک ردیف داشته باشد.
		if ( 1 === group.querySelectorAll( '.ky-item' ).length ) {
			clearValues( item );
			return;
		}

		item.remove();
	} );

	/**
	 * به‌روز نگه‌داشتن عنوان ردیف هنگام تایپ.
	 */
	document.addEventListener( 'input', function ( event ) {
		var field = event.target;

		if ( ! field.name || ! /\[label\]$/.test( field.name ) ) {
			return;
		}

		var item  = field.closest( '.ky-item' );
		var title = item ? item.querySelector( '.ky-item-title' ) : null;

		if ( title ) {
			title.textContent = field.value || '…';
		}
	} );
}() );
