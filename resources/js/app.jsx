import React, { useState, useEffect } from 'react';
import { createRoot } from 'react-dom/client';
import Navbar from './components/Navbar';
import Footer from './components/Footer';
import HomeView from './views/HomeView';
import ReportView from './views/ReportView';
import CompareView from './views/CompareView';
import ToolsIndexView from './views/ToolsIndexView';
import ToolDetailView from './views/ToolDetailView';
import GuidesIndexView from './views/GuidesIndexView';
import GuideDetailView from './views/GuideDetailView';
import AboutView from './views/AboutView';
import MonitoringView from './views/MonitoringView';
import LoginView from './views/LoginView';
import RegisterView from './views/RegisterView';
import DashboardView from './views/DashboardView';
import BillingView from './views/BillingView';
import AgencyView from './views/AgencyView';
import DevelopersView from './views/DevelopersView';
import { analytics } from './services/analytics';

export default function App() {
  const initial = window.__W3_INITIAL_DATA__ || {};

  const [currentView, setCurrentView] = useState(initial.initialPage || 'home');
  const [currentUser, setCurrentUser] = useState(null);
  const [reportDomain, setReportDomain] = useState(initial.reportDomain || null);
  const [reportPublicId, setReportPublicId] = useState(initial.reportPublicId || null);
  const [activeToolSlug, setActiveToolSlug] = useState(initial.toolSlug || null);
  const [toolsData, setToolsData] = useState(initial.toolsData || {});
  const [toolData, setToolData] = useState(initial.toolData || null);

  const [activeGuideSlug, setActiveGuideSlug] = useState(initial.guideSlug || null);
  const [guidesData, setGuidesData] = useState(initial.guidesData || {});
  const [guideData, setGuideData] = useState(initial.guideData || null);
  const [monitoringPrefillUrl, setMonitoringPrefillUrl] = useState('');

  // Scan state
  const [scanning, setScanning] = useState(false);
  const [scanProgress, setScanProgress] = useState(0);
  const [currentStage, setCurrentStage] = useState('Website reachable');
  const [scanTargetUrl, setScanTargetUrl] = useState('');
  const [scanError, setScanError] = useState(null);

  // Check authenticated user on mount
  useEffect(() => {
    fetchCurrentUser();
  }, []);

  const fetchCurrentUser = async () => {
    try {
      const res = await fetch('/api/auth/user');
      const data = await res.json();
      if (data.authenticated && data.user) {
        setCurrentUser(data.user);
      }
    } catch {
      // Guest session
    }
  };

  const handleLogout = async () => {
    try {
      await fetch('/api/auth/logout', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': initial.csrfToken || '',
        },
      });
    } finally {
      setCurrentUser(null);
      handleNavigate('home');
    }
  };

  const handleLoginSuccess = (user) => {
    setCurrentUser(user);
  };

  // Track page views on state change
  useEffect(() => {
    analytics.trackPageView(currentView);
  }, [currentView]);

  // Handle browser back/forward buttons
  useEffect(() => {
    const handlePopState = () => {
      const path = window.location.pathname;
      if (path === '/') {
        setCurrentView('home');
      } else if (path === '/compare') {
        setCurrentView('compare');
      } else if (path === '/monitoring') {
        setCurrentView('monitoring');
      } else if (path === '/dashboard') {
        setCurrentView('dashboard');
      } else if (path === '/billing' || path === '/pricing') {
        setCurrentView('billing');
      } else if (path === '/agency') {
        setCurrentView('agency');
      } else if (path === '/developers') {
        setCurrentView('developers');
      } else if (path === '/login') {
        setCurrentView('login');
      } else if (path === '/register') {
        setCurrentView('register');
      } else if (path === '/tools') {
        setCurrentView('tools');
      } else if (path.startsWith('/tools/')) {
        const slug = path.split('/')[2];
        setActiveToolSlug(slug);
        setCurrentView('tool');
      } else if (path === '/guides') {
        setCurrentView('guides_index');
      } else if (path.startsWith('/guides/')) {
        const slug = path.split('/')[2];
        setActiveGuideSlug(slug);
        setCurrentView('guide_detail');
      } else if (path === '/about') {
        setCurrentView('about');
      } else if (path.startsWith('/report/')) {
        const parts = path.split('/');
        setReportDomain(parts[2]);
        setReportPublicId(parts[3]);
        setCurrentView('report');
      }
    };

    window.addEventListener('popstate', handlePopState);
    return () => window.removeEventListener('popstate', handlePopState);
  }, []);

  // Navigation handler
  const handleNavigate = (view, payload = null) => {
    setScanError(null);
    if (view === 'home') {
      window.history.pushState({}, '', '/');
      setCurrentView('home');
    } else if (view === 'compare') {
      window.history.pushState({}, '', '/compare');
      setCurrentView('compare');
    } else if (view === 'monitoring') {
      const url = typeof payload === 'string' ? payload : payload?.url;
      setMonitoringPrefillUrl(url || '');
      window.history.pushState({}, '', '/monitoring');
      setCurrentView('monitoring');
    } else if (view === 'dashboard') {
      window.history.pushState({}, '', '/dashboard');
      setCurrentView('dashboard');
    } else if (view === 'billing' || view === 'pricing') {
      window.history.pushState({}, '', '/billing');
      setCurrentView('billing');
    } else if (view === 'agency') {
      window.history.pushState({}, '', '/agency');
      setCurrentView('agency');
    } else if (view === 'developers') {
      window.history.pushState({}, '', '/developers');
      setCurrentView('developers');
    } else if (view === 'login') {
      window.history.pushState({}, '', '/login');
      setCurrentView('login');
    } else if (view === 'register') {
      window.history.pushState({}, '', '/register');
      setCurrentView('register');
    } else if (view === 'tools' || view === 'tools_index') {
      window.history.pushState({}, '', '/tools');
      setCurrentView('tools');
    } else if (view === 'tool' || view === 'tool_detail') {
      const slug = typeof payload === 'string' ? payload : payload?.slug;
      setActiveToolSlug(slug);
      window.history.pushState({}, '', `/tools/${slug}`);
      setCurrentView('tool');
    } else if (view === 'guides_index') {
      window.history.pushState({}, '', '/guides');
      setCurrentView('guides_index');
    } else if (view === 'guide_detail') {
      const slug = typeof payload === 'string' ? payload : payload?.slug;
      setActiveGuideSlug(slug);
      window.history.pushState({}, '', `/guides/${slug}`);
      setCurrentView('guide_detail');
    } else if (view === 'about') {
      window.history.pushState({}, '', '/about');
      setCurrentView('about');
    } else if (view === 'report') {
      const { domain, publicId } = payload;
      setReportDomain(domain);
      setReportPublicId(publicId);
      window.history.pushState({}, '', `/report/${domain}/${publicId}`);
      setCurrentView('report');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Launch a new website health scan
  const startScan = async (url) => {
    setScanning(true);
    setScanProgress(10);
    setCurrentStage('Website reachable');
    setScanTargetUrl(url);
    setScanError(null);
    analytics.trackScanInitiated(url, currentView);

    try {
      const res = await fetch('/api/scan', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': initial.csrfToken || '',
        },
        body: JSON.stringify({ url, sync: true }),
      });

      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.message || 'Failed to initialize scan.');
      }

      const publicId = data.public_id;
      const domain = data.domain;

      // If already completed in synchronous mode
      if (data.status === 'completed') {
        setScanProgress(100);
        setCurrentStage('Generating recommendations');
        analytics.trackScanCompleted(domain, data.scan?.overall_score || 85, data.scan?.issues?.length || 0);
        setTimeout(() => {
          setScanning(false);
          handleNavigate('report', { domain, publicId });
        }, 500);
        return;
      }

      // Otherwise, poll for asynchronous progress
      pollScanProgress(publicId, domain);
    } catch (err) {
      setScanning(false);
      setScanError(err.message || 'A network error occurred while initiating the scan.');
    }
  };

  const pollScanProgress = (publicId, domain) => {
    const interval = setInterval(async () => {
      try {
        const res = await fetch(`/api/scan/status/${publicId}`);
        const data = await res.json();

        if (data.success) {
          setScanProgress(data.progress_percentage || 50);
          setCurrentStage(data.status_stage || 'Processing');

          if (data.status === 'completed') {
            clearInterval(interval);
            setScanProgress(100);
            analytics.trackScanCompleted(domain, data.scan?.overall_score || 85, data.scan?.issues?.length || 0);
            setTimeout(() => {
              setScanning(false);
              handleNavigate('report', { domain, publicId });
            }, 500);
          } else if (data.status === 'failed') {
            clearInterval(interval);
            setScanning(false);
            setScanError(data.error_message || 'Scan failed.');
          }
        }
      } catch (e) {
        clearInterval(interval);
        setScanning(false);
        setScanError('Connection lost while polling scan progress.');
      }
    }, 1500);
  };

  return (
    <div className="min-h-screen flex flex-col bg-slate-950 text-slate-100 font-sans selection:bg-indigo-500 selection:text-white">
      <Navbar onNavigate={handleNavigate} currentUser={currentUser} onLogout={handleLogout} />

      <main className="flex-1 flex flex-col">
        {currentView === 'home' && (
          <HomeView
            onStartScan={startScan}
            scanning={scanning}
            scanProgress={scanProgress}
            currentStage={currentStage}
            scanTargetUrl={scanTargetUrl}
            scanError={scanError}
            onNavigate={handleNavigate}
          />
        )}

        {currentView === 'report' && (
          <ReportView
            domain={reportDomain}
            publicId={reportPublicId}
            onNavigate={handleNavigate}
            onReScan={startScan}
          />
        )}

        {currentView === 'compare' && (
          <CompareView onNavigate={handleNavigate} />
        )}

        {(currentView === 'tools' || currentView === 'tools_index') && (
          <ToolsIndexView
            onNavigate={handleNavigate}
            toolsData={toolsData}
          />
        )}

        {(currentView === 'tool' || currentView === 'tool_detail') && (
          <ToolDetailView
            toolSlug={activeToolSlug}
            toolData={toolData || toolsData[activeToolSlug]}
            toolsData={toolsData}
            onStartScan={startScan}
            scanning={scanning}
            scanProgress={scanProgress}
            currentStage={currentStage}
            scanTargetUrl={scanTargetUrl}
            scanError={scanError}
            onNavigate={handleNavigate}
          />
        )}

        {currentView === 'guides_index' && (
          <GuidesIndexView
            guidesData={guidesData}
          />
        )}

        {currentView === 'guide_detail' && (
          <GuideDetailView
            guide={guideData || guidesData[activeGuideSlug]}
            toolsData={toolsData}
          />
        )}

        {currentView === 'about' && (
          <AboutView />
        )}

        {currentView === 'monitoring' && (
          <MonitoringView
            onNavigate={handleNavigate}
            prefillUrl={monitoringPrefillUrl}
          />
        )}

        {currentView === 'login' && (
          <LoginView
            onNavigate={handleNavigate}
            onLoginSuccess={handleLoginSuccess}
          />
        )}

        {currentView === 'register' && (
          <RegisterView
            onNavigate={handleNavigate}
            onLoginSuccess={handleLoginSuccess}
          />
        )}

        {currentView === 'dashboard' && (
          <DashboardView
            user={currentUser}
            onNavigate={handleNavigate}
            onLogout={handleLogout}
          />
        )}

        {currentView === 'billing' && (
          <BillingView
            user={currentUser}
            onNavigate={handleNavigate}
          />
        )}

        {currentView === 'agency' && (
          <AgencyView
            user={currentUser}
            onNavigate={handleNavigate}
          />
        )}

        {currentView === 'developers' && (
          <DevelopersView
            onNavigate={handleNavigate}
          />
        )}
      </main>

      <Footer onNavigate={handleNavigate} />
    </div>
  );
}

// Mount to DOM
const container = document.getElementById('root');
if (container) {
  const root = createRoot(container);
  root.render(<App />);
}
