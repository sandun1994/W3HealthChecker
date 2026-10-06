import React from 'react';
import { CheckCircle2, Loader2, AlertCircle, Globe, Shield, Search, Zap, Eye, Cpu, FileCheck } from 'lucide-react';

const STAGES = [
  { id: 'Website reachable', label: 'Website Reachable & DNS Resolution', icon: Globe },
  { id: 'Technical analysis', label: 'Technical & Infrastructure Analysis', icon: Shield },
  { id: 'SEO analysis', label: 'SEO & Meta Tag Audit', icon: Search },
  { id: 'Security analysis', label: 'Defensive Security & SSL Headers', icon: Shield },
  { id: 'Performance analysis', label: 'Performance & Document Hygiene', icon: Zap },
  { id: 'Accessibility analysis', label: 'Accessibility & Mobile Signals', icon: Eye },
  { id: 'AI/Search readiness', label: 'AI & Knowledge Graph Readiness', icon: Cpu },
  { id: 'Generating recommendations', label: 'Prioritizing Actionable Fixes', icon: FileCheck },
];

export default function ScannerProgress({ progress = 10, currentStage = 'Website reachable', targetUrl = '', error = null, onReset = null }) {
  const currentStageIndex = STAGES.findIndex(s => s.id === currentStage);

  return (
    <div className="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 shadow-2xl max-w-2xl mx-auto w-full">
      {/* Target site banner */}
      <div className="flex items-center gap-3 pb-6 border-b border-slate-800">
        <div className="w-10 h-10 rounded-xl bg-brand-500/20 border border-brand-500/30 flex items-center justify-center text-brand-400">
          <Globe className="w-5 h-5 animate-pulse" />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2">
            <span className="text-xs uppercase font-bold text-brand-400 tracking-wider">Scanning Target</span>
            <span className="relative flex h-2 w-2">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
          </div>
          <p className="text-sm sm:text-base font-mono text-white truncate font-medium">
            {targetUrl}
          </p>
        </div>
        <div className="text-right">
          <span className="text-2xl font-extrabold text-white">{progress}%</span>
        </div>
      </div>

      {/* Progress Bar */}
      <div className="my-6">
        <div className="h-2.5 w-full bg-slate-900 rounded-full overflow-hidden border border-slate-800">
          <div
            className="h-full bg-gradient-to-r from-brand-600 via-indigo-500 to-emerald-500 transition-all duration-500 rounded-full"
            style={{ width: `${progress}%` }}
          />
        </div>
      </div>

      {/* Error state */}
      {error && (
        <div className="mb-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-start justify-between gap-3">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 flex-shrink-0 text-rose-400 mt-0.5" />
            <div>
              <strong className="font-semibold block text-sm text-rose-200">Scan Interrupted</strong>
              <p className="mt-1 leading-relaxed">{error}</p>
            </div>
          </div>
          {onReset && (
            <button
              onClick={onReset}
              className="px-3 py-1.5 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 font-semibold text-xs border border-rose-500/30 transition-colors whitespace-nowrap self-center"
            >
              Try Again
            </button>
          )}
        </div>
      )}

      {/* Progressive Stage Checklist */}
      <div className="space-y-3 pt-2">
        {STAGES.map((stage, idx) => {
          const isDone = currentStageIndex > idx || progress === 100;
          const isCurrent = currentStageIndex === idx && progress < 100;
          const Icon = stage.icon;

          return (
            <div
              key={stage.id}
              className={`flex items-center justify-between p-3 rounded-xl border transition-all text-xs sm:text-sm ${
                isCurrent
                  ? 'bg-brand-500/10 border-brand-500/30 text-white shadow-sm'
                  : isDone
                  ? 'bg-slate-900/60 border-slate-800/80 text-slate-300'
                  : 'bg-slate-950/40 border-slate-900 text-slate-400'
              }`}
            >
              <div className="flex items-center gap-3">
                <Icon className={`w-4 h-4 ${isCurrent ? 'text-brand-400 animate-bounce' : isDone ? 'text-emerald-400' : 'text-slate-400'}`} />
                <span className={isCurrent ? 'font-semibold' : ''}>{stage.label}</span>
              </div>

              <div>
                {isDone ? (
                  <CheckCircle2 className="w-4 h-4 text-emerald-400" />
                ) : isCurrent ? (
                  <Loader2 className="w-4 h-4 text-brand-400 animate-spin" />
                ) : (
                  <span className="w-2 h-2 rounded-full bg-slate-800 block" />
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
