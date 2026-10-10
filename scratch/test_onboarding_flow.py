import asyncio
from playwright.async_api import async_playwright
import cv2

async def inspect():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        w_css = 414
        h_css = 736
        d_scale = 1080.0 / w_css

        context = await browser.new_context(
            viewport={'width': w_css, 'height': h_css},
            device_scale_factor=d_scale,
            is_mobile=True,
            has_touch=True
        )
        page = await context.new_page()

        # Login as cici@gmail.com
        await page.goto('http://127.0.0.1:8000/login', wait_until='networkidle')
        await page.fill('#email', 'cici@gmail.com')
        await page.fill('#password', 'password')
        await page.click('button[type="submit"]')
        await page.wait_for_load_state('networkidle')

        if '/parent/onboarding' not in page.url:
            await page.goto('http://127.0.0.1:8000/parent/onboarding', wait_until='networkidle')

        # Step 1
        phone_input = await page.query_selector("input[type='tel']")
        await phone_input.fill('081234567890')
        btn_step1 = await page.query_selector("button[type='submit']:has-text('Lanjut ke Langkah 2')")
        await btn_step1.click()
        await page.wait_for_timeout(800)

        # Wait for options in Step 2 to be attached in DOM
        await page.wait_for_selector("select[x-model='selectedClassId'] option[value='1']", state='attached', timeout=5000)
        class_sel = await page.query_selector("select[x-model='selectedClassId']")
        await class_sel.select_option(value='1')
        await page.evaluate("""() => {
            const sel = document.querySelector("select[x-model='selectedClassId']");
            sel.dispatchEvent(new Event('change'));
        }""")
        await page.wait_for_timeout(500)

        name_in = await page.query_selector("input[x-model='typedStudentName']")
        await name_in.fill('Citra Lestari')
        await page.evaluate("""() => {
            const el = document.querySelector("input[x-model='typedStudentName']");
            el.dispatchEvent(new Event('input'));
        }""")
        await page.wait_for_timeout(800)

        match_btn = await page.wait_for_selector("button:has-text('Pilih Siswa Ini')", timeout=5000)
        print("Match found! Text:", await page.inner_text("text=Siswa Ditemukan di Database"))
        await match_btn.click()
        await page.wait_for_timeout(500)

        nis_in = await page.query_selector("input[x-model='verificationCode']")
        await nis_in.fill('1002')
        await page.evaluate("""() => {
            const el = document.querySelector("input[x-model='verificationCode']");
            el.dispatchEvent(new Event('input'));
        }""")

        await page.screenshot(path='scratch/step2_filled.png')
        print("Screenshot step2_filled saved!")

        # Click Hubungkan Siswa Ini
        btn_step2 = await page.query_selector("button[type='submit']:has-text('Hubungkan Siswa Ini')")
        await btn_step2.click()
        await page.wait_for_timeout(1500)

        await page.screenshot(path='scratch/step2_connected.png')
        print("Screenshot step2_connected saved!")

        await browser.close()

if __name__ == '__main__':
    asyncio.run(inspect())
