# MCP Transport & Protocol Specification

## Endpoint URL
`POST https://your-wordpress-site.com/wp-json/ai-elementor/v1/mcp`

## Authorization Header
```http
Authorization: Bearer aiea_live_secret_token_generated_in_wp_admin
Content-Type: application/json
```

---

## 1. Server Initialization

### Request
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {}
}
```

### Response
```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": {
    "protocolVersion": "2024-11-05",
    "capabilities": {
      "tools": { "listChanged": true },
      "resources": { "subscribe": false, "listChanged": false }
    },
    "serverInfo": {
      "name": "AI Elementor Agent Server",
      "version": "1.0.0"
    }
  }
}
```

---

## 2. Tool Discovery (`tools/list`)

### Request
```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/list",
  "params": {}
}
```

---

## 3. Tool Execution (`tools/call`)

### Request
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "elementor_add_heading",
    "arguments": {
      "page_id": 12,
      "text": "Automate WordPress With AI",
      "tag": "h1",
      "color": "#6C63FF"
    }
  }
}
```

### Response
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "result": {
    "content": [
      {
        "type": "text",
        "text": "{\n  \"success\": true,\n  \"page_id\": 12,\n  \"element_id\": \"b3a91e4\",\n  \"node\": {\n    \"id\": \"b3a91e4\",\n    \"elType\": \"widget\",\n    \"widgetType\": \"heading\",\n    \"settings\": {\n      \"title\": \"Automate WordPress With AI\",\n      \"header_size\": \"h1\",\n      \"title_color\": \"#6C63FF\"\n    }\n  }\n}"
      }
    ],
    "isError": false
  }
}
```
