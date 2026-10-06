import React from 'react';
import { Activity, ShieldCheck, Cpu, ArrowRight, User, LogOut } from 'lucide-react';

export default function Navbar({ onNavigate, currentUser, onLogout }) {
  return (
    <header className="sticky top-0 z-50 glass-panel border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        {/* Brand Logo */}
        <a 
          href="/" 
          onClick={(e) => { e.preventDefault(); onNavigate('home'); }} 
          className="flex items-center gap-3 group focus:outline-none"
        >
          <div className="w-10 h-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center shadow-lg shadow-brand-500/20 group-hover:scale-105 transition-transform duration-200">
            <Activity className="w-5 h-5 text-white" />
          </div>
          <div>
            <div className="flex items-center gap-1.5">
              <span className="font-extrabold text-lg tracking-tight text-white font-sans">W3Health</span>
              <span className="text-xs px-2 py-0.5 rounded-full bg-brand-500/20 text-brand-300 font-semibold border border-brand-500/30">CHECKER</span>
            </div>
            <p className="text-[10px] text-slate-400 tracking-wider uppercase font-medium">Website Intelligence</p>
          </div>
        </a>

        {/* Desktop Navigation Links */}
        <nav className="hidden lg:flex items-center gap-6 text-sm font-medium text-slate-300">
          <a 
            href="/" 
            onClick={(e) => { e.preventDefault(); onNavigate('home'); }}
            className="hover:text-white transition-colors"
          >
            Scanner
          </a>
          <a 
            href="/tools" 
            onClick={(e) => { e.preventDefault(); onNavigate('tools'); }}
            className="hover:text-white transition-colors flex items-center gap-1.5"
          >
            Tools
            <span className="text-[10px] px-1.5 py-0.5 rounded bg-indigo-900/60 text-indigo-300 font-semibold border border-indigo-700/50">19 FREE</span>
          </a>
          <a 
            href="/guides" 
            onClick={(e) => { e.preventDefault(); onNavigate('guides_index'); }}
            className="hover:text-white transition-colors"
          >
            Guides
          </a>
          <a 
            href="/monitoring" 
            onClick={(e) => { e.preventDefault(); onNavigate('monitoring'); }}
            className="hover:text-white transition-colors flex items-center gap-1.5"
          >
            Monitoring
            <span className="text-[10px] px-1.5 py-0.5 rounded bg-emerald-950/80 text-emerald-300 font-semibold border border-emerald-800/60">AUTO</span>
          </a>
          <a 
            href="/billing" 
            onClick={(e) => { e.preventDefault(); onNavigate('billing'); }}
            className="hover:text-white transition-colors"
          >
            Pricing
          </a>
          {currentUser && (
            <a 
              href="/dashboard" 
              onClick={(e) => { e.preventDefault(); onNavigate('dashboard'); }}
              className="text-indigo-400 hover:text-indigo-300 font-semibold transition-colors"
            >
              Workspace
            </a>
          )}
          <a 
            href="/compare" 
            onClick={(e) => { e.preventDefault(); onNavigate('compare'); }}
            className="hover:text-white transition-colors"
          >
            Compare
          </a>
          <a 
            href="/about" 
            onClick={(e) => { e.preventDefault(); onNavigate('about'); }}
            className="hover:text-white transition-colors"
          >
            About
          </a>
        </nav>

        {/* Auth / CTA Button */}
        <div className="flex items-center gap-3">
          {currentUser ? (
            <div className="flex items-center gap-2">
              <a
                href="/dashboard"
                onClick={(e) => { e.preventDefault(); onNavigate('dashboard'); }}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-900 border border-slate-800 text-xs text-white hover:border-indigo-500/50 transition-colors"
              >
                <User className="w-3.5 h-3.5 text-indigo-400" />
                <span className="max-w-[120px] truncate">{currentUser.name}</span>
              </a>
              <button
                onClick={onLogout}
                className="p-2 rounded-lg bg-slate-900 hover:bg-slate-800 text-slate-400 hover:text-rose-400 border border-slate-800 transition-colors"
                title="Sign Out"
              >
                <LogOut className="w-3.5 h-3.5" />
              </button>
            </div>
          ) : (
            <a
              href="/login"
              onClick={(e) => { e.preventDefault(); onNavigate('login'); }}
              className="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-800 transition-colors"
            >
              Sign In
            </a>
          )}

          <a
            href="/"
            onClick={(e) => {
              if (window.location.pathname !== '/') {
                onNavigate('home');
              }
            }}
            className="px-4 py-2 rounded-lg text-xs font-semibold bg-gradient-to-r from-brand-600 to-indigo-600 hover:from-brand-500 hover:to-indigo-500 text-white shadow-md shadow-brand-600/25 flex items-center gap-2 transition-all"
          >
            <span>Run Scan</span>
            <ArrowRight className="w-3.5 h-3.5" />
          </a>
        </div>
      </div>
    </header>
  );
}
