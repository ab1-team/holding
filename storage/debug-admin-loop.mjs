import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
const page = await ctx.newPage();

let redirectCount = 0;
const chain = [];

page.on('request', req => {
    chain.push('→ ' + req.method() + ' ' + req.url());
});
page.on('response', res => {
    chain.push('← ' + res.status() + ' ' + res.url());
    if ([301, 302, 303, 307, 308].includes(res.status())) {
        redirectCount++;
    }
});
page.on('requestfailed', req => chain.push('✗ ' + req.url() + ' :: ' + req.failure()?.errorText));

try {
    await page.goto('http://admin.holding.test/', { waitUntil: 'networkidle', timeout: 15000 });
} catch (e) {
    chain.push('ERROR: ' + e.message);
}

console.log('=== REQUEST CHAIN ===');
chain.forEach(l => console.log(l));
console.log('Total redirects:', redirectCount);
console.log('=== FINAL URL ===');
console.log(page.url());
console.log('=== TITLE ===');
console.log(await page.title());

await browser.close();
