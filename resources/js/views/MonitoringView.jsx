import React, { useState, useEffect } from 'react';
import { 
  Activity, Globe, Calendar, Clock, Bell, AlertTriangle, ShieldCheck, 
  ArrowRight, RefreshCw, Trash2, CheckCircle2, TrendingUp, TrendingDown, 
  Layers, ExternalLink, ChevronRight, Eye, Check, Sparkles 
} from 'lucide-react';

export default function MonitoringView({ onNavigate, prefillUrl = '' }) {
  const [monitors, setMonitors] = useState([]);
  const [loading, setLoading] = useState(true);
  const [urlInput, setUrlInput] = useState(prefillUrl || '');
  const [schedule, setSchedule] = useState('daily');
  const [alertEmail, setAlertEmail] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState(null);
  const [successMessage, setSuccessMessage] = useState(null);

  // Selected domain for trend/changes view
  const [selectedDomain, setSelectedDomain] = useState(null);
  const [trendData, setTrendData] = useState(null);
  const [loadingTrend, setLoadingTrend] = useState(false);
  const [runningId, setRunningId] = useState(null);

  useEffect(() => {
    fetchMonitors();
  }, []);

  const fetchMonitors = async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/monitoring');
      const data = await res.json();
      if (data.success) {
        setMonitors(data.monitored_websites || []);
        if (data.monitored_websites?.length > 0 && !selectedDomain) {
          fetchTrend(data.monitored_websites[0].domain);
        }
      }
    } catch (e) {
      console.error('Failed to load monitors', e);
    } finally {
      setLoading(false);
    }
  };

  const fetchTrend = async (domain) => {
    setSelectedDomain(domain);
    setLoadingTrend(true);
    try {
      const res = await fetch(`/api/websites/${domain}/trend`);
      const data = await res.json();
      if (data.success) {
        setTrendData(data);
      }
    } catch (e) {
      console.error('Failed to load trend', e);
    } finally {
      setLoadingTrend(false);
    }
  };

  const handleAddMonitor = async (e) => {
    e.preventDefault();
    if (!urlInput.trim()) return;

    setSubmitting(true);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      const res = await fetch('/api/monitoring', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
        body: JSON.stringify({
          url: urlInput.trim(),
          schedule,
          alert_email: alertEmail.trim() || null,
        }),
      });

      const data = await res.json();

      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Failed to configure monitoring.');
      }

      setSuccessMessage(data.message || 'Website monitoring active.');
      setUrlInput('');
      await fetchMonitors();
      if (data.monitored_website?.domain) {
        fetchTrend(data.monitored_website.domain);
      }
    } catch (err) {
      setErrorMessage(err.message || 'An error occurred while adding website.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleRunNow = async (id, domain) => {
    setRunningId(id);
    try {
      const res = await fetch(`/api/monitoring/${id}/run`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
      });
      const data = await res.json();
      if (data.success) {
        await fetchMonitors();
        fetchTrend(domain);
      } else {
        alert(data.error || 'Failed to run monitor check.');
      }
    } catch (e) {
      alert('Network error while running monitor.');
    } finally {
      setRunningId(null);
    }
  };

  const handleDelete = async (id, domain) => {
    if (!confirm(`Stop monitoring ${domain}?`)) return;

    try {
      const res = await fetch(`/api/monitoring/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
      });
      const data = await res.json();
      if (data.success) {
        await fetchMonitors();
        if (selectedDomain === domain) {
          setSelectedDomain(null);
          setTrendData(null);
        }
      }
    } catch (e) {
      alert('Failed to remove monitoring.');
    }
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16 flex-1">
      {/* Header */}
      <div className="max-w-3xl mb-10">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 mb-3">
          <Activity className="w-3.5 h-3.5" />
          Automated Health Monitoring & Change Detection
        </div>
        <h1 className="text-3xl md:text-4xl font-extrabold text-white tracking-tight">
          Website Health Over Time
        </h1>
        <p className="mt-2 text-base text-slate-400 leading-relaxed">
          Track technical regressions, score drops, and document changes with scheduled daily or weekly passive audits. Never get caught off-guard by broken security headers or unexpected noindex flags.
        </p>
      </div>

      {/* Add Monitor Form */}
      <div className="p-6 md:p-8 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-xl mb-12">
        <h2 className="text-lg font-bold text-white mb-4 flex items-center gap-2">
          <Bell className="w-4 h-4 text-indigo-400" />
          <span>Monitor a Website</span>
        </h2>

        <form onSubmit={handleAddMonitor} className="grid grid-cols-1 md:grid-cols-12 gap-4">
          <div className="md:col-span-5">
            <label className="block text-xs font-medium text-slate-400 mb-1">Target Website URL</label>
            <div className="relative">
              <Globe className="w-4 h-4 text-slate-500 absolute left-3.5 top-3.5" />
              <input
                type="text"
                value={urlInput}
                onChange={(e) => setUrlInput(e.target.value)}
                placeholder="https://example.com"
                className="w-full pl-10 pr-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                required
              />
            </div>
          </div>

          <div className="md:col-span-3">
            <label className="block text-xs font-medium text-slate-400 mb-1">Frequency</label>
            <select
              value={schedule}
              onChange={(e) => setSchedule(e.target.value)}
              className="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500"
            >
              <option value="daily">Daily Check</option>
              <option value="weekly">Weekly Check</option>
            </select>
          </div>

          <div className="md:col-span-4">
            <label className="block text-xs font-medium text-slate-400 mb-1">Alert Email (Optional)</label>
            <input
              type="email"
              value={alertEmail}
              onChange={(e) => setAlertEmail(e.target.value)}
              placeholder="alerts@company.com"
              className="w-full px-3 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            />
          </div>

          <div className="md:col-span-12 flex items-center justify-between pt-2">
            <p className="text-xs text-slate-500">
              * Passive audits run safely through the existing SSRF-protected scanning engine.
            </p>
            <button
              type="submit"
              disabled={submitting}
              className="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs sm:text-sm shadow-lg shadow-indigo-600/25 flex items-center gap-2 transition-all disabled:opacity-50"
            >
              {submitting ? (
                <>
                  <RefreshCw className="w-4 h-4 animate-spin" />
                  <span>Configuring...</span>
                </>
              ) : (
                <>
                  <span>Start Monitoring</span>
                  <ArrowRight className="w-4 h-4" />
                </>
              )}
            </button>
          </div>
        </form>

        {errorMessage && (
          <div className="mt-4 p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
            {errorMessage}
          </div>
        )}

        {successMessage && (
          <div className="mt-4 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center gap-2">
            <CheckCircle2 className="w-4 h-4 flex-shrink-0" />
            <span>{successMessage}</span>
          </div>
        )}
      </div>

      {/* Main Grid: Monitored Websites & Trend Drawer */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
        {/* Left Column: Monitored Websites List */}
        <div className="lg:col-span-5 space-y-4">
          <div className="flex items-center justify-between pb-2">
            <h2 className="text-base font-bold text-white flex items-center gap-2">
              <span>Monitored Websites</span>
              <span className="text-xs px-2 py-0.5 rounded-full bg-slate-800 text-indigo-400 font-semibold border border-slate-700">
                {monitors.length}
              </span>
            </h2>
            <button
              onClick={fetchMonitors}
              className="p-1.5 text-slate-400 hover:text-white transition-colors"
              title="Refresh monitors"
            >
              <RefreshCw className="w-4 h-4" />
            </button>
          </div>

          {loading ? (
            <div className="p-8 text-center text-slate-500 text-sm">
              <RefreshCw className="w-6 h-6 animate-spin mx-auto mb-2 text-indigo-500" />
              Loading monitored websites...
            </div>
          ) : monitors.length === 0 ? (
            <div className="p-8 rounded-2xl bg-slate-900/40 border border-dashed border-slate-800 text-center text-slate-400 text-sm">
              No websites currently monitored. Add your first website above.
            </div>
          ) : (
            monitors.map((m) => {
              const isSelected = selectedDomain === m.domain;
              const isRunning = runningId === m.id;

              return (
                <div
                  key={m.id}
                  onClick={() => fetchTrend(m.domain)}
                  className={`p-4 rounded-2xl cursor-pointer transition-all border ${
                    isSelected
                      ? 'bg-slate-900 border-indigo-500/60 shadow-lg shadow-indigo-500/5'
                      : 'bg-slate-900/40 border-slate-800 hover:border-slate-700 hover:bg-slate-900/70'
                  }`}
                >
                  <div className="flex items-center justify-between gap-3 mb-2">
                    <span className="font-bold text-sm text-white truncate">{m.domain}</span>
                    <div className="flex items-center gap-2">
                      <span className="text-[10px] uppercase font-bold px-2 py-0.5 rounded bg-slate-800 text-slate-300">
                        {m.schedule}
                      </span>
                      {m.latest_score !== null && m.latest_score !== undefined && (
                        <span className={`text-xs font-bold px-2 py-0.5 rounded-full ${
                          m.latest_score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                          m.latest_score >= 60 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' :
                          'bg-rose-500/10 text-rose-400 border border-rose-500/20'
                        }`}>
                          {m.latest_score}/100
                        </span>
                      )}
                    </div>
                  </div>

                  <div className="text-xs text-slate-500 flex items-center justify-between gap-2 mt-3 pt-3 border-t border-slate-800/60">
                    <div className="flex items-center gap-1.5">
                      <Clock className="w-3 h-3 text-slate-600" />
                      <span>{m.last_scanned_at ? `Scanned ${new Date(m.last_scanned_at).toLocaleDateString()}` : 'Pending first scan'}</span>
                    </div>

                    <div className="flex items-center gap-2">
                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleRunNow(m.id, m.domain);
                        }}
                        disabled={isRunning}
                        className="px-2.5 py-1 rounded-md text-[11px] font-semibold bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 flex items-center gap-1 transition-colors"
                        title="Run check now"
                      >
                        <RefreshCw className={`w-3 h-3 ${isRunning ? 'animate-spin' : ''}`} />
                        <span>Run</span>
                      </button>

                      <button
                        onClick={(e) => {
                          e.stopPropagation();
                          handleDelete(m.id, m.domain);
                        }}
                        className="p-1 rounded-md text-slate-500 hover:text-rose-400 transition-colors"
                        title="Delete monitor"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>

        {/* Right Column: Health Trend & Detected Changes */}
        <div className="lg:col-span-7">
          {loadingTrend ? (
            <div className="p-16 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-slate-500 text-sm">
              <RefreshCw className="w-8 h-8 animate-spin mx-auto mb-3 text-indigo-500" />
              Calculating health trends & changes for {selectedDomain}...
            </div>
          ) : !selectedDomain || !trendData ? (
            <div className="p-16 rounded-3xl bg-slate-900/40 border border-slate-800 text-center text-slate-500 text-sm">
              <Activity className="w-8 h-8 mx-auto mb-3 text-slate-600" />
              Select a monitored website on the left to view historical trends and change detection.
            </div>
          ) : (
            <div className="space-y-6">
              {/* Trend Summary Card */}
              <div className="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-xl">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
                  <div>
                    <span className="text-xs uppercase font-extrabold text-indigo-400 tracking-wider">Health Trend</span>
                    <h3 className="text-xl font-bold text-white flex items-center gap-2">
                      <span>{trendData.domain}</span>
                    </h3>
                  </div>

                  {trendData.deltas && (
                    <div className="flex items-center gap-3">
                      <div className="text-right">
                        <span className="text-xs text-slate-400 block">Overall Score Change</span>
                        <span className={`text-base font-extrabold flex items-center justify-end gap-1 ${
                          trendData.deltas.overall > 0 ? 'text-emerald-400' :
                          trendData.deltas.overall < 0 ? 'text-rose-400' : 'text-slate-400'
                        }`}>
                          {trendData.deltas.overall > 0 && <TrendingUp className="w-4 h-4" />}
                          {trendData.deltas.overall < 0 && <TrendingDown className="w-4 h-4" />}
                          {trendData.deltas.overall > 0 ? `+${trendData.deltas.overall}` : trendData.deltas.overall} pts
                        </span>
                      </div>
                    </div>
                  )}
                </div>

                {/* Score Trajectory Bar */}
                {trendData.score_trajectory?.length > 1 && (
                  <div className="mt-5">
                    <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-3">
                      Historical Progression ({trendData.total_scans} scans)
                    </span>
                    <div className="flex items-center gap-2 overflow-x-auto pb-2">
                      {trendData.score_trajectory.map((t, idx) => (
                        <React.Fragment key={idx}>
                          <a
                            href={`/report/${trendData.domain}/${t.public_id}`}
                            className="flex flex-col items-center p-2.5 rounded-xl bg-slate-950/80 border border-slate-800 hover:border-indigo-500/50 transition-all text-center min-w-[72px]"
                          >
                            <span className="text-[10px] text-slate-500 mb-1">{t.date}</span>
                            <span className="text-sm font-black text-white">{t.score}</span>
                          </a>
                          {idx < trendData.score_trajectory.length - 1 && (
                            <ChevronRight className="w-3.5 h-3.5 text-slate-700 flex-shrink-0" />
                          )}
                        </React.Fragment>
                      ))}
                    </div>
                  </div>
                )}

                {/* Pillar Deltas Grid */}
                {trendData.deltas && (
                  <div className="mt-6 pt-5 border-t border-slate-800/80">
                    <span className="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-3">
                      Pillar Performance Changes
                    </span>
                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                      {[
                        { label: 'SEO', delta: trendData.deltas.seo },
                        { label: 'Security', delta: trendData.deltas.security },
                        { label: 'Performance', delta: trendData.deltas.performance },
                        { label: 'Accessibility', delta: trendData.deltas.accessibility },
                        { label: 'Mobile', delta: trendData.deltas.mobile },
                        { label: 'Technical', delta: trendData.deltas.technical },
                        { label: 'AI Readiness', delta: trendData.deltas.ai_readiness },
                      ].map((p, idx) => (
                        <div key={idx} className="p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80 flex items-center justify-between text-xs">
                          <span className="text-slate-400 font-medium">{p.label}</span>
                          <span className={`font-bold ${
                            p.delta > 0 ? 'text-emerald-400' :
                            p.delta < 0 ? 'text-rose-400' : 'text-slate-500'
                          }`}>
                            {p.delta > 0 ? `+${p.delta}` : p.delta}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                )}
              </div>

              {/* Detected Changes Log */}
              <div className="p-6 rounded-3xl bg-slate-900/60 border border-slate-800 shadow-xl">
                <h4 className="text-base font-bold text-white mb-4 flex items-center justify-between">
                  <span>Detected Changes & Regressions</span>
                  <span className="text-xs text-slate-500 font-normal">
                    {trendData.changes?.length || 0} event(s) logged
                  </span>
                </h4>

                {(!trendData.changes || trendData.changes.length === 0) ? (
                  <div className="p-8 text-center text-slate-500 text-xs rounded-2xl bg-slate-950/40 border border-slate-800">
                    <CheckCircle2 className="w-5 h-5 mx-auto mb-2 text-emerald-400" />
                    No regressions or major document modifications detected between scans.
                  </div>
                ) : (
                  <div className="space-y-3">
                    {trendData.changes.map((change) => {
                      const sev = change.severity;
                      const sevBadgeClass = 
                        sev === 'critical' ? 'bg-rose-500/10 text-rose-400 border-rose-500/20' :
                        sev === 'high' ? 'bg-amber-500/10 text-amber-400 border-amber-500/20' :
                        sev === 'medium' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' :
                        'bg-slate-800 text-slate-400 border-slate-700';

                      return (
                        <div
                          key={change.id}
                          className="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800/80 text-xs space-y-1.5"
                        >
                          <div className="flex items-center justify-between gap-2">
                            <span className="font-bold text-white text-sm">{change.title}</span>
                            <span className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase border ${sevBadgeClass}`}>
                              {sev}
                            </span>
                          </div>
                          <p className="text-slate-400 leading-relaxed">{change.description}</p>
                          {(change.old_value || change.new_value) && (
                            <div className="flex flex-wrap items-center gap-3 pt-1 text-[11px] font-mono text-slate-500">
                              {change.old_value && (
                                <span>Previous: <strong className="text-slate-300">{change.old_value}</strong></span>
                              )}
                              {change.new_value && (
                                <span>Current: <strong className="text-indigo-400">{change.new_value}</strong></span>
                              )}
                            </div>
                          )}
                        </div>
                      );
                    })}
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
