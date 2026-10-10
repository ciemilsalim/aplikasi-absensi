import asyncio
from playwright.async_api import async_playwright

async def inspect_step3_btn():
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
        
        # Switch to step 2 with connected student
        await page.evaluate("""() => {
            const scope = Alpine.$data(document.querySelector('main'));
            scope.currentStep = 2;
            scope.connectedStudents = [{ id: 26, name: 'Najwa Dayani', nis: '1219', school_class: { name: '8A' } }];
        }""")
        await page.wait_for_timeout(500)
        
        # Scroll down so next button is visible
        await page.evaluate("window.scrollBy(0, 360)")
        await page.wait_for_timeout(500)
        
        scale = 2.608695652
        step2_next_btn = await page.query_selector("button:has-text('Lanjut ke Langkah 3')")
        if step2_next_btn:
            b_next = await step2_next_btn.bounding_box()
            print(f"Step 2 Next to Step 3: center=({(b_next['x']+b_next['width']/2)*scale:.1f}, {(b_next['y']+b_next['height']/2)*scale:.1f})")
            
        await page.screenshot(path='scratch/screen_scrolled_to_step3_btn.png')
        print("Saved scratch/screen_scrolled_to_step3_btn.png")
        await browser.close()

if __name__ == '__main__':
    asyncio.run(inspect_step3_btn())
