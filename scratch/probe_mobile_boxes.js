import playwright from 'playwright';

async function main() {
  const browser = await playwright.chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true,
    deviceScaleFactor: 2
  });
  const page = await context.newPage();
  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle' });

  console.log('--- SCENE 2: LOGIN ---');
  const emailBox = await page.locator('#email:visible').boundingBox();
  const passBox = await page.locator('#password:visible').boundingBox();
  const submitBox = await page.locator('button[type=submit]:visible').boundingBox();
  console.log('email:', emailBox);
  console.log('password:', passBox);
  console.log('submit:', submitBox);

  await page.fill('#email', 'emil@gmail.com');
  await page.fill('#password', 'password');
  await page.click('button[type=submit]');
  await page.waitForURL('**/parent/**', { timeout: 10000 });

  console.log('--- SCENE 3: BUKA FORM ---');
  const navBox = await page.locator("a[href*='leave-requests/create']:visible, a[href*='leave-requests']:visible").first().boundingBox();
  console.log('nav:', navBox);

  await page.goto('http://127.0.0.1:8000/parent/leave-requests/create', { waitUntil: 'networkidle' });

  console.log('--- SCENE 4: PILIH ANAK ---');
  const studentBox = await page.locator('#student_id:visible').boundingBox();
  console.log('student_id:', studentBox);

  console.log('--- SCENE 5: PILIH JENIS IZIN ---');
  const typeBox = await page.locator('#type:visible').boundingBox();
  console.log('type:', typeBox);

  console.log('--- SCENE 6: ISI ALASAN ---');
  const reasonBox = await page.locator('#reason:visible').boundingBox();
  console.log('reason:', reasonBox);

  console.log('--- SCENE 7: TOMBOL KIRIM ---');
  const sendBox = await page.locator('button[type=submit]:visible').boundingBox();
  console.log('send:', sendBox);

  await browser.close();
}

main().catch(console.error);
