import asyncio
from playwright.async_api import async_playwright

async def run():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(viewport={'width': 1920, 'height': 1080})
        page = await context.new_page()
        
        print("Navigating to login...")
        await page.goto("http://127.0.0.1:8000/login")
        await page.fill("#email", "emil@gmail.com")
        await page.fill("#password", "password")
        await page.click("button[type='submit']")
        await page.wait_for_load_state("networkidle")
        print("Current URL after login:", page.url)
        
        await page.goto("http://127.0.0.1:8000/parent/leave-requests")
        await page.wait_for_load_state("networkidle")
        print("Current URL on leave-requests:", page.url)
        
        btn = page.locator("a[href*='leave-requests/create']")
        count = await btn.count()
        print("Count of a[href*='leave-requests/create']:", count)
        if count > 0:
            print("Is visible:", await btn.first.is_visible())
            print("Text:", await btn.first.inner_text())
        else:
            print("Page HTML sample:", (await page.content())[:1000])
        
        await browser.close()

asyncio.run(run())
