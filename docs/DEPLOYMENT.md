# Deployment

Build locally in `demo/` with `npm ci` and `npm run build`. `wrangler.toml` publishes `demo/dist` as Cloudflare Workers Static Assets under project `flotraq-jnt` and uses SPA fallback handling.

Deploy with the existing authenticated Wrangler account using `npx wrangler deploy`. No PHP, Node runtime, database, Redis, Docker, PM2, worker, or cron process is required on the VPS. Verify the assigned URL with `curl -I`, then exercise the browser workflows against the public URL.
