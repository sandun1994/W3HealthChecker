import React, { useState, useEffect } from 'react';
import { 
  Globe, Folder, Activity, Bell, Shield, ArrowRight, Plus, Trash2, 
  RefreshCw, CheckCircle2, AlertTriangle, Key, ExternalLink, Clock, 
  Layers, Lock, Database 
} from 'lucide-react';

export default function DashboardView({ user, onNavigate, onLogout }) {
  const [activeTab, setActiveTab] = useState('websites'); // 'websites' | 'projects' | 'scans' | 'settings'
  const [overview, setOverview] = useState(null);
  const [websites, setWebsites] = useState([]);
  const [projects, setProjects] = useState([]);
  const [recentScans, setRecentScans] = useState([]);
  const [loading, setLoading] = useState(true);

  // New Website Modal / Form
  const [newUrl, setNewUrl] = useState('');
  const [selectedProjectId, setSelectedProjectId] = useState('');
  const [addingWebsite, setAddingWebsite] = useState(false);

  // New Project Form
  const [projectName, setProjectName] = useState('');
  const [projectDesc, setProjectDesc] = useState('');
  const [creatingProject, setCreatingProject] = useState(false);

  // Settings / Password Form
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordStatus, setPasswordStatus] = useState(null);
  const [syncStatus, setSyncStatus] = useState(null);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    setLoading(true);
    try {
      const res = await fetch('/api/workspace/overview');
      if (res.status === 401) {
        onNavigate('login');
        return;
      }
      const data = await res.json();
      if (data.success) {
        setOverview(data.overview);
        setWebsites(data.websites || []);
        setRecentScans(data.recent_scans || []);
      }

      // Fetch projects
      const projRes = await fetch('/api/workspace/projects');
      const projData = await projRes.json();
      if (projData.success) {
        setProjects(projData.projects || []);
      }
    } catch (e) {
      console.error('Failed to load dashboard data', e);
    } finally {
      setLoading(false);
    }
  };

  const handleAddWebsite = async (e) => {
    e.preventDefault();
    if (!newUrl.trim()) return;

    setAddingWebsite(true);
    try {
      const res = await fetch('/api/workspace/websites', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
        body: JSON.stringify({
          url: newUrl.trim(),
          project_id: selectedProjectId ? parseInt(selectedProjectId) : null,
        }),
      });

      const data = await res.json();
      if (data.success) {
        setNewUrl('');
        await fetchDashboardData();
      } else {
        alert(data.message || 'Failed to add website.');
      }
    } catch (e) {
      alert('Error adding website.');
    } finally {
      setAddingWebsite(false);
    }
  };

  const handleDeleteWebsite = async (id) => {
    if (!confirm('Remove this website from your workspace?')) return;
    try {
      const res = await fetch(`/api/workspace/websites/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
      });
      const data = await res.json();
      if (data.success) {
        await fetchDashboardData();
      }
    } catch (e) {
      alert('Failed to remove website.');
    }
  };

  const handleCreateProject = async (e) => {
    e.preventDefault();
    if (!projectName.trim()) return;

    setCreatingProject(true);
    try {
      const res = await fetch('/api/workspace/projects', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
        body: JSON.stringify({
          name: projectName.trim(),
          description: projectDesc.trim() || null,
        }),
      });

      const data = await res.json();
      if (data.success) {
        setProjectName('');
        setProjectDesc('');
        await fetchDashboardData();
      }
    } catch (e) {
      alert('Failed to create project.');
    } finally {
      setCreatingProject(false);
    }
  };

  const handleUpdatePassword = async (e) => {
    e.preventDefault();
    if (newPassword !== confirmPassword) {
      setPasswordStatus({ success: false, message: 'Passwords do not match.' });
      return;
    }

    try {
      const res = await fetch('/api/auth/password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
        body: JSON.stringify({
          current_password: currentPassword,
          password: newPassword,
          password_confirmation: confirmPassword,
        }),
      });

      const data = await res.json();
      setPasswordStatus({
        success: data.success,
        message: data.message || (data.success ? 'Password updated.' : 'Failed to update password.'),
      });
      if (data.success) {
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
      }
    } catch (e) {
      setPasswordStatus({ success: false, message: 'Network error.' });
    }
  };

  const handleSyncLocalHistory = async () => {
    try {
      const localEvents = localStorage.getItem('w3_analytics_events');
      const publicIds = [];
      // Extract any public IDs from local session
      if (localEvents) {
        const events = JSON.parse(localEvents);
        events.forEach((ev) => {
          if (ev.properties?.public_id) publicIds.push(ev.properties.public_id);
        });
      }

      if (publicIds.length === 0) {
        setSyncStatus('No local scans found to synchronize.');
        return;
      }

      const res = await fetch('/api/workspace/sync-history', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
        body: JSON.stringify({ public_ids: publicIds }),
      });

      const data = await res.json();
      setSyncStatus(data.message || 'Scans synchronized.');
      await fetchDashboardData();
    } catch (e) {
      setSyncStatus('Sync failed.');
    }
  };

  const handleDeleteAccount = async () => {
    const pwd = prompt('Enter your password to permanently delete your account and all workspace data:');
    if (!pwd) return;

    try {
      const res = await fetch('/api/auth/account', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': window.__W3_INITIAL_DATA__?.csrfToken || '',
        },
        body: JSON.stringify({ password: pwd }),
      });

      const data = await res.json();
      if (data.success) {
        if (onLogout) onLogout();
        onNavigate('home');
      } else {
        alert(data.message || 'Incorrect password.');
      }
    } catch (e) {
      alert('Failed to delete account.');
    }
  };

  if (loading) {
    return (
      <div className="flex-1 flex flex-col items-center justify-center p-16 text-center">
        <RefreshCw className="w-8 h-8 text-indigo-500 animate-spin mb-4" />
        <h2 className="text-lg font-bold text-white">Loading Workspace...</h2>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-16 flex-1">
      {/* Workspace Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
          <span className="text-xs uppercase font-extrabold tracking-wider text-indigo-400">
            User Workspace
          </span>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            Welcome back, {user?.name || 'Developer'}
          </h1>
          <p className="text-xs sm:text-sm text-slate-400 mt-1">
            {user?.email} • Workspace Account
          </p>
        </div>

        <div className="flex items-center gap-3">
          <a
            href="/"
            onClick={(e) => { e.preventDefault(); onNavigate('home'); }}
            className="px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/20 flex items-center gap-1.5 transition-colors"
          >
            <Plus className="w-3.5 h-3.5" /> Run New Scan
          </a>
          <button
            onClick={onLogout}
            className="px-3.5 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition-colors"
          >
            Sign Out
          </button>
        </div>
      </div>

      {/* Metrics Overview Row */}
      {overview && (
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
          <div className="p-5 rounded-2xl bg-slate-900/60 border border-slate-800">
            <span className="text-xs font-semibold text-slate-400 block mb-1">Saved Websites</span>
            <span className="text-2xl font-black text-white">{overview.total_websites}</span>
          </div>
          <div className="p-5 rounded-2xl bg-slate-900/60 border border-slate-800">
            <span className="text-xs font-semibold text-slate-400 block mb-1">Active Monitors</span>
            <span className="text-2xl font-black text-indigo-400">{overview.total_monitored}</span>
          </div>
          <div className="p-5 rounded-2xl bg-slate-900/60 border border-slate-800">
            <span className="text-xs font-semibold text-slate-400 block mb-1">Projects</span>
            <span className="text-2xl font-black text-white">{overview.total_projects}</span>
          </div>
          <div className="p-5 rounded-2xl bg-slate-900/60 border border-slate-800">
            <span className="text-xs font-semibold text-slate-400 block mb-1">Avg Health Score</span>
            <span className="text-2xl font-black text-emerald-400">
              {overview.average_score ? `${overview.average_score}/100` : '—'}
            </span>
          </div>
        </div>
      )}

      {/* Tab Navigation */}
      <div className="flex items-center gap-2 border-b border-slate-800 mb-8 overflow-x-auto">
        {[
          { key: 'websites', label: 'My Websites', icon: Globe },
          { key: 'projects', label: 'Projects', icon: Folder },
          { key: 'scans', label: 'Scan History', icon: Clock },
          { key: 'settings', label: 'Account & Security', icon: Lock },
        ].map((tab) => {
          const Icon = tab.icon;
          const isActive = activeTab === tab.key;
          return (
            <button
              key={tab.key}
              onClick={() => setActiveTab(tab.key)}
              className={`flex items-center gap-2 px-4 py-3 text-xs sm:text-sm font-semibold border-b-2 transition-all whitespace-nowrap ${
                isActive
                  ? 'border-indigo-500 text-white'
                  : 'border-transparent text-slate-400 hover:text-slate-200'
              }`}
            >
              <Icon className="w-4 h-4" />
              <span>{tab.label}</span>
            </button>
          );
        })}
      </div>

      {/* Tab Content: Websites */}
      {activeTab === 'websites' && (
        <div className="space-y-6">
          {/* Add Website Box */}
          <div className="p-6 rounded-2xl bg-slate-900/40 border border-slate-800">
            <h3 className="text-sm font-bold text-white mb-3">Add Website to Workspace</h3>
            <form onSubmit={handleAddWebsite} className="flex flex-col sm:flex-row gap-3">
              <input
                type="text"
                value={newUrl}
                onChange={(e) => setNewUrl(e.target.value)}
                placeholder="https://example.com"
                required
                className="flex-1 px-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500"
              />
              {projects.length > 0 && (
                <select
                  value={selectedProjectId}
                  onChange={(e) => setSelectedProjectId(e.target.value)}
                  className="px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white"
                >
                  <option value="">No Project (Uncategorized)</option>
                  {projects.map((p) => (
                    <option key={p.id} value={p.id}>{p.name}</option>
                  ))}
                </select>
              )}
              <button
                type="submit"
                disabled={addingWebsite}
                className="px-5 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white transition-colors"
              >
                {addingWebsite ? 'Adding...' : 'Save Website'}
              </button>
            </form>
          </div>

          {/* Websites Table / List */}
          <div className="p-6 rounded-3xl bg-slate-900/60 border border-slate-800">
            <h3 className="text-base font-bold text-white mb-4">Workspace Websites ({websites.length})</h3>

            {websites.length === 0 ? (
              <div className="p-8 text-center text-slate-500 text-sm">
                No saved websites yet. Add a website above or run a scan.
              </div>
            ) : (
              <div className="space-y-3">
                {websites.map((w) => {
                  const score = w.latest_scan?.overall_score;
                  return (
                    <div
                      key={w.id}
                      className="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                    >
                      <div>
                        <div className="flex items-center gap-2 mb-1">
                          <span className="font-bold text-sm text-white">{w.domain}</span>
                          {w.project && (
                            <span className="text-[10px] px-2 py-0.5 rounded bg-slate-800 text-indigo-300 font-medium">
                              {w.project.name}
                            </span>
                          )}
                          {w.is_monitored && (
                            <span className="text-[10px] px-2 py-0.5 rounded bg-emerald-950 text-emerald-300 font-semibold border border-emerald-800/60">
                              MONITORED
                            </span>
                          )}
                        </div>
                        <span className="text-xs text-slate-500">{w.canonical_url}</span>
                      </div>

                      <div className="flex items-center gap-3">
                        {score !== null && score !== undefined && (
                          <span className={`text-xs font-bold px-2.5 py-1 rounded-full ${
                            score >= 80 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' :
                            score >= 60 ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' :
                            'bg-rose-500/10 text-rose-400 border border-rose-500/20'
                          }`}>
                            Score: {score}/100
                          </span>
                        )}

                        {w.latest_scan?.public_id && (
                          <a
                            href={`/report/${w.domain}/${w.latest_scan.public_id}`}
                            className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition-colors"
                          >
                            View Report
                          </a>
                        )}

                        <button
                          onClick={() => onNavigate('monitoring', { url: w.canonical_url })}
                          className="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 transition-colors"
                        >
                          Monitor
                        </button>

                        <button
                          onClick={() => handleDeleteWebsite(w.id)}
                          className="p-1.5 text-slate-500 hover:text-rose-400 transition-colors"
                          title="Remove from workspace"
                        >
                          <Trash2 className="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  );
                })}
              </div>
            )}
          </div>
        </div>
      )}

      {/* Tab Content: Projects */}
      {activeTab === 'projects' && (
        <div className="space-y-6">
          <div className="p-6 rounded-2xl bg-slate-900/40 border border-slate-800">
            <h3 className="text-sm font-bold text-white mb-3">Create New Project</h3>
            <form onSubmit={handleCreateProject} className="grid grid-cols-1 md:grid-cols-12 gap-3">
              <input
                type="text"
                value={projectName}
                onChange={(e) => setProjectName(e.target.value)}
                placeholder="Project Name (e.g. Client Portfolios)"
                required
                className="md:col-span-5 px-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500"
              />
              <input
                type="text"
                value={projectDesc}
                onChange={(e) => setProjectDesc(e.target.value)}
                placeholder="Description (Optional)"
                className="md:col-span-5 px-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500"
              />
              <button
                type="submit"
                disabled={creatingProject}
                className="md:col-span-2 px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white transition-colors"
              >
                {creatingProject ? 'Creating...' : 'Create Project'}
              </button>
            </form>
          </div>

          <div className="p-6 rounded-3xl bg-slate-900/60 border border-slate-800">
            <h3 className="text-base font-bold text-white mb-4">Workspace Projects ({projects.length})</h3>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {projects.map((p) => (
                <div key={p.id} className="p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex flex-col justify-between">
                  <div>
                    <h4 className="font-bold text-white text-sm">{p.name}</h4>
                    <p className="text-xs text-slate-400 mt-1 line-clamp-2">{p.description || 'No description provided.'}</p>
                  </div>
                  <div className="flex items-center justify-between mt-4 pt-3 border-t border-slate-800/80 text-xs text-slate-500">
                    <span>{p.websites_count || 0} websites</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Tab Content: Scans */}
      {activeTab === 'scans' && (
        <div className="p-6 rounded-3xl bg-slate-900/60 border border-slate-800">
          <h3 className="text-base font-bold text-white mb-4">Recent Scans ({recentScans.length})</h3>
          {recentScans.length === 0 ? (
            <div className="p-8 text-center text-slate-500 text-sm">
              No scan history associated with this account yet.
            </div>
          ) : (
            <div className="space-y-3">
              {recentScans.map((s) => (
                <div key={s.id} className="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 flex items-center justify-between text-xs">
                  <div>
                    <span className="font-bold text-white block">{s.website?.domain || s.target_url}</span>
                    <span className="text-slate-500">{new Date(s.created_at).toLocaleString()}</span>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="font-bold text-slate-300">{s.overall_score}/100</span>
                    <a
                      href={`/report/${s.website?.domain || 'site'}/${s.public_id}`}
                      className="px-3 py-1 rounded bg-slate-900 hover:bg-slate-800 text-indigo-400 border border-slate-800"
                    >
                      Report &rarr;
                    </a>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Tab Content: Settings & Privacy */}
      {activeTab === 'settings' && (
        <div className="space-y-8 max-w-2xl">
          {/* Password Update */}
          <div className="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
            <h3 className="text-base font-bold text-white flex items-center gap-2">
              <Key className="w-4 h-4 text-indigo-400" />
              <span>Change Password</span>
            </h3>

            {passwordStatus && (
              <div className={`p-3 rounded-xl text-xs ${
                passwordStatus.success ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'
              }`}>
                {passwordStatus.message}
              </div>
            )}

            <form onSubmit={handleUpdatePassword} className="space-y-3">
              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Current Password</label>
                <input
                  type="password"
                  value={currentPassword}
                  onChange={(e) => setCurrentPassword(e.target.value)}
                  required
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">New Password (min 8 chars)</label>
                <input
                  type="password"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  required
                  minLength={8}
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white"
                />
              </div>

              <div>
                <label className="block text-xs font-medium text-slate-400 mb-1">Confirm New Password</label>
                <input
                  type="password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  required
                  minLength={8}
                  className="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white"
                />
              </div>

              <button
                type="submit"
                className="px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white"
              >
                Update Password
              </button>
            </form>
          </div>

          {/* Sync Local Scans */}
          <div className="p-6 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
            <h3 className="text-base font-bold text-white flex items-center gap-2">
              <Database className="w-4 h-4 text-indigo-400" />
              <span>Import Anonymous Local Scans</span>
            </h3>
            <p className="text-xs text-slate-400 leading-relaxed">
              If you conducted scans prior to signing in, you can associate those reports with your account workspace now.
            </p>
            {syncStatus && <p className="text-xs text-indigo-400">{syncStatus}</p>}
            <button
              onClick={handleSyncLocalHistory}
              className="px-4 py-2 rounded-xl text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-800"
            >
              Sync Local History
            </button>
          </div>

          {/* Privacy & Account Deletion */}
          <div className="p-6 rounded-2xl bg-rose-500/5 border border-rose-500/20 space-y-3">
            <h3 className="text-base font-bold text-rose-400 flex items-center gap-2">
              <AlertTriangle className="w-4 h-4" />
              <span>Delete Account & Privacy Clean-Up</span>
            </h3>
            <p className="text-xs text-slate-400 leading-relaxed">
              Permanently removes your account profile, projects, and disassociates all website records in compliance with our data retention policy.
            </p>
            <button
              onClick={handleDeleteAccount}
              className="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-500 text-white"
            >
              Delete My Account
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
