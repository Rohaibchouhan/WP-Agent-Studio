# Elementor Deep Integration Specification

> **Core Principle**: Do not hardcode a small set of widgets. Dynamically inspect all registered Elementor Core, Elementor Pro, and 3rd-party widgets and expose their controls, types, defaults, and conditions to the AI agent.

---

## 1. Widget & Schema Discovery Engine

The platform includes a dynamic reflection system implemented in [WidgetSchemaExtractor.php](file:///d:/Laravel/AI%20Elementor%20Agent/src/Elementor/WidgetSchemaExtractor.php):

```text
┌─────────────────────────────────────────────────────────┐
│              Elementor Widgets Registry                 │
│   (Core Widgets, Pro Widgets, 3rd Party Plugin Widgets) │
└────────────────────────────┬────────────────────────────┘
                             │
                             ▼
              ┌─────────────────────────────┐
              │   Widget Discovery Engine   │
              │  `WidgetSchemaExtractor.php`│
              └──────────────┬──────────────┘
                             │
            Parses Controls, Types, & Defaults
                             │
                             ▼
              ┌─────────────────────────────┐
              │   AI-Ready Widget Schemas   │
              │  `elementor_get_widget_schema`│
              └─────────────────────────────┘
```

---

## 2. Dynamic Control Reflection

For each registered widget, [WidgetSchemaExtractor.php](file:///d:/Laravel/AI%20Elementor%20Agent/src/Elementor/WidgetSchemaExtractor.php) extracts:

1. **Widget Meta**: Widget name (`heading`, `button`, `eael-adv-tabs`), title, icon CSS class, categories, and pro/3rd-party status.
2. **Controls Map**:
   - `id`: Control key (e.g., `title`, `align`, `title_color`, `typography_typography`).
   - `label`: Human-readable label.
   - `type`: Control type (`text`, `select`, `color`, `media`, `typography`, `dimensions`).
   - `default`: Default control value.
   - `options`: Select/dropdown key-value options.
   - `responsive`: Boolean flag indicating mobile/tablet/desktop breakpoint support.
   - `dynamic`: Capability to bind ACF fields or WooCommerce post data.
   - `description`: Additional guidance string.

### MCP Tool Endpoint

The schema is exposed to external AI agents via [ElementorGetWidgetSchemaTool.php](file:///d:/Laravel/AI%20Elementor%20Agent/src/MCP/Tools/ElementorGetWidgetSchemaTool.php):

```json
// Call: elementor_get_widget_schema { "widget_type": "heading" }
{
  "success": true,
  "data": {
    "found": true,
    "type": "heading",
    "title": "Heading",
    "categories": ["basic"],
    "controls": {
      "title": {
        "id": "title",
        "label": "Title",
        "type": "text",
        "default": "Add Your Heading Text Here",
        "responsive": false,
        "dynamic": true
      },
      "header_size": {
        "id": "header_size",
        "label": "HTML Tag",
        "type": "select",
        "default": "h2",
        "options": { "h1": "H1", "h2": "H2", "h3": "H3", "h4": "H4", "p": "p" }
      }
    }
  }
}
```

---

## 3. Elementor Flexbox Container Hierarchy Model

```text
Page Element Tree
 └── Flexbox Container (Direction: Column / Row)
      ├── Sub-Container 1 (Direction: Row)
      │    ├── Heading Widget
      │    ├── Text Editor Widget
      │    └── Button Widget
      │
      └── Sub-Container 2 (Direction: Column)
           └── Image Widget
```

### JSON Structure Representation

```json
{
  "elType": "container",
  "isInner": false,
  "settings": {
    "flex_direction": "row",
    "flex_direction_mobile": "column",
    "container_type": "flex",
    "content_width": "boxed"
  },
  "elements": [
    {
      "elType": "widget",
      "widgetType": "heading",
      "settings": {
        "title": "Welcome to ElementAI Studio",
        "header_size": "h1",
        "align": "left",
        "title_color": "__globals__?id=primary"
      }
    }
  ]
}
```

---

## 4. Implementation Status

- [x] Created `WidgetSchemaExtractor.php` to dynamically inspect `\Elementor\Plugin::$instance->widgets_manager`.
- [x] Created `ElementorGetWidgetSchemaTool.php` exposing `elementor_get_widget_schema` via MCP.
- [x] Registered `ElementorGetWidgetSchemaTool` in `ToolRegistry.php`.
- [x] Updated `ContextManager::get_available_widgets()` to dynamically list all registered widgets.
