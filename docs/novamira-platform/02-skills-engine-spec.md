# AI Skills Engine Specification

The **AI Skills Engine** decouples domain knowledge from code execution. Instead of hardcoding prompt strings into PHP code, capabilities are packaged as modular, declarative **Skills**.

---

## 1. Skill Folder & Package Layout

```text
skills/
├── wordpress/
│   ├── create-page/
│   ├── edit-page/
│   ├── manage-media/
│   └── manage-users/
│
├── elementor/
│   ├── create-page/
│   ├── create-container/
│   ├── create-widget/
│   ├── modify-widget/
│   ├── responsive-design/
│   ├── global-styles/
│   └── visual-qa/
│
├── woocommerce/
│   ├── create-product/
│   ├── edit-product/
│   ├── create-category/
│   └── manage-orders/
│
├── acf/
│   ├── create-cpt/
│   ├── create-fields/
│   └── dynamic-content/
│
└── seo/
    ├── yoast/
    ├── rankmath/
    └── aioseo/
```

---

## 2. Anatomy of a Skill Package

Each skill contains a standard directory structure:

```text
skill-name/
├── skill.json          # Metadata, permissions, tools, and trigger keywords
├── prompt.md           # Instructions, best practices, and guidelines for LLM
├── schema.json         # Input/Output validation schema
├── examples.json       # Few-shot execution examples
└── validation.json     # Post-execution sanity checks & rollback criteria
```

### Example: `skill.json`

```json
{
  "name": "elementor-create-landing-page",
  "version": "1.0.0",
  "category": "elementor",
  "description": "Construct high-converting, responsive landing pages using native Elementor Flexbox containers.",
  "permissions": ["edit_pages", "publish_pages"],
  "tools": [
    "wordpress_get_site_info",
    "elementor_get_global_colors",
    "elementor_get_global_fonts",
    "agent_build_page",
    "elementor_validate_page"
  ],
  "safety": {
    "dry_run_supported": true,
    "auto_backup": true,
    "require_approval": true
  }
}
```

### Example: `prompt.md`

```markdown
# Skill: Create Elementor Landing Page

## Objective
Build a complete landing page matching the user's brand identity and requirements.

## Workflow Rules
1. Call `wordpress_get_site_info` to retrieve site title, logo, and active theme.
2. Call `elementor_get_global_colors` and `elementor_get_global_fonts` to fetch design tokens.
3. Construct container tree:
   - Header / Hero Section
   - Features Grid
   - Social Proof / Testimonials
   - Pricing Table
   - FAQ Accordion
   - Call to Action (CTA)
4. Apply global design system tokens to widgets instead of hardcoding raw hex values.
5. Apply mobile-first responsive settings (column layout on mobile, row on desktop).
6. Validate element structure via `elementor_validate_page`.
```
