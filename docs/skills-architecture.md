# AI Skills System Architecture

The **AI Skills System** provides declarative knowledge, rules, and best-practice blueprints that guide AI agents (Claude, Gemini, OpenAI, Cursor, etc.) on how to execute multi-step operations against the **AI Elementor Agent MCP Server**.

While **MCP Tools** define *what capabilities* the AI can execute (`create_container`, `add_widget`, `get_global_styles`), **AI Skills** define *how to use those capabilities intelligently* to build high-converting, beautiful, accessible, and responsive Elementor pages.

---

## 1. Skill Architecture Overview

```text
                  ┌─────────────────────────────────┐
                  │          User Request           │
                  │ "Build a modern SaaS page..."   │
                  └────────────────┬────────────────┘
                                   │
                                   ▼
                  ┌─────────────────────────────────┐
                  │            AI Agent             │
                  │   (Selects applicable skill)    │
                  └────────────────┬────────────────┘
                                   │
                    Loads procedural guidelines & rules
                                   │
                                   ▼
                  ┌─────────────────────────────────┐
                  │      Skill Procedural Logic     │
                  │ 1. Discover site & branding     │
                  │ 2. Read global tokens           │
                  │ 3. Plan container layout        │
                  │ 4. Execute MCP tool sequence    │
                  │ 5. Validate visual structure    │
                  └────────────────┬────────────────┘
                                   │
                                   ▼
                  ┌─────────────────────────────────┐
                  │     WordPress MCP Server        │
                  │ (Executes real actions on site) │
                  └─────────────────────────────────┘
```

---

## 2. Core Skill Packs

### `elementor-create-landing-page`
- **Goal**: Construct complete, responsive landing pages from high-level intent.
- **Workflow**:
  1. Inspect site info & branding via `wordpress_get_site_info`.
  2. Retrieve global color & typography tokens via `elementor_get_global_colors` and `elementor_get_global_fonts`.
  3. Plan section hierarchy (Hero, Features, Social Proof, Pricing, FAQ, CTA).
  4. Create container tree structure using Flexbox layout principles.
  5. Add widgets (Heading, Text Editor, Button, Image, Icon Box).
  6. Validate layout structure via `elementor_validate_page`.

### `elementor-responsive-design`
- **Goal**: Apply mobile-first responsive settings to Elementor elements.
- **Rules**:
  - Desktop container direction: `row`; Mobile container direction: `column`.
  - Padding adjustments: `40px` on desktop, `20px` on mobile.
  - Typography sizing: Scaled down appropriately per breakpoint (`mobile_font_size`).

### `elementor-design-system`
- **Goal**: Maintain visual consistency across all generated pages.
- **Rules**:
  - Always link widget colors to active system colors (`primary`, `secondary`, `text`, `accent`).
  - Do not hardcode ad-hoc hex values when global color tokens exist.
  - Apply active typography presets for headings (`h1`, `h2`, `h3`) and body text.

---

## 3. Skill Definition Schema Example

```yaml
name: elementor-create-landing-page
description: Guide the AI agent in creating a modern, conversion-focused Elementor page using native flexbox containers.
tools:
  - wordpress_get_site_info
  - elementor_get_global_colors
  - elementor_get_global_fonts
  - agent_build_page
  - elementor_validate_page
rules:
  - Avoid raw HTML widget injection when native widgets exist.
  - Reuse site branding logo URL returned by `wordpress_get_site_info`.
  - Max container nesting level: 3 levels deep.
  - Always generate layout revision snapshot before execution.
```
