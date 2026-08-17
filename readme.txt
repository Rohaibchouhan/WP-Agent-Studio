=== WP Agent Studio ===
Contributors: rohaibchouhan, wp-agent-studio
Tags: elementor, mcp, ai, agent, automation, wordpress
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WP Agent Studio exposes a Model Context Protocol (MCP) server and WordPress Abilities API allowing AI agents to control WordPress and Elementor natively.

== Description ==

WP Agent Studio is a production-quality, MCP-first WordPress plugin that creates a deep bridge between external AI coding agents (such as Antigravity IDE) and WordPress + Elementor.

= Key Features =
* **Official WordPress Abilities API Bridge:** Full compatibility with the official `WordPress/mcp-adapter` standard.
* **MCP Server Layer:** Exposed JSON-RPC 2.0 endpoint for tool discovery and execution.
* **Elementor 4.0 Atomic & Composite Engine:** Flexbox layout building, typed-props elements, and token-saving composite page generators.
* **WordPress Ecosystem Integrations:** WooCommerce store & products, ACF dynamic tags, Universal Forms builder, and SEO JSON-LD schema engine.
* **Stock Media Sideloading:** Unsplash, Pexels, and Pixabay media import directly into WP Media Library.
* **Granular Access Control:** Toggle module permissions and manage API keys via WP Admin Dashboard.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-agent-studio` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **WP Agent Studio -> MCP Access Control** in your WP-Admin dashboard.
4. Click **Generate Token** to generate a secret Bearer Token for your AI Agent.
5. Configure your MCP Client (e.g. Antigravity IDE) to connect to `https://your-site.com/wp-json/ai-elementor/v1/mcp`.
