import React from 'react';
import { ArrowLeft, ArrowRight, Globe, ShieldCheck, Zap, Smartphone, Cpu, CheckCircle2, ChevronRight, Layers } from 'lucide-react';

export default function ToolsIndexView({ onNavigate, toolsData = {} }) {
  const tools = Object.entries(toolsData).map(([slug, data]) => ({ slug, ...data }));

  const getToolIcon = (cat) => {
    switch (cat) {
      case 'SEO': return Globe;
      case 'Security': return ShieldCheck;
      case 'Performance': return Zap;
      case 'Mobile': return Smartphone;
      case 'AI Readiness': return Cpu;
      default: return Layers;
    }
  };

  return (
    <div className="flex-1 pb-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      {/* Back button */}
      <button
        onClick={() => onNavigate('home')}
        className="flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition-colors mb-6"
      >
        <ArrowLeft className="w-4 h-4" />
        <span>Return to Scanner</span>
      </button>

      {/* Header */}
      <div className="text-center max-w-3xl mx-auto mb-16">
        <span className="text-xs uppercase font-extrabold tracking-widest text-brand-400 mb-2 block">
          Website Intelligence Ecosystem
        </span>
        <h1 className="text-3xl sm:text-5xl font-extrabold text-white tracking-tight mb-4">
          Free Specialized Website Audit Tools
        </h1>
        <p className="text-sm sm:text-base text-slate-400">
          Individual, single-purpose diagnostic utilities built for developers, webmasters, and SEO consultants. Every tool is 100% free and requires no sign-up.
        </p>
      </div>

      {/* Grid of Tools */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {tools.map((tool) => {
          const Icon = getToolIcon(tool.category);
          return (
            <div
              key={tool.slug}
              className="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 flex flex-col justify-between hover:border-brand-500/40 transition-all duration-200 hover:-translate-y-1"
            >
              <div>
                <div className="flex items-center justify-between mb-4">
                  <div className="w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-brand-400">
                    <Icon className="w-5 h-5" />
                  </div>
                  <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-brand-500/10 text-brand-300 border border-brand-500/20 uppercase tracking-wider">
                    {tool.category}
                  </span>
                </div>

                <h3 className="text-lg font-bold text-white mb-2">{tool.name}</h3>
                <p className="text-xs text-slate-400 leading-relaxed mb-6">
                  {tool.meta_description}
                </p>

                <div className="space-y-2 mb-6">
                  {tool.features?.slice(0, 3).map((feat, i) => (
                    <div key={i} className="flex items-start gap-2 text-xs text-slate-300">
                      <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400 mt-0.5 flex-shrink-0" />
                      <span>{feat}</span>
                    </div>
                  ))}
                </div>
              </div>

              <button
                type="button"
                onClick={() => onNavigate('tool', tool.slug)}
                className="w-full py-2.5 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-brand-600 text-slate-200 hover:text-white border border-slate-800 hover:border-brand-600 transition-colors flex items-center justify-center gap-1.5"
              >
                <span>Launch {tool.short_name || 'Tool'}</span>
                <ChevronRight className="w-3.5 h-3.5" />
              </button>
            </div>
          );
        })}
      </div>
    </div>
  );
}
