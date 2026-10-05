<?php
/**
 * Second Blog Theme
 * Theme folder: second-blog-theme
 * Persian name: دومین بلاگ
 *
 * URL architecture for WordPress installed at /blog/:
 *
 *   Blog:             /blog/
 *   Articles:         /blog/articles/
 *   Category:         /blog/category/{slug}/
 *   Sub Category:   /blog/category/{slug}/
 *   Tag:              /blog/{tag-slug}/
 *   Single Post:      unchanged
 *  
 * Notes:
 * - Category hierarchy remains intact inside WordPress.
 * - Category URLs are intentionally FLAT: parent/child/grandchild are not
 *   included in the URL.
 * - Breadcrumbs expose the real taxonomy hierarchy.
 * - Old category/tag URLs are redirected with HTTP 301 when they resolve to a
 *   404, preserving SEO signals after the migration.
 */

declare(strict_types=1);

add_action('after_setup_theme', static function (): void {
    add_theme_support('post-thumbnails');
    add_theme_support('responsive-embeds');
    add_theme_support('editor-styles');
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'agency-blog',
        get_stylesheet_uri(),
        [],
        wp_get_theme()->get('Version')
    );
});

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme constants.
 */
define( 'AGENCY_BLOG_THEME_VERSION', '1.0.0' );
define( 'AGENCY_BLOG_TEXTDOMAIN', 'agency-blog-block-theme' );

/* ------------------------------------------------------------------------- *
 * 1. TAXONOMY REWRITE
 * ------------------------------------------------------------------------- */

/**
 * Category URLs:
 *   /blog/category/category-slug/
 *
 * The taxonomy itself stays hierarchical, but the rewrite is flat.
 * Therefore:
 *
 *   Marketing
 *     └── Content Marketing
 *
 * becomes:
 *   /blog/category/marketing/
 *   /blog/category/content-marketing/
 */
function agency_blog_category_rewrite_args( $args, $taxonomy ) {
	if ( 'category' !== $taxonomy ) {
		return $args;
	}

	// Do not alter the first pre-init registration performed by WordPress.
	if ( ! did_action( 'init' ) ) {
		return $args;
	}

	$args['rewrite'] = array(
		'slug'         => 'category',
		'with_front'   => false,
		'hierarchical' => false,
		'ep_mask'      => EP_CATEGORIES,
	);

	return $args;
}
add_filter( 'register_category_taxonomy_args', 'agency_blog_category_rewrite_args', 10, 2 );

/**
 * Tag URLs:
 *   /blog/{tag-slug}/
 *
 * The empty rewrite slug is intentional because WordPress itself lives under
 * /blog/. Therefore WordPress supplies /blog/ as the home path.
 */
function agency_blog_tag_rewrite_args( $args, $taxonomy ) {
	if ( 'post_tag' !== $taxonomy ) {
		return $args;
	}

	if ( ! did_action( 'init' ) ) {
		return $args;
	}

	$args['rewrite'] = array(
		'slug'         => '',
		'with_front'   => false,
		'hierarchical' => false,
		'ep_mask'      => EP_TAGS,
	);

	return $args;
}
add_filter( 'register_post_tag_taxonomy_args', 'agency_blog_tag_rewrite_args', 10, 2 );

/**
 * Flush rewrite rules only when the theme is activated/switching.
 * Never call flush_rewrite_rules() on every request.
 */
function agency_blog_flush_rewrite_rules() {
	flush_rewrite_rules( false );
}
add_action( 'after_switch_theme', 'agency_blog_flush_rewrite_rules' );

/* ------------------------------------------------------------------------- *
 * 2. RESERVED TAG SLUGS
 * ------------------------------------------------------------------------- */

/**
 * Prevent tag slugs that would collide with the site's URL structure.
 *
 * /blog/category/  -> category archive base
 * /blog/articles/  -> articles page
 * /blog/feed/      -> feed endpoints
 */
function agency_blog_reject_reserved_tag_slugs( $term, $taxonomy, $args ) {
	if ( 'post_tag' !== $taxonomy ) {
		return $term;
	}

	$reserved = array(
		'articles',
		'category',
		'feed',
	);

	$reserved = apply_filters( 'agency_blog_reserved_tag_slugs', $reserved );
	$slug     = sanitize_title( $term );

	if ( in_array( $slug, $reserved, true ) ) {
		return new WP_Error(
			'agency_blog_reserved_tag_slug',
			sprintf(
				/* translators: %s: reserved tag slug */
				__( 'The tag slug "%s" is reserved by the Agency Blog URL structure.', AGENCY_BLOG_TEXTDOMAIN ),
				$slug
			)
		);
	}

	return $term;
}
add_filter( 'pre_insert_term', 'agency_blog_reject_reserved_tag_slugs', 10, 3 );

/* ------------------------------------------------------------------------- *
 * 3. URL / REQUEST HELPERS
 * ------------------------------------------------------------------------- */

/**
 * Get the path requested by the browser, relative to the WordPress home URL.
 *
 * Example when home_url() is https://pedaragency.com/blog/:
 *   /blog/category/branding/        -> category/branding
 *   /blog/tag/seo/                  -> tag/seo
 */
function agency_blog_get_relative_request_path() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] )
		? wp_unslash( $_SERVER['REQUEST_URI'] )
		: '/';

	$path = wp_parse_url( $request_uri, PHP_URL_PATH );
	$path = is_string( $path ) ? rawurldecode( $path ) : '';

	$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$home_path = is_string( $home_path ) ? trim( $home_path, '/' ) : '';
	$path      = trim( $path, '/' );

	if ( $home_path ) {
		$prefix = $home_path . '/';

		if ( 0 === strpos( $path, $prefix ) ) {
			$path = substr( $path, strlen( $prefix ) );
		} elseif ( $path === $home_path ) {
			$path = '';
		}
	}

	return trim( $path, '/' );
}

/**
 * Get the main website URL.
 *
 * In the Agency Blog architecture WordPress lives at /blog/, while the main
 * agency website lives at the domain root. This helper infers that root.
 * A filter is provided so the URL can be overridden in another project.
 */
function agency_blog_main_site_url() {
	$home_url   = home_url( '/' );
	$parsed_url = wp_parse_url( $home_url );
	$path       = isset( $parsed_url['path'] ) ? trim( $parsed_url['path'], '/' ) : '';

	if ( 'blog' === $path ) {
		$scheme = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] : 'https';
		$host   = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
		$port   = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';

		$url = $scheme . '://' . $host . $port . '/';
	} else {
		$url = home_url( '/' );
	}

	return trailingslashit( apply_filters( 'agency_blog_main_site_url', $url ) );
}

/**
 * Check whether a popular SEO plugin already owns canonical/breadcrumb
 * structured data output. If so, our duplicate canonical/JSON-LD output is
 * disabled.
 */
function agency_blog_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| defined( 'AIOSEO_VERSION' );
}

/* ------------------------------------------------------------------------- *
 * 4. 301 REDIRECTS FROM OLD TAXONOMY URLS
 * ------------------------------------------------------------------------- */

/**
 * Resolve a category from the final slug of an old hierarchical URL.
 * Category slugs are unique inside the category taxonomy, so the final slug
 * is sufficient for this migration.
 */
function agency_blog_get_category_by_legacy_path( $legacy_path ) {
	$segments = array_values(
		array_filter(
			explode( '/', trim( $legacy_path, '/' ) )
		)
	);

	if ( empty( $segments ) ) {
		return false;
	}

	$slug = end( $segments );
	$slug = sanitize_title( $slug );

	if ( ! $slug ) {
		return false;
	}

	$term = get_term_by( 'slug', $slug, 'category' );

	return ( $term instanceof WP_Term ) ? $term : false;
}

/**
 * Redirect common legacy category/tag URLs with 301.
 *
 * Supported legacy examples:
 *   /blog/category/parent/child/  -> /blog/category/child/
 *   /blog/tag/seo/                -> /blog/seo/
 *
 * The redirect only runs for 404 requests, so valid pages/posts are not
 * intercepted.
 */
function agency_blog_redirect_legacy_taxonomy_urls() {
	if ( ! is_404() ) {
		return;
	}

	$relative = agency_blog_get_relative_request_path();

	if ( ! $relative ) {
		return;
	}

	$category_base = trim( (string) get_option( 'category_base' ), '/' );
	$tag_base      = trim( (string) get_option( 'tag_base' ), '/' );

	$old_category_base = $category_base ? $category_base : 'category';
	$old_tag_base      = $tag_base ? $tag_base : 'tag';

	/* Legacy hierarchical category URL. */
	$category_regex = '#^' . preg_quote( $old_category_base, '#' ) . '/(.+)$#';

	if ( preg_match( $category_regex, $relative, $matches ) ) {
		$category = agency_blog_get_category_by_legacy_path( $matches[1] );

		if ( $category ) {
			$target = get_term_link( $category, 'category' );

			if ( ! is_wp_error( $target ) ) {
				wp_safe_redirect( $target, 301, 'Agency Blog Theme' );
				exit;
			}
		}
	}

	/* Legacy tag URL: /blog/tag/{slug}/ */
	$tag_regex = '#^' . preg_quote( $old_tag_base, '#' ) . '/([^/]+)$#';

	if ( preg_match( $tag_regex, $relative, $matches ) ) {
		$slug = sanitize_title( $matches[1] );
		$tag  = get_term_by( 'slug', $slug, 'post_tag' );

		if ( $tag instanceof WP_Term ) {
			$target = get_term_link( $tag, 'post_tag' );

			if ( ! is_wp_error( $target ) ) {
				wp_safe_redirect( $target, 301, 'Agency Blog Theme' );
				exit;
			}
		}
	}
}
add_action( 'template_redirect', 'agency_blog_redirect_legacy_taxonomy_urls', 1 );

/* ------------------------------------------------------------------------- *
 * 5. CANONICAL URL FOR CATEGORY / TAG ARCHIVES
 * ------------------------------------------------------------------------- */

/**
 * Output canonical URLs for taxonomy archives when an SEO plugin is not
 * already providing them.
 *
 * WordPress core's redirect_canonical() handles normal canonical redirects;
 * this function provides the HTML <link rel="canonical"> for taxonomy
 * archives as well.
 */
function agency_blog_taxonomy_canonical_link() {
	if ( agency_blog_seo_plugin_active() ) {
		return;
	}

	if ( ! is_category() && ! is_tag() ) {
		return;
	}

	$term = get_queried_object();

	if ( ! ( $term instanceof WP_Term ) ) {
		return;
	}

	$canonical = get_term_link( $term );

	if ( is_wp_error( $canonical ) ) {
		return;
	}

	$paged = max( 1, (int) get_query_var( 'paged' ) );

	if ( $paged > 1 ) {
		$canonical = trailingslashit( $canonical ) . user_trailingslashit( 'page/' . $paged, 'paged' );
	}

	echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
}
add_action( 'wp_head', 'agency_blog_taxonomy_canonical_link', 1 );

/* ------------------------------------------------------------------------- *
 * 6. BREADCRUMB DATA
 * ------------------------------------------------------------------------- */

/**
 * Return the category that best represents a post for breadcrumb purposes.
 *
 * If a post has several categories, the deepest category is preferred so the
 * breadcrumb exposes the most specific taxonomy branch.
 */
function agency_blog_get_primary_breadcrumb_category( $post_id = 0 ) {
	$post_id    = $post_id ? absint( $post_id ) : get_the_ID();
	$categories = get_the_category( $post_id );

	if ( empty( $categories ) ) {
		return false;
	}

	$non_default = array_filter(
		$categories,
		static function ( $category ) {
			return 'uncategorized' !== $category->slug;
		}
	);

	if ( ! empty( $non_default ) ) {
		$categories = array_values( $non_default );
	}

	usort(
		$categories,
		static function ( $a, $b ) {
			$a_depth = count( get_ancestors( $a->term_id, 'category', 'taxonomy' ) );
			$b_depth = count( get_ancestors( $b->term_id, 'category', 'taxonomy' ) );

			return $b_depth <=> $a_depth;
		}
	);

	return $categories[0];
}

/**
 * Build breadcrumb items for the current request.
 *
 * Each item is an array:
 *   [
 *     'name' => 'Branding',
 *     'url'  => 'https://example.com/blog/branding/',
 *   ]
 */
function agency_blog_get_breadcrumb_items() {
	$items = array(
		array(
			'name' => __( 'Home', AGENCY_BLOG_TEXTDOMAIN ),
			'url'  => agency_blog_main_site_url(),
		),
		array(
			'name' => __( 'Blog', AGENCY_BLOG_TEXTDOMAIN ),
			'url'  => home_url( '/' ),
		),
	);

	if ( is_category() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$ancestors = array_reverse( get_ancestors( $term->term_id, 'category', 'taxonomy' ) );

			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'category' );

				if ( $ancestor instanceof WP_Term ) {
					$url = get_term_link( $ancestor, 'category' );

					$items[] = array(
						'name' => $ancestor->name,
						'url'  => is_wp_error( $url ) ? '' : $url,
					);
				}
			}

			$items[] = array(
				'name' => $term->name,
				'url'  => get_term_link( $term, 'category' ),
			);
		}

		return $items;
	}

	if ( is_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$url = get_term_link( $term, 'post_tag' );

			$items[] = array(
				'name' => $term->name,
				'url'  => is_wp_error( $url ) ? '' : $url,
			);
		}

		return $items;
	}

	if ( is_singular( 'post' ) ) {
		$category = agency_blog_get_primary_breadcrumb_category( get_the_ID() );

		if ( $category instanceof WP_Term ) {
			$ancestors = array_reverse( get_ancestors( $category->term_id, 'category', 'taxonomy' ) );

			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'category' );

				if ( $ancestor instanceof WP_Term ) {
					$url = get_term_link( $ancestor, 'category' );

					$items[] = array(
						'name' => $ancestor->name,
						'url'  => is_wp_error( $url ) ? '' : $url,
					);
				}
			}

			$category_url = get_term_link( $category, 'category' );

			$items[] = array(
				'name' => $category->name,
				'url'  => is_wp_error( $category_url ) ? '' : $category_url,
			);
		}

		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);

		return $items;
	}

	if ( is_page( 'articles' ) ) {
		$items[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);

		return $items;
	}

	if ( is_search() ) {
		$items[] = array(
			'name' => sprintf(
				/* translators: %s: search query */
				__( 'Search: %s', AGENCY_BLOG_TEXTDOMAIN ),
				get_search_query()
			),
			'url'  => '',
		);
	}

	return $items;
}

/**
 * Render breadcrumbs as accessible HTML.
 *
 * Usage in PHP:
 *   echo agency_blog_breadcrumbs();
 *
 * Usage in a Block Theme:
 *   Add a Shortcode block containing:
 *   [agency_breadcrumbs]
 */
function agency_blog_breadcrumbs() {
	$items = agency_blog_get_breadcrumb_items();

	if ( count( $items ) < 2 ) {
		return '';
	}

	$output = '<nav class="agency-blog-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', AGENCY_BLOG_TEXTDOMAIN ) . '">';
	$output .= '<ol class="agency-blog-breadcrumbs__list">';

	$last_index = count( $items ) - 1;

	foreach ( $items as $index => $item ) {
		$is_current = $index === $last_index;
		$output    .= '<li class="agency-blog-breadcrumbs__item' . ( $is_current ? ' is-current' : '' ) . '">';

		if ( ! $is_current && ! empty( $item['url'] ) ) {
			$output .= '<a class="agency-blog-breadcrumbs__link" href="' . esc_url( $item['url'] ) . '">';
			$output .= esc_html( $item['name'] );
			$output .= '</a>';
		} else {
			$output .= '<span aria-current="page">' . esc_html( $item['name'] ) . '</span>';
		}

		$output .= '</li>';
	}

	$output .= '</ol>';
	$output .= '</nav>';

	return $output;
}

/**
 * Shortcode bridge for Block Theme templates/patterns.
 */
function agency_blog_breadcrumbs_shortcode() {
	return agency_blog_breadcrumbs();
}
add_shortcode( 'agency_breadcrumbs', 'agency_blog_breadcrumbs_shortcode' );

/* ------------------------------------------------------------------------- *
 * 7. BREADCRUMB STRUCTURED DATA (JSON-LD)
 * ------------------------------------------------------------------------- */

/**
 * Output BreadcrumbList JSON-LD if an SEO plugin is not already doing it.
 */
function agency_blog_breadcrumb_schema() {
	if ( agency_blog_seo_plugin_active() ) {
		return;
	}

	if ( is_front_page() ) {
		return;
	}

	$items = agency_blog_get_breadcrumb_items();

	if ( count( $items ) < 2 ) {
		return;
	}

	$list = array();

	foreach ( $items as $index => $item ) {
		if ( empty( $item['url'] ) ) {
			continue;
		}

		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => wp_strip_all_tags( $item['name'] ),
			'item'     => $item['url'],
		);
	}

	if ( count( $list ) < 2 ) {
		return;
	}

	$schema = array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $list,
	);

	echo '<script type="application/ld+json">' . wp_json_encode(
		$schema,
		JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
	) . '</script>' . "\n";
}
add_action( 'wp_head', 'agency_blog_breadcrumb_schema', 20 );

/* ------------------------------------------------------------------------- *
 * 8. OPTIONAL THEME SETUP
 * ------------------------------------------------------------------------- *
 * Keep the setup small. Block-theme functionality itself is primarily driven
 * by theme.json, block templates and patterns.
 */

function agency_blog_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
}
add_action( 'after_setup_theme', 'agency_blog_theme_setup' );
