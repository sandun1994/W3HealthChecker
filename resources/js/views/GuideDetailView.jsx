import React, { useEffect } from 'react';
import { BookOpen, ArrowLeft, ArrowRight, Clock, CheckCircle2, ChevronRight, Wrench, ShieldCheck, Zap, Sparkles, Search, Layers, Copy, Check } from 'lucide-react';
import { analytics } from '../services/analytics';

const categoryIcons = {
    'SEO': Search,
    'Performance': Zap,
    'Security': ShieldCheck,
    'Accessibility': CheckCircle2,
    'Structured Data': Layers,
    'AI Readiness': Sparkles,
};

export default function GuideDetailView({ guide, toolsData }) {
    const [copiedIndex, setCopiedIndex] = React.useState(null);

    useEffect(() => {
        if (guide) {
            analytics.trackGuideRead(guide.slug, guide.read_time);
        }
    }, [guide]);

    if (!guide) {
        return (
            <div className="max-w-4xl mx-auto px-4 py-20 text-center">
                <h1 className="text-2xl font-bold text-white mb-4">Guide Not Found</h1>
                <a href="/guides" className="text-indigo-400 hover:underline">Return to Guides Directory</a>
            </div>
        );
    }

    const IconComponent = categoryIcons[guide.category] || BookOpen;

    const copyCode = (code, index) => {
        navigator.clipboard.writeText(code);
        setCopiedIndex(index);
        setTimeout(() => setCopiedIndex(null), 2000);
    };

    return (
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            {/* Breadcrumb Navigation */}
            <nav className="flex items-center gap-2 text-sm text-slate-400 mb-8 overflow-x-auto whitespace-nowrap">
                <a href="/" className="hover:text-white transition-colors">Home</a>
                <ChevronRight className="w-3.5 h-3.5 flex-shrink-0 text-slate-600" />
                <a href="/guides" className="hover:text-white transition-colors">Guides</a>
                <ChevronRight className="w-3.5 h-3.5 flex-shrink-0 text-slate-600" />
                <span className="text-slate-200 font-medium truncate">{guide.short_title || guide.title}</span>
            </nav>

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-12">
                {/* Main Article Content */}
                <article className="lg:col-span-8">
                    {/* Header */}
                    <div className="border-b border-slate-800 pb-8 mb-10">
                        <div className="flex items-center gap-3 mb-4">
                            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                <IconComponent className="w-3.5 h-3.5" />
                                {guide.category}
                            </span>
                            <span className="inline-flex items-center gap-1 text-xs text-slate-400">
                                <Clock className="w-3.5 h-3.5" />
                                {guide.read_time}
                            </span>
                        </div>
                        <h1 className="text-3xl sm:text-4xl md:text-5xl font-black text-white tracking-tight leading-tight mb-6">
                            {guide.title}
                        </h1>
                        <p className="text-lg text-slate-300 leading-relaxed bg-slate-900/60 border-l-4 border-indigo-500 p-4 rounded-r-xl">
                            {guide.summary}
                        </p>
                    </div>

                    {/* Content Sections */}
                    <div className="space-y-12">
                        {guide.sections?.map((section, idx) => (
                            <section key={idx} id={`section-${idx}`} className="scroll-mt-24">
                                <h2 className="text-2xl font-bold text-white mb-4 flex items-center gap-3">
                                    <span className="flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-500/20 text-indigo-400 text-sm font-bold border border-indigo-500/30">
                                        {idx + 1}
                                    </span>
                                    {section.heading.replace(/^\d+\.\s*/, '')}
                                </h2>
                                <p className="text-slate-300 text-base leading-relaxed mb-4">
                                    {section.content}
                                </p>
                                {section.code && (
                                    <div className="relative mt-4 rounded-xl bg-slate-950 border border-slate-800 overflow-hidden shadow-md">
                                        <div className="flex items-center justify-between px-4 py-2 bg-slate-900/80 border-b border-slate-800 text-xs text-slate-400 font-mono">
                                            <span>Implementation Snippet</span>
                                            <button
                                                onClick={() => copyCode(section.code, idx)}
                                                className="flex items-center gap-1 hover:text-white transition-colors text-xs"
                                                title="Copy to clipboard"
                                            >
                                                {copiedIndex === idx ? (
                                                    <>
                                                        <Check className="w-3.5 h-3.5 text-emerald-400" />
                                                        <span className="text-emerald-400">Copied!</span>
                                                    </>
                                                ) : (
                                                    <>
                                                        <Copy className="w-3.5 h-3.5" />
                                                        <span>Copy</span>
                                                    </>
                                                )}
                                            </button>
                                        </div>
                                        <pre className="p-4 text-xs sm:text-sm font-mono text-emerald-300 overflow-x-auto">
                                            <code>{section.code}</code>
                                        </pre>
                                    </div>
                                )}
                            </section>
                        ))}
                    </div>

                    {/* Bottom Hub Callout */}
                    <div className="mt-16 p-8 rounded-2xl bg-gradient-to-r from-slate-900 to-indigo-950/40 border border-indigo-500/20">
                        <h3 className="text-xl font-bold text-white mb-2">Automate this audit on your website</h3>
                        <p className="text-sm text-slate-400 mb-6">
                            Instead of manually auditing every heading, canonical tag, and security header, let W3HealthChecker run an instant automated diagnostic scan.
                        </p>
                        <a
                            href="/"
                            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-sm transition-colors shadow-lg shadow-indigo-600/20"
                        >
                            Launch Health Scanner <ArrowRight className="w-4 h-4" />
                        </a>
                    </div>
                </article>

                {/* Sidebar: Table of Contents & Topic Cluster Tools */}
                <aside className="lg:col-span-4 space-y-8">
                    {/* Table of Contents */}
                    <div className="sticky top-24 space-y-8">
                        <div className="p-6 rounded-2xl bg-slate-900/60 border border-slate-800">
                            <h3 className="text-sm font-semibold uppercase tracking-wider text-slate-400 mb-4">
                                Table of Contents
                            </h3>
                            <ul className="space-y-2 text-sm">
                                {guide.sections?.map((section, idx) => (
                                    <li key={idx}>
                                        <a
                                            href={`#section-${idx}`}
                                            className="text-slate-400 hover:text-indigo-400 transition-colors line-clamp-1 flex items-center gap-2"
                                        >
                                            <span className="w-1.5 h-1.5 rounded-full bg-slate-600"></span>
                                            {section.heading}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {/* Related Specialized Tools (Internal Linking Topic Cluster) */}
                        {guide.related_tools && guide.related_tools.length > 0 && (
                            <div className="p-6 rounded-2xl bg-slate-900/60 border border-slate-800">
                                <div className="flex items-center gap-2 text-sm font-semibold text-white mb-4">
                                    <Wrench className="w-4 h-4 text-indigo-400" />
                                    Related Diagnostic Tools
                                </div>
                                <div className="space-y-3">
                                    {guide.related_tools.map((toolSlug) => {
                                        const tool = toolsData?.[toolSlug];
                                        return (
                                            <a
                                                key={toolSlug}
                                                href={`/tools/${toolSlug}`}
                                                className="group block p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 hover:border-indigo-500/40 hover:bg-slate-900 transition-all"
                                            >
                                                <div className="text-xs font-bold text-white group-hover:text-indigo-400 transition-colors flex items-center justify-between">
                                                    <span>{tool?.title || toolSlug}</span>
                                                    <ArrowRight className="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all text-indigo-400" />
                                                </div>
                                                <div className="text-xs text-slate-500 line-clamp-1 mt-1">
                                                    {tool?.description || 'Run quick diagnostic check'}
                                                </div>
                                            </a>
                                        );
                                    })}
                                </div>
                            </div>
                        )}

                        {/* Return to Guides Link */}
                        <div className="text-center">
                            <a
                                href="/guides"
                                className="inline-flex items-center gap-2 text-sm font-medium text-slate-400 hover:text-white transition-colors"
                            >
                                <ArrowLeft className="w-4 h-4" /> Back to all guides
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
}
