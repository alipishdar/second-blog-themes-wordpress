# Second Blog Theme Child Theme

Persian name: قالب فرزند وبلاگ آژانسی

A lightweight WordPress Block Child Theme for:

`second-blog-theme`

## Why a child theme?

The parent theme can be updated without directly overwriting this project's
custom code, templates, patterns, CSS, or theme.json overrides.

## Important: where Site Editor changes are saved

WordPress Site Editor changes are normally saved in the site's database as
user customizations. They are not automatically written into this child theme's
files.

For a file-based workflow, install the official WordPress **Create Block Theme**
plugin. After editing in Appearance > Editor, use its wrench menu and choose
**Save Changes to Theme** to write supported editor changes into the active
child theme. You can also use **Export Zip** for deployment or backups.

## Child theme workflow

1. Install the parent theme first:
   `second-blog-theme`
2. Install this child theme ZIP.
3. Activate **Second Blog Theme Child**.
4. Keep project-specific PHP in `functions.php`.
5. Keep persistent CSS in `assets/css/custom.css`.
6. Put persistent block-theme design overrides in `theme.json`.
7. Override a parent block template by creating a file with the same path/name
   inside this child theme, for example:
   `templates/index.html`
8. Override a template part by using the same path/name, for example:
   `parts/header.html`

## Important rule

Do not copy the parent's entire `functions.php` into the child theme. The
child and parent `functions.php` files are both loaded.

## Theme directory

```text
wp-content/
└── themes/
    ├── second-blog-theme/
    └── second-blog-theme-child/
        ├── assets/
        │   └── css/
        │       └── custom.css
        ├── functions.php
        ├── README.md
        ├── style.css
        └── theme.json
```

## ZIP installation

In WordPress:

Appearance > Themes > Add New > Upload Theme

Upload the ZIP and activate the child theme.
