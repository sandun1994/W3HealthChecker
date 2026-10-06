import React, { useState, useEffect } from 'react';
import { Activity, Globe, Share2, Copy, Check, Printer, RefreshCw, ArrowLeft, ExternalLink, ShieldCheck, Zap, Smartphone, Eye, Cpu, AlertTriangle, Layers, Calendar, Clock, Terminal, HelpCircle, ChevronDown, ChevronUp, Info } from 'lucide-react';
import ScoreRing from '../components/ScoreRing';
import IssueCard from '../components/IssueCard';

export default function ReportView({ domain, publicId, onNavigate, onReScan }) {
  const [report, setReport] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeCategory, setActiveCategory] = useState('all');
  const [copiedLink, setCopiedLink] = useState(false);
  const [activeTab, setActiveTab] = useState('issues'); // 'issues' | 'structured' | 'social' | 'headers'
  const [showScoreExplanation, setShowScoreExplanation] = useState(false);

  useEffect(() => {
    fetchReport();
  }, [domain, publicId]);

  const fetchReport = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await fetch(`/api/scan/report/${domain}/${publicId}`);
      const data = await res.json();
      if (data.success) {
        setReport(data);
      } else {
        setError(data.message || 'Unable to load report.');
      }
    } catch (err) {
      setError('Network error while retrieving report data.');
    } finally {
      setLoading(false);
    }
  };

  const handleCopyLink = () => {
    const url = window.location.href;
    navigator.clipboard.writeText(url);
    setCopiedLink(true);
    setTimeout(() => setCopiedLink(false), 2000);
  };

  const handlePrint = () => {
    window.print();
  };

  if (loading) {
    return (
      <div className="flex-1 flex flex-col items-center justify-center p-12 text-center">
        <RefreshCw className="w-10 h-10 text-brand-500 animate-spin mb-4" />
        <h2 className="text-xl font-bold text-white">Loading Website Health Report...</h2>
        <p className="text-sm text-slate-400 mt-1">Retrieving audit results for {domain}</p>
      </div>
    );
  }

  if (error || !report) {
    return (
      <div className="flex-1 flex flex-col items-center justify-center p-12 text-center max-w-lg mx-auto">
        <div className="w-12 h-12 rounded-full bg-rose-500/20 text-rose-400 flex items-center justify-center mb-4">
          <AlertTriangle className="w-6 h-6" />
        </div>
        <h2 className="text-xl font-bold text-white mb-2">Report Not Found</h2>
        <p className="text-sm text-slate-400 mb-6">{error || 'The requested scan report could not be located.'}</p>
        <button
          onClick={() => onNavigate('home')}
          className="px-5 py-2.5 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-500 flex items-center gap-2"
        >
          <ArrowLeft className="w-4 h-4" />
          <span>Return to Scanner</span>
        </button>
      </div>
    );
  }

  const { scan, fix_these_first, all_issues, metrics, summary } = report;
  const scores = scan.scores || {};

  // Filter issues by category
  const filteredIssues = activeCategory === 'all'
    ? all_issues
    : all_issues.filter(i => (i.category || '').toLowerCase() === activeCategory.toLowerCase());

  const categories = [
    { key: 'all', label: 'All Issues', count: all_issues.length },
    { key: 'seo', label: 'SEO', score: scores.seo, icon: Globe },
    { key: 'performance', label: 'Performance', score: scores.performance, icon: Zap },
    { key: 'security', label: 'Security', score: scores.security, icon: ShieldCheck },
    { key: 'accessibility', label: 'Accessibility', score: scores.accessibility, icon: Eye },
    { key: 'mobile', label: 'Mobile', score: scores.mobile, icon: Smartphone },
    { key: 'technical', label: 'Technical', score: scores.technical, icon: Layers },
    { key: 'ai_readiness', label: 'AI Readiness', score: scores.ai_readiness, icon: Cpu },
  ];

  return (
    <div className="flex-1 pb-20">
      {/* Top Banner / Actions Bar (Hidden during print) */}
      <div className="border-b border-slate-800/80 bg-slate-950/60 py-4 print:hidden">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap items-center justify-between gap-4">
          <button
            onClick={() => onNavigate('home')}
            className="flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            <span>New Scan</span>
          </button>

          <div className="flex flex-wrap items-center gap-2.5">
            <button
              onClick={handleCopyLink}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition-colors"
            >
              {copiedLink ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
              <span>{copiedLink ? 'Link Copied!' : 'Copy Share Link'}</span>
            </button>

            <button
              onClick={handlePrint}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition-colors"
            >
              <Printer className="w-3.5 h-3.5" />
              <span>Download PDF</span>
            </button>

            <button
              onClick={() => onNavigate('monitoring', { url: scan.target_url })}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 transition-colors"
              title="Track website health over time with automated checks"
            >
              <Activity className="w-3.5 h-3.5 text-indigo-400" />
              <span>Monitor Website</span>
            </button>

            <button
              onClick={() => onNavigate('compare')}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-600/20 hover:bg-brand-600/30 text-brand-300 border border-brand-500/30 transition-colors"
            >
              <span>Compare Site</span>
            </button>

            <button
              onClick={() => onReScan(scan.target_url)}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-brand-600 hover:bg-brand-500 text-white shadow-md shadow-brand-600/20 transition-colors"
            >
              <RefreshCw className="w-3.5 h-3.5" />
              <span>Re-Scan</span>
            </button>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
        {/* REPORT HEADER */}
        <div className="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 shadow-xl mb-8">
          <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 pb-6 border-b border-slate-800/80">
            <div>
              <div className="flex items-center gap-2 mb-1">
                <span className="text-xs uppercase font-extrabold text-brand-400 tracking-wider">Website Health Report</span>
                <span className="text-xs text-slate-500">•</span>
                <span className="text-xs font-mono text-slate-400">{scan.public_id}</span>
              </div>
              <h1 className="text-2xl sm:text-4xl font-extrabold text-white tracking-tight flex items-center gap-3">
                <span>{scan.domain}</span>
                <a
                  href={scan.final_url || scan.target_url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-slate-500 hover:text-white transition-colors"
                  aria-label="Open inspected website"
                >
                  <ExternalLink className="w-5 h-5" />
                </a>
              </h1>
              <div className="flex flex-wrap items-center gap-4 text-xs text-slate-400 mt-2 font-mono">
                <span className="flex items-center gap-1">
                  <Clock className="w-3.5 h-3.5 text-slate-500" />
                  Latency: <strong className="text-slate-200">{scan.response_time_ms || 0}ms</strong>
                </span>
                <span>•</span>
                <span>HTTP Status: <strong className="text-slate-200">{scan.http_status_code || 200}</strong></span>
                <span>•</span>
                <span className="flex items-center gap-1">
                  <Calendar className="w-3.5 h-3.5 text-slate-500" />
                  {scan.completed_at ? new Date(scan.completed_at).toLocaleString() : 'Just now'}
                </span>
              </div>
            </div>

            {/* Overall Score Gauge */}
            <div className="flex items-center gap-6 bg-slate-900/90 px-6 py-4 rounded-2xl border border-slate-800 w-full sm:w-auto justify-between sm:justify-start">
              <ScoreRing score={scan.overall_score || 0} size="large" label={scan.status_label} />
              <div className="text-left">
                <span className="text-xs uppercase font-bold text-slate-400 tracking-wider">Overall Health Score</span>
                <div className="text-2xl font-black text-white mt-0.5">{scan.overall_score} / 100</div>
                <p className="text-xs text-slate-400 mt-1 max-w-[160px] leading-tight">
                  Weighted composite across all 7 pillars.
                </p>
              </div>
            </div>
          </div>

          {/* 7 Category Mini Scores */}
          <div className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 pt-6">
            {categories.filter(c => c.key !== 'all').map((cat) => (
              <div
                key={cat.key}
                onClick={() => { setActiveCategory(cat.key); setActiveTab('issues'); }}
                className={`p-3.5 rounded-xl border text-center cursor-pointer transition-all ${
                  activeCategory === cat.key && activeTab === 'issues'
                    ? 'bg-brand-500/10 border-brand-500/50 shadow-md shadow-brand-500/10'
                    : 'bg-slate-900/40 border-slate-800/80 hover:bg-slate-900/80 hover:border-slate-700'
                }`}
              >
                <ScoreRing score={cat.score || 0} size="small" showLabel={false} />
                <span className="text-xs font-bold text-slate-200 mt-2 block truncate">{cat.label}</span>
                <span className="text-[11px] font-semibold text-slate-400">{cat.score || 0}/100</span>
              </div>
            ))}
          </div>

          {/* How is this score calculated? Accordion */}
          <div className="mt-6 pt-5 border-t border-slate-800/80">
            <button
              onClick={() => setShowScoreExplanation(!showScoreExplanation)}
              className="flex items-center justify-between w-full text-left py-2 px-3 rounded-xl bg-slate-900/50 hover:bg-slate-900 border border-slate-800/60 transition-all text-xs text-slate-300 font-medium"
            >
              <span className="flex items-center gap-2">
                <HelpCircle className="w-4 h-4 text-brand-400" />
                <span className="font-semibold text-white">How is this score calculated?</span>
                <span className="text-[11px] text-slate-400 hidden sm:inline">— Transparent 7-pillar diagnostic methodology</span>
              </span>
              {showScoreExplanation ? (
                <ChevronUp className="w-4 h-4 text-slate-400" />
              ) : (
                <ChevronDown className="w-4 h-4 text-slate-400" />
              )}
            </button>

            {showScoreExplanation && (
              <div className="mt-4 p-5 rounded-2xl bg-slate-900/90 border border-slate-800 text-xs text-slate-300 space-y-4 animate-in fade-in duration-200">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <h4 className="font-bold text-white text-sm mb-2 flex items-center gap-2">
                      <span className="w-2 h-2 rounded-full bg-brand-400"></span>
                      Category Weights (100% Total)
                    </h4>
                    <ul className="space-y-1.5 text-slate-300">
                      <li className="flex justify-between border-b border-slate-800/50 pb-1">
                        <span>Technical & On-Page SEO</span>
                        <strong className="text-white font-mono">20%</strong>
                      </li>
                      <li className="flex justify-between border-b border-slate-800/50 pb-1">
                        <span>Defensive Security & SSL</span>
                        <strong className="text-white font-mono">20%</strong>
                      </li>
                      <li className="flex justify-between border-b border-slate-800/50 pb-1">
                        <span>Performance & Document Hygiene</span>
                        <strong className="text-white font-mono">15%</strong>
                      </li>
                      <li className="flex justify-between border-b border-slate-800/50 pb-1">
                        <span>Accessibility Indicators</span>
                        <strong className="text-white font-mono">15%</strong>
                      </li>
                      <li className="flex justify-between border-b border-slate-800/50 pb-1">
                        <span>Mobile Readiness</span>
                        <strong className="text-white font-mono">10%</strong>
                      </li>
                      <li className="flex justify-between border-b border-slate-800/50 pb-1">
                        <span>Technical Infrastructure</span>
                        <strong className="text-white font-mono">10%</strong>
                      </li>
                      <li className="flex justify-between">
                        <span>AI & Search Readiness</span>
                        <strong className="text-white font-mono">10%</strong>
                      </li>
                    </ul>
                  </div>

                  <div>
                    <h4 className="font-bold text-white text-sm mb-2 flex items-center gap-2">
                      <span className="w-2 h-2 rounded-full bg-indigo-400"></span>
                      Deduction & Scoring Model
                    </h4>
                    <p className="text-slate-400 mb-2 leading-relaxed">
                      Each category begins at a 100-point baseline. Points are deducted based on detected technical violations:
                    </p>
                    <div className="grid grid-cols-2 gap-2 text-[11px] mb-3">
                      <div className="p-2 rounded-lg bg-rose-500/10 border border-rose-500/20 text-rose-300">
                        <strong className="block text-rose-200">Critical (-15 to -20 pts)</strong>
                        <span>Blocking crawler or severe security flaw</span>
                      </div>
                      <div className="p-2 rounded-lg bg-orange-500/10 border border-orange-500/20 text-orange-300">
                        <strong className="block text-orange-200">High (-8 to -12 pts)</strong>
                        <span>Significant SEO or missing core protection</span>
                      </div>
                      <div className="p-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-300">
                        <strong className="block text-amber-200">Medium (-4 to -6 pts)</strong>
                        <span>Suboptimal configuration or hygiene</span>
                      </div>
                      <div className="p-2 rounded-lg bg-blue-500/10 border border-blue-500/20 text-blue-300">
                        <strong className="block text-blue-200">Low (-1 to -3 pts)</strong>
                        <span>Minor advisory or recommended polish</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div className="p-3 rounded-xl bg-slate-950 border border-slate-800 text-[11px] text-slate-400 flex items-start gap-2.5">
                  <Info className="w-4 h-4 text-brand-400 flex-shrink-0 mt-0.5" />
                  <p>
                    <strong className="text-slate-200">Diagnostic Integrity:</strong> Scores reflect an objective technical audit of publicly served HTML responses and HTTP headers. They are not official Google search rankings or third-party endorsements. Performance reflects server-side passive measurements; Core Web Vitals require field RUM or Chrome User Experience data.
                  </p>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* SECTION: FIX THESE FIRST */}
        {fix_these_first && fix_these_first.length > 0 && (
          <div className="mb-10 p-6 sm:p-8 rounded-3xl bg-gradient-to-b from-slate-900 to-slate-950 border border-indigo-900/40 shadow-xl">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
              <div>
                <div className="inline-flex items-center gap-2 text-xs uppercase font-bold tracking-wider text-orange-400 mb-1">
                  <AlertTriangle className="w-4 h-4" />
                  Prioritized Action Items
                </div>
                <h2 className="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                  Fix These First
                </h2>
                <p className="text-xs text-slate-400 mt-0.5">
                  If you only have 10 minutes, resolve these highest-impact items to boost website health immediately.
                </p>
              </div>
              <span className="text-xs px-3 py-1 rounded-full bg-slate-800 border border-slate-700 text-slate-300 font-semibold self-start sm:self-auto">
                {fix_these_first.length} Critical / High Issues
              </span>
            </div>

            <div className="space-y-3">
              {fix_these_first.map((issue, idx) => (
                <IssueCard key={issue.id || idx} issue={issue} index={idx} defaultOpen={idx === 0} />
              ))}
            </div>
          </div>
        )}

        {/* DIAGNOSTIC TABS & ALL ISSUES FILTER */}
        <div className="flex flex-wrap items-center justify-between gap-4 mb-6 border-b border-slate-800 pb-4">
          <div className="flex items-center gap-2">
            <button
              onClick={() => setActiveTab('issues')}
              className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all ${
                activeTab === 'issues'
                  ? 'bg-brand-600 text-white shadow-md'
                  : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
              }`}
            >
              All Audit Issues ({all_issues.length})
            </button>
            <button
              onClick={() => setActiveTab('structured')}
              className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all ${
                activeTab === 'structured'
                  ? 'bg-brand-600 text-white shadow-md'
                  : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
              }`}
            >
              Structured Data
            </button>
            <button
              onClick={() => setActiveTab('social')}
              className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all ${
                activeTab === 'social'
                  ? 'bg-brand-600 text-white shadow-md'
                  : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
              }`}
            >
              Social Preview
            </button>
            <button
              onClick={() => setActiveTab('headers')}
              className={`px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-all ${
                activeTab === 'headers'
                  ? 'bg-brand-600 text-white shadow-md'
                  : 'bg-slate-900 text-slate-400 hover:text-white border border-slate-800'
              }`}
            >
              Headers &amp; SSL
            </button>
          </div>

          {activeTab === 'issues' && (
            <div className="flex flex-wrap items-center gap-1.5">
              {categories.map((c) => (
                <button
                  key={c.key}
                  onClick={() => setActiveCategory(c.key)}
                  className={`px-2.5 py-1 rounded-lg text-xs font-semibold transition-colors ${
                    activeCategory === c.key
                      ? 'bg-slate-800 text-white border border-slate-600'
                      : 'text-slate-400 hover:text-slate-200'
                  }`}
                >
                  {c.label} {c.count !== undefined && `(${c.count})`}
                </button>
              ))}
            </div>
          )}
        </div>

        {/* TAB 1: ALL ISSUES LIST */}
        {activeTab === 'issues' && (
          <div>
            {filteredIssues.length === 0 ? (
              <div className="p-12 text-center rounded-2xl bg-slate-900/40 border border-slate-800">
                <CheckCircle2 className="w-10 h-10 text-emerald-400 mx-auto mb-3" />
                <h3 className="text-base font-bold text-white">No Issues Detected</h3>
                <p className="text-xs text-slate-400 mt-1">
                  No issues found matching the selected category.
                </p>
              </div>
            ) : (
              <div className="space-y-3">
                {filteredIssues.map((issue, idx) => (
                  <IssueCard key={issue.id || idx} issue={issue} index={idx} defaultOpen={false} />
                ))}
              </div>
            )}
          </div>
        )}

        {/* TAB 2: STRUCTURED DATA DEEP DIVE */}
        {activeTab === 'structured' && (
          <div className="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800">
            <h3 className="text-lg font-bold text-white mb-2">Schema.org JSON-LD Entities</h3>
            <p className="text-xs text-slate-400 mb-6">
              Verified machine-readable structured data used by search knowledge graphs and AI assistants.
            </p>

            {metrics?.structured_data && metrics.structured_data.length > 0 ? (
              <div className="space-y-4">
                {metrics.structured_data.map((schema, idx) => (
                  <div key={idx} className="p-4 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs">
                    <div className="flex items-center justify-between mb-2 pb-2 border-b border-slate-800/80">
                      <span className="font-bold text-brand-400 uppercase">Type: {schema.type}</span>
                      <span className="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px]">
                        Valid Schema
                      </span>
                    </div>
                    <pre className="text-slate-300 overflow-x-auto">{JSON.stringify(schema.data, null, 2)}</pre>
                  </div>
                ))}
              </div>
            ) : (
              <div className="p-8 text-center bg-slate-950/60 rounded-xl border border-slate-800/80 text-xs text-slate-400">
                No JSON-LD structured schema detected on this page. Consider adding Organization or WebSite schema.
              </div>
            )}
          </div>
        )}

        {/* TAB 3: SOCIAL PREVIEW CARD */}
        {activeTab === 'social' && (
          <div className="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800">
            <h3 className="text-lg font-bold text-white mb-2">Social Sharing Preview (Open Graph &amp; Twitter)</h3>
            <p className="text-xs text-slate-400 mb-6">
              How this URL renders when shared on X, LinkedIn, Facebook, Slack, and messaging apps.
            </p>

            <div className="max-w-md mx-auto rounded-2xl border border-slate-700 bg-slate-900 overflow-hidden shadow-2xl">
              {metrics?.open_graph?.image ? (
                <div className="h-48 bg-slate-800 overflow-hidden relative">
                  <img
                    src={metrics.open_graph.image}
                    alt="Social Card Preview"
                    className="w-full h-full object-cover"
                    onError={(e) => { e.target.style.display = 'none'; }}
                  />
                </div>
              ) : (
                <div className="h-40 bg-slate-950 flex items-center justify-center text-slate-600 text-xs font-mono">
                  No og:image detected
                </div>
              )}
              <div className="p-4">
                <span className="text-[11px] font-mono uppercase text-slate-400 block mb-1">
                  {scan.domain}
                </span>
                <h4 className="font-bold text-white text-sm line-clamp-2 mb-1">
                  {metrics?.open_graph?.title || scan.domain}
                </h4>
                <p className="text-xs text-slate-400 line-clamp-2">
                  {metrics?.open_graph?.description || 'No description provided.'}
                </p>
              </div>
            </div>
          </div>
        )}

        {/* TAB 4: HEADERS & SSL INSPECTION */}
        {activeTab === 'headers' && (
          <div className="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 space-y-6">
            {/* SSL Inspection */}
            <div>
              <h3 className="text-lg font-bold text-white mb-2">SSL / TLS Certificate</h3>
              {metrics?.ssl_data ? (
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-2">
                  <div className="p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <span className="text-[11px] uppercase font-bold text-slate-500 block">Certificate Status</span>
                    <strong className="text-sm font-semibold text-emerald-400">
                      {metrics.ssl_data.valid ? 'Valid & Trusted' : 'Invalid / Expired'}
                    </strong>
                  </div>
                  <div className="p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <span className="text-[11px] uppercase font-bold text-slate-500 block">Issuer Authority</span>
                    <strong className="text-sm font-semibold text-slate-200 truncate block">
                      {metrics.ssl_data.issuer || 'Unknown'}
                    </strong>
                  </div>
                  <div className="p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <span className="text-[11px] uppercase font-bold text-slate-500 block">Expires In</span>
                    <strong className="text-sm font-semibold text-slate-200">
                      {metrics.ssl_data.days_remaining} Days
                    </strong>
                  </div>
                  <div className="p-4 rounded-xl bg-slate-950 border border-slate-800">
                    <span className="text-[11px] uppercase font-bold text-slate-500 block">Valid Until</span>
                    <strong className="text-xs font-semibold text-slate-300">
                      {metrics.ssl_data.valid_to || 'N/A'}
                    </strong>
                  </div>
                </div>
              ) : (
                <div className="p-4 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-400">
                  No SSL certificate details captured (HTTP connection or unverified host).
                </div>
              )}
            </div>

            {/* HTTP Response Headers */}
            <div>
              <h3 className="text-lg font-bold text-white mb-2">Observed HTTP Headers</h3>
              <div className="bg-slate-950 p-4 rounded-xl border border-slate-800 font-mono text-xs text-slate-300 overflow-x-auto max-h-96">
                <pre>{JSON.stringify(metrics?.headers || {}, null, 2)}</pre>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
