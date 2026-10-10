import asyncio
import os
from playwright.async_api import async_playwright

async def run_full_flow():
    # 1. Reset cici
    os.system("php scratch/reset_cici.php")
    
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            viewport={'width': 414, 'height': 736},
            device_scale_factor=2.608695652,
            is_mobile=True,
            has_touch=True
        )
        page = await context.new_page()
        
        # Scene 1: Intro / Login page
        print("[Scene 1] Intro Login")
        await page.goto('http://127.0.0.1:8000/login', wait_until='networkidle')
        await page.wait_for_timeout(600)
        await page.screenshot(path='scratch/flow_sc01_intro.png')
        
        # Scene 2: Login action
        print("[Scene 2] Fill Login")
        await page.fill('#email', 'cici@gmail.com')
        await page.fill('#password', 'password')
        await page.click('button[type="submit"]')
        await page.wait_for_load_state('networkidle')
        await page.wait_for_timeout(800)
        await page.screenshot(path='scratch/flow_sc02_logged_in.png')
        print("URL after login:", page.url)
        
        # Ensure we are at onboarding
        if 'onboarding' not in page.url:
            await page.goto('http://127.0.0.1:8000/parent/onboarding', wait_until='networkidle')
            await page.wait_for_timeout(600)
            
        # Scene 3: Step 1 Fill phone
        print("[Scene 3] Fill Phone")
        phone_input = await page.wait_for_selector("input[x-model='phoneNumber']")
        await phone_input.fill('081234567890')
        await page.wait_for_timeout(600)
        await page.screenshot(path='scratch/flow_sc03_phone.png')
        
        # Scene 4: Click Lanjut ke Langkah 2
        print("[Scene 4] Next to Step 2")
        await page.click("button:has-text('Lanjut ke Langkah 2')")
        await page.wait_for_timeout(800)
        await page.screenshot(path='scratch/flow_sc04_step2_open.png')
        
        # Scene 5: Select Class 8A
        print("[Scene 5] Select Class 8A")
        class_sel = await page.wait_for_selector("select[x-model='selectedClassId']")
        await class_sel.select_option('10')
        await page.evaluate("""() => {
            const sel = document.querySelector("select[x-model='selectedClassId']");
            sel.dispatchEvent(new Event('change'));
        }""")
        await page.wait_for_timeout(800)
        await page.screenshot(path='scratch/flow_sc05_class_selected.png')
        
        # Scene 6: Type student name Najwa Dayani
        print("[Scene 6] Type Student Name")
        name_input = await page.wait_for_selector("input[x-model='typedStudentName']")
        await name_input.fill('Najwa Dayani')
        await page.evaluate("""() => {
            const el = document.querySelector("input[x-model='typedStudentName']");
            el.dispatchEvent(new Event('input'));
        }""")
        await page.wait_for_timeout(1000)
        await page.screenshot(path='scratch/flow_sc06_matched_card.png')
        
        # Scene 7: Click Pilih Siswa Ini & Scroll
        print("[Scene 7] Click Pilih Siswa Ini")
        match_btn = await page.wait_for_selector("button:has-text('Pilih Siswa Ini')")
        await match_btn.click()
        await page.wait_for_timeout(600)
        await page.evaluate("window.scrollBy({ top: 320, behavior: 'instant' })")
        await page.wait_for_timeout(600)
        await page.screenshot(path='scratch/flow_sc07_pilih_siswa.png')
        
        # Scene 8: Type NIS 1219 & Click Hubungkan Siswa Ini
        print("[Scene 8] Fill NIS and Hubungkan")
        nis_input = await page.wait_for_selector("input[x-model='verificationCode']")
        await nis_input.fill('1219')
        await page.evaluate("""() => {
            const el = document.querySelector("input[x-model='verificationCode']");
            el.dispatchEvent(new Event('input'));
        }""")
        await page.wait_for_timeout(500)
        await page.click("button:has-text('Hubungkan Siswa Ini')")
        await page.wait_for_timeout(1500)
        await page.screenshot(path='scratch/flow_sc08_connected_badge.png')
        
        # Scene 9: Click Lanjut ke Langkah 3 & Selesaikan
        print("[Scene 9] Step 3 Confirmation")
        await page.click("button:has-text('Lanjut ke Langkah 3')")
        await page.wait_for_timeout(800)
        await page.evaluate("window.scrollTo(0, 0)")
        await page.wait_for_timeout(500)
        await page.screenshot(path='scratch/flow_sc09_step3_summary.png')
        
        # Click Selesai & Masuk ke Dasbor Utama
        await page.click("button:has-text('Selesai & Masuk ke Dasbor Utama')")
        await page.wait_for_load_state('networkidle')
        await page.wait_for_timeout(1000)
        await page.screenshot(path='scratch/flow_sc10_dashboard_success.png')
        print("[Scene 10] Final Dashboard URL:", page.url)
        
        await browser.close()

if __name__ == '__main__':
    asyncio.run(run_full_flow())
