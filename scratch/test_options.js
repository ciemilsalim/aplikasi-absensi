import { chromium } from 'playwright';

async function main() {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:8000/login');
  await page.fill('input[name=email]', 'jalil@gmail.com');
  await page.fill('input[name=password]', 'password');
  await page.click('button[type=submit]');
  await page.waitForTimeout(2000);
  await page.goto('http://127.0.0.1:8000/teacher/journals/create');
  await page.waitForTimeout(1000);
  console.log('Selecting option 7...');
  await page.locator('#schedule_id').first().selectOption('7');
  console.log('Selected option 7 successfully!');
  const val = await page.$eval('#schedule_id', el => el.value);
  console.log('Value is now:', val);
  await browser.close();
}

main().catch(console.error);
