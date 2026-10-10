import asyncio
from playwright.async_api import async_playwright

async def inspect():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(viewport={'width': 1080, 'height': 1920}, is_mobile=True, has_touch=True)
        page = await context.new_page()
        
        await page.goto('http://127.0.0.1:8000/login', wait_until='networkidle')
        reg_link = await page.query_selector("a[href*='register']")
        box_reg = await reg_link.bounding_box() if reg_link else None
        print('Mobile Login - reg_link:', box_reg)
        
        await page.goto('http://127.0.0.1:8000/register', wait_until='networkidle')
        name_input = await page.query_selector('#name')
        email_input = await page.query_selector('#email')
        pass_input = await page.query_selector('#password')
        confirm_input = await page.query_selector('#password_confirmation')
        submit_btn = await page.query_selector("button[type='submit']")
        
        print('Mobile Register - name:', await name_input.bounding_box() if name_input else None)
        print('Mobile Register - email:', await email_input.bounding_box() if email_input else None)
        print('Mobile Register - pass:', await pass_input.bounding_box() if pass_input else None)
        print('Mobile Register - confirm:', await confirm_input.bounding_box() if confirm_input else None)
        print('Mobile Register - submit:', await submit_btn.bounding_box() if submit_btn else None)
        
        await browser.close()

if __name__ == '__main__':
    asyncio.run(inspect())
