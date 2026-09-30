const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

async function run() {
  const recordingDir = path.resolve('.tutorial-video/recording');
  fs.mkdirSync(recordingDir, { recursive: true });

  console.log('Launching browser for Milestone 1 tutorial recording...');
  const browser = await chromium.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });

  const context = await browser.newContext({
    viewport: { width: 1920, height: 1080 },
    recordVideo: {
      dir: recordingDir,
      size: { width: 1920, height: 1080 }
    }
  });

  const page = await context.newPage();

  console.log('Step 1: Navigating to login page...');
  await page.goto('http://127.0.0.1:8000/login', { waitUntil: 'networkidle' });
  await page.waitForTimeout(2000);

  console.log('Step 2: Filling demo credentials...');
  await page.fill('input[name="email"]', 'budi.guru@mokopani.sch.id');
  await page.fill('input[name="password"]', 'password');
  await page.waitForTimeout(1000);

  console.log('Step 3: Submitting login...');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]')
  ]);
  await page.waitForTimeout(2000);

  console.log('Step 4: Navigating to Jurnal Mengajar menu...');
  await page.goto('http://127.0.0.1:8000/teacher/journals', { waitUntil: 'networkidle' });
  await page.waitForTimeout(2500);

  console.log('Step 5: Clicking Tulis Jurnal Baru...');
  await page.click('a[href*="journals/create"]');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(2000);

  console.log('Step 6: Filling Jurnal Form...');
  // Select Schedule ID 19 or first available option
  const scheduleSelect = page.locator('#schedule_id');
  const options = await scheduleSelect.locator('option').all();
  if (options.length > 1) {
    const val = await options[1].getAttribute('value');
    await scheduleSelect.selectOption(val);
  }
  await page.waitForTimeout(1000);

  await page.fill('#topic', 'Persamaan Linear Satu Variabel & Konsep Aljabar');
  await page.waitForTimeout(800);

  await page.fill('#learning_objective', 'Peserta didik mampu memahami konsep variabel, koefisien, dan menyelesaikan persamaan linear satu variabel secara tepat.');
  await page.waitForTimeout(800);

  await page.fill('#activity', 'Apersepsi → Demonstrasi Penyelesaian Soal → Diskusi Kelompok LKPD → Presentasi Hasil');
  await page.waitForTimeout(800);

  await page.fill('#assessment', 'Observasi Kinerja & LKPD Harian');
  await page.waitForTimeout(800);

  await page.fill('#reflection', 'Sebagian besar peserta didik (85%) mencapai Tujuan Pembelajaran dengan sangat baik.');
  await page.waitForTimeout(800);

  await page.fill('#follow_up', 'Pengayaan materi aljabar tingkat lanjut dan pendampingan remedial bagi 3 murid.');
  await page.waitForTimeout(2500);

  console.log('Step 7: Submitting form...');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.click('button[type="submit"]')
  ]);
  await page.waitForTimeout(3500);

  console.log('Step 8: Verified success screen!');
  const videoPath = await page.video().path();
  await context.close();
  await browser.close();

  console.log('Raw video saved to:', videoPath);

  // Copy raw video to deterministic name in .tutorial-video/recording/raw-recording.webm
  const destPath = path.join(recordingDir, 'raw-recording.webm');
  fs.copyFileSync(videoPath, destPath);
  console.log('Recording complete:', destPath);
}

run().catch(err => {
  console.error('Error recording:', err);
  process.exit(1);
});
