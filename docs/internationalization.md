# W3HealthChecker — Internationalization (i18n) Architecture

## 1. Overview
W3HealthChecker is designed for global website owners, developers, and international agencies.
Localization is structured around centralized JSON translation catalogs residing in `lang/` and served via `GET /api/i18n/{locale}`.

---

## 2. Localization Principles
1. **Default English with Safe Fallbacks**: English (`en`) serves as the canonical base language. Any missing key in localized dictionaries gracefully falls back to the English string.
2. **Deterministic Technical Accuracy**:
   - Technical diagnostic headers (e.g., `Strict-Transport-Security`, `Content-Security-Policy`, `canonical`, `viewport`) are kept in their exact web standard naming.
   - We avoid machine translations that distort security concepts or header directives.
3. **Semantic HTML Lang Declaration**:
   - Root template dynamically renders `<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">` ensuring search engine crawlers and screen readers recognize the declared language.
