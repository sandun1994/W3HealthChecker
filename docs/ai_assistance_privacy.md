# W3HealthChecker — AI-Assisted Explanations & Privacy Architecture

## 1. Objective & Scope
The AI Explanation interface ([`AiExplanationService`](file:///c:/xampp/htdocs/W3HealthChecker/app/Services/Remediation/AiExplanationService.php)) helps non-technical site owners, intermediate webmasters, and senior engineers understand technical findings in plain language tailored to their skill level.

---

## 2. Privacy & Security Guarantees
1. **Server-Side Token Shielding**: All AI API credentials (if configured) reside strictly on the server in `.env` and are never exposed to React or the browser.
2. **Data Minimization**:
   - Only the specific finding title, category, and observed technical violation are processed.
   - Raw page contents, customer database passwords, API tokens, and payment records are strictly excluded from AI prompts.
3. **Deterministic Engine Fallback**:
   - When external AI providers are unavailable, disabled, or unconfigured, the system provides high-quality deterministic explanations tailored by skill level (`beginner`, `intermediate`, `advanced`).
4. **Honest Attribution**:
   - Explanations are clearly attributed to their source.
   - The platform never claims AI output guarantees Google search rankings or third-party certifications.
