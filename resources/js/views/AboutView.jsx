import React from 'react';
import { ShieldCheck, Search, Zap, CheckCircle2, Smartphone, Server, Sparkles, AlertTriangle, ArrowRight, BookOpen, Wrench, HeartHandshake } from 'lucide-react';

export default function AboutView() {
    return (
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
            {/* Mission Header */}
            <div className="text-center max-w-3xl mx-auto mb-16">
                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 mb-4">
                    <HeartHandshake className="w-3.5 h-3.5" />
                    Our Methodology & Mission
                </div>
                <h1 className="text-3xl md:text-5xl font-extrabold text-white tracking-tight">
                    Know Your Website. Understand What Matters.
                </h1>
                <p className="mt-4 text-lg text-slate-400 leading-relaxed">
                    W3HealthChecker is a website intelligence and diagnostic platform that helps website owners, developers, SEO professionals, and agencies understand technical website problems and prioritize improvements.
                </p>
            </div>

            {/* Core Integrity Pledge */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-16">
                <div className="p-6 md:p-8 rounded-2xl bg-slate-900/60 border border-emerald-500/20">
                    <h2 className="text-xl font-bold text-emerald-400 mb-3 flex items-center gap-2">
                        <CheckCircle2 className="w-5 h-5" /> What We Deliver
                    </h2>
                    <ul className="space-y-3 text-sm text-slate-300">
                        <li className="flex items-start gap-2">
                            <span className="text-emerald-400 font-bold">•</span>
                            <span><strong>Objective Technical Diagnostics:</strong> Deterministic evaluation of on-page elements, response headers, and document structure.</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-emerald-400 font-bold">•</span>
                            <span><strong>Prioritized Action Plans:</strong> Issues categorized by severity (Critical, Warning, Info) with estimated resolution time and technical rationale.</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-emerald-400 font-bold">•</span>
                            <span><strong>Multi-Pillar Balance:</strong> Balancing SEO visibility with browser security headers, fast TTFB, and WCAG accessibility standards.</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-emerald-400 font-bold">•</span>
                            <span><strong>Privacy-First & Respectful:</strong> We never sell scan data or store sensitive credentials.</span>
                        </li>
                    </ul>
                </div>

                <div className="p-6 md:p-8 rounded-2xl bg-slate-900/60 border border-amber-500/20">
                    <h2 className="text-xl font-bold text-amber-400 mb-3 flex items-center gap-2">
                        <AlertTriangle className="w-5 h-5" /> Transparent Positioning
                    </h2>
                    <ul className="space-y-3 text-sm text-slate-300">
                        <li className="flex items-start gap-2">
                            <span className="text-amber-400 font-bold">•</span>
                            <span><strong>Not an Official Google Rating:</strong> Our scores reflect engineering best practices and public specifications, not Google's proprietary search ranking algorithm.</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-amber-400 font-bold">•</span>
                            <span><strong>Not a Penetration Test:</strong> We perform non-invasive HTTP header and structure inspection. We never execute offensive vulnerability probing.</span>
                        </li>
                        <li className="flex items-start gap-2">
                            <span className="text-amber-400 font-bold">•</span>
                            <span><strong>No Fake Guarantees:</strong> We do not promise #1 organic rankings or guaranteed AI citation inclusions.</span>
                        </li>
                    </ul>
                </div>
            </div>

            {/* The 7 Pillars Breakdown */}
            <div className="mb-20">
                <h2 className="text-2xl md:text-3xl font-bold text-white text-center mb-8">
                    The 7 Pillars of Website Health
                </h2>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div className="p-5 rounded-xl bg-slate-900/50 border border-slate-800">
                        <div className="w-9 h-9 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center mb-3">
                            <Search className="w-5 h-5" />
                        </div>
                        <h3 className="text-base font-bold text-white mb-1">1. Technical & On-Page SEO</h3>
                        <p className="text-xs text-slate-400 leading-relaxed">
                            Titles, descriptions, canonical tags, open graph metadata, headings hierarchy, and crawl budget hygiene.
                        </p>
                    </div>

                    <div className="p-5 rounded-xl bg-slate-900/50 border border-slate-800">
                        <div className="w-9 h-9 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center mb-3">
                            <Zap className="w-5 h-5" />
                        </div>
                        <h3 className="text-base font-bold text-white mb-1">2. Web Performance</h3>
                        <p className="text-xs text-slate-400 leading-relaxed">
                            Server latency (TTFB), text compression (Gzip/Brotli), page weight, asset count, and resource hints.
                        </p>
                    </div>

                    <div className="p-5 rounded-xl bg-slate-900/50 border border-slate-800">
                        <div className="w-9 h-9 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center mb-3">
                            <ShieldCheck className="w-5 h-5" />
                        </div>
                        <h3 className="text-base font-bold text-white mb-1">3. Defensive Security</h3>
                        <p className="text-xs text-slate-400 leading-relaxed">
                            HSTS strict encryption, Content-Security-Policy (CSP), anti-clickjacking headers, and SSL certificate validity.
                        </p>
                    </div>

                    <div className="p-5 rounded-xl bg-slate-900/50 border border-slate-800">
                        <div className="w-9 h-9 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center mb-3">
                            <CheckCircle2 className="w-5 h-5" />
                        </div>
                        <h3 className="text-base font-bold text-white mb-1">4. Accessibility (WCAG)</h3>
                        <p className="text-xs text-slate-400 leading-relaxed">
                            Document language declarations, button names, image alternative text, and form input label pairings.
                        </p>
                    </div>

                    <div className="p-5 rounded-xl bg-slate-900/50 border border-slate-800">
                        <div className="w-9 h-9 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center mb-3">
                            <Smartphone className="w-5 h-5" />
                        </div>
                        <h3 className="text-base font-bold text-white mb-1">5. Mobile Readiness</h3>
                        <p className="text-xs text-slate-400 leading-relaxed">
                            Responsive viewport configurations, legible mobile typography, touch-target safety, and flexible layouts.
                        </p>
                    </div>

                    <div className="p-5 rounded-xl bg-slate-900/50 border border-slate-800">
                        <div className="w-9 h-9 rounded-lg bg-rose-500/10 text-rose-400 flex items-center justify-center mb-3">
                            <Server className="w-5 h-5" />
                        </div>
                        <h3 className="text-base font-bold text-white mb-1">6. Technical Infrastructure</h3>
                        <p className="text-xs text-slate-400 leading-relaxed">
                            Clean HTTP status codes, redirect loops prevention, mixed content security, and server version leakage.
                        </p>
                    </div>

                    <div className="p-5 rounded-xl bg-slate-900/50 border border-slate-800 sm:col-span-2 lg:col-span-3">
                        <div className="w-9 h-9 rounded-lg bg-violet-500/10 text-violet-400 flex items-center justify-center mb-3">
                            <Sparkles className="w-5 h-5" />
                        </div>
                        <h3 className="text-base font-bold text-white mb-1">7. AI Search Readiness & Structured Data</h3>
                        <p className="text-xs text-slate-400 leading-relaxed">
                            Schema.org JSON-LD richness, clear markdown hierarchy, robots bot directives, and llms.txt standard support.
                        </p>
                    </div>
                </div>
            </div>

            {/* Quick Exploration Footer */}
            <div className="flex flex-col sm:flex-row items-center justify-center gap-4 text-center">
                <a
                    href="/"
                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-colors"
                >
                    Run Free Website Audit <ArrowRight className="w-4 h-4" />
                </a>
                <a
                    href="/tools"
                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 font-semibold transition-colors"
                >
                    <Wrench className="w-4 h-4" /> Browse Specialized Tools
                </a>
                <a
                    href="/guides"
                    className="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800 font-semibold transition-colors"
                >
                    <BookOpen className="w-4 h-4" /> Read Engineering Guides
                </a>
            </div>
        </div>
    );
}
