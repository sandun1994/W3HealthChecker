import React from 'react';

export default function ScoreRing({ score = 0, size = 'large', label = null, showLabel = true }) {
  const normalizedScore = Math.max(0, Math.min(100, Math.round(score)));

  // SVG parameters
  const isLarge = size === 'large';
  const dimension = isLarge ? 160 : 72;
  const strokeWidth = isLarge ? 12 : 6;
  const radius = (dimension - strokeWidth) / 2;
  const circumference = 2 * Math.PI * radius;
  const strokeDashoffset = circumference - (normalizedScore / 100) * circumference;

  // Determine theme
  const getTheme = (val) => {
    if (val >= 90) {
      return {
        color: '#10b981',
        trackColor: 'rgba(16, 185, 129, 0.15)',
        textBadge: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
        label: 'Excellent',
      };
    }
    if (val >= 80) {
      return {
        color: '#3b82f6',
        trackColor: 'rgba(59, 130, 246, 0.15)',
        textBadge: 'text-blue-400 bg-blue-500/10 border-blue-500/20',
        label: 'Good',
      };
    }
    if (val >= 70) {
      return {
        color: '#f59e0b',
        trackColor: 'rgba(245, 158, 11, 0.15)',
        textBadge: 'text-amber-400 bg-amber-500/10 border-amber-500/20',
        label: 'Needs Improvement',
      };
    }
    if (val >= 50) {
      return {
        color: '#f97316',
        trackColor: 'rgba(249, 115, 22, 0.15)',
        textBadge: 'text-orange-400 bg-orange-500/10 border-orange-500/20',
        label: 'Poor',
      };
    }
    return {
      color: '#f43f5e',
      trackColor: 'rgba(244, 63, 94, 0.15)',
      textBadge: 'text-rose-400 bg-rose-500/10 border-rose-500/20',
      label: 'Critical',
    };
  };

  const theme = getTheme(normalizedScore);
  const displayLabel = label || theme.label;

  return (
    <div className="flex flex-col items-center justify-center">
      <div className="relative flex items-center justify-center" style={{ width: dimension, height: dimension }}>
        <svg
          width={dimension}
          height={dimension}
          viewBox={`0 0 ${dimension} ${dimension}`}
          className="transform -rotate-90"
        >
          {/* Background circle track */}
          <circle
            cx={dimension / 2}
            cy={dimension / 2}
            r={radius}
            stroke={theme.trackColor}
            strokeWidth={strokeWidth}
            fill="transparent"
          />
          {/* Animated score stroke */}
          <circle
            cx={dimension / 2}
            cy={dimension / 2}
            r={radius}
            stroke={theme.color}
            strokeWidth={strokeWidth}
            strokeDasharray={circumference}
            strokeDashoffset={strokeDashoffset}
            strokeLinecap="round"
            fill="transparent"
            style={{
              transition: 'stroke-dashoffset 1s cubic-bezier(0.16, 1, 0.3, 1)',
            }}
          />
        </svg>

        {/* Center score readout */}
        <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
          <span className={`font-extrabold tracking-tight text-white ${isLarge ? 'text-4xl' : 'text-lg'}`}>
            {normalizedScore}
          </span>
          {isLarge && (
            <span className="text-[11px] font-semibold text-slate-400 -mt-1">
              / 100
            </span>
          )}
        </div>
      </div>

      {/* Text label badge */}
      {showLabel && (
        <div className={`mt-3 px-2.5 py-0.5 rounded-full text-xs font-semibold border ${theme.textBadge}`}>
          {displayLabel}
        </div>
      )}
    </div>
  );
}
