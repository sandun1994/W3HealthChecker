import React, { useState, useEffect } from 'react';
import { 
  Check, 
  Sparkles, 
  ShieldCheck, 
  Zap, 
  Building2, 
  AlertCircle, 
  CreditCard, 
  RefreshCw, 
  ArrowRight,
  HelpCircle
} from 'lucide-react';

export default function BillingView({ user, onNavigate }) {
  const [plans, setPlans] = useState([]);
  const [usage, setUsage] = useState(null);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [statusMsg, setStatusMsg] = useState(null);
  const [errorMsg, setErrorMsg] = useState(null);

  useEffect(() => {
    fetchBillingData();
  }, [user]);

  const fetchBillingData = async () => {
    setLoading(true);
    try {
      const plansRes = await fetch('/api/billing/plans');
      const plansData = await plansRes.json();
      if (plansData.success) {
        setPlans(plansData.plans);
      }

      if (user) {
        const usageRes = await fetch('/api/billing/usage');
        const usageData = await usageRes.json();
        if (usageData.success) {
          setUsage(usageData.usage);
        }
      }
    } catch (err) {
      console.error('Failed to load billing details', err);
    } finally {
      setLoading(false);
    }
  };

  const handleSubscribe = async (planId) => {
    if (!user) {
      onNavigate && onNavigate('register');
      return;
    }

    setSubmitting(true);
    setStatusMsg(null);
    setErrorMsg(null);

    try {
      const csrfToken = window.__W3_INITIAL_DATA__?.csrfToken || '';
      const res = await fetch('/api/billing/subscribe', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ plan: planId }),
      });

      const data = await res.json();
      if (res.ok && data.success) {
        setStatusMsg(data.message);
        setUsage(data.usage);
      } else {
        setErrorMsg(data.message || 'Failed to change plan.');
      }
    } catch (err) {
      setErrorMsg('Network error occurred.');
    } finally {
      setSubmitting(false);
    }
  };

  const handleCancel = async () => {
    if (!confirm('Are you sure you want to cancel your paid subscription? You will retain access until the end of the billing period.')) {
      return;
    }

    setSubmitting(true);
    setStatusMsg(null);
    setErrorMsg(null);

    try {
      const csrfToken = window.__W3_INITIAL_DATA__?.csrfToken || '';
      const res = await fetch('/api/billing/cancel', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
      });

      const data = await res.json();
      if (res.ok && data.success) {
        setStatusMsg(data.message);
        setUsage(data.usage);
      } else {
        setErrorMsg(data.message || 'Failed to cancel subscription.');
      }
    } catch (err) {
      setErrorMsg('Network error occurred.');
    } finally {
      setSubmitting(false);
    }
  };

  const currentPlanId = usage?.plan || 'free';

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      {/* Header */}
      <div className="text-center max-w-3xl mx-auto mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-semibold uppercase tracking-wider mb-4">
          <Sparkles className="w-3.5 h-3.5" />
          Transparent, Fair Plans
        </div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight mb-4">
          Scale Your Website Intelligence
        </h1>
        <p className="text-lg text-slate-600">
          Everything you need to audit, monitor, and defend websites over time. Upgrade or cancel anytime with zero lock-in.
        </p>
      </div>

      {statusMsg && (
        <div className="mb-8 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Check className="w-5 h-5 text-emerald-600 flex-shrink-0" />
            <p className="text-sm font-medium">{statusMsg}</p>
          </div>
          <button onClick={() => setStatusMsg(null)} className="text-emerald-600 hover:text-emerald-800 text-sm font-semibold">Dismiss</button>
        </div>
      )}

      {errorMsg && (
        <div className="mb-8 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <AlertCircle className="w-5 h-5 text-rose-600 flex-shrink-0" />
            <p className="text-sm font-medium">{errorMsg}</p>
          </div>
          <button onClick={() => setErrorMsg(null)} className="text-rose-600 hover:text-rose-800 text-sm font-semibold">Dismiss</button>
        </div>
      )}

      {/* Authenticated Usage Overview */}
      {user && usage && (
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8 mb-16">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-6 mb-6">
            <div>
              <div className="flex items-center gap-3">
                <h2 className="text-xl font-bold text-slate-900">Current Subscription</h2>
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wide ${
                  currentPlanId === 'agency' ? 'bg-purple-100 text-purple-700' :
                  currentPlanId === 'pro' ? 'bg-indigo-100 text-indigo-700' :
                  'bg-slate-100 text-slate-700'
                }`}>
                  {usage.plan_name}
                </span>
                {usage.is_cancelled && (
                  <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                    Cancelled (Active until renewal)
                  </span>
                )}
              </div>
              <p className="text-sm text-slate-500 mt-1">
                {currentPlanId === 'free' 
                  ? 'Free tier active. Upgrade to unlock automated daily alerts & higher capacity.' 
                  : `Monthly renewal: $${usage.price_monthly}/month.`}
              </p>
            </div>

            {currentPlanId !== 'free' && (
              <div>
                {!usage.is_cancelled ? (
                  <button
                    onClick={handleCancel}
                    disabled={submitting}
                    className="text-sm text-rose-600 hover:text-rose-700 font-semibold px-4 py-2 border border-rose-200 rounded-lg hover:bg-rose-50 transition-colors"
                  >
                    Cancel Subscription
                  </button>
                ) : (
                  <button
                    onClick={() => handleSubscribe(currentPlanId)}
                    disabled={submitting}
                    className="text-sm text-indigo-600 hover:text-indigo-700 font-semibold px-4 py-2 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors"
                  >
                    Resume Subscription
                  </button>
                )}
              </div>
            )}
          </div>

          {/* Quotas & Usage Progress */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <div className="flex items-center justify-between text-sm mb-2">
                <span className="text-slate-600 font-medium">Monitored Websites</span>
                <span className="font-bold text-slate-900">
                  {usage.quotas.monitored_websites.current} / {usage.quotas.monitored_websites.limit}
                </span>
              </div>
              <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div 
                  className="bg-indigo-600 h-2 rounded-full transition-all"
                  style={{ width: `${Math.min(100, (usage.quotas.monitored_websites.current / usage.quotas.monitored_websites.limit) * 100)}%` }}
                />
              </div>
              <p className="text-xs text-slate-500 mt-2">
                {usage.quotas.monitored_websites.remaining} slots remaining
              </p>
            </div>

            <div className="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <div className="flex items-center justify-between text-sm mb-2">
                <span className="text-slate-600 font-medium">Workspace Projects</span>
                <span className="font-bold text-slate-900">
                  {usage.quotas.projects.current} / {usage.quotas.projects.limit}
                </span>
              </div>
              <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div 
                  className="bg-indigo-600 h-2 rounded-full transition-all"
                  style={{ width: `${Math.min(100, (usage.quotas.projects.current / usage.quotas.projects.limit) * 100)}%` }}
                />
              </div>
              <p className="text-xs text-slate-500 mt-2">
                {usage.quotas.projects.remaining} project slots remaining
              </p>
            </div>

            <div className="p-4 rounded-xl bg-slate-50 border border-slate-100">
              <div className="flex items-center justify-between text-sm mb-2">
                <span className="text-slate-600 font-medium">Monthly Audits</span>
                <span className="font-bold text-slate-900">
                  {usage.quotas.monthly_scans.current} / {usage.quotas.monthly_scans.limit}
                </span>
              </div>
              <div className="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
                <div 
                  className="bg-indigo-600 h-2 rounded-full transition-all"
                  style={{ width: `${Math.min(100, (usage.quotas.monthly_scans.current / usage.quotas.monthly_scans.limit) * 100)}%` }}
                />
              </div>
              <p className="text-xs text-slate-500 mt-2">
                Resets on the 1st of every month
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Plan Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        {plans.map((p) => {
          const isCurrent = currentPlanId === p.id;
          const isPro = p.id === 'pro';
          const isAgency = p.id === 'agency';

          return (
            <div 
              key={p.id}
              className={`relative rounded-3xl p-8 flex flex-col justify-between transition-all ${
                isPro 
                  ? 'bg-gradient-to-b from-indigo-900/90 to-slate-900 text-white shadow-xl shadow-indigo-500/10 border-2 border-indigo-500' 
                  : 'bg-white text-slate-900 border border-slate-200 shadow-sm'
              }`}
            >
              {isPro && (
                <div className="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-indigo-500 to-cyan-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                  Most Popular
                </div>
              )}

              <div>
                <div className="flex items-center justify-between mb-4">
                  <h3 className={`text-xl font-bold ${isPro ? 'text-white' : 'text-slate-900'}`}>
                    {p.name}
                  </h3>
                  {isAgency && (
                    <Building2 className="w-6 h-6 text-purple-500" />
                  )}
                  {isPro && (
                    <Zap className="w-6 h-6 text-cyan-400" />
                  )}
                  {p.id === 'free' && (
                    <ShieldCheck className="w-6 h-6 text-emerald-500" />
                  )}
                </div>

                <p className={`text-sm mb-6 ${isPro ? 'text-slate-300' : 'text-slate-600'}`}>
                  {p.tagline}
                </p>

                <div className="flex items-baseline gap-1 mb-8">
                  <span className="text-4xl font-extrabold tracking-tight">
                    ${p.price_monthly}
                  </span>
                  <span className={`text-sm ${isPro ? 'text-slate-400' : 'text-slate-500'}`}>
                    /month
                  </span>
                </div>

                <div className="space-y-3 mb-8">
                  <p className={`text-xs font-bold uppercase tracking-wider ${isPro ? 'text-slate-400' : 'text-slate-500'}`}>
                    Included Privileges:
                  </p>
                  {p.highlighted_features?.map((feat, idx) => (
                    <div key={idx} className="flex items-start gap-3">
                      <div className={`mt-0.5 rounded-full p-0.5 flex-shrink-0 ${
                        isPro ? 'bg-indigo-500/20 text-cyan-400' : 'bg-emerald-100 text-emerald-700'
                      }`}>
                        <Check className="w-3.5 h-3.5" />
                      </div>
                      <span className={`text-sm ${isPro ? 'text-slate-200' : 'text-slate-700'}`}>
                        {feat}
                      </span>
                    </div>
                  ))}
                </div>
              </div>

              <div>
                {isCurrent ? (
                  <div className={`w-full py-3 px-4 rounded-xl text-center font-bold text-sm ${
                    isPro ? 'bg-white/10 text-slate-300' : 'bg-slate-100 text-slate-500'
                  }`}>
                    Current Plan
                  </div>
                ) : (
                  <button
                    onClick={() => handleSubscribe(p.id)}
                    disabled={submitting}
                    className={`w-full py-3 px-4 rounded-xl font-bold text-sm transition-all shadow-sm flex items-center justify-center gap-2 ${
                      isPro
                        ? 'bg-gradient-to-r from-indigo-500 to-cyan-500 hover:from-indigo-400 hover:to-cyan-400 text-white'
                        : isAgency
                        ? 'bg-purple-600 hover:bg-purple-700 text-white'
                        : 'bg-slate-900 hover:bg-slate-800 text-white'
                    }`}
                  >
                    {p.id === 'free' ? 'Switch to Free' : `Upgrade to ${p.name}`}
                    <ArrowRight className="w-4 h-4" />
                  </button>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Feature Comparison Guarantee */}
      <div className="bg-slate-50 rounded-2xl border border-slate-200 p-8 text-center max-w-4xl mx-auto">
        <h3 className="text-lg font-bold text-slate-900 mb-2">Our Billing Commitments</h3>
        <p className="text-sm text-slate-600 max-w-2xl mx-auto mb-6">
          No fake reviews, no artificial score gating, and no dark cancellation patterns. You can downgrade to the Free Community plan anytime with zero friction.
        </p>
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-left">
          <div className="bg-white p-4 rounded-xl border border-slate-200/60 shadow-xs">
            <h4 className="font-semibold text-xs text-slate-900 mb-1">Cancel Anytime</h4>
            <p className="text-xs text-slate-500">One-click cancellation directly from your dashboard whenever you want.</p>
          </div>
          <div className="bg-white p-4 rounded-xl border border-slate-200/60 shadow-xs">
            <h4 className="font-semibold text-xs text-slate-900 mb-1">Defense-First Audits</h4>
            <p className="text-xs text-slate-500">Every scan is 100% passive, ethical, safe, and privacy-preserving.</p>
          </div>
          <div className="bg-white p-4 rounded-xl border border-slate-200/60 shadow-xs">
            <h4 className="font-semibold text-xs text-slate-900 mb-1">Transparent Data</h4>
            <p className="text-xs text-slate-500">Real metrics and standards compliance—never arbitrary or inflated scores.</p>
          </div>
        </div>
      </div>
    </div>
  );
}
