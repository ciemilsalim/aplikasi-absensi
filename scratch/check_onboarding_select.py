import asyncio
from playwright.async_api import async_playwright

async def check_options():
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
        
        await page.evaluate("""() => {
            const scope = Alpine.$data(document.querySelector('main'));
            scope.currentStep = 2;
        }""")
        await page.wait_for_timeout(500)
        
        html = await page.inner_html("select[x-model='selectedClassId']")
        print('Select HTML:\n', html[:300])
        
        sel = await page.query_selector("select[x-model='selectedClassId']")
        await sel.select_option(index=1)
        val = await sel.input_value()
        print('Selected value by index 1:', val)
        
        # Trigger change event for Alpine
        await page.evaluate("""() => {
            const sel = document.querySelector("select[x-model='selectedClassId']");
            sel.dispatchEvent(new Event('change'));
        }""")
        await page.wait_for_timeout(500)
        
        # Check student input and type
        name_in = await page.query_selector("input[x-model='typedStudentName']")
        await name_in.fill('Citra Lestari')
        await page.evaluate("""() => {
            const el = document.querySelector("input[x-model='typedStudentName']");
            el.dispatchEvent(new Event('input'));
        }""")
        await page.wait_for_timeout(800)
        
        match_visible = await page.is_visible("text=Siswa Ditemukan di Database")
        print('Match detected?:', match_visible)
        
        await browser.close()

if __name__ == '__main__':
    asyncio.run(check_options())
