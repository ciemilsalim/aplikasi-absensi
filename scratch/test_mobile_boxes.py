import asyncio
from playwright.async_api import async_playwright
import cv2

async def inspect():
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        # 414x736 (iPhone Plus / Max aspect 9:16) with scale 2.60869565 -> 1080x1920
        # Or 450x800 with scale 2.4 -> 1080x1920
        # Or 405x720 with scale 2.6666667 -> 1080x1920
        # Or 540x960 with scale 2.0 -> 1080x1920
        # Let's test 414x736 (standard iPhone Plus mobile width):
        w_css = 414
        h_css = 736
        d_scale = 1080.0 / w_css # 2.608695652173913
        
        context = await browser.new_context(
            viewport={'width': w_css, 'height': h_css},
            device_scale_factor=d_scale,
            is_mobile=True,
            has_touch=True
        )
        page = await context.new_page()
        
        await page.goto('http://127.0.0.1:8000/login', wait_until='networkidle')
        await page.screenshot(path='scratch/test_login_414.png')
        
        img = cv2.imread('scratch/test_login_414.png')
        print(f"Screenshot size: {img.shape[1]}x{img.shape[0]}")
        
        reg_link = await page.query_selector("a[href*='register']")
        box = await reg_link.bounding_box()
        sx = img.shape[1] / float(w_css)
        sy = img.shape[0] / float(h_css)
        
        cx = (box['x'] + box['width']/2) * sx
        cy = (box['y'] + box['height']/2) * sy
        print(f"Login 'Daftar sekarang' -> CSS: {box} | Image: ({cx:.1f}, {cy:.1f})")
        
        await page.goto('http://127.0.0.1:8000/register', wait_until='networkidle')
        await page.screenshot(path='scratch/test_reg_414.png')
        img_reg = cv2.imread('scratch/test_reg_414.png')
        
        for sel in ['#name', '#email', '#password', '#password_confirmation', "button[type='submit']"]:
            el = await page.query_selector(sel)
            b = await el.bounding_box()
            ecx = (b['x'] + b['width']/2) * sx
            ecy = (b['y'] + b['height']/2) * sy
            print(f"Register '{sel}' -> CSS: {b} | Image: ({ecx:.1f}, {ecy:.1f})")
        
        await browser.close()

if __name__ == '__main__':
    asyncio.run(inspect())
