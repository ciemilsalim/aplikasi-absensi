import asyncio
from playwright.async_api import async_playwright

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            viewport={'width': 390, 'height': 844},
            is_mobile=True,
            has_touch=True
        )
        page = await context.new_page()
        
        await page.goto("http://127.0.0.1:8000/login")
        await page.fill("#email", "emil@gmail.com")
        await page.fill("#password", "password")
        await page.click("button[type='submit']")
        await page.wait_for_load_state("networkidle")
        
        await page.goto("http://127.0.0.1:8000/parent/leave-requests")
        await page.wait_for_load_state("networkidle")
        
        # Test selectors
        s1 = page.locator("header a[href*='leave-requests/create']")
        print("header a[href*='leave-requests/create'] visible:", await s1.is_visible() if await s1.count() else 0)
        
        s2 = page.locator("a:has-text('Buat Pengajuan Baru')")
        print("a:has-text('Buat Pengajuan Baru') visible:", await s2.is_visible() if await s2.count() else 0)
        
        s3 = page.locator("a[href*='leave-requests/create']:visible")
        print("a[href*='leave-requests/create']:visible visible:", await s3.is_visible() if await s3.count() else 0)
        
        await browser.close()

asyncio.run(run())
