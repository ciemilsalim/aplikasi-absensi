import playwright from 'playwright';

(async () => {
  const browser = await playwright.chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1920, height: 1080 } });
  const page = await context.newPage();
  
  console.log("Navigating to login...");
  await page.goto('http://127.0.0.1:8000/login');
  await page.fill('#email', 'emil@gmail.com');
  await page.fill('#password', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
  
  console.log("Current URL after login:", page.url());
  const links = await page.$$eval('a', els => els.map(e => ({ href: e.href, text: e.innerText.trim() })));
  console.log("Relevant links found:", links.filter(l => l.href.includes('leave') || l.href.includes('izin')));
  
  const createLink = page.locator("a:has-text('Ajukan Izin / Sakit')").first();
  console.log("Is visible:", await createLink.isVisible());
  console.log("Bounding box:", await createLink.boundingBox());
  
  await browser.close();
})();
