from playwright.sync_api import sync_playwright

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    ctx = browser.new_context(viewport={'width': 390, 'height': 844}, is_mobile=True, has_touch=True)
    page = ctx.new_page()
    page.goto('http://127.0.0.1:8000/login')
    page.fill('#email', 'emil@gmail.com')
    page.fill('#password', 'password')
    page.click('button[type="submit"]')
    page.wait_for_timeout(2000)
    page.goto('http://127.0.0.1:8000/parent/leave-requests/create')
    page.wait_for_load_state('networkidle')

    btn = page.locator('button[type="submit"]').first
    if btn:
        btn.scroll_into_view_if_needed()
        page.wait_for_timeout(400)
        box = btn.bounding_box()
        print('Scrolled button box (390x844):', box)
        scale_x = 1080 / 390.0
        scale_y = 1920 / 844.0
        box_1080 = {
            'x': round(box['x'] * scale_x),
            'y': round(box['y'] * scale_y),
            'width': round(box['width'] * scale_x),
            'height': round(box['height'] * scale_y),
            'center_x': round((box['x'] + box['width']/2) * scale_x),
            'center_y': round((box['y'] + box['height']/2) * scale_y),
        }
        print('In 1080x1920:', box_1080)
        page.screenshot(path='scratch/scrolled_btn.png')
    browser.close()
