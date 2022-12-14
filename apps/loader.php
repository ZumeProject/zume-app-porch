<?php
/**
 * Auto loads level 1 php files, and file in subdirectories
 */
$dir = scandir( __DIR__ );
foreach ( $dir as $file ){
    if ( 'php' === substr( $file, -3, 3 ) && 'index.php' !== $file && 'loader.php' !== $file && substr( $file, 0, 1 ) !== '.' ) {
        require_once( __DIR__ . '/' . $file );
    }
    if ( 'index.php' !== $file && 'loader.php' !== $file && substr( $file, 0, 1 ) !== '.' && is_dir( __DIR__ . '/' . $file ) ) {
        $subdir = scandir( __DIR__ . '/'. $file );
        foreach ( $subdir as $subfile ){
            if ( 'php' === substr( $subfile, -3, 3 ) && 'index.php' !== $subfile && 'loader.php' !== $subfile && substr( $subfile, 0, 1 ) !== '.' ) {
                require_once( __DIR__ . '/'. $file . '/' . $subfile );
            }
        }
    }
}
