# Antigravity IDE Integration Guide

This guide details how to connect **Antigravity IDE** (or any MCP-capable agent) to your WordPress site.

---

## Step 1: Install & Activate Plugin
1. Copy `ai-elementor-agent` directory into your WordPress site's `wp-content/plugins/` directory.
2. Log into WP-Admin and click **Activate** under **Plugins -> AI Elementor Agent**.

---

## Step 2: Generate MCP Bearer Token
1. Go to **WP-Admin -> AI Elementor Agent -> MCP Settings**.
2. Click **Generate New Token**.
3. Label it `Antigravity IDE`.
4. Copy the generated secret token (`aiea_live_...`).

---

## Step 3: Configure MCP Connection in Antigravity IDE

Add the server entry to your MCP client configuration (e.g. `mcp_config.json` or Antigravity Settings):

```json
{
  "mcpServers": {
    "wordpress-elementor": {
      "url": "https://your-wordpress-site.com/wp-json/ai-elementor/v1/mcp",
      "headers": {
        "Authorization": "Bearer aiea_live_YOUR_GENERATED_BEARER_TOKEN"
      }
    }
  }
}
```

---

## Step 4: Example Workflows

### Scenario 1: Create a New Landing Page
Prompt Antigravity:
> "Connect to WordPress MCP and build a new Elementor landing page called 'AI Agency' with a dark hero section, H1 heading, descriptive subtext, and a purple CTA button."

Antigravity will automatically:
1. Discover tools via `tools/list`.
2. Call `wordpress_get_site_info` to inspect environment.
3. Call `agent_build_page` with an Agent DSL payload or step-by-step:
   - `elementor_create_page`
   - `elementor_add_container`
   - `elementor_add_heading`
   - `elementor_add_text`
   - `elementor_add_button`

---

### Scenario 2: Redesign Existing Page & Responsive Styling
Prompt Antigravity:
> "Open page ID 12, inspect its structure, increase hero padding, and adjust heading typography for mobile devices."

Antigravity will:
1. Call `elementor_get_page` to retrieve page AST.
2. Locate the target heading element ID.
3. Call `elementor_update_element` with responsive typography parameters.
