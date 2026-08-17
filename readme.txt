=== AI Elementor Agent ===
Contributors: rohaibchouhan, ai-elementor-agent
Tags: elementor, mcp, ai, agent, automation
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI Elementor Agent exposes a Model Context Protocol (MCP) server allowing AI agents to control WordPress and Elementor natively.

== Description ==

AI Elementor Agent is a production-quality, MCP-first WordPress plugin that creates a bridge between external AI coding agents (such as Antigravity IDE) and WordPress + Elementor.

= Key Features =
* **MCP Server Layer:** Exposed JSON-RPC 2.0 endpoint for tool discovery and execution.
* **Elementor Agent DSL:** Safe intermediate domain-specific schema translating AI intents into valid Elementor documents.
* **Granular Security:** Bearer token authentication, capability verification, and non-destructive defaults.
* **Audit Logging & Revisions:** Complete audit trail of all mutations and automated pre-edit snapshots.
* **Secondary Direct AI:** Built-in adapters for OpenAI, Anthropic, Gemini, OpenRouter, and custom endpoints.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/ai-elementor-agent` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **AI Elementor Agent -> MCP** in your WP-Admin dashboard.
4. Click **Generate Token** to generate a secret Bearer Token for your AI Agent.
5. Configure your MCP Client (e.g. Antigravity IDE) to connect to `https://your-site.com/wp-json/ai-elementor/v1/mcp`.
