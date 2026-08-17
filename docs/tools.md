# WP Agent Studio — Complete MCP Tools Reference

The plugin exposes 24 structured tools categorized into Site, Media, Elementor Page, Widgets, Global Design System, Safety, and High-Level Agent Orchestration.

---

## 1. WordPress Site & Media Tools

### `wordpress_get_site_info`
Returns WordPress version, PHP version, active theme, plugins, and Elementor status.

### `wordpress_get_pages`
Lists WordPress pages with post status, slug, permalink, and Elementor status.
* **Params:** `search` (string), `limit` (int)

### `wordpress_get_media`
Queries attachment media library items.
* **Params:** `search` (string), `limit` (int)

### `wordpress_upload_media`
Uploads image files via base64 strings or remote URLs with strict MIME validation.
* **Params:** `filename` (string), `base64` (string), `url` (string)

---

## 2. Elementor Page & Layout Tools

### `elementor_get_page`
Returns simplified layout tree representation of an Elementor page.
* **Params:** `page_id` (int), `verbose` (bool)

### `elementor_create_page`
Creates a new WordPress page and initializes Elementor builder mode.
* **Params:** `title` (string), `slug` (string), `status` (string: draft/publish), `dry_run` (bool)

### `elementor_add_container`
Inserts a Flexbox container element.
* **Params:** `page_id` (int), `parent_id` (string), `direction` (column/row), `width` (boxed/full), `position` (int), `dry_run` (bool)

### `elementor_add_heading`
Adds a heading widget with typography and styling parameters.
* **Params:** `page_id` (int), `text` (string), `tag` (h1-h6), `parent_id` (string), `color` (string), `align` (string), `dry_run` (bool)

### `elementor_add_text`
Adds a text editor widget.
* **Params:** `page_id` (int), `content` (string), `parent_id` (string), `color` (string), `dry_run` (bool)

### `elementor_add_button`
Adds a CTA button widget.
* **Params:** `page_id` (int), `text` (string), `url` (string), `background_color` (string), `text_color` (string), `align` (string), `dry_run` (bool)

### `elementor_add_image`
Adds an image widget referencing attachment ID or URL.
* **Params:** `page_id` (int), `url` (string), `attachment_id` (int), `parent_id` (string), `align` (string), `dry_run` (bool)

### `elementor_add_element`
Generic widget creator using strict widget allowlist.
* **Params:** `page_id` (int), `widget_type` (string), `settings` (object), `parent_id` (string), `position` (int), `dry_run` (bool)

### `elementor_update_element`
Updates settings, styles, typography, or responsive rules for an existing element ID.
* **Params:** `page_id` (int), `element_id` (string), `settings` (object), `dry_run` (bool)

### `elementor_delete_element`
Deletes an element and its children from a page tree.
* **Params:** `page_id` (int), `element_id` (string), `dry_run` (bool)

### `elementor_move_element`
Changes parent container or positional index of an element.
* **Params:** `page_id` (int), `element_id` (string), `new_parent_id` (string), `position` (int)

### `elementor_duplicate_element`
Duplicates an element subtree recursively with unique 7-char hex IDs.
* **Params:** `page_id` (int), `element_id` (string)

---

## 3. Global Design System Tools

### `elementor_get_global_colors`
Lists global color palette tokens.

### `elementor_set_global_color`
Updates or creates a global color token.
* **Params:** `id` (string: primary, secondary, text, accent), `color` (hex string), `title` (string)

### `elementor_get_global_fonts`
Lists global typography font tokens.

### `elementor_set_global_font`
Updates or creates a global typography token.
* **Params:** `id` (string), `font_family` (string), `font_weight` (string), `title` (string)

---

## 4. Safety & Orchestration Tools

### `elementor_create_backup`
Explicitly snapshot backs up page data state.
* **Params:** `page_id` (int), `reason` (string)

### `elementor_restore_backup`
Restores page state to a previous snapshot revision.
* **Params:** `page_id` (int), `backup_id` (string)

### `elementor_validate_page`
Audits page structure for orphan nodes or duplicate element IDs.
* **Params:** `page_id` (int)

### `agent_build_page`
Compiles full Agent DSL payload into a complete Elementor page layout.
* **Params:** `dsl` (object), `dry_run` (bool)
