import asyncio
from playwright.async_api import async_playwright

async def inspect_step2():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page()
        await page.goto('http://127.0.0.1:8000/login')
        await page.fill('#email', 'cici@gmail.com')
        await page.fill('#password', 'password')
        await page.click('button[type="submit"]')
        await page.wait_for_load_state('networkidle')
        await page.goto('http://127.0.0.1:8000/parent/onboarding')
        await page.wait_for_load_state('networkidle')
        
        # Switch to step 2 in Alpine
        await page.evaluate("""() => {
            const scope = Alpine.$data(document.querySelector('main'));
            scope.currentStep = 2;
        }""")
        await page.wait_for_timeout(500)
        
        # Select option 10
        sel = await page.query_selector("select[x-model='selectedClassId']")
        await sel.select_option(value='10')
        await page.evaluate("""() => {
            const sel = document.querySelector("select[x-model='selectedClassId']");
            sel.dispatchEvent(new Event('change'));
        }""")
        await page.wait_for_timeout(500)
        
        # Type student name
        name_in = await page.query_selector("input[x-model='typedStudentName']")
        await name_in.fill('Najwa Dayani')
        await page.evaluate("""() => {
            const el = document.querySelector("input[x-model='typedStudentName']");
            el.dispatchEvent(new Event('input'));
        }""")
        await page.wait_for_timeout(800)
        
        match_visible = await page.is_visible("text=Siswa Ditemukan di Database")
        print('Match detected?:', match_visible)
        if match_visible:
            print('Detected text:', await page.inner_text("text=Siswa Ditemukan di Database"))
            match_btn = await page.query_selector("button:has-text('Pilih Siswa Ini')")
            b_btn = await match_btn.bounding_box()
            print('Match button bounding box:', b_btn)
            await match_btn.click()
            await page.wait_for_timeout(500)
            
            # Fill NIS
            nis_in = await page.query_selector("input[x-model='verificationCode']")
            await nis_in.fill('1219')
            await page.evaluate("""() => {
                const el = document.querySelector("input[x-model='verificationCode']");
                el.dispatchEvent(new Event('input'));
            }""")
            
            await page.screenshot(path='scratch/najwa_matched.png')
            print('Screenshot najwa_matched.png saved!')

        await browser.close()

if __name__ == '__main__':
    asyncio.run(inspect_step2())
