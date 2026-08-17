# WP Agent Studio — Technical Architecture

The **WP Agent Studio** WordPress plugin acts as a secure, high-performance bridge between external AI coding agents (such as Antigravity IDE) and WordPress + Elementor.

---

## 5-Layer System Architecture

```text
┌─────────────────────────────────────────────────────────┐
│                      External AI Agent                  │
│             (Antigravity IDE / MCP Client)              │
└────────────────────────────┬────────────────────────────┘
                             │
            JSON-RPC 2.0 over HTTP (`/wp-json/ai-elementor/v1/mcp`)
                             │
┌────────────────────────────▼────────────────────────────┐
│                    1. MCP Server Layer                  │
│       `src/MCP/Server.php` & `src/Core/RestApi.php`     │
└────────────────────────────┬────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────┐
│                 2. Security & Auth Guard                │
│    `TokenManager.php` | `PermissionManager.php`         │
└────────────────────────────┬────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────┐
│                    3. Agent Engine                      │
│    `AgentEngine.php` | `DSLCompiler.php` | `Context`   │
└────────────────────────────┬────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────┐
│                4. Elementor Abstraction                 │
│    `ElementorWriter.php` | `ElementorReader.php`        │
└────────────────────────────┬────────────────────────────┘
                             │
┌────────────────────────────▼────────────────────────────┐
│              5. WordPress Core & Elementor              │
│       `wp_posts` | `_elementor_data` | Media            │
└─────────────────────────────────────────────────────────┘
```

---

## Component Roles

* **MCP Server Layer (`src/MCP/Server.php`):** Implements Model Context Protocol 2024-11-05 specification. Dispatches `initialize`, `tools/list`, `tools/call`, `resources/list`, and `resources/read`.
* **Security Layer (`src/Security/TokenManager.php`):** Authenticates incoming Bearer tokens using salted hashes (`wp_hash_password()`) and enforces capability policies (`PermissionManager`).
* **Agent Engine (`src/Agent/AgentEngine.php`):** Central orchestrator. Translates incoming intents or high-level Agent DSL blueprints into Elementor elements, executes dry-runs, triggers revision snapshots, and coordinates audit logging.
* **Elementor Adapter (`src/Elementor/ElementorWriter.php` & `ElementorReader.php`):** Encapsulates raw `_elementor_data` serialization, unique element ID generation (7-char hex), and Flexbox container manipulation.
