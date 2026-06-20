import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
const page = await ctx.newPage();

// Login as superadmin
await page.goto('http://admin.holding.test/login');
await page.fill('input[name="email"]', 'admin@holding.local');
await page.fill('input[name="password"]', 'password');
await page.click('button[type="submit"]');
await page.waitForURL(/admin\/dashboard/);

// Navigate to tenant 1
await page.goto('http://admin.holding.test/admin/tenants/1');
await page.waitForLoadState('networkidle');

// Find and click the Regenerate Secret confirm trigger
// The button is inside <x-ui.confirm id="regen-secret-{id}">
const regenBtn = page.locator('#regen-secret-1 button, [x-on\\:click] >> text=Regenerate').first();
await page.waitForTimeout(500);

// Try clicking the key icon button
const keyBtn = page.locator('button[title="Regenerate Secret"], button[aria-label*="Regenerate"], button:has(svg)').filter({ has: page.locator('svg path[d*="M15.75 5.25"]') }).first();

let clicked = false;
try {
    await keyBtn.click({ timeout: 2000 });
    clicked = true;
} catch (e) {
    // Try another selector
    const allButtons = await page.locator('button').all();
    for (const btn of allButtons) {
        const title = await btn.getAttribute('title');
        if (title && title.toLowerCase().includes('regen')) {
            await btn.click();
            clicked = true;
            break;
        }
    }
}

if (!clicked) {
    // Last resort: find confirm trigger via x-data and dispatch click
    await page.evaluate(() => {
        const triggers = document.querySelectorAll('[x-data] span[\\@click]');
        // Just click anything in the licenses table that might be a regenerate button
    });
    console.log('Could not find regen button');
}

await page.waitForTimeout(800);

// Screenshot
await page.screenshot({ path: 'storage/confirm-modal.png', fullPage: false });

// Inspect the message div computed style
const debug = await page.evaluate(() => {
    const modals = document.querySelectorAll('[x-show="open"]');
    const out = [];
    modals.forEach((m, i) => {
        if (m.offsetParent === null) return; // hidden
        const msgDiv = m.querySelector('div.mt-1\\.5, h2');
        if (!msgDiv) return;
        const cs = window.getComputedStyle(msgDiv);
        const rect = msgDiv.getBoundingClientRect();
        const parent = msgDiv.parentElement;
        const pcs = parent ? window.getComputedStyle(parent) : null;
        const prect = parent ? parent.getBoundingClientRect() : null;
        const grandparent = parent?.parentElement;
        const gpcs = grandparent ? window.getComputedStyle(grandparent) : null;
        const gprect = grandparent ? grandparent.getBoundingClientRect() : null;
        out.push({
            modal: i,
            tag: msgDiv.tagName,
            text: msgDiv.textContent.substring(0, 80),
            computed: {
                display: cs.display,
                whiteSpace: cs.whiteSpace,
                overflowWrap: cs.overflowWrap,
                wordBreak: cs.wordBreak,
                width: cs.width,
                maxWidth: cs.maxWidth,
                textAlign: cs.textAlign,
            },
            rect: { width: rect.width, height: rect.height, x: rect.x, right: rect.right },
            parentTag: parent?.tagName,
            parentClass: parent?.className,
            parentComputed: pcs ? { display: pcs.display, width: pcs.width, flex: pcs.flex } : null,
            parentRect: prect ? { width: prect.width, x: prect.x, right: prect.right } : null,
            grandparentClass: grandparent?.className,
            grandparentRect: gprect ? { width: gprect.width, x: gprect.x, right: gprect.right } : null,
        });
    });
    return out;
});

console.log(JSON.stringify(debug, null, 2));

await browser.close();
