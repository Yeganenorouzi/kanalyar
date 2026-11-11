<?php
/**
 * ساخت بسته‌های نصب.
 *
 * یک بسته‌ی تمیز (بدون هیچ برندی) و یک بسته به‌ازای هر پروفایل برندینگ
 * موجود در پوشه‌ی branding/ می‌سازد.
 *
 * اجرا:  php build.php
 *
 * @package Kanalyar
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$root = __DIR__;
$dist = $root . '/dist';

// نام پوشه‌ی افزونه در بسته. عمداً به نام پوشه‌ی کاری وابسته نیست،
// تا نام دلخواه توسعه‌دهنده روی خروجی اثر نگذارد.
$slug = 'kanalyar';
$main = $slug . '.php';

// ── نسخه را از هدر افزونه بخوان ───────────────────────
$header = file_get_contents( $root . '/' . $main );

if ( ! preg_match( '/^\s*\*\s*Version:\s*(.+)$/mi', $header, $m ) ) {
	fwrite( STDERR, "نسخه در هدر افزونه پیدا نشد.\n" );
	exit( 1 );
}

$version = trim( $m[1] );

// ── فایل‌هایی که هرگز وارد بسته نمی‌شوند ──────────────
$skip_dirs  = array( '.git', 'dist', 'node_modules', 'vendor', '.github' );
$skip_files = array( 'build.php', '.gitignore', '.gitattributes' );
$skip_ext   = array( 'zip', 'bak', 'log', 'tmp' );

/**
 * فهرست همه‌ی فایل‌های قابل بسته‌بندی.
 *
 * @param string $root ریشه‌ی پروژه.
 * @param array  $skip_dirs پوشه‌های نادیده.
 * @param array  $skip_files فایل‌های نادیده.
 * @param array  $skip_ext پسوندهای نادیده.
 * @return array مسیرهای نسبی.
 */
function ky_collect( $root, $skip_dirs, $skip_files, $skip_ext ) {
	$out = array();

	$it = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
			function ( $current ) use ( $skip_dirs ) {
				return ! ( $current->isDir() && in_array( $current->getFilename(), $skip_dirs, true ) );
			}
		)
	);

	foreach ( $it as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}

		$rel = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );

		if ( in_array( $rel, $skip_files, true ) ) {
			continue;
		}

		if ( in_array( strtolower( $file->getExtension() ), $skip_ext, true ) ) {
			continue;
		}

		$out[] = $rel;
	}

	sort( $out );

	return $out;
}

/**
 * ساخت یک فایل zip.
 *
 * @param string $target  مسیر فایل خروجی.
 * @param string $root    ریشه‌ی پروژه.
 * @param string $slug    نام پوشه‌ی داخل zip.
 * @param array  $files   فایل‌ها (مسیر نسبی).
 * @param string $active  نام پروفایل برندینگ فعال، یا رشته‌ی خالی.
 * @return int تعداد فایل‌ها.
 */
function ky_zip( $target, $root, $slug, array $files, $active ) {
	$zip = new ZipArchive();

	if ( true !== $zip->open( $target, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		fwrite( STDERR, "ساخت فایل ناموفق بود: $target\n" );
		exit( 1 );
	}

	foreach ( $files as $rel ) {
		$zip->addFile( $root . '/' . $rel, $slug . '/' . $rel );
	}

	$count = count( $files );

	if ( '' !== $active ) {
		$zip->addFromString(
			$slug . '/branding/active.php',
			"<?php\n// پروفایل برندینگ فعال این بسته. این فایل را اسکریپت build می‌سازد.\nreturn '" . $active . "';\n"
		);
		$count++;
	}

	$zip->close();

	return $count;
}

// ── شروع ───────────────────────────────────────────────
if ( ! is_dir( $dist ) ) {
	mkdir( $dist, 0777, true );
}

$all = ky_collect( $root, $skip_dirs, $skip_files, $skip_ext );

// بسته‌ی تمیز: پوشه‌ی branding اصلاً وارد نمی‌شود.
$clean = array_values(
	array_filter(
		$all,
		function ( $rel ) {
			return 0 !== strpos( $rel, 'branding/' );
		}
	)
);

echo "نسخه: $version\n\n";

$target = "$dist/$slug-$version.zip";
$n      = ky_zip( $target, $root, $slug, $clean, '' );
printf( "  ✓ %-44s %3d فایل  (تمیز)\n", basename( $target ), $n );

// یک بسته به‌ازای هر پروفایل برندینگ.
$profiles = glob( $root . '/branding/*/profile.php' );

foreach ( $profiles as $profile ) {
	$name = basename( dirname( $profile ) );

	// پروفایل نمونه فقط برای مستندسازی است.
	if ( 'example' === $name ) {
		continue;
	}

	// فقط فایل‌های همین پروفایل، نه پروفایل‌های دیگر.
	$files = array_values(
		array_filter(
			$all,
			function ( $rel ) use ( $name ) {
				return 0 !== strpos( $rel, 'branding/' ) || 0 === strpos( $rel, "branding/$name/" );
			}
		)
	);

	$target = "$dist/$slug-$version-$name.zip";
	$n      = ky_zip( $target, $root, $slug, $files, $name );
	printf( "  ✓ %-44s %3d فایل  (برند: %s)\n", basename( $target ), $n, $name );
}

echo "\nخروجی در پوشه‌ی dist/\n";
