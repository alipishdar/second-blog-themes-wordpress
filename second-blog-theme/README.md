# Agency Blog Block Theme

A lightweight WordPress block theme intended for a WordPress installation mounted at `/blog/` while the main website lives on another stack at the root domain.

## URL architecture

- `https://example.com/` → main application/site (not WordPress)
- `https://example.com/blog/` → WordPress front page (custom Blog landing)
- `https://example.com/blog/articles/` → WordPress Posts Page / article archive
- `https://example.com/blog/<article-slug>/` → single article

## WordPress setup

1. Install WordPress under `/blog/`.
2. Install and activate this theme.
3. Create a page with title `Articles` and slug `articles`.
4. Go to Settings → Reading.
5. Set the Posts page to `Articles`.
6. The WordPress front page at `/blog/` is controlled by `templates/front-page.html`; a separate WordPress Page named `Blog` is intentionally not required.
7. In Navigation, add `/blog/` as Blog and `/blog/articles/` as Articles.

The theme intentionally does not create a WordPress page with slug `blog`, because WordPress itself is already mounted at `/blog/`.

## Editing

Use Appearance → Editor. The landing page is controlled by `templates/front-page.html`; the archive is controlled by `templates/home.html`. Reusable header/footer markup lives in `/parts/`.
