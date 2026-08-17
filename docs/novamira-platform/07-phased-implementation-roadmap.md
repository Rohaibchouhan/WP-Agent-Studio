# Phased Implementation Roadmap

To systematically build the full **ElementAI Studio WordPress Platform**, development is structured across 4 progressive phases:

---

## Phase 1: Core Foundation & Elementor Integration (Current & Active)

- [x] **WordPress Plugin Architecture**: Standalone plugin boilerplate with Composer autoloader.
- [x] **MCP Server Layer**: Implementation of Model Context Protocol 2024-11-05 standard (`src/MCP/Server.php`).
- [x] **Security & Authentication**: Token management, capability checks, and audit logging.
- [x] **Elementor Reader & Writer**: Direct manipulation of `_elementor_data` Flexbox element tree.
- [x] **Branding & Site Context Capture**: Extraction of theme logos, favicons, site info, global colors, and fonts.
- [x] **Revision & Rollback System**: Automatic pre-action backups and revision snapshots.
- [x] **AI Skills System Core**: Declarative JSON/YAML skill loaders and prompt schemas (`SkillLoader.php`, `SkillRegistry.php`, `skills/` packages).
- [x] **Native WP Admin AI Chat UI**: In-dashboard chat panel with dry-run diff approval (`admin/views/chat-panel.php`).

---

## Phase 2: AI Templates, Vision AI & Visual QA

- [ ] **AI Template Engine**: Metadata-driven template library with variable replacement.
- [ ] **Screenshot → Elementor Pipeline**: Vision AI analysis mapping layout blocks to native Elementor widgets.
- [ ] **Figma → Elementor Integration**: Auto-layout conversion to Elementor Flexbox containers.
- [ ] **Visual QA Closed Loop**: Headless browser rendering and screenshot diff validation.

---

## Phase 3: WordPress Ecosystem Integrations

- [ ] **WooCommerce Adapter**: AI management of products, store layouts, categories, and checkout sections.
- [ ] **ACF / Dynamic Content Adapter**: Binding custom post fields directly to Elementor widget dynamic tags.
- [ ] **Forms Adapter**: Universal form builder support (Elementor Forms, Fluent, Gravity, WPForms).
- [ ] **SEO Integrations**: Dynamic meta optimization and schema markup (Yoast, RankMath, AIOSEO).

---

## Phase 4: Cloud Engine & Ecosystem Marketplace

- [ ] **Cloud Template Marketplace**: Remote synchronization of prebuilt AI templates and section kits.
- [ ] **Cloud Skills Repository**: Shareable agency and enterprise skill packages.
- [ ] **Multi-Model Router**: Dynamic LLM routing (Claude 3.5 Sonnet, Gemini 1.5 Pro, OpenAI GPT-4o).
- [ ] **Agency & Workspace Management**: Role-based access control and multi-site network support.
