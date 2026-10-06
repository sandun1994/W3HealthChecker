import React, { useState } from 'react';
import { ArrowLeft, ArrowRight, Globe, CheckCircle2, ShieldCheck, HelpCircle, Sparkles, ChevronDown, Wrench, BookOpen, Layers } from 'lucide-react';
import ScannerProgress from '../components/ScannerProgress';
import { analytics } from '../services/analytics';

export default function ToolDetailView({
  toolSlug,
  toolData,
  toolsData,
  onStartScan,
  scanning,
  scanProgress,
  currentStage,
  scanTargetUrl,
  scanError,
  onNavigate
}) {
  const [urlInput, setUrlInput] = useState('');
  const [openFaqIndex, setOpenFaqIndex] = useState(0);

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!urlInput.trim()) return;
    analytics.trackToolExecuted(toolSlug, urlInput.trim());
    onStartScan(urlInput.trim());
  };

  const handleFullScanConversion = (e) => {
    e.preventDefault();
    analytics.trackConversion(toolSlug, urlInput.trim() || 'unspecified');
    if (urlInput.trim()) {
      onStartScan(urlInput.trim());
    } else {
      window.location.href = '/';
    }
  };

  if (!toolData) {
    return (
      <div className="flex-1 flex flex-col items-center justify-center p-12 text-center">
        <h2 className="text-xl font-bold text-white mb-4">Tool Not Found</h2>
        <button
          onClick={() => onNavigate('tools')}
          className="px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 text-white"
        >
          View All Tools
        </button>
      </div>
    );
  }

  // Map category to relevant guide slug
  const categoryGuideMap = {
    'SEO': 'seo',
    'Performance': 'performance',
    'Security': 'security',
    'Accessibility': 'accessibility',
    'Structured Data': 'structured-data',
    'AI Readiness': 'ai-search',
  };
  const guideSlug = categoryGuideMap[toolData.category] || 'seo';

  return (
    <div className="flex-1 pb-20 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      {/* Breadcrumbs */}
      <nav className="flex items-center gap-2 text-xs text-slate-400 mb-8 overflow-x-auto whitespace-nowrap">
        <a href="/" onClick={(e) => { e.preventDefault(); onNavigate('home'); }} className="hover:text-white transition-colors">Home</a>
        <span>/</span>
        <a href="/tools" onClick={(e) => { e.preventDefault(); onNavigate('tools'); }} className="hover:text-white transition-colors">Tools</a>
        <span>/</span>
        <span className="text-slate-200 font-medium">{toolData.short_name || toolData.name || toolData.title}</span>
      </nav>

      {/* Hero */}
      <div className="text-center max-w-3xl mx-auto mb-10">
        <span className="text-xs uppercase font-extrabold tracking-widest text-indigo-400 mb-2 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20">
          <Sparkles className="w-3.5 h-3.5" />
          {toolData.category} Diagnostic
        </span>
        <h1 className="text-3xl sm:text-5xl font-extrabold text-white tracking-tight mb-4">
          {toolData.name || toolData.title}
        </h1>
        <p className="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed">
          {toolData.headline || toolData.description}
        </p>
      </div>

      {/* Dedicated Tool Input Form / Progress */}
      <div className="max-w-2xl mx-auto mb-16">
        {scanning ? (
          <ScannerProgress
            progress={scanProgress}
            currentStage={currentStage}
            targetUrl={scanTargetUrl}
            error={scanError}
          />
        ) : (
          <form onSubmit={handleSubmit} className="p-2.5 rounded-2xl bg-slate-900/80 border border-slate-800 shadow-2xl flex flex-col sm:flex-row items-center gap-3 backdrop-blur-sm">
            <div className="relative flex-1 w-full flex items-center">
              <Globe className="w-5 h-5 text-slate-500 absolute left-4 pointer-events-none" />
              <input
                type="text"
                value={urlInput}
                onChange={(e) => setUrlInput(e.target.value)}
                placeholder="Enter URL to test (e.g. example.com)"
                className="w-full pl-12 pr-4 py-3 bg-transparent text-white placeholder-slate-500 text-sm font-medium focus:outline-none"
                required
              />
            </div>

            <button
              type="submit"
              className="w-full sm:w-auto px-6 py-3 rounded-xl font-bold text-xs sm:text-sm bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2 whitespace-nowrap transition-colors"
            >
              <span>RUN {(toolData.short_name || toolData.name || toolData.title).toUpperCase().replace('CHECKER', '').trim()} CHECK</span>
              <ArrowRight className="w-4 h-4" />
            </button>
          </form>
        )}

        {scanError && !scanning && (
          <div className="mt-4 p-3 rounded-lg bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs text-left">
            {scanError}
          </div>
        )}
      </div>

      {/* Feature Checklist */}
      <div className="p-8 rounded-3xl bg-slate-900/60 border border-slate-800 mb-12">
        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-2">
          <Sparkles className="w-5 h-5 text-indigo-400" />
          <span>What This Diagnostic Evaluates</span>
        </h2>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {toolData.features?.map((feat, idx) => (
            <div key={idx} className="flex items-start gap-3 p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 text-xs sm:text-sm text-slate-300">
              <CheckCircle2 className="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" />
              <span>{feat}</span>
            </div>
          ))}
        </div>
      </div>

      {/* Why It Matters Guide & Best Practices */}
      <div className="p-8 rounded-3xl bg-slate-900/40 border border-slate-800 text-slate-300 space-y-4 mb-12">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <h3 className="text-lg font-bold text-white flex items-center gap-2">
            <HelpCircle className="w-5 h-5 text-indigo-400" />
            <span>Why This Diagnostic Matters</span>
          </h3>
          <a
            href={`/guides/${guideSlug}`}
            className="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition-colors"
          >
            <BookOpen className="w-4 h-4" /> Read In-Depth {toolData.category} Guide &rarr;
          </a>
        </div>
        <p className="text-xs sm:text-sm leading-relaxed text-slate-400">
          {toolData.why_it_matters}
        </p>
      </div>

      {/* Frequently Asked Questions (FAQ Accordion for Rich Snippets) */}
      {toolData.faq && toolData.faq.length > 0 && (
        <div className="p-8 rounded-3xl bg-slate-900/60 border border-slate-800 mb-12">
          <h3 className="text-xl font-bold text-white mb-6">
            Frequently Asked Questions
          </h3>
          <div className="space-y-3">
            {toolData.faq.map((item, idx) => {
              const isOpen = openFaqIndex === idx;
              return (
                <div key={idx} className="rounded-xl border border-slate-800/80 bg-slate-950/40 overflow-hidden">
                  <button
                    onClick={() => setOpenFaqIndex(isOpen ? null : idx)}
                    className="w-full flex items-center justify-between p-4 text-left font-semibold text-sm text-white hover:text-indigo-400 transition-colors"
                  >
                    <span>{item.q}</span>
                    <ChevronDown className={`w-4 h-4 text-slate-500 transition-transform ${isOpen ? 'rotate-180 text-indigo-400' : ''}`} />
                  </button>
                  {isOpen && (
                    <div className="px-4 pb-4 pt-1 text-xs sm:text-sm text-slate-400 leading-relaxed border-t border-slate-800/40">
                      {item.a}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* Topic Cluster: Related Diagnostic Tools */}
      {toolData.related_tools && toolData.related_tools.length > 0 && (
        <div className="mb-16">
          <div className="flex items-center justify-between mb-6">
            <h3 className="text-lg font-bold text-white flex items-center gap-2">
              <Wrench className="w-5 h-5 text-indigo-400" />
              <span>Related Diagnostic Tools</span>
            </h3>
            <a href="/tools" className="text-xs font-semibold text-indigo-400 hover:text-indigo-300">
              View All Tools &rarr;
            </a>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            {toolData.related_tools.map((relSlug) => {
              const relTool = toolsData?.[relSlug];
              return (
                <a
                  key={relSlug}
                  href={`/tools/${relSlug}`}
                  className="group p-4 rounded-2xl bg-slate-900/50 border border-slate-800 hover:border-indigo-500/40 hover:bg-slate-900 transition-all flex flex-col justify-between"
                >
                  <div>
                    <span className="text-[10px] font-semibold text-indigo-400 uppercase tracking-wider block mb-1">
                      {relTool?.category || 'Tool'}
                    </span>
                    <h4 className="text-sm font-bold text-white group-hover:text-indigo-400 transition-colors mb-1">
                      {relTool?.title || relSlug}
                    </h4>
                    <p className="text-xs text-slate-400 line-clamp-2">
                      {relTool?.description || 'Quick diagnostic check.'}
                    </p>
                  </div>
                  <div className="flex items-center text-xs font-medium text-indigo-400 mt-4 group-hover:translate-x-0.5 transition-transform">
                    <span>Test Now</span>
                    <ArrowRight className="w-3.5 h-3.5 ml-1" />
                  </div>
                </a>
              );
            })}
          </div>
        </div>
      )}

      {/* Tool-to-Scanner Conversion Hero */}
      <div className="p-8 md:p-10 rounded-3xl bg-gradient-to-r from-indigo-950/40 via-slate-900 to-slate-950 border border-indigo-500/30 flex flex-col md:flex-row items-center justify-between gap-6 shadow-xl">
        <div className="text-center md:text-left">
          <span className="text-xs uppercase font-extrabold tracking-widest text-indigo-400 mb-1 inline-block">
            Comprehensive Website Intelligence
          </span>
          <h3 className="text-xl md:text-2xl font-bold text-white mb-2">
            Want to audit all 7 health pillars at once?
          </h3>
          <p className="text-xs md:text-sm text-slate-400 max-w-xl">
            Get an instant complete website report across SEO, speed, browser security headers, accessibility, and AI search readiness.
          </p>
        </div>
        <button
          onClick={handleFullScanConversion}
          className="px-6 py-3.5 rounded-xl font-bold text-sm bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/25 flex items-center gap-2 whitespace-nowrap transition-colors"
        >
          <span>Run Full Health Scan</span>
          <ArrowRight className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
}
