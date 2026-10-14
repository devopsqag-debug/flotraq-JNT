# Architecture

`demo/` is a Vite-built React/TypeScript single-page application. Reusable UI and state logic are kept in `src/main.tsx` for this milestone, with a dedicated responsive stylesheet. Recharts renders the dashboard activity and status charts; Lucide supplies icons.

The browser owns all state. A deterministic `seed()` creates 50 fictional shipments and 15 fictional customers. `localStorage` key `flotraq-jnt-demo-v1` stores changes; malformed or incomplete values fall back to the seed. Hash navigation keeps direct client-side routes usable by the Workers SPA fallback. No information is transmitted externally.

Illustrative rate formula: RM7.50 base + RM2.80 per kilogram above 1 kg + RM7 Express or RM13 Premium. These are demo estimates, not official courier quotations.
