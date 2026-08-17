# MCP Gateway, WP Admin AI Chat & Approval System

> **Control & Safety**: The AI Platform includes both a Model Context Protocol (MCP) server for external IDEs/Agents (Antigravity, Cursor, Claude Code) and a native WP Admin Chat interface with diff approval and revision safety.

---

## 1. MCP Gateway Protocol Specification

The platform exposes an MCP 2024-11-05 endpoint at `/wp-json/ai-elementor/v1/mcp`.

### Core MCP Tools Inventory

```text
WordPress Tools:
  - wordpress_get_site_info      # Returns WP metadata, theme, plugins, site logo & branding
  - wordpress_get_pages          # List published/draft pages with Elementor status
  - wordpress_create_page        # Create a new blank page initialized for Elementor
  - wordpress_get_media          # Search media library attachments
  - wordpress_upload_media       # Upload new image asset to WP media library

Elementor Tools:
  - elementor_get_page_tree      # Returns full Flexbox element tree of a page
  - elementor_add_container      # Add new container node to layout
  - elementor_add_widget         # Add native widget (Heading, Text, Button, Image, etc.)
  - elementor_update_element     # Update settings/styles of any container or widget
  - elementor_delete_element     # Delete element node
  - elementor_get_global_colors  # Fetch global color tokens
  - elementor_set_global_color   # Create or update global color token
  - elementor_get_global_fonts   # Fetch global typography presets
  - elementor_set_global_font    # Create or update typography preset
  - elementor_validate_page      # Validate container nesting & widget settings
  - elementor_create_backup      # Take snapshot backup of page before modification
  - elementor_restore_backup     # Rollback page to specified backup ID
```

---

## 2. WP Admin Native AI Chat Interface

```text
┌─────────────────────────────────────────────────────────────┐
│ 🤖 ElementAI Studio Assistant                               │
├─────────────────────────────────────────────────────────────┤
│ User: Build a hero section with two buttons and a logo grid │
│                                                             │
│ AI: Planned layout changes:                                 │
│                                                             │
│  [+] ADD Container (Flex Row)                               │
│  [+] ADD Heading "Build Faster with ElementAI Studio"       │
│  [+] ADD Button "Get Started" (Color: Primary)              │
│  [+] ADD Button "View Demo" (Color: Accent)                 │
│  [+] ADD Logo Cloud Container (5 Partner Logos)             │
│                                                             │
│  [ Preview Dry Run ]   [ Approve & Apply ]   [ Reject ]     │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Version History & Revision Safety

Every AI action triggers a snapshot revision in `wp_postmeta` under `_ai_elementor_revisions`:

```json
{
  "backup_id": "bak_66bc10f2a9",
  "page_id": 42,
  "timestamp": "2026-08-14 21:45:00",
  "client_id": "mcp-client",
  "note": "Before adding Hero container",
  "elements_snapshot": [...]
}
```

If any layout modification fails visual QA or user approval, one-click rollback restores the previous revision cleanly.
