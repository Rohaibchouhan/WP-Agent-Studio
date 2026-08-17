# Security & Hardening Architecture

Security is built into every layer of **AI Elementor Agent**.

---

## 1. Authentication & Bearer Tokens
* All remote requests to `/wp-json/ai-elementor/v1/mcp` require an `Authorization: Bearer <token>` header.
* Tokens are generated with cryptographically random hex strings (`aiea_live_...`).
* Only salted password hashes (`wp_hash_password()`) are stored in WordPress database options. Raw secret keys are displayed **ONCE** at generation time.
* Administrator can revoke any token instantly from WP-Admin dashboard.

---

## 2. Authorization & Capability Enforcement
* Every authenticated token is bound to a specific WordPress user ID.
* Tool invocations verify user capabilities (`current_user_can('edit_pages')`).
* Destructive operations (publishing, deleting) check plugin permission policy (`PermissionManager`).
* `publish_pages` and `delete_pages` are disabled by default.

---

## 3. Strict Execution Isolation
* **Zero Arbitrary Code Execution:** No `eval()`, no system commands, no shell execution.
* **Zero Arbitrary SQL:** No raw SQL queries exposed to MCP clients.
* **Widget Allowlisting:** Widget additions are restricted to `WidgetAllowlist` (e.g. `container`, `heading`, `text-editor`, `button`, `image`, `icon`, `divider`, `spacer`, `form`). Unregistered or unsafe class names are rejected.
* **Input Sanitization:** All incoming text parameters pass through `sanitize_text_field()`, `wp_kses_post()`, and `esc_url_raw()`.

---

## 4. Audit Logging & Secrets Shielding
* Every tool execution records an immutable audit entry in custom table `wp_ai_elementor_audit_logs`.
* Audit logger automatically strips sensitive parameters (`api_key`, `token`, `secret`, `password`) before serializing log details.
