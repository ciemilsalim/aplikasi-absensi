import playwright from 'playwright';

(async () => {
  const browser = await playwright.chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 2,
    isMobile: true,
    hasTouch: true
  });
  const page = await context.newPage();
  
  console.log("Mobile Navigating to login...");
  await page.goto('http://127.0.0.1:8000/login');
  await page.fill('#email', 'emil@gmail.com');
  await page.fill('#password', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  
  console.log("Mobile Current URL after login:", page.url());
  const links = await page.$$eval('a', els => els.map(e => ({ href: e.href, text: e.innerText.trim(), visible: e.offsetParent !== null })));
  console.log("Mobile Relevant links:", links.filter(l => l.href.includes('leave') || l.href.includes('izin')));
  
  const createLinks = await page.$$("a[href*='leave-requests/create']");
  console.log("Total create links on mobile:", createLinks.length);
  for (let i = 0; i < createLinks.length; i++) {
    const isVis = await createLinks[i].isVisible();
    const box = await createLinks[i].boundingBox();
    console.log(`Link ${i}: visible=${isVis}`, box);
  }
  
  await browser.close();
})();
