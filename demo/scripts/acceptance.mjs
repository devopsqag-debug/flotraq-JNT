import { chromium } from 'playwright';
import fs from 'node:fs/promises';

const baseURL = process.env.BASE_URL || 'https://jnt.flotraq.com';
const outputDir = process.env.OUTPUT_DIR || 'artifacts/browser';
await fs.mkdir(outputDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, acceptDownloads: true });
const page = await context.newPage();
page.setDefaultTimeout(7000);
const consoleErrors = [];
page.on('console', message => { if (message.type() === 'error') consoleErrors.push(message.text()); });
page.on('pageerror', error => consoleErrors.push(error.message));

const check = async (label, action) => {
  try { await action(); console.log(`PASS ${label}`); }
  catch (error) { console.error(`FAIL ${label}: ${error.message}`); process.exitCode = 1; }
};
const go = async hash => {
  await page.goto(`${baseURL}/?acceptance=${Date.now()}#${hash}`, { waitUntil: 'networkidle' });
};
const clickText = text => page.getByText(text, { exact: true }).first().click();

await check('dashboard loading', async () => {
  await go('dashboard');
  await page.getByRole('heading', { name: 'Good morning, Flotraq' }).waitFor();
  await page.screenshot({ path: `${outputDir}/desktop-dashboard.png`, fullPage: true });
});
await check('shipment creation', async () => {
  await clickText('Create shipment');
  await page.getByRole('heading', { name: 'Create shipment' }).waitFor();
  await page.getByLabel('Receiver name').fill('Browser Acceptance User');
  await page.getByLabel('Receiver phone').fill('+60 12 999 8888');
  await page.getByLabel('Receiver address').fill('1 Test Street');
  await page.getByLabel('Destination').selectOption({ label: 'George Town' });
  await page.getByRole('button', { name: 'Continue' }).click();
  await page.getByLabel('Parcel description').fill('Acceptance parcel');
  await page.getByRole('button', { name: 'Review shipment' }).click();
  await page.getByRole('button', { name: 'Create demo shipment' }).click();
  await page.getByRole('heading', { name: 'Waybill management' }).waitFor();
  await page.reload({ waitUntil: 'networkidle' });
});
await check('shipment editing', async () => {
  await go('shipments');
  await page.locator('tbody tr').first().click();
  await page.getByRole('heading', { name: 'Edit shipment' }).waitFor();
  await page.getByLabel('Receiver name').fill('Edited Acceptance User');
  await page.getByRole('button', { name: 'Save changes' }).click();
  await page.getByText('Shipment updated').waitFor();
});
await check('tracking interface', async () => {
  await go('track');
  await page.getByRole('heading', { name: 'Track a shipment' }).waitFor();
  await page.locator('main').getByRole('button', { name: 'Track parcel' }).click();
  await page.screenshot({ path: `${outputDir}/tracking-interface.png`, fullPage: true });
});
await check('shipping calculator', async () => {
  await go('rates');
  await page.getByRole('heading', { name: 'Rate calculator' }).waitFor();
  await page.getByLabel('Weight (kg)').fill('2');
  await page.locator('.service-compare button').filter({ hasText: 'Express' }).click();
  await page.getByText('RM 17.30', { exact: true }).first().waitFor();
});
await check('customer management', async () => {
  await go('customers');
  await page.getByRole('heading', { name: 'Customers' }).waitFor();
  await page.getByRole('button', { name: 'Add customer' }).click();
  await page.getByLabel('Full name').fill('Acceptance Customer');
  await page.getByLabel('Phone').fill('+60 12 111 2222');
  await page.getByLabel('Email').fill('acceptance@example.test');
  await page.getByLabel('Address').fill('2 Test Street');
  await page.getByRole('button', { name: 'Save customer' }).click();
  await page.getByText('Customer saved').waitFor();
});
await check('waybill preview and PDF popup', async () => {
  await go('waybill');
  await page.getByRole('heading', { name: 'Waybill management' }).waitFor();
  await page.locator('.waybill-list button').first().click();
  await page.screenshot({ path: `${outputDir}/waybill-preview.png`, fullPage: true });
  const popupPromise = page.waitForEvent('popup');
  await page.locator('main').getByRole('button', { name: /Print \/ save PDF/ }).click();
  const popup = await popupPromise;
  await popup.waitForLoadState();
  if (!(await popup.title()).includes('DEMO Waybill')) throw new Error('waybill print popup missing');
  await popup.close();
});
await check('CSV import/export', async () => {
  await go('shipments');
  const downloadPromise = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Export' }).click();
  const download = await downloadPromise;
  if (!download.suggestedFilename().endsWith('.csv')) throw new Error('CSV download missing');
});
await check('reports and settings', async () => {
  await go('reports');
  await page.getByRole('heading', { name: 'Reports & analytics' }).waitFor();
  await go('settings');
  await page.getByRole('heading', { name: 'Settings' }).waitFor();
});
await check('localStorage persistence and reset', async () => {
  await go('settings');
  page.once('dialog', dialog => dialog.accept());
  await page.getByRole('button', { name: 'Restore original dataset' }).click();
  await page.getByText('Demo data restored').waitFor();
  const storage = await page.evaluate(() => localStorage.getItem('flotraq-jnt-demo-v1'));
  if (!storage || JSON.parse(storage).shipments.length !== 50) throw new Error('localStorage reset not persisted');
  await context.storageState({ path: `${outputDir}/storage-state.json` });
});
await check('mobile responsiveness', async () => {
  await page.setViewportSize({ width: 390, height: 844 });
  await go('dashboard');
  await page.getByRole('heading', { name: 'Good morning, Flotraq' }).waitFor();
  await page.screenshot({ path: `${outputDir}/mobile-dashboard.png`, fullPage: true });
  if (await page.locator('body').evaluate(el => el.scrollWidth > 390)) throw new Error('horizontal overflow at mobile width');
});
await check('SPA direct navigation', async () => {
  await page.goto(`${baseURL}/#reports`, { waitUntil: 'networkidle' });
  await page.getByRole('heading', { name: 'Reports & analytics' }).waitFor();
});

console.log(`Console errors: ${consoleErrors.length}`);
for (const error of consoleErrors) console.error(`CONSOLE ${error}`);
if (consoleErrors.length) process.exitCode = 1;
await browser.close();
