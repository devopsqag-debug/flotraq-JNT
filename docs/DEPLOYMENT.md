# Deployment

Build locally in `demo/` with `npm ci` and `npm run build`. `wrangler.toml` publishes `demo/dist` as Cloudflare Workers Static Assets under project `flotraq-jnt` and uses SPA fallback handling.

Deploy with the authenticated Cloudflare account that owns `flotraq-jnt`:

```bash
npx wrangler whoami
npx wrangler deploy
```

If `whoami` reports that Wrangler is not authenticated, run `npx wrangler login` and complete the browser OAuth flow, then repeat the account check before deploying. Never put an API token in this repository or in command output.

After deployment, verify both the response and SPA fallback:

```bash
curl -I https://jnt.flotraq.com/
curl -I https://jnt.flotraq.com/#shipments
```

The separate portfolio compatibility URL should also return `200`:

```bash
curl -I https://flotraq.com/freelance/jnt-demo
```

The original package ZIP at `/var/www/flotraq.com/freelance/jnt-demo/laravel-jnt-express.zip` must remain preserved.

No PHP, Node runtime, database, Redis, Docker, PM2, worker, or cron process is required on the VPS. Exercise the browser workflows against the public URL after the deployment returns successfully.
