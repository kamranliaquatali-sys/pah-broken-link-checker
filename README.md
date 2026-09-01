# PAH Broken Link Checker

A free, browser-based SEO tool that audits up to **500 public website pages** for broken links, redirects, blocked responses, and request errors.

## Try It Online

- **Live tool:** https://proarticleshub.com/broken-link-checker/
- **Free SEO tools hub:** https://proarticleshub.com/free-online-seo-tools/
- **Product Hunt:** https://www.producthunt.com/products/pah-broken-link-checker?launch=pah-broken-link-checker

## Features

- Discovers eligible pages through a website's XML sitemap
- Scans up to 500 public pages
- Checks internal and external links
- Separates working links, redirects, broken responses, blocked responses, and request errors
- Shows the source page where every link was found
- Provides result filters, search, and CSV export
- Runs online without installing desktop software

## Response Guide

| Classification | Typical response | Recommended action |
|---|---|---|
| Working | 200 | Keep the link if it is still relevant |
| Redirect | 301, 302, 307, 308 | Review it; update permanent redirects to the final URL |
| Restricted | 401, 403, 429 | Verify manually before editing or removing the link |
| Broken | 404, 410 | Confirm the result, then replace or remove the link |
| Server error | 500–599 | Retry later and verify the destination |
| Request error | Timeout, DNS, or connection failure | Recheck in a browser or from another network |

> A non-200 response is not automatically a broken link. Authentication, rate limiting, bot protection, temporary outages, and firewall rules may require manual verification.

## How to Use

1. Open the [PAH Broken Link Checker](https://proarticleshub.com/broken-link-checker/).
2. Enter the website's homepage URL.
3. Start the link audit.
4. Review broken links, redirects, blocked responses, and request errors separately.
5. Open the reported source page to understand the link's context.
6. Confirm uncertain results manually before changing content.
7. Export the report as CSV when needed.

## Repository Scope

This public repository is maintained for:

- Product documentation
- Bug reports and reproducible false positives
- Feature requests
- Roadmap updates
- Community feedback

The production source code of the hosted tool is **not currently published** in this repository.

## Roadmap

- Improve retry and timeout handling
- Refine blocked-response classification
- Add clearer redirect-chain reporting
- Expand export and reporting options
- Publish troubleshooting examples

## Reporting a Problem

Before opening an issue, please verify the result in a normal browser. When reporting a reproducible problem, include:

- The public URL tested
- The status or classification reported by the tool
- The result observed in a normal browser
- Clear reproduction steps
- A screenshot with sensitive information removed, if useful

Please do not disclose credentials, private endpoints, personal information, or security-sensitive URLs.

## Contributing

Feedback and documentation improvements are welcome. Read [CONTRIBUTING.md](CONTRIBUTING.md) before submitting an issue or suggestion.

## Security

For responsible reporting guidance, see [SECURITY.md](SECURITY.md).

## License and Ownership

Repository documentation may be referenced with attribution. The hosted application, brand assets, and production source code remain proprietary unless explicitly stated otherwise.

© Pro Articles Hub
