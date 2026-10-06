import React, { useState } from 'react';
import { AlertTriangle, AlertCircle, Info, CheckCircle2, ChevronDown, ChevronUp, Copy, Check, ExternalLink } from 'lucide-react';

export default function IssueCard({ issue, index, defaultOpen = false }) {
  const [isOpen, setIsOpen] = useState(defaultOpen);
  const [copied, setCopied] = useState(false);

  const severity = (issue.severity || 'medium').toLowerCase();

  const getSeverityBadge = (sev) => {
    switch (sev) {
      case 'critical':
        return {
          bg: 'bg-rose-500/10 text-rose-400 border-rose-500/30',
          label: 'CRITICAL',
          icon: AlertCircle,
        };
      case 'high':
        return {
          bg: 'bg-orange-500/10 text-orange-400 border-orange-500/30',
          label: 'HIGH PRIORITY',
          icon: AlertTriangle,
        };
      case 'medium':
        return {
          bg: 'bg-amber-500/10 text-amber-400 border-amber-500/30',
          label: 'MEDIUM',
          icon: AlertTriangle,
        };
      case 'low':
        return {
          bg: 'bg-blue-500/10 text-blue-400 border-blue-500/30',
          label: 'LOW',
          icon: Info,
        };
      default:
        return {
          bg: 'bg-slate-500/10 text-slate-400 border-slate-500/30',
          label: 'INFO',
          icon: Info,
        };
    }
  };

  const badge = getSeverityBadge(severity);
  const IconComponent = badge.icon;

  const handleCopy = (text) => {
    navigator.clipboard.writeText(text);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className={`rounded-xl border transition-all duration-200 ${isOpen ? 'border-slate-700 bg-slate-900/90 shadow-lg' : 'border-slate-800/80 bg-slate-900/40 hover:bg-slate-900/70 hover:border-slate-700'}`}>
      {/* Header bar */}
      <div 
        onClick={() => setIsOpen(!isOpen)}
        className="p-4 sm:p-5 flex items-start justify-between gap-4 cursor-pointer select-none"
      >
        <div className="flex items-start gap-3.5">
          {/* Priority index badge or icon */}
          <div className="mt-0.5 w-6 h-6 rounded-md bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-300 border border-slate-700">
            {issue.priority_order || index + 1}
          </div>

          <div>
            <div className="flex flex-wrap items-center gap-2 mb-1.5">
              <span className={`text-[10px] font-bold px-2 py-0.5 rounded border uppercase tracking-wider ${badge.bg}`}>
                {badge.label}
              </span>
              <span className="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700 uppercase tracking-wider">
                {issue.category}
              </span>
              <span className="text-[10px] text-slate-400">
                Confidence: <strong className="text-slate-300 capitalize">{issue.confidence || 'High'}</strong>
              </span>
            </div>

            <h3 className="text-sm sm:text-base font-semibold text-white tracking-tight">
              {issue.title}
            </h3>

            {issue.affected_resource && (
              <p className="text-xs text-slate-400 mt-1 font-mono break-all line-clamp-1">
                Where: <span className="text-indigo-300">{issue.affected_resource}</span>
              </p>
            )}
          </div>
        </div>

        <button 
          type="button"
          className="p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
          aria-label={isOpen ? 'Collapse issue details' : 'Expand issue details'}
        >
          {isOpen ? <ChevronUp className="w-5 h-5" /> : <ChevronDown className="w-5 h-5" />}
        </button>
      </div>

      {/* Expanded Details Body */}
      {isOpen && (
        <div className="px-4 pb-5 sm:px-5 border-t border-slate-800/80 pt-4 space-y-4 text-xs sm:text-sm">
          {/* Why it matters */}
          <div>
            <h4 className="font-semibold text-slate-200 text-xs uppercase tracking-wider mb-1 flex items-center gap-1.5 text-amber-400">
              <AlertTriangle className="w-3.5 h-3.5" />
              Why It Matters
            </h4>
            <p className="text-slate-300 leading-relaxed bg-slate-950/60 p-3 rounded-lg border border-slate-800">
              {issue.why_it_matters}
            </p>
          </div>

          {/* Evidence */}
          {issue.evidence && (
            <div>
              <h4 className="font-semibold text-slate-200 text-xs uppercase tracking-wider mb-1 text-slate-400">
                Observed Evidence
              </h4>
              <div className="bg-slate-950/80 p-3 rounded-lg border border-slate-800/80 font-mono text-xs text-slate-300 overflow-x-auto">
                <pre>{typeof issue.evidence === 'string' ? issue.evidence : JSON.stringify(issue.evidence, null, 2)}</pre>
              </div>
            </div>
          )}

          {/* Recommended fix */}
          <div>
            <h4 className="font-semibold text-slate-200 text-xs uppercase tracking-wider mb-1 flex items-center gap-1.5 text-emerald-400">
              <CheckCircle2 className="w-3.5 h-3.5" />
              Recommended Fix
            </h4>
            <p className="text-slate-300 leading-relaxed bg-slate-950/60 p-3 rounded-lg border border-slate-800">
              {issue.recommendation}
            </p>
          </div>

          {/* Technical code snippet */}
          {issue.technical_details && (
            <div>
              <div className="flex items-center justify-between mb-1">
                <h4 className="font-semibold text-slate-200 text-xs uppercase tracking-wider text-indigo-400">
                  Technical Fix Example / Code
                </h4>
                <button
                  type="button"
                  onClick={() => handleCopy(issue.technical_details)}
                  className="flex items-center gap-1 text-[11px] text-slate-400 hover:text-white px-2 py-0.5 rounded bg-slate-800 hover:bg-slate-700 transition-colors"
                >
                  {copied ? <Check className="w-3 h-3 text-emerald-400" /> : <Copy className="w-3 h-3" />}
                  <span>{copied ? 'Copied' : 'Copy'}</span>
                </button>
              </div>
              <div className="bg-slate-950 p-3 rounded-lg border border-slate-800 font-mono text-xs text-indigo-200 overflow-x-auto whitespace-pre">
                {issue.technical_details}
              </div>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
