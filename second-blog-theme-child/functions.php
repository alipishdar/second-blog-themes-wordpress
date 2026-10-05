<?php
/**
 * Second Blog Theme Child theme functions.
 *
 * This file augments the parent theme; it does not replace the parent's
 * functions.php file.
 *
 * @package Second_Blog_Theme_Child
 */

declare(strict_types=1);

/**
 * Enqueue child-theme custom CSS.
 *
 * Block themes do not always need style.css enqueued explicitly. Since this
 * child theme keeps its project CSS in a dedicated asset file, we enqueue it
 * explicitly and use the file modification time for cache-busting.
 *
 * @return void
 */
function second_blog_theme_child_enqueue_styles(): void {
	$relative_path = '/assets/css/custom.css';
	$file_path     = get_stylesheet_directory() . $relative_path;
	$file_version  = file_exists( $file_path ) ? (string) filemtime( $file_path ) : '1.0.0';

	wp_enqueue_style(
		'second-blog-theme-child-custom',
		get_stylesheet_directory_uri() . $relative_path,
		array(),
		$file_version
	);
}
add_action( 'wp_enqueue_scripts', 'second_blog_theme_child_enqueue_styles' );

/**
 * Enqueue the same custom CSS in the block editor so front-end and editor
 * previews stay closer to one another.
 *
 * @return void
 */
function second_blog_theme_child_enqueue_editor_styles(): void {
	$relative_path = '/assets/css/custom.css';
	$file_path     = get_stylesheet_directory() . $relative_path;
	$file_version  = file_exists( $file_path ) ? (string) filemtime( $file_path ) : '1.0.0';

	wp_enqueue_style(
		'second-blog-theme-child-editor',
		get_stylesheet_directory_uri() . $relative_path,
		array(),
		$file_version
	);
}
add_action( 'enqueue_block_editor_assets', 'second_blog_theme_child_enqueue_editor_styles' );
