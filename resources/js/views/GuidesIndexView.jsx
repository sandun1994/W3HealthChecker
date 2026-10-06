import React from 'react';
import { BookOpen, ArrowRight, ShieldCheck, Zap, Sparkles, Search, Layers, Clock, CheckCircle2 } from 'lucide-react';

const categoryIcons = {
    'SEO': Search,
    'Performance': Zap,
    'Security': ShieldCheck,
    'Accessibility': CheckCircle2,
    'Structured Data': Layers,
    'AI Readiness': Sparkles,
};

export default function GuidesIndexView({ guidesData }) {
    const guides = Object.values(guidesData || {});

    return (
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-16">
            {/* Header */}
            <div className="text-center max-w-3xl mx-auto mb-16">
                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 mb-4">
                    <BookOpen className="w-3.5 h-3.5" />
                    Engineering & Technical SEO Guides
                </div>
                <h1 className="text-3xl md:text-5xl font-extrabold text-white tracking-tight">
                    Website Health & Optimization Guides
                </h1>
                <p className="mt-4 text-lg text-slate-400 leading-relaxed">
                    Actionable, defensible guides covering on-page technical SEO, modern defensive security headers, web server latency, WCAG accessibility, and generative AI search readiness.
                </p>
            </div>

            {/* Guides Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {guides.map((guide) => {
                    const IconComponent = categoryIcons[guide.category] || BookOpen;
                    return (
                        <a
                            key={guide.slug}
                            href={`/guides/${guide.slug}`}
                            className="group flex flex-col justify-between p-6 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-indigo-500/50 hover:bg-slate-900/90 transition-all duration-200 hover:-translate-y-1 shadow-sm hover:shadow-indigo-500/10"
                        >
                            <div>
                                <div className="flex items-center justify-between gap-4 mb-4">
                                    <span className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-800 text-indigo-400 border border-slate-700">
                                        <IconComponent className="w-3.5 h-3.5" />
                                        {guide.category}
                                    </span>
                                    <span className="inline-flex items-center gap-1 text-xs text-slate-400">
                                        <Clock className="w-3 h-3" />
                                        {guide.read_time}
                                    </span>
                                </div>
                                <h2 className="text-xl font-bold text-white group-hover:text-indigo-400 transition-colors mb-3">
                                    {guide.title}
                                </h2>
                                <p className="text-sm text-slate-400 leading-relaxed mb-6">
                                    {guide.summary}
                                </p>
                            </div>

                            <div className="flex items-center text-sm font-semibold text-indigo-400 group-hover:text-indigo-300 gap-1 pt-4 border-t border-slate-800/80">
                                Read Guide <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                            </div>
                        </a>
                    );
                })}
            </div>

            {/* Hub Call-to-Action to Scanner */}
            <div className="mt-20 p-8 md:p-12 rounded-3xl bg-gradient-to-br from-indigo-950/40 via-slate-900 to-slate-950 border border-indigo-500/20 text-center max-w-4xl mx-auto shadow-xl">
                <h3 className="text-2xl md:text-3xl font-bold text-white mb-3">
                    Want to test your website against these guidelines?
                </h3>
                <p className="text-slate-400 max-w-xl mx-auto mb-6 text-base">
                    Run our comprehensive scanner to receive an instant, multi-pillar audit identifying exactly which titles, headers, images, and schemas need improvement.
                </p>
                <a
                    href="/"
                    className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold transition-all duration-200 shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/40"
                >
                    Run Full Website Health Scan <ArrowRight className="w-4 h-4" />
                </a>
            </div>
        </div>
    );
}
