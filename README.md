# Flotraq J&T Express Shipping Management Demo

Demo URL : https://jnt.flotraq.com/

An interactive logistics SaaS demonstration based on the supplied Laravel J&T Express package. The demo uses React, TypeScript, Vite, Recharts, Lucide, browser localStorage, and Cloudflare Workers Static Assets.

## Features

Dashboard metrics and charts, shipment creation and filtering, status tracking, illustrative rate calculation, CSV import/export, printable demo waybills, customer management, analytics, settings, and resettable fictional seed data.

## Run locally

```bash
cd demo
npm ci
npm run dev
```

Build with `npm run build`. Deploy static assets with `npx wrangler deploy` from the repository root.

Run the browser acceptance suite against the canonical demo with `node demo/scripts/acceptance.mjs`. It requires the locally installed Playwright Chromium browser and writes screenshots to `demo/artifacts/browser/`.

## Cloudflare deployment

The configured project is `flotraq-jnt`, with the canonical public URL `https://jnt.flotraq.com`. Before deploying, authenticate and confirm the intended account with `npx wrangler whoami`; deployment uses `wrangler.toml` and publishes `demo/dist` with SPA fallback routing. The URL is not considered live until a successful `npx wrangler deploy` and HTTP/browser acceptance check have been completed.

The portfolio case study uses `/projects/jnt-express-shipping-demo`. The legacy `/freelance/jnt-demo` path is retained by the separate Flotraq Laravel application as a compatibility route; the original Laravel package ZIP remains preserved on the portfolio host.

## Boundary and disclaimer

Independent demonstration project. Not affiliated with J&T Express. All shipments and rates are simulated. No real courier transactions, customer data, API credentials, backend, database, or external submission are used. Browser data persists in localStorage until cleared or reset.

## Source

The original Laravel package is preserved under `original-package/`. Its declared license is GPL-2.0-or-later. See `docs/SOURCE-PACKAGE-ANALYSIS.md` for the verified functionality boundary.
