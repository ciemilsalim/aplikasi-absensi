import asyncio
from playwright.async_api import async_playwright

async def inspect_scrolled_coords():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            viewport={'width': 414, 'height': 736},
            device_scale_factor=2.608695652,
            is_mobile=True,
            has_touch=True
        )
        page = await context.new_page()
        
        await page.goto('http://127.0.0.1:8000/login')
        await page.fill('#email', 'cici@gmail.com')
        await page.fill('#password', 'password')
        await page.click('button[type="submit"]')
        await page.wait_for_load_state('networkidle')
        
        await page.goto('http://127.0.0.1:8000/parent/onboarding')
        await page.wait_for_load_state('networkidle')
        
        # Switch to step 2
        await page.evaluate("""() => {
            const scope = Alpine.$data(document.querySelector('main'));
            scope.currentStep = 2;
            scope.selectedClassId = '10';
            scope.typedStudentName = 'Najwa Dayani';
        }""")
        await page.wait_for_timeout(600)
        
        # Scroll down so NIS input and Hubungkan button are visible
        await page.evaluate("window.scrollBy(0, 320)")
        await page.wait_for_timeout(500)
        
        scale = 2.608695652
        nis_in = await page.query_selector("input[x-model='verificationCode']")
        if nis_in:
            b_nis = await nis_in.bounding_box()
            print(f"Scrolled NIS Input: x={b_nis['x']*scale:.1f}, y={b_nis['y']*scale:.1f}, center=({(b_nis['x']+b_nis['width']/2)*scale:.1f}, {(b_nis['y']+b_nis['height']/2)*scale:.1f})")
            
        hub_btn = await page.query_selector("button:has-text('Hubungkan Siswa Ini')")
        if hub_btn:
            b_hub = await hub_btn.bounding_box()
            print(f"Scrolled Hubungkan Btn: x={b_hub['x']*scale:.1f}, y={b_hub['y']*scale:.1f}, center=({(b_hub['x']+b_hub['width']/2)*scale:.1f}, {(b_hub['y']+b_hub['height']/2)*scale:.1f})")
            
        await page.screenshot(path='scratch/screen_scrolled_step2.png')
        print("Saved scratch/screen_scrolled_step2.png")
        await browser.close()

if __name__ == '__main__':
    asyncio.run(inspect_scrolled_coords())
