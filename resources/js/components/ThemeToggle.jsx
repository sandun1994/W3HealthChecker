import React, { useState, useEffect, useRef } from 'react';
import { Sun, Moon, Monitor, Check } from 'lucide-react';

export default function ThemeToggle() {
  const [theme, setTheme] = useState(() => {
    if (typeof window !== 'undefined') {
      return localStorage.getItem('w3_theme') || 'dark';
    }
    return 'dark';
  });

  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef(null);

  // Apply theme to DOM
  const applyTheme = (selectedTheme) => {
    const root = document.documentElement;
    let isDark = false;

    if (selectedTheme === 'system') {
      isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    } else {
      isDark = selectedTheme === 'dark';
    }

    if (isDark) {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
  };

  const handleSelectTheme = (newTheme) => {
    setTheme(newTheme);
    localStorage.setItem('w3_theme', newTheme);
    applyTheme(newTheme);
    setIsOpen(false);
  };

  // Quick toggle between light & dark if directly clicked
  const handleQuickToggle = () => {
    const nextTheme = theme === 'dark' ? 'light' : 'dark';
    handleSelectTheme(nextTheme);
  };

  // Sync on initial load and handle system preference changes
  useEffect(() => {
    applyTheme(theme);

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handleSystemChange = (e) => {
      const currentStored = localStorage.getItem('w3_theme') || 'dark';
      if (currentStored === 'system') {
        if (e.matches) {
          document.documentElement.classList.add('dark');
        } else {
          document.documentElement.classList.remove('dark');
        }
      }
    };

    mediaQuery.addEventListener('change', handleSystemChange);
    return () => mediaQuery.removeEventListener('change', handleSystemChange);
  }, [theme]);

  // Close dropdown on click outside
  useEffect(() => {
    const handleClickOutside = (event) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target)) {
        setIsOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }
    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isOpen]);

  const options = [
    { id: 'light', label: 'Light', icon: Sun },
    { id: 'dark', label: 'Dark', icon: Moon },
    { id: 'system', label: 'System', icon: Monitor },
  ];

  const CurrentIcon = theme === 'light' ? Sun : (theme === 'system' ? Monitor : Moon);

  return (
    <div className="relative inline-block text-left" ref={dropdownRef}>
      {/* Theme Trigger Button */}
      <div className="flex items-center rounded-xl bg-slate-900 border border-slate-800 p-0.5 shadow-sm">
        <button
          type="button"
          onClick={handleQuickToggle}
          className="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none"
          title={`Switch theme (Currently: ${theme.charAt(0).toUpperCase() + theme.slice(1)})`}
          aria-label={`Toggle theme, current theme is ${theme}`}
        >
          <CurrentIcon className="w-4 h-4 text-indigo-400" />
        </button>

        <button
          type="button"
          onClick={() => setIsOpen(!isOpen)}
          className="px-1.5 py-1 text-[10px] font-semibold tracking-wider uppercase text-slate-400 hover:text-white transition-colors focus:outline-none"
          title="Choose theme option"
          aria-expanded={isOpen}
        >
          {theme === 'system' ? 'Auto' : theme}
        </button>
      </div>

      {/* Floating Dropdown Menu */}
      {isOpen && (
        <div className="absolute right-0 mt-2 w-36 rounded-2xl bg-slate-900 border border-slate-800 shadow-2xl py-1 z-50 animate-in fade-in zoom-in-95 duration-150 glass-panel">
          <div className="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-800/80 mb-1">
            Theme Mode
          </div>
          {options.map((option) => {
            const Icon = option.icon;
            const isSelected = theme === option.id;
            return (
              <button
                key={option.id}
                onClick={() => handleSelectTheme(option.id)}
                className={`w-full flex items-center justify-between px-3 py-2 text-xs font-medium transition-colors ${
                  isSelected
                    ? 'text-indigo-400 bg-indigo-950/40'
                    : 'text-slate-300 hover:text-white hover:bg-slate-800/60'
                }`}
              >
                <div className="flex items-center gap-2">
                  <Icon className={`w-3.5 h-3.5 ${isSelected ? 'text-indigo-400' : 'text-slate-400'}`} />
                  <span>{option.label}</span>
                </div>
                {isSelected && <Check className="w-3.5 h-3.5 text-indigo-400" />}
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}
