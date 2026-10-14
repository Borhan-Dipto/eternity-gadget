import { chromium, devices } from 'playwright';

const browser = await chromium.launch();
const out = 'storage/app/screenshots';
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

await page.goto('http://127.0.0.1:8000/', { waitUntil: 'domcontentloaded', timeout: 60000 });
await page.waitForTimeout(1500);
await page.screenshot({ path: `${out}/01-home-header.png`, fullPage: false });

await page.locator('.ig-menu a', { hasText: 'Mobile Cases' }).hover();
await page.waitForTimeout(700);
await page.screenshot({ path: `${out}/02-home-mobile-cases-mega.png`, fullPage: false });

const more = page.locator('.ig-menu-more-btn');
if (await more.count()) {
  await more.hover();
  await page.waitForTimeout(500);
  await page.screenshot({ path: `${out}/03-home-more-menu.png`, fullPage: false });
}

await page.goto('http://127.0.0.1:8000/category/iphone-case?per_page=24', { waitUntil: 'domcontentloaded', timeout: 60000 });
await page.waitForTimeout(1200);
await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
await page.waitForTimeout(500);
await page.screenshot({ path: `${out}/04-category-pagination.png`, fullPage: false });

await page.goto('http://127.0.0.1:8000/product/creative-skin-feel-magnetic-ring-holder-stand-phone-case-for-iphone-209', { waitUntil: 'domcontentloaded', timeout: 60000 });
await page.waitForTimeout(1200);
await page.screenshot({ path: `${out}/05-product-desktop.png`, fullPage: false });

const mobile = await browser.newPage({
  ...devices['iPhone 12'],
});
await mobile.goto('http://127.0.0.1:8000/product/creative-skin-feel-magnetic-ring-holder-stand-phone-case-for-iphone-209', { waitUntil: 'domcontentloaded', timeout: 60000 });
await mobile.waitForTimeout(1200);
await mobile.screenshot({ path: `${out}/06-product-mobile.png`, fullPage: false });

await mobile.locator('.chat-trigger-btn').click();
await mobile.waitForTimeout(500);
await mobile.screenshot({ path: `${out}/07-mobile-chat-widget.png`, fullPage: false });

const phoneHref = await mobile.locator('.chat-popup-link.phone').getAttribute('href');
const waHref = await mobile.locator('.chat-popup-link.whatsapp').getAttribute('href');
const msgHref = await mobile.locator('.chat-popup-link.facebook').getAttribute('href');

await page.goto('http://127.0.0.1:8000/', { waitUntil: 'domcontentloaded' });
await page.waitForTimeout(800);
const headerMeta = await page.evaluate(() => {
  const actions = document.querySelector('.header-actions');
  const icons = [...document.querySelectorAll('.header-actions .header-icon-btn')];
  const styles = icons.map((el) => getComputedStyle(el).color);
  const rect = actions?.getBoundingClientRect();
  const logo = document.querySelector('.ig-logo')?.getBoundingClientRect();
  return {
    iconCount: icons.length,
    colors: styles,
    actionsTop: rect?.top,
    logoTop: logo?.top,
    actionsRight: rect?.right,
    wrapDetected: actions && logo ? rect.top > logo.bottom + 10 : false,
    hasMega: !!document.querySelector('.ig-mega'),
    hasMore: !!document.querySelector('.ig-menu-more'),
  };
});

const paginationCount = await page.evaluate(async () => {
  const res = await fetch('/category/iphone-case?per_page=24');
  const html = await res.text();
  return (html.match(/sf-page-num/g) || []).length;
});

console.log(JSON.stringify({ phoneHref, waHref, msgHref, headerMeta, paginationCount }, null, 2));
await browser.close();
