import React from 'react';
import { Activity, ShieldCheck, Zap, Globe, FileText, CheckCircle2 } from 'lucide-react';

export default function Footer({ onNavigate }) {
  return (
    <footer className="border-t border-slate-800/80 bg-slate-950/90 text-slate-400 text-xs py-14 mt-auto">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-5 gap-10 mb-12">
          {/* Brand Info */}
          <div className="md:col-span-2 space-y-4">
            <div className="flex items-center gap-3">
              <div className="w-8 h-8 rounded-lg bg-brand-600 flex items-center justify-center text-white font-bold">
                <Activity className="w-4 h-4" />
              </div>
              <span className="font-extrabold text-base text-white tracking-tight">W3HealthChecker</span>
            </div>
            <p className="text-slate-400 leading-relaxed max-w-sm text-sm">
              Know your website. Understand what's wrong. Know exactly how to fix it. Built for developers, webmasters, SEO professionals, and agencies.
            </p>
            <div className="flex items-center gap-4 text-slate-500 pt-2">
              <span className="flex items-center gap-1.5 text-xs text-slate-400">
                <ShieldCheck className="w-4 h-4 text-emerald-400" />
                Defensive &amp; Safe Scanning
              </span>
              <span className="flex items-center gap-1.5 text-xs text-slate-400">
                <CheckCircle2 className="w-4 h-4 text-brand-400" />
                No Vanity Metrics
              </span>
            </div>
          </div>

          {/* Pillars */}
          <div className="space-y-3">
            <h4 className="font-semibold text-slate-200 uppercase tracking-wider text-[11px]">7 Core Pillars</h4>
            <ul className="space-y-2 text-slate-400">
              <li><a href="/tools/seo-checker" onClick={(e) => { e.preventDefault(); onNavigate('tool', 'seo-checker'); }} className="hover:text-white transition-colors">Technical &amp; On-Page SEO</a></li>
              <li><a href="/tools/website-performance-checker" onClick={(e) => { e.preventDefault(); onNavigate('tool', 'website-performance-checker'); }} className="hover:text-white transition-colors">Server &amp; Asset Speed</a></li>
              <li><a href="/tools/security-header-checker" onClick={(e) => { e.preventDefault(); onNavigate('tool', 'security-header-checker'); }} className="hover:text-white transition-colors">Security &amp; SSL Analysis</a></li>
              <li><a href="/tools/mobile-friendly-checker" onClick={(e) => { e.preventDefault(); onNavigate('tool', 'mobile-friendly-checker'); }} className="hover:text-white transition-colors">Mobile Readiness</a></li>
              <li><a href="/tools/ai-search-readiness-checker" onClick={(e) => { e.preventDefault(); onNavigate('tool', 'ai-search-readiness-checker'); }} className="hover:text-white transition-colors">AI &amp; Search Readiness</a></li>
            </ul>
          </div>

          {/* Tools */}
          <div className="space-y-3">
            <h4 className="font-semibold text-slate-200 uppercase tracking-wider text-[11px]">Free Tools</h4>
            <ul className="space-y-2 text-slate-400">
              <li><a href="/tools/sitemap-checker" onClick={(e) => { e.preventDefault(); onNavigate('tool', 'sitemap-checker'); }} className="hover:text-white transition-colors">XML Sitemap &amp; Robots</a></li>
              <li><a href="/compare" onClick={(e) => { e.preventDefault(); onNavigate('compare'); }} className="hover:text-white transition-colors">Website Comparison</a></li>
              <li><a href="/sitemap.xml" target="_blank" className="hover:text-white transition-colors">Dynamic XML Sitemap</a></li>
              <li><a href="/robots.txt" target="_blank" className="hover:text-white transition-colors">Robots.txt Directive</a></li>
            </ul>
          </div>

          {/* SaaS & Legal */}
          <div className="space-y-3">
            <h4 className="font-semibold text-slate-200 uppercase tracking-wider text-[11px]">Resources & About</h4>
            <ul className="space-y-2 text-slate-400">
              <li><a href="/guides" onClick={(e) => { e.preventDefault(); onNavigate('guides_index'); }} className="hover:text-white transition-colors">Technical SEO Guides</a></li>
              <li><a href="/about" onClick={(e) => { e.preventDefault(); onNavigate('about'); }} className="hover:text-white transition-colors">About &amp; Integrity Methodology</a></li>
              <li><a href="/sitemap.xml" target="_blank" className="hover:text-white transition-colors">Dynamic XML Sitemap</a></li>
              <li><a href="/robots.txt" target="_blank" className="hover:text-white transition-colors">Robots.txt Directives</a></li>
              <li className="pt-2 text-[11px] text-slate-500">
                Passive audit only. Zero invasive exploitation or credential scraping.
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom Bar */}
        <div className="pt-8 border-t border-slate-900 flex flex-col sm:flex-row items-center justify-between text-slate-400 text-[11px] gap-4">
          <p>© {new Date().getFullYear()} W3HealthChecker. All rights reserved. Know Your Website. Improve Everything.</p>
          <div className="flex items-center gap-6">
            <span>Privacy Policy</span>
            <span>Terms of Service</span>
            <span>Security Policy</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
