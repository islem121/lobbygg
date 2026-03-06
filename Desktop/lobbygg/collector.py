from playwright.sync_api import sync_playwright
from playwright_stealth import stealth_sync_api

def main():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=False)
        page = browser.new_page()
        stealth_sync_api(page)
        # Il resto del codice per il collector immobiliare
        page.goto("https://example.com")
        # Aggiungi qui la logica per raccogliere dati immobiliari
        browser.close()

if __name__ == "__main__":
    main()
