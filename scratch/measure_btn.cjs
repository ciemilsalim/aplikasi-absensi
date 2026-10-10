const playwright = require('playwright');
(async () => {
  const browser = await playwright.chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true,
    deviceScaleFactor: 2
  });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:8000/login');
  await page.fill('#email', 'emil@gmail.com');
  await page.fill('#password', 'password');
  await page.click('button[type="submit"]');
  await page.waitForURL('**/dashboard**');
  await page.goto('http://127.0.0.1:8000/leave-requests/create');
  await page.waitForLoadState('networkidle');

  const btn = await page.$('button[type="submit"]');
  if (btn) {
    const boxBefore = await btn.boundingBox();
    console.log('Button box before scroll (390x844):', boxBefore);
    await btn.scrollIntoViewIfNeeded();
    await page.waitForTimeout(300);
    const boxAfter = await btn.boundingBox();
    console.log('Button box after scroll (390x844):', boxAfter);
    const scrollY = await page.evaluate(() => window.scrollY);
    console.log('Window scrollY:', scrollY);
    console.log('Button box in 1080x1920:', {
      x: Math.round(boxAfter.x * (1080 / 390)),
      y: Math.round(boxAfter.y * (1920 / 844)),
      width: Math.round(boxAfter.width * (1080 / 390)),
      height: Math.round(boxAfter.height * (1920 / 844))
    });
  }
  await browser.close();
})();
