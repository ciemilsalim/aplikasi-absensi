import asyncio
from playwright.async_api import async_playwright

async def inspect_mobile_flow():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        # Mobile viewport standard for V3.7: 414 x 736, device_scale_factor: 2.608695652 -> 1080 x 1920
        context = await browser.new_context(
            viewport={'width': 414, 'height': 736},
            device_scale_factor=2.608695652,
            is_mobile=True,
            has_touch=True
        )
        page = await context.new_page()
        
        print("=== STEP 0: LOGIN ===")
        await page.goto('http://127.0.0.1:8000/login')
        await page.fill('#email', 'cici@gmail.com')
        await page.fill('#password', 'password')
        await page.click('button[type="submit"]')
        await page.wait_for_load_state('networkidle')
        print("Logged in, URL:", page.url)
        
        if 'onboarding' not in page.url:
            await page.goto('http://127.0.0.1:8000/parent/onboarding')
            await page.wait_for_load_state('networkidle')
            
        await page.wait_for_timeout(1000)
        await page.screenshot(path='scratch/screen_step1.png')
        
        # In Step 1: phone input, submit button
        phone_el = await page.query_selector("input[x-model='phoneNumber']")
        phone_box = await phone_el.bounding_box()
        step1_next_btn = await (await page.query_selector("button:has-text('Lanjut ke Langkah 2')")).bounding_box()
        
        scale = 2.608695652
        print(f"Step 1 Phone (1080p): x={phone_box['x']*scale:.1f}, y={phone_box['y']*scale:.1f}, w={phone_box['width']*scale:.1f}, h={phone_box['height']*scale:.1f}, center=({(phone_box['x'] + phone_box['width']/2)*scale:.1f}, {(phone_box['y'] + phone_box['height']/2)*scale:.1f})")
        print(f"Step 1 Next (1080p): x={step1_next_btn['x']*scale:.1f}, y={step1_next_btn['y']*scale:.1f}, w={step1_next_btn['width']*scale:.1f}, h={step1_next_btn['height']*scale:.1f}, center=({(step1_next_btn['x'] + step1_next_btn['width']/2)*scale:.1f}, {(step1_next_btn['y'] + step1_next_btn['height']/2)*scale:.1f})")

        # Fill phone and click next
        await phone_el.fill('081234567890')
        await page.click("button:has-text('Lanjut ke Langkah 2')")
        await page.wait_for_timeout(1000)
        
        await page.screenshot(path='scratch/screen_step2_init.png')
        print("Now in Step 2, screenshot taken.")
        
        # Step 2 Elements
        class_sel = await page.query_selector("select[x-model='selectedClassId']")
        class_box = await class_sel.bounding_box()
        print(f"Step 2 Class Select (1080p): center=({(class_box['x'] + class_box['width']/2)*scale:.1f}, {(class_box['y'] + class_box['height']/2)*scale:.1f})")

        # Select class 10 (Kelas 8A)
        await class_sel.select_option(value='10')
        await page.evaluate("""() => {
            const sel = document.querySelector("select[x-model='selectedClassId']");
            sel.dispatchEvent(new Event('change'));
        }""")
        await page.wait_for_timeout(500)
        
        # Student Name Input
        name_in = await page.query_selector("input[x-model='typedStudentName']")
        name_box = await name_in.bounding_box()
        print(f"Step 2 Name Input (1080p): center=({(name_box['x'] + name_box['width']/2)*scale:.1f}, {(name_box['y'] + name_box['height']/2)*scale:.1f})")
        
        await name_in.fill('Najwa Dayani')
        await page.evaluate("""() => {
            const el = document.querySelector("input[x-model='typedStudentName']");
            el.dispatchEvent(new Event('input'));
        }""")
        await page.wait_for_timeout(800)
        
        # Match button "Pilih Siswa Ini"
        match_btn = await page.query_selector("button:has-text('Pilih Siswa Ini')")
        if match_btn:
            match_box = await match_btn.bounding_box()
            print(f"Step 2 Match Btn (1080p): center=({(match_box['x'] + match_box['width']/2)*scale:.1f}, {(match_box['y'] + match_box['height']/2)*scale:.1f})")
            await match_btn.click()
            await page.wait_for_timeout(500)
            
            # NIS input
            nis_in = await page.query_selector("input[x-model='verificationCode']")
            nis_box = await nis_in.bounding_box()
            print(f"Step 2 NIS Input (1080p): center=({(nis_box['x'] + nis_box['width']/2)*scale:.1f}, {(nis_box['y'] + nis_box['height']/2)*scale:.1f})")
            
            await nis_in.fill('1219')
            await page.evaluate("""() => {
                const el = document.querySelector("input[x-model='verificationCode']");
                el.dispatchEvent(new Event('input'));
            }""")
            await page.wait_for_timeout(300)
            
            # Hubungkan Siswa Ini button
            hub_btn = await page.query_selector("button:has-text('Hubungkan Siswa Ini')")
            hub_box = await hub_btn.bounding_box()
            print(f"Step 2 Hubungkan Btn (1080p): center=({(hub_box['x'] + hub_box['width']/2)*scale:.1f}, {(hub_box['y'] + hub_box['height']/2)*scale:.1f})")
            
            await hub_btn.click()
            await page.wait_for_timeout(1200)
            
            await page.screenshot(path='scratch/screen_step2_connected.png')
            print("Step 2 connected screenshot taken.")
            
            # Step 2 Next to Step 3 button
            step2_next_btn = await page.query_selector("button:has-text('Lanjut ke Langkah 3')")
            step2_next_box = await step2_next_btn.bounding_box()
            print(f"Step 2 Next to Step 3 (1080p): center=({(step2_next_box['x'] + step2_next_box['width']/2)*scale:.1f}, {(step2_next_box['y'] + step2_next_box['height']/2)*scale:.1f})")
            
            await step2_next_btn.click()
            await page.wait_for_timeout(1000)
            
            await page.screenshot(path='scratch/screen_step3.png')
            print("Step 3 screenshot taken.")
            
            # Step 3 Finish button
            step3_fin_btn = await page.query_selector("button:has-text('Selesai & Masuk ke Dasbor Utama')")
            step3_fin_box = await step3_fin_btn.bounding_box()
            print(f"Step 3 Finish Btn (1080p): center=({(step3_fin_box['x'] + step3_fin_box['width']/2)*scale:.1f}, {(step3_fin_box['y'] + step3_fin_box['height']/2)*scale:.1f})")
            
            await step3_fin_btn.click()
            await page.wait_for_load_state('networkidle')
            await page.wait_for_timeout(1000)
            
            await page.screenshot(path='scratch/screen_parent_dashboard.png')
            print("Parent Dashboard URL:", page.url)
            
        await browser.close()

if __name__ == '__main__':
    asyncio.run(inspect_mobile_flow())
