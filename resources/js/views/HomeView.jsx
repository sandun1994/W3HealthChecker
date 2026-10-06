import React, { useState } from 'react';
import { Activity, ArrowRight, ShieldCheck, Zap, Globe, Smartphone, Cpu, CheckCircle2, ChevronRight, HelpCircle, Layers, Users, Code, Award, Sparkles } from 'lucide-react';
import ScoreRing from '../components/ScoreRing';
import ScannerProgress from '../components/ScannerProgress';

export default function HomeView({ onStartScan, scanning, scanProgress, currentStage, scanTargetUrl, scanError, onNavigate }) {
  const [urlInput, setUrlInput] = useState('');
  const [activeAudienceTab, setActiveAudienceTab] = useState('developers');

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!urlInput.trim()) return;
    onStartScan(urlInput.trim());
  };

  return (
    <div className="flex-1 flex flex-col">
      {/* 1. HERO SECTION */}
      <section className="relative overflow-hidden pt-12 pb-20 sm:pt-20 sm:pb-28 border-b border-slate-900">
        {/* Ambient background glow */}
        <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-brand-600/15 blur-[120px] rounded-full pointer-events-none -z-10" />

        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          {/* Eyebrow badge */}
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-900 border border-slate-800 text-xs font-semibold text-brand-300 mb-6 shadow-sm">
            <Sparkles className="w-3.5 h-3.5 text-brand-400" />
            <span>Next-Gen Website Intelligence &amp; AI Readiness</span>
          </div>

          {/* Main Headline */}
          <h1 className="text-4xl sm:text-6xl lg:text-7xl font-extrabold text-white tracking-tight leading-[1.1] mb-6">
            Know Your Website. <br />
            <span className="bg-gradient-to-r from-brand-400 via-indigo-300 to-emerald-400 bg-clip-text text-transparent">
              Improve Everything.
            </span>
          </h1>

          {/* Subtitle */}
          <p className="max-w-2xl mx-auto text-base sm:text-xl text-slate-300 font-normal leading-relaxed mb-10">
            A comprehensive, evidence-backed website health scanner. Uncover technical SEO flaws, security headers, mobile signals, performance bottlenecks, and AI discovery readiness in seconds.
          </p>

          {/* Scan Form / Progress */}
          <div id="scanner-input" className="max-w-2xl mx-auto">
            {scanning ? (
              <ScannerProgress
                progress={scanProgress}
                currentStage={currentStage}
                targetUrl={scanTargetUrl}
                error={scanError}
                onReset={() => { setScanning(false); setScanError(null); }}
              />
            ) : (
              <form onSubmit={handleSubmit} className="relative flex flex-col sm:flex-row items-center gap-3 p-2 rounded-2xl glass-panel border border-slate-800 shadow-2xl focus-within:border-brand-500/50 transition-colors">
                <div className="relative flex-1 w-full flex items-center">
                  <Globe className="w-5 h-5 text-slate-500 absolute left-4 pointer-events-none" />
                  <input
                    type="text"
                    value={urlInput}
                    onChange={(e) => setUrlInput(e.target.value)}
                    placeholder="Enter website URL (e.g. example.com)"
                    className="w-full pl-12 pr-4 py-3.5 bg-transparent text-white placeholder-slate-500 text-sm sm:text-base font-medium focus:outline-none"
                    required
                  />
                </div>

                <button
                  type="submit"
                  className="w-full sm:w-auto px-7 py-3.5 rounded-xl font-bold text-sm bg-gradient-to-r from-brand-600 via-indigo-600 to-indigo-500 hover:from-brand-500 hover:to-indigo-400 text-white shadow-lg shadow-brand-500/25 flex items-center justify-center gap-2 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] whitespace-nowrap"
                >
                  <span>ANALYZE WEBSITE</span>
                  <ArrowRight className="w-4 h-4" />
                </button>
              </form>
            )}

            {scanError && !scanning && (
              <div className="mt-4 p-3 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs text-left">
                {scanError}
              </div>
            )}

            <div className="mt-4 flex flex-wrap items-center justify-center gap-4 text-xs text-slate-400">
              <span className="flex items-center gap-1.5">
                <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400" /> Free Instant Audit
              </span>
              <span className="flex items-center gap-1.5">
                <CheckCircle2 className="w-3.5 h-3.5 text-brand-400" /> SSRF-Safe &amp; Passive
              </span>
              <span className="flex items-center gap-1.5">
                <CheckCircle2 className="w-3.5 h-3.5 text-indigo-400" /> Actionable Fix Guidance
              </span>
            </div>
          </div>
        </div>
      </section>

      {/* 2. LIVE / EXAMPLE REPORT PREVIEW */}
      <section className="py-16 sm:py-24 border-b border-slate-900 bg-slate-950/60">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-2xl mx-auto mb-12">
            <h2 className="text-xs uppercase font-extrabold tracking-widest text-brand-400 mb-2">
              SaaS-Grade Visual Diagnostic
            </h2>
            <p className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
              Actionable Intelligence, Not Meaningless Scores
            </p>
          </div>

          {/* Example Report Card Box */}
          <div className="glass-panel p-6 sm:p-10 rounded-3xl border border-slate-800 shadow-2xl max-w-5xl mx-auto">
            <div className="flex flex-col md:flex-row items-center justify-between gap-8 pb-8 border-b border-slate-800/80">
              {/* Target info */}
              <div>
                <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Example Audit Target</span>
                <h3 className="text-2xl font-mono font-bold text-white mt-1">example.com</h3>
                <p className="text-xs text-slate-400 mt-1">Status: Checked in 640ms • 7 Pillars Inspected</p>
              </div>

              {/* Master Health Score */}
              <div className="flex items-center gap-6 bg-slate-900/80 px-6 py-4 rounded-2xl border border-slate-800">
                <ScoreRing score={87} size="large" label="GOOD" />
                <div className="text-left">
                  <span className="text-xs uppercase font-bold text-slate-400 tracking-wider">Overall Health</span>
                  <div className="text-lg font-bold text-white">87 / 100</div>
                  <span className="inline-block mt-1 text-xs px-2.5 py-0.5 rounded-full bg-blue-500/10 text-blue-400 font-semibold border border-blue-500/20">
                    GOOD STATUS
                  </span>
                </div>
              </div>
            </div>

            {/* 7 Category Scores Grid */}
            <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4 pt-8 text-center">
              {[
                { title: 'SEO', score: 91, label: 'Excellent', color: 'emerald' },
                { title: 'Performance', score: 74, label: 'Needs Imp.', color: 'amber' },
                { title: 'Security', score: 88, label: 'Good', color: 'blue' },
                { title: 'Accessibility', score: 82, label: 'Good', color: 'blue' },
                { title: 'Mobile', score: 94, label: 'Excellent', color: 'emerald' },
                { title: 'Technical', score: 89, label: 'Good', color: 'blue' },
                { title: 'AI / Search', score: 76, label: 'Needs Imp.', color: 'amber' },
              ].map((item, i) => (
                <div key={i} className="p-4 rounded-xl bg-slate-900/50 border border-slate-800 flex flex-col items-center">
                  <ScoreRing score={item.score} size="small" showLabel={false} />
                  <span className="text-xs font-bold text-slate-200 mt-2">{item.title}</span>
                  <span className="text-[10px] font-semibold text-slate-400 mt-0.5">{item.score}/100</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* 3. WHAT WE CHECK (7 PILLARS) */}
      <section className="py-20 sm:py-28 border-b border-slate-900">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-3xl mx-auto mb-16">
            <span className="text-xs uppercase font-extrabold tracking-widest text-brand-400 mb-2 block">
              Complete Coverage
            </span>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-4">
              The 7 Pillars of Complete Website Health
            </h2>
            <p className="text-slate-400 text-sm sm:text-base leading-relaxed">
              We look beyond superficial meta tags to analyze the entire technical stack powering your web presence.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {[
              {
                icon: Globe,
                title: 'Technical & On-Page SEO',
                desc: 'Audits titles, descriptions, canonical tags, H1-H3 hierarchy, image alt text, robots directives, and XML sitemaps.',
                slug: 'seo-checker',
              },
              {
                icon: Zap,
                title: 'Performance & Latency',
                desc: 'Measures server TTFB, text compression (gzip/brotli), document weight, render-blocking scripts, and lazy loading.',
                slug: 'website-performance-checker',
              },
              {
                icon: ShieldCheck,
                title: 'Defensive Security & SSL',
                desc: 'Validates HTTPS, TLS certificate expiry, HSTS, CSP, X-Frame-Options, X-Content-Type-Options, and passive exposure alerts.',
                slug: 'security-header-checker',
              },
              {
                icon: Smartphone,
                title: 'Mobile Readiness',
                desc: 'Verifies viewport tags, responsive width scaling, pinch-to-zoom accessibility, and mobile layout indicators.',
                slug: 'mobile-friendly-checker',
              },
              {
                icon: Activity,
                title: 'Accessibility Signals (WCAG 2.2)',
                desc: 'Evaluates HTML lang declarations, empty button and link names, heading hierarchy jumps, and image contrast tags.',
                slug: 'seo-checker',
              },
              {
                icon: Cpu,
                title: 'AI & Knowledge Search Readiness',
                desc: 'Analyzes structured schema.org JSON-LD, machine-readable hierarchy, /llms.txt discovery, and AI bot crawlability.',
                slug: 'ai-search-readiness-checker',
              },
            ].map((col, idx) => {
              const Icon = col.icon;
              return (
                <div key={idx} className="glass-panel p-6 rounded-2xl border border-slate-800 flex flex-col justify-between hover:border-slate-700 transition-colors">
                  <div>
                    <div className="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-brand-400 mb-4">
                      <Icon className="w-5 h-5" />
                    </div>
                    <h3 className="text-lg font-bold text-white mb-2">{col.title}</h3>
                    <p className="text-slate-400 text-xs sm:text-sm leading-relaxed mb-6">
                      {col.desc}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => onNavigate('tool', col.slug)}
                    className="text-xs font-semibold text-brand-400 hover:text-brand-300 flex items-center gap-1.5"
                  >
                    <span>Explore Tool</span>
                    <ChevronRight className="w-3.5 h-3.5" />
                  </button>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* 4. HOW IT WORKS */}
      <section className="py-20 sm:py-28 border-b border-slate-900 bg-slate-950/40">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <span className="text-xs uppercase font-extrabold tracking-widest text-emerald-400 mb-2 block">
              The Resolution Loop
            </span>
            <h2 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-4">
              How W3HealthChecker Solves Web Issues
            </h2>
            <p className="text-slate-400 text-sm sm:text-base">
              From submission to resolution in four effortless steps.
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            {[
              { step: '01', title: 'Enter URL', desc: 'Provide any publicly accessible web address. Our engine performs strict SSRF & protocol checks.' },
              { step: '02', title: 'Instant Scan', desc: 'Asynchronous workers analyze headers, HTML DOM, SSL certificates, sitemaps, and robots directives.' },
              { step: '03', title: 'Understand Why', desc: 'Review prioritized findings: What is wrong, why it matters, and where exactly the issue resides.' },
              { step: '04', title: 'Deploy the Fix', desc: 'Copy developer-ready code snippets and server configuration rules. Re-scan to verify resolution.' },
            ].map((step, idx) => (
              <div key={idx} className="relative p-6 rounded-2xl bg-slate-900/40 border border-slate-800">
                <span className="text-4xl font-extrabold text-slate-800 block mb-3 font-mono">{step.step}</span>
                <h3 className="text-base font-bold text-white mb-2">{step.title}</h3>
                <p className="text-xs text-slate-400 leading-relaxed">{step.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 5. AUDIENCE TABS: DEVELOPERS / SEO / AGENCIES */}
      <section className="py-20 sm:py-28 border-b border-slate-900">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
              Engineered For Modern Web Teams
            </h2>
          </div>

          {/* Tab Selector */}
          <div className="flex justify-center gap-2 mb-8">
            {[
              { id: 'developers', label: 'For Developers', icon: Code },
              { id: 'seo', label: 'For SEO Professionals', icon: Globe },
              { id: 'agencies', label: 'For Agencies', icon: Users },
            ].map(tab => {
              const Icon = tab.icon;
              return (
                <button
                  key={tab.id}
                  onClick={() => setActiveAudienceTab(tab.id)}
                  className={`flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all ${
                    activeAudienceTab === tab.id
                      ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/20'
                      : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
                  }`}
                >
                  <Icon className="w-4 h-4" />
                  <span>{tab.label}</span>
                </button>
              );
            })}
          </div>

          {/* Content Card */}
          <div className="glass-panel p-8 rounded-3xl border border-slate-800 text-slate-300">
            {activeAudienceTab === 'developers' && (
              <div className="space-y-4">
                <h3 className="text-xl font-bold text-white">Cut Through Marketing Fluff With Technical Accuracy</h3>
                <p className="text-sm leading-relaxed text-slate-400">
                  Get exact HTTP header directives, copyable Nginx/Apache configs, DOM element selectors, and JSON-LD schema syntax validators. No speculative ranking claims—just precise technical evidence.
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 text-xs">
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Copyable server config snippets
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Precise TTFB &amp; payload weight breakdown
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> SSL certificate expiration monitoring
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Automated REST API endpoints
                  </div>
                </div>
              </div>
            )}

            {activeAudienceTab === 'seo' && (
              <div className="space-y-4">
                <h3 className="text-xl font-bold text-white">Actionable On-Page &amp; Technical SEO Audits</h3>
                <p className="text-sm leading-relaxed text-slate-400">
                  Ensure canonical tags, robots.txt directives, title lengths, and heading structures comply with search engine guidelines. Distinguish confirmed crawl issues from advisory recommendations.
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 text-xs">
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Snippet truncation pixel-width checks
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Canonical consistency verification
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Robots.txt &amp; XML sitemap discovery
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Social card preview rendering
                  </div>
                </div>
              </div>
            )}

            {activeAudienceTab === 'agencies' && (
              <div className="space-y-4">
                <h3 className="text-xl font-bold text-white">Deliver Client Reports That Actually Make Sense</h3>
                <p className="text-sm leading-relaxed text-slate-400">
                  Give clients a clean, shareable report with prioritized action items. "Fix These First" eliminates the paralysis of 200-page automated PDFs and helps your team demonstrate immediate ROI.
                </p>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 text-xs">
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Clean public shareable links
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Printable clean PDF reports
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> Side-by-side competitor comparison
                  </div>
                  <div className="flex items-center gap-2 text-slate-300">
                    <CheckCircle2 className="w-4 h-4 text-emerald-400" /> "Fix These First" prioritized queue
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* 6. FAQ SECTION */}
      <section className="py-20 border-b border-slate-900 bg-slate-950/40">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
              Frequently Asked Questions
            </h2>
          </div>

          <div className="space-y-4">
            {[
              {
                q: 'How does W3HealthChecker differ from standard SEO checkers?',
                a: 'Most checkers dump hundreds of disorganized warnings without prioritizing what matters. W3HealthChecker inspects 7 technical pillars (SEO, performance, security headers, accessibility, mobile, infrastructure, and AI readiness) and algorithmically prioritizes the top fixes with clear why, where, and how answers.',
              },
              {
                q: 'Does W3HealthChecker perform intrusive security vulnerability scans?',
                a: 'No. W3HealthChecker is strictly a defensive website health scanner. We only inspect publicly accessible response headers, SSL certificates, and safe passive indicators. We never execute brute-force attacks, penetration exploits, or intrusive payloads.',
              },
              {
                q: 'What is AI / Search Readiness?',
                a: 'As search shifts toward generative AI overviews (ChatGPT, Perplexity, Google AI Overviews), AI agents rely on machine-readable semantic HTML, schema.org structured data, and /llms.txt to extract entity facts. We test your technical discoverability without claiming unrealistic ranking guarantees.',
              },
              {
                q: 'Can I share the report with clients or teammates?',
                a: 'Yes! Every scan generates a unique, shareable public URL (/report/{domain}/{id}) that contains sanitized results, scores, and prioritized fixes without exposing internal infrastructure credentials.',
              },
            ].map((faq, i) => (
              <div key={i} className="p-5 rounded-2xl bg-slate-900/60 border border-slate-800">
                <h3 className="text-sm sm:text-base font-bold text-white mb-2 flex items-center gap-2">
                  <HelpCircle className="w-4 h-4 text-brand-400 flex-shrink-0" />
                  {faq.q}
                </h3>
                <p className="text-xs sm:text-sm text-slate-400 leading-relaxed pl-6">
                  {faq.a}
                </p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* 7. FINAL CALL TO ACTION */}
      <section className="py-20 text-center relative overflow-hidden">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
          <h2 className="text-3xl sm:text-5xl font-extrabold text-white tracking-tight mb-4">
            Audit Your Website Right Now
          </h2>
          <p className="text-slate-400 text-sm sm:text-base mb-8">
            Get an instant, actionable website intelligence report in under 15 seconds. Completely free.
          </p>
          <a
            href="#scanner-input"
            className="inline-flex items-center gap-2 px-8 py-4 rounded-xl font-bold text-sm bg-gradient-to-r from-brand-600 via-indigo-600 to-emerald-500 hover:opacity-95 text-white shadow-xl shadow-brand-500/25 transition-all hover:scale-105"
          >
            <span>Analyze Website Free</span>
            <ArrowRight className="w-4 h-4" />
          </a>
        </div>
      </section>
    </div>
  );
}
