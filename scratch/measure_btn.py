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
    print('After login URL:', page.url)

    # Click leave-requests
    link = page.query_selector('a[href*="leave-requests"]')
    if link:
        print('Found leave-requests link:', link.get_attribute('href'))
        link.click()
        page.wait_for_timeout(1000)
    print('After click 1 URL:', page.url)

    link2 = page.query_selector('a[href*="leave-requests/create"]')
    if link2:
        print('Found create link:', link2.get_attribute('href'))
        link2.click()
        page.wait_for_timeout(1500)
    print('After click 2 URL:', page.url)

    buttons = page.query_selector_all('button')
    print('Found buttons:', len(buttons))
    for b in buttons:
        print('Button text:', b.inner_text(), 'type:', b.get_attribute('type'), 'box:', b.bounding_box())

    browser.close()
