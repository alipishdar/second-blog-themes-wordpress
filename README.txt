Second Blog Theme — Parent + Child package
============================================

Parent theme
- Folder: second-blog-theme
- Theme Name: Second Blog Theme
- Text Domain: second-blog-theme
- PHP prefix: second_blog_theme_
- Constants: SECOND_BLOG_THEME_*
- Version: 1.1.0

Child theme
- Folder: second-blog-theme-child
- Theme Name: Second Blog Theme Child
- Template: second-blog-theme
- Text Domain: second-blog-theme-child
- PHP prefix: second_blog_theme_child_
- Constants: SECOND_BLOG_THEME_CHILD_*
- Version: 1.1.0

URL architecture
- Blog: /blog/
- Articles: /blog/articles/
- Category: /blog/category/{slug}/
- Child category: /blog/category/{slug}/
- Tag: /blog/{tag-slug}/
- Single post: unchanged

Breadcrumb
- Shortcode: [second_breadcrumbs]
- CSS namespace: second-blog-breadcrumbs
