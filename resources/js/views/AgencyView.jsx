import React, { useState, useEffect } from 'react';
import { 
  Building2, 
  Key, 
  Webhook, 
  Users, 
  Plus, 
  Trash2, 
  Copy, 
  Check, 
  ExternalLink, 
  ShieldAlert, 
  Sparkles,
  Palette,
  Globe
} from 'lucide-react';

export default function AgencyView({ user, onNavigate }) {
  const [activeTab, setActiveTab] = useState('clients');
  const [clients, setClients] = useState([]);
  const [apiKeys, setApiKeys] = useState([]);
  const [webhooks, setWebhooks] = useState([]);
  const [overview, setOverview] = useState(null);
  const [loading, setLoading] = useState(true);

  // Form states
  const [newClientName, setNewClientName] = useState('');
  const [newClientCompany, setNewClientCompany] = useState('');
  const [newClientEmail, setNewClientEmail] = useState('');
  const [newKeyName, setNewKeyName] = useState('');
  const [generatedToken, setGeneratedToken] = useState(null);
  const [copiedToken, setCopiedToken] = useState(false);
  const [newWebhookUrl, setNewWebhookUrl] = useState('');
  const [webhookEvents, setWebhookEvents] = useState(['scan.completed']);

  const [statusMsg, setStatusMsg] = useState(null);
  const [errorMsg, setErrorMsg] = useState(null);

  useEffect(() => {
    if (user) {
      fetchAgencyData();
    }
  }, [user]);

  const fetchAgencyData = async () => {
    setLoading(true);
    try {
      const [ovRes, clRes, keyRes, whRes] = await Promise.all([
        fetch('/api/agency/overview'),
        fetch('/api/agency/clients'),
        fetch('/api/agency/api-keys'),
        fetch('/api/agency/webhooks'),
      ]);

      const [ovData, clData, keyData, whData] = await Promise.all([
        ovRes.json(),
        clRes.json(),
        keyRes.json(),
        whRes.json(),
      ]);

      if (ovData.success) setOverview(ovData);
      if (clData.success) setClients(clData.clients);
      if (keyData.success) setApiKeys(keyData.api_keys);
      if (whData.success) setWebhooks(whData.webhooks);
    } catch (err) {
      console.error('Failed to load agency data', err);
    } finally {
      setLoading(false);
    }
  };

  const getCsrfToken = () => window.__W3_INITIAL_DATA__?.csrfToken || '';

  const handleCreateClient = async (e) => {
    e.preventDefault();
    setErrorMsg(null);
    setStatusMsg(null);

    try {
      const res = await fetch('/api/agency/clients', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          name: newClientName,
          company: newClientCompany,
          email: newClientEmail,
        }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        setStatusMsg(data.message);
        setNewClientName('');
        setNewClientCompany('');
        setNewClientEmail('');
        fetchAgencyData();
      } else {
        setErrorMsg(data.message || 'Failed to create client.');
      }
    } catch {
      setErrorMsg('Network error.');
    }
  };

  const handleDeleteClient = async (id) => {
    if (!confirm('Are you sure you want to delete this client? Attached websites will remain in your workspace.')) return;
    try {
      const res = await fetch(`/api/agency/clients/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
      });
      const data = await res.json();
      if (data.success) {
        setStatusMsg(data.message);
        fetchAgencyData();
      }
    } catch {
      setErrorMsg('Failed to delete client.');
    }
  };

  const handleCreateApiKey = async (e) => {
    e.preventDefault();
    setErrorMsg(null);
    setStatusMsg(null);
    setGeneratedToken(null);

    try {
      const res = await fetch('/api/agency/api-keys', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ name: newKeyName }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        setGeneratedToken(data.api_key.token);
        setNewKeyName('');
        fetchAgencyData();
      } else {
        setErrorMsg(data.message || 'Failed to create API key.');
      }
    } catch {
      setErrorMsg('Network error.');
    }
  };

  const handleRevokeApiKey = async (id) => {
    if (!confirm('Are you sure you want to revoke this API key? Applications using it will immediately lose access.')) return;
    try {
      const res = await fetch(`/api/agency/api-keys/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
      });
      const data = await res.json();
      if (data.success) {
        setStatusMsg(data.message);
        fetchAgencyData();
      }
    } catch {
      setErrorMsg('Failed to revoke API key.');
    }
  };

  const handleCreateWebhook = async (e) => {
    e.preventDefault();
    setErrorMsg(null);
    setStatusMsg(null);

    try {
      const res = await fetch('/api/agency/webhooks', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({
          url: newWebhookUrl,
          events: webhookEvents,
        }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        setStatusMsg(data.message);
        setNewWebhookUrl('');
        fetchAgencyData();
      } else {
        setErrorMsg(data.message || 'Failed to create webhook.');
      }
    } catch {
      setErrorMsg('Network error.');
    }
  };

  const handleDeleteWebhook = async (id) => {
    if (!confirm('Remove this webhook endpoint?')) return;
    try {
      const res = await fetch(`/api/agency/webhooks/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': getCsrfToken() },
      });
      const data = await res.json();
      if (data.success) {
        setStatusMsg(data.message);
        fetchAgencyData();
      }
    } catch {
      setErrorMsg('Failed to delete webhook.');
    }
  };

  const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    setCopiedToken(true);
    setTimeout(() => setCopiedToken(false), 2000);
  };

  if (!user) {
    return (
      <div className="max-w-4xl mx-auto px-4 py-20 text-center">
        <Building2 className="w-16 h-16 text-indigo-400 mx-auto mb-4" />
        <h2 className="text-2xl font-bold text-white mb-2">Agency Workspace</h2>
        <p className="text-slate-400 mb-6">Sign in or register to manage client portfolios, generate API keys, and configure webhooks.</p>
        <button
          onClick={() => onNavigate('login')}
          className="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-white shadow-lg shadow-indigo-600/30"
        >
          Sign In to Your Workspace
        </button>
      </div>
    );
  }

  const isAgencyPlan = overview?.agency?.can_use_agency_features;

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      {/* Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-8 mb-8">
        <div>
          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-950/80 border border-purple-800/60 text-purple-300 text-xs font-semibold uppercase tracking-wider mb-2">
            <Building2 className="w-3.5 h-3.5" />
            Agency & Developer Suite
          </div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
            Agency Workspace
          </h1>
          <p className="text-sm text-slate-400 mt-1">
            Manage multi-client audits, developer API tokens, signed webhooks, and white-label reporting.
          </p>
        </div>

        <div className="flex items-center gap-3">
          <button
            onClick={() => onNavigate('developers')}
            className="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-800 text-xs font-semibold text-slate-300 transition-colors"
          >
            <ExternalLink className="w-4 h-4 text-indigo-400" />
            API v1 Docs
          </button>
          {!isAgencyPlan && (
            <button
              onClick={() => onNavigate('billing')}
              className="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 font-semibold text-xs text-white shadow-md shadow-purple-600/20 transition-all"
            >
              <Sparkles className="w-4 h-4" />
              Upgrade to Agency
            </button>
          )}
        </div>
      </div>

      {statusMsg && (
        <div className="mb-6 p-4 rounded-xl bg-emerald-950/60 border border-emerald-800/80 text-emerald-300 text-sm flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Check className="w-4 h-4" />
            {statusMsg}
          </div>
          <button onClick={() => setStatusMsg(null)} className="text-emerald-400 font-semibold hover:text-emerald-200">Dismiss</button>
        </div>
      )}

      {errorMsg && (
        <div className="mb-6 p-4 rounded-xl bg-rose-950/60 border border-rose-800/80 text-rose-300 text-sm flex items-center justify-between">
          <div className="flex items-center gap-2">
            <ShieldAlert className="w-4 h-4" />
            {errorMsg}
          </div>
          <button onClick={() => setErrorMsg(null)} className="text-rose-400 font-semibold hover:text-rose-200">Dismiss</button>
        </div>
      )}

      {!isAgencyPlan && (
        <div className="mb-8 p-6 rounded-2xl bg-gradient-to-r from-purple-950/40 via-indigo-950/40 to-slate-900 border border-purple-800/50 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div>
            <h3 className="text-base font-bold text-white mb-1">Agency Privileges Locked</h3>
            <p className="text-xs text-slate-400 max-w-2xl">
              API keys, bulk audit queues, client white-labeling, and signed webhooks require an Agency plan subscription.
            </p>
          </div>
          <button
            onClick={() => onNavigate('billing')}
            className="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 font-bold text-xs text-white flex-shrink-0"
          >
            View Agency Plan
          </button>
        </div>
      )}

      {/* Tabs */}
      <div className="flex border-b border-slate-800 mb-8 gap-8 text-sm font-semibold">
        <button
          onClick={() => setActiveTab('clients')}
          className={`pb-4 flex items-center gap-2 transition-colors ${
            activeTab === 'clients' ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-slate-400 hover:text-white'
          }`}
        >
          <Users className="w-4 h-4" />
          Clients ({clients.length})
        </button>
        <button
          onClick={() => setActiveTab('api_keys')}
          className={`pb-4 flex items-center gap-2 transition-colors ${
            activeTab === 'api_keys' ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-slate-400 hover:text-white'
          }`}
        >
          <Key className="w-4 h-4" />
          API Keys ({apiKeys.length})
        </button>
        <button
          onClick={() => setActiveTab('webhooks')}
          className={`pb-4 flex items-center gap-2 transition-colors ${
            activeTab === 'webhooks' ? 'text-indigo-400 border-b-2 border-indigo-400' : 'text-slate-400 hover:text-white'
          }`}
        >
          <Webhook className="w-4 h-4" />
          Webhooks ({webhooks.length})
        </button>
      </div>

      {/* TAB 1: CLIENTS */}
      {activeTab === 'clients' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-4">
            <h2 className="text-base font-bold text-white mb-4">Your Agency Clients</h2>
            {clients.length === 0 ? (
              <div className="p-8 rounded-2xl bg-slate-900 border border-slate-800 text-center">
                <Users className="w-10 h-10 text-slate-600 mx-auto mb-3" />
                <p className="text-sm font-medium text-slate-300">No clients created yet.</p>
                <p className="text-xs text-slate-500 mt-1">Create your first client to group websites and customize reports.</p>
              </div>
            ) : (
              clients.map((client) => (
                <div key={client.id} className="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 flex items-start justify-between gap-4">
                  <div>
                    <div className="flex items-center gap-2">
                      <h3 className="font-bold text-white text-base">{client.name}</h3>
                      {client.company && (
                        <span className="text-xs px-2 py-0.5 rounded bg-slate-800 text-slate-400">
                          {client.company}
                        </span>
                      )}
                    </div>
                    {client.email && (
                      <p className="text-xs text-slate-400 mt-1">{client.email}</p>
                    )}
                    <div className="flex items-center gap-4 mt-4 text-xs text-slate-500">
                      <span>{client.websites_count} websites managed</span>
                      <span>•</span>
                      <span>Created {new Date(client.created_at).toLocaleDateString()}</span>
                    </div>
                  </div>
                  <button
                    onClick={() => handleDeleteClient(client.id)}
                    className="p-2 text-slate-500 hover:text-rose-400 transition-colors"
                    title="Delete Client"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              ))
            )}
          </div>

          <div>
            <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 sticky top-24">
              <h3 className="text-sm font-bold text-white mb-4 flex items-center gap-2">
                <Plus className="w-4 h-4 text-indigo-400" />
                Add New Client
              </h3>
              <form onSubmit={handleCreateClient} className="space-y-4">
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Client Name</label>
                  <input
                    type="text"
                    required
                    value={newClientName}
                    onChange={(e) => setNewClientName(e.target.value)}
                    placeholder="e.g. Acme Web Solutions"
                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Company / Brand (Optional)</label>
                  <input
                    type="text"
                    value={newClientCompany}
                    onChange={(e) => setNewClientCompany(e.target.value)}
                    placeholder="e.g. Acme Corp"
                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Contact Email (Optional)</label>
                  <input
                    type="email"
                    value={newClientEmail}
                    onChange={(e) => setNewClientEmail(e.target.value)}
                    placeholder="client@example.com"
                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500"
                  />
                </div>
                <button
                  type="submit"
                  className="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white transition-colors"
                >
                  Create Client
                </button>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* TAB 2: API KEYS */}
      {activeTab === 'api_keys' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-4">
            <h2 className="text-base font-bold text-white mb-4">Active API Keys</h2>

            {/* Plain Token Display Modal / Alert */}
            {generatedToken && (
              <div className="p-6 rounded-2xl bg-indigo-950/80 border-2 border-indigo-500 mb-6">
                <div className="flex items-center gap-2 text-indigo-300 text-xs font-bold uppercase mb-2">
                  <Check className="w-4 h-4 text-emerald-400" />
                  Key Generated Successfully
                </div>
                <p className="text-xs text-slate-300 mb-3">
                  Please copy this key immediately. For security, we never store or display the secret token again.
                </p>
                <div className="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-emerald-400 break-all select-all">
                  <span className="flex-1">{generatedToken}</span>
                  <button
                    onClick={() => copyToClipboard(generatedToken)}
                    className="p-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-300 flex-shrink-0"
                    title="Copy token"
                  >
                    {copiedToken ? <Check className="w-4 h-4 text-emerald-400" /> : <Copy className="w-4 h-4" />}
                  </button>
                </div>
              </div>
            )}

            {apiKeys.length === 0 ? (
              <div className="p-8 rounded-2xl bg-slate-900 border border-slate-800 text-center">
                <Key className="w-10 h-10 text-slate-600 mx-auto mb-3" />
                <p className="text-sm font-medium text-slate-300">No API keys created yet.</p>
                <p className="text-xs text-slate-500 mt-1">Generate a key to programmatically trigger audits and fetch metrics via REST API v1.</p>
              </div>
            ) : (
              apiKeys.map((k) => (
                <div key={k.id} className="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between gap-4">
                  <div>
                    <div className="flex items-center gap-3">
                      <h3 className="font-bold text-white text-sm">{k.name}</h3>
                      <span className="font-mono text-xs px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-slate-400">
                        {k.key_prefix}...
                      </span>
                      <span className={`text-[10px] px-2 py-0.5 rounded-full font-semibold ${
                        k.is_active ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/60' : 'bg-slate-800 text-slate-500'
                      }`}>
                        {k.is_active ? 'Active' : 'Revoked'}
                      </span>
                    </div>
                    <div className="flex items-center gap-4 mt-2 text-xs text-slate-500">
                      <span>Requests: {k.requests_count}</span>
                      <span>•</span>
                      <span>Last used: {k.last_used_at ? new Date(k.last_used_at).toLocaleDateString() : 'Never'}</span>
                    </div>
                  </div>
                  {k.is_active && (
                    <button
                      onClick={() => handleRevokeApiKey(k.id)}
                      className="text-xs text-rose-400 hover:text-rose-300 font-semibold px-3 py-1.5 rounded-lg border border-rose-900/50 hover:bg-rose-950/40 transition-colors"
                    >
                      Revoke
                    </button>
                  )}
                </div>
              ))
            )}
          </div>

          <div>
            <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 sticky top-24">
              <h3 className="text-sm font-bold text-white mb-4 flex items-center gap-2">
                <Plus className="w-4 h-4 text-indigo-400" />
                Generate API Key
              </h3>
              <form onSubmit={handleCreateApiKey} className="space-y-4">
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Key Description / Name</label>
                  <input
                    type="text"
                    required
                    value={newKeyName}
                    onChange={(e) => setNewKeyName(e.target.value)}
                    placeholder="e.g. GitHub Actions CI"
                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500"
                  />
                </div>
                <button
                  type="submit"
                  disabled={!isAgencyPlan}
                  className="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white transition-colors disabled:opacity-50"
                >
                  Generate Key
                </button>
              </form>
            </div>
          </div>
        </div>
      )}

      {/* TAB 3: WEBHOOKS */}
      {activeTab === 'webhooks' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-4">
            <h2 className="text-base font-bold text-white mb-4">Configured Webhook Endpoints</h2>
            {webhooks.length === 0 ? (
              <div className="p-8 rounded-2xl bg-slate-900 border border-slate-800 text-center">
                <Webhook className="w-10 h-10 text-slate-600 mx-auto mb-3" />
                <p className="text-sm font-medium text-slate-300">No webhooks registered.</p>
                <p className="text-xs text-slate-500 mt-1">Receive cryptographically signed HTTP POST notifications on completed audits or score regressions.</p>
              </div>
            ) : (
              webhooks.map((wh) => (
                <div key={wh.id} className="p-5 rounded-2xl bg-slate-900 border border-slate-800 flex items-start justify-between gap-4">
                  <div>
                    <h4 className="font-mono text-xs text-indigo-400 break-all">{wh.url}</h4>
                    <div className="flex flex-wrap gap-1.5 mt-2">
                      {wh.events?.map((ev, i) => (
                        <span key={i} className="text-[10px] px-2 py-0.5 rounded bg-slate-950 border border-slate-800 text-slate-400">
                          {ev}
                        </span>
                      ))}
                    </div>
                    <p className="text-xs text-slate-500 mt-3 font-mono">
                      Secret: {wh.secret.substring(0, 10)}...
                    </p>
                  </div>
                  <button
                    onClick={() => handleDeleteWebhook(wh.id)}
                    className="p-2 text-slate-500 hover:text-rose-400 transition-colors"
                  >
                    <Trash2 className="w-4 h-4" />
                  </button>
                </div>
              ))
            )}
          </div>

          <div>
            <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800 sticky top-24">
              <h3 className="text-sm font-bold text-white mb-4 flex items-center gap-2">
                <Plus className="w-4 h-4 text-indigo-400" />
                Add Webhook Endpoint
              </h3>
              <form onSubmit={handleCreateWebhook} className="space-y-4">
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-1">Target Endpoint URL</label>
                  <input
                    type="url"
                    required
                    value={newWebhookUrl}
                    onChange={(e) => setNewWebhookUrl(e.target.value)}
                    placeholder="https://api.example.com/webhooks"
                    className="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500"
                  />
                </div>
                <div>
                  <label className="block text-xs font-medium text-slate-400 mb-2">Subscribed Events</label>
                  <div className="space-y-2 text-xs text-slate-300">
                    {['scan.completed', 'scan.failed', 'score.changed', 'critical_issue.detected'].map((ev) => (
                      <label key={ev} className="flex items-center gap-2 cursor-pointer">
                        <input
                          type="checkbox"
                          checked={webhookEvents.includes(ev)}
                          onChange={(e) => {
                            if (e.target.checked) {
                              setWebhookEvents([...webhookEvents, ev]);
                            } else {
                              setWebhookEvents(webhookEvents.filter(x => x !== ev));
                            }
                          }}
                          className="rounded border-slate-800 text-indigo-600 focus:ring-0"
                        />
                        <span>{ev}</span>
                      </label>
                    ))}
                  </div>
                </div>
                <button
                  type="submit"
                  disabled={!isAgencyPlan}
                  className="w-full py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold text-xs text-white transition-colors disabled:opacity-50"
                >
                  Register Webhook
                </button>
              </form>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
