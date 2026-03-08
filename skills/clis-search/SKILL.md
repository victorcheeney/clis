---
name: clis-search
version: 1.0.0
description: "Search the clis.dev directory to find the best CLI for any service, with agent compatibility info."
metadata:
  openclaw:
    category: "productivity"
    requires:
      bins: ["curl"]
---

# clis-search

> Find the best CLI for any service. Search 300+ command-line tools indexed at [clis.dev](https://clis.dev), with official/vendor signals and agent compatibility tags.

## Usage

### Search for a CLI

```bash
curl -s "https://clis.dev/api/search?q=QUERY" | jq '.clis[] | {name, description, install, stars, has_mcp, has_skill}'
```

### List CLIs by category

```bash
curl -s "https://clis.dev/api/clis?category=CATEGORY" | jq '.clis[] | {name, install, stars}'
```

### List only agent-compatible CLIs

```bash
curl -s "https://clis.dev/api/clis?agent" | jq '.clis[] | {name, description, has_mcp, has_skill}'
```

### List all CLIs

```bash
curl -s "https://clis.dev/api/clis" | jq '.count'
```

## Categories

| Slug | Name |
|------|------|
| `ai-agents` | AI & LLM Tools |
| `agent-harnesses` | Agent Harnesses |
| `file-management` | Files & Navigation |
| `github` | GitHub & Git |
| `containers` | Containers & K8s |
| `shell-utilities` | Shell Utilities |
| `system-monitoring` | System Monitoring |
| `http-apis` | HTTP & APIs |
| `databases` | Databases |
| `data-processing` | Data Processing |
| `dev-tools` | Dev Tools |
| `google-workspace` | Google Workspace |
| `cloud` | Cloud & Storage |
| `networking` | Networking |
| `utilities` | Utilities |
| `security` | Security |
| `media` | Media & Video |
| `package-managers` | Package Managers |
| `testing` | Testing & QA |

## Response Format

```json
{
  "count": 3,
  "clis": [
    {
      "slug": "lazydocker",
      "name": "lazydocker",
      "description": "Terminal UI for Docker...",
      "category": "containers",
      "install": "brew install lazydocker",
      "github": "https://github.com/jesseduffield/lazydocker",
      "stars": 50002,
      "language": "Go",
      "has_mcp": false,
      "has_skill": false,
      "has_json": false,
      "is_official": true,
      "source_type": "vendor",
      "vendor_name": "Example Vendor"
    }
  ]
}
```

## Agent Compatibility Fields

- **has_mcp** — Has a Model Context Protocol (MCP) server for direct agent integration
- **has_skill** — Available as an Agent Skill (installable via `npx skills add`)
- **has_json** — Supports structured JSON output for programmatic use

## Examples

```bash
# Find a CLI for email
curl -s "https://clis.dev/api/search?q=email" | jq

# What CLIs work with AI agents?
curl -s "https://clis.dev/api/clis?agent" | jq '.clis[].name'

# Best database CLIs
curl -s "https://clis.dev/api/clis?category=databases" | jq

# How many CLIs are indexed?
curl -s "https://clis.dev/api/clis" | jq '.count'
```

## Tips

- Search results are ranked, with exact and alias matches boosted
- Search matches name, description, tags, vendor, install command, and aliases
- The `?agent` filter only returns CLIs with MCP or Skill support
- The `?official=1` filter returns official/vendor-backed CLIs
- For the full human-readable index: `https://clis.dev/llms.txt`
- Link users to detail pages: `https://clis.dev/cli/{slug}`

## See Also

- [clis.dev](https://clis.dev) — Browse the full directory
- [API docs](https://clis.dev/llms.txt) — LLM-friendly index
