# Testing

The production build is verified with `npm run build`, which runs TypeScript compilation followed by Vite production bundling. Functional browser acceptance should cover dashboard seed data, creation, filtering/search, local persistence, tracking status simulation, rate calculation, CSV import/export, waybill printing, customer editing, reset, responsive layout, direct hash navigation, and the absence of network requests.

Browser screenshots are captured only when a browser runner is available; source/build success is not treated as browser acceptance evidence.
