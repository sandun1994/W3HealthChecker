import React, { useState } from 'react';
import { ArrowLeft, ArrowRight, Activity, Globe, RefreshCw, AlertCircle, CheckCircle2, Minus } from 'lucide-react';
import ScoreRing from '../components/ScoreRing';

export default function CompareView({ onNavigate }) {
  const [urlA, setUrlA] = useState('');
  const [urlB, setUrlB] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [comparison, setComparison] = useState(null);

  const handleCompare = async (e) => {
    e.preventDefault();
    if (!urlA.trim() || !urlB.trim()) return;

    setLoading(true);
    setError(null);
    try {
      const res = await fetch('/api/compare', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
        body: JSON.stringify({ url_a: urlA.trim(), url_b: urlB.trim() }),
      });

      const data = await res.json();
      if (data.success) {
        setComparison(data);
      } else {
        setError(data.message || 'Comparison failed.');
      }
    } catch (err) {
      setError('Network error while running comparison.');
    } finally {
      setLoading(false);
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
      <div className="text-center max-w-3xl mx-auto mb-10">
        <span className="text-xs uppercase font-extrabold tracking-widest text-brand-400 mb-2 block">
          Website Comparison Tool
        </span>
        <h1 className="text-3xl sm:text-5xl font-extrabold text-white tracking-tight mb-4">
          Compare Websites Side-by-Side
        </h1>
        <p className="text-sm sm:text-base text-slate-400">
          Audit technical website health, SEO fundamentals, response latency, and defensive security posture between any two domains.
        </p>
      </div>

      {/* Input Form */}
      <form onSubmit={handleCompare} className="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-2xl max-w-4xl mx-auto mb-12">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
          <div>
            <label className="block text-xs uppercase font-bold text-brand-400 mb-2">
              Website A (e.g. yoursite.com)
            </label>
            <div className="relative">
              <Globe className="w-4 h-4 text-slate-500 absolute left-3.5 top-3.5" />
              <input
                type="text"
                value={urlA}
                onChange={(e) => setUrlA(e.target.value)}
                placeholder="https://example-a.com"
                className="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-white text-sm focus:outline-none focus:border-brand-500"
                required
              />
            </div>
          </div>

          <div>
            <label className="block text-xs uppercase font-bold text-indigo-400 mb-2">
              Website B (e.g. competitor.com)
            </label>
            <div className="relative">
              <Globe className="w-4 h-4 text-slate-500 absolute left-3.5 top-3.5" />
              <input
                type="text"
                value={urlB}
                onChange={(e) => setUrlB(e.target.value)}
                placeholder="https://example-b.com"
                className="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-white text-sm focus:outline-none focus:border-brand-500"
                required
              />
            </div>
          </div>
        </div>

        {error && (
          <div className="mt-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center gap-2">
            <AlertCircle className="w-4 h-4 text-rose-400" />
            <span>{error}</span>
          </div>
        )}

        <div className="mt-8 text-center">
          <button
            type="submit"
            disabled={loading}
            className="px-8 py-3.5 rounded-xl font-bold text-sm bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white shadow-xl shadow-brand-600/25 flex items-center justify-center gap-2 mx-auto disabled:opacity-50"
          >
            {loading ? (
              <>
                <RefreshCw className="w-4 h-4 animate-spin" />
                <span>Scanning Both Websites...</span>
              </>
            ) : (
              <>
                <span>Compare Websites</span>
                <ArrowRight className="w-4 h-4" />
              </>
            )}
          </button>
        </div>
      </form>

      {/* Comparison Results */}
      {comparison && (
        <div className="glass-panel p-6 sm:p-10 rounded-3xl border border-slate-800 shadow-2xl max-w-5xl mx-auto">
          <div className="grid grid-cols-2 gap-8 pb-10 border-b border-slate-800 text-center">
            {/* Site A Header */}
            <div className="flex flex-col items-center">
              <span className="text-xs uppercase font-bold text-slate-400 tracking-wider">Website A</span>
              <h2 className="text-xl sm:text-2xl font-mono font-bold text-white mt-1 truncate max-w-xs">
                {comparison.site_a.website?.domain}
              </h2>
              <div className="mt-4">
                <ScoreRing score={comparison.site_a.overall_score || 0} size="large" label={comparison.site_a.status_label} />
              </div>
            </div>

            {/* Site B Header */}
            <div className="flex flex-col items-center">
              <span className="text-xs uppercase font-bold text-slate-400 tracking-wider">Website B</span>
              <h2 className="text-xl sm:text-2xl font-mono font-bold text-white mt-1 truncate max-w-xs">
                {comparison.site_b.website?.domain}
              </h2>
              <div className="mt-4">
                <ScoreRing score={comparison.site_b.overall_score || 0} size="large" label={comparison.site_b.status_label} />
              </div>
            </div>
          </div>

          {/* Metric Comparison Rows */}
          <div className="divide-y divide-slate-800/80 pt-6">
            {[
              { label: 'Overall Score', key: 'overall_score', max: 100 },
              { label: 'SEO Score', key: 'score_seo', max: 100 },
              { label: 'Performance Score', key: 'score_performance', max: 100 },
              { label: 'Security Score', key: 'score_security', max: 100 },
              { label: 'Accessibility Score', key: 'score_accessibility', max: 100 },
              { label: 'Mobile Score', key: 'score_mobile', max: 100 },
              { label: 'Technical Score', key: 'score_technical', max: 100 },
              { label: 'AI & Search Readiness', key: 'score_ai_readiness', max: 100 },
              { label: 'Server Latency (TTFB)', key: 'response_time_ms', suffix: 'ms', lowerIsBetter: true },
            ].map((metric, i) => {
              const valA = comparison.site_a[metric.key] ?? 0;
              const valB = comparison.site_b[metric.key] ?? 0;
              const aBetter = metric.lowerIsBetter ? valA < valB : valA > valB;
              const bBetter = metric.lowerIsBetter ? valB < valA : valB > valA;

              return (
                <div key={i} className="py-4 flex items-center justify-between text-xs sm:text-sm">
                  <div className={`w-1/3 font-bold text-left ${aBetter ? 'text-emerald-400' : 'text-slate-300'}`}>
                    {valA} {metric.suffix || '/ 100'}
                  </div>

                  <div className="w-1/3 text-center text-slate-400 font-medium">
                    {metric.label}
                  </div>

                  <div className={`w-1/3 font-bold text-right ${bBetter ? 'text-emerald-400' : 'text-slate-300'}`}>
                    {valB} {metric.suffix || '/ 100'}
                  </div>
                </div>
              );
            })}
          </div>

          {/* Disclaimer */}
          <div className="mt-8 p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 text-[11px] text-slate-400 text-center leading-relaxed">
            <strong>Important Note:</strong> A higher overall score reflects cleaner technical adherence, faster initial server delivery, and stronger defensive headers. It does not constitute a guarantee of superior Google search rankings.
          </div>
        </div>
      )}
    </div>
  );
}
