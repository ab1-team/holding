import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
const page = await ctx.newPage();

// Capture console + network
page.on('console', msg => console.log('[console]', msg.type(), msg.text()));
page.on('pageerror', err => console.log('[pageerror]', err.message));
page.on('requestfailed', req => console.log('[reqfailed]', req.url(), req.failure()?.errorText));
page.on('response', res => {
    if (res.status() >= 400) console.log('[response]', res.status(), res.url());
});

// Try login at acme.holding.test
await page.goto('http://acme.holding.test/login');
await page.waitForLoadState('networkidle');

console.log('--- cookies before login ---');
console.log(await ctx.cookies());

// Get form token
const html = await page.content();
const tokenMatch = html.match(/name="_token"\s+value="([^"]+)"/);
console.log('CSRF token in form:', tokenMatch?.[1]?.substring(0, 20) + '...');

// Get first owner email
const owner = await page.evaluate(async () => {
    return null; // can't query DB
});

await page.fill('input[name="email"]', 'direktur@sdm.com');
await page.fill('input[name="password"]', 'password');
await page.screenshot({ path: 'storage/login-before.png' });

await Promise.all([
    page.waitForLoadState('networkidle'),
    page.click('button[type="submit"]'),
]);

await page.waitForTimeout(2000);

console.log('--- URL after submit ---');
console.log(page.url());
console.log('--- title ---');
console.log(await page.title());

await page.screenshot({ path: 'storage/login-after.png', fullPage: true });

// Check for 419
const bodyText = await page.locator('body').textContent();
console.log('--- body text (first 500) ---');
console.log(bodyText.substring(0, 500));

console.log('--- cookies after ---');
console.log(await ctx.cookies());

await browser.close();
