import React, { useState } from 'react';
import { 
  Code2, 
  Key, 
  Terminal, 
  Copy, 
  Check, 
  ShieldCheck, 
  Zap, 
  Webhook, 
  FileJson,
  Layers,
  ArrowRight
} from 'lucide-react';

export default function DevelopersView({ onNavigate }) {
  const [selectedLang, setSelectedLang] = useState('curl');
  const [copiedKey, setCopiedKey] = useState(null);

  const copySnippet = (code, id) => {
    navigator.clipboard.writeText(code);
    setCopiedKey(id);
    setTimeout(() => setCopiedKey(null), 2000);
  };

  const codeSnippets = {
    curl: `curl -X POST https://w3healthchecker.com/api/v1/scans \\
  -H "Authorization: Bearer w3_live_YOUR_SECRET_KEY" \\
  -H "Content-Type: application/json" \\
  -d '{"url": "https://example.com"}'`,

    javascript: `const response = await fetch('https://w3healthchecker.com/api/v1/scans', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer w3_live_YOUR_SECRET_KEY',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({ url: 'https://example.com' })
});

const data = await response.json();
console.log('Score:', data.scan.overall_score);`,

    php: `<?php
$ch = curl_init('https://w3healthchecker.com/api/v1/scans');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer w3_live_YOUR_SECRET_KEY',
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode(['url' => 'https://example.com']),
]);

$response = json_decode(curl_exec($ch), true);
echo "Website Score: " . $response['scan']['overall_score'];`,

    python: `import requests

response = requests.post(
    'https://w3healthchecker.com/api/v1/scans',
    headers={
        'Authorization': 'Bearer w3_live_YOUR_SECRET_KEY',
        'Content-Type': 'application/json'
    },
    json={'url': 'https://example.com'}
)

data = response.json()
print('Score:', data['scan']['overall_score'])`
  };

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      {/* Header */}
      <div className="text-center max-w-3xl mx-auto mb-16">
        <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-cyan-950/80 border border-cyan-800/60 text-cyan-300 text-xs font-semibold uppercase tracking-wider mb-4">
          <Terminal className="w-3.5 h-3.5" />
          Developer Platform v1
        </div>
        <h1 className="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-4">
          REST API & Webhooks Documentation
        </h1>
        <p className="text-base text-slate-400">
          Programmatically trigger defensive website audits, monitor targets, retrieve 7-pillar telemetry, and integrate health scoring into your CI/CD pipelines.
        </p>
      </div>

      {/* Quick Start Card */}
      <div className="bg-slate-900 rounded-3xl border border-slate-800 p-8 mb-16">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
          <div>
            <h2 className="text-lg font-bold text-white flex items-center gap-2">
              <Zap className="w-5 h-5 text-amber-400" />
              Quick Start Request
            </h2>
            <p className="text-xs text-slate-400 mt-1">
              Trigger a real-time audit using your API key. Base URL: <code className="text-indigo-400 font-mono">https://w3healthchecker.com/api/v1</code>
            </p>
          </div>

          <div className="flex items-center gap-1 bg-slate-950 p-1 rounded-xl border border-slate-800">
            {['curl', 'javascript', 'php', 'python'].map((lang) => (
              <button
                key={lang}
                onClick={() => setSelectedLang(lang)}
                className={`px-3 py-1.5 rounded-lg text-xs font-semibold capitalize transition-colors ${
                  selectedLang === lang ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-white'
                }`}
              >
                {lang}
              </button>
            ))}
          </div>
        </div>

        <div className="relative">
          <pre className="p-5 rounded-2xl bg-slate-950 border border-slate-800 font-mono text-xs text-emerald-400 overflow-x-auto leading-relaxed">
            {codeSnippets[selectedLang]}
          </pre>
          <button
            onClick={() => copySnippet(codeSnippets[selectedLang], 'quickstart')}
            className="absolute top-4 right-4 p-2 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300"
            title="Copy code"
          >
            {copiedKey === 'quickstart' ? <Check className="w-4 h-4 text-emerald-400" /> : <Copy className="w-4 h-4" />}
          </button>
        </div>
      </div>

      {/* Endpoints Matrix */}
      <div className="mb-16">
        <h2 className="text-xl font-bold text-white mb-6 flex items-center gap-2">
          <Layers className="w-5 h-5 text-indigo-400" />
          API v1 Endpoints Matrix
        </h2>

        <div className="space-y-4">
          <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <div className="flex items-center gap-3 mb-2">
              <span className="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-emerald-950 text-emerald-400 border border-emerald-800/60">
                POST
              </span>
              <code className="text-sm font-bold text-white font-mono">/api/v1/scans</code>
            </div>
            <p className="text-xs text-slate-400 mb-3">
              Initiates an active audit on a target website. Enforces full SSRF protection, DNS validation, and rate limiting.
            </p>
            <div className="text-xs font-mono text-slate-500">
              Payload: <span className="text-slate-300">{"{\"url\": \"https://example.com\"}"}</span>
            </div>
          </div>

          <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <div className="flex items-center gap-3 mb-2">
              <span className="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-blue-950 text-blue-400 border border-blue-800/60">
                GET
              </span>
              <code className="text-sm font-bold text-white font-mono">/api/v1/scans/:id</code>
            </div>
            <p className="text-xs text-slate-400">
              Fetches execution status and summary health scores for a specific scan identifier.
            </p>
          </div>

          <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <div className="flex items-center gap-3 mb-2">
              <span className="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-blue-950 text-blue-400 border border-blue-800/60">
                GET
              </span>
              <code className="text-sm font-bold text-white font-mono">/api/v1/reports/:id</code>
            </div>
            <p className="text-xs text-slate-400">
              Retrieves the comprehensive 7-pillar report, including prioritized recommendations, issues, metrics, and actionable fixes.
            </p>
          </div>

          <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <div className="flex items-center gap-3 mb-2">
              <span className="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-purple-950 text-purple-400 border border-purple-800/60">
                POST
              </span>
              <code className="text-sm font-bold text-white font-mono">/api/v1/scans/bulk</code>
            </div>
            <p className="text-xs text-slate-400 mb-3">
              Safely submits a batch of up to 10 URLs for controlled sequential auditing without queue saturation.
            </p>
            <div className="text-xs font-mono text-slate-500">
              Payload: <span className="text-slate-300">{"{\"urls\": [\"https://example.com\", \"https://sub.example.com\"]}"}</span>
            </div>
          </div>

          <div className="p-6 rounded-2xl bg-slate-900 border border-slate-800">
            <div className="flex items-center gap-3 mb-2">
              <span className="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-emerald-950 text-emerald-400 border border-emerald-800/60">
                POST
              </span>
              <code className="text-sm font-bold text-white font-mono">/api/v1/monitoring</code>
            </div>
            <p className="text-xs text-slate-400">
              Configures automated daily or weekly recurring monitoring for a website target.
            </p>
          </div>
        </div>
      </div>

      {/* Webhook Signatures */}
      <div className="p-8 rounded-3xl bg-slate-900 border border-slate-800">
        <h2 className="text-lg font-bold text-white mb-2 flex items-center gap-2">
          <Webhook className="w-5 h-5 text-indigo-400" />
          Webhook Cryptographic Signature Verification
        </h2>
        <p className="text-xs text-slate-400 mb-4 max-w-3xl">
          Every webhook delivery includes an <code className="text-indigo-400 font-mono">X-W3-Signature</code> header generated using HMAC-SHA256 with your endpoint secret.
        </p>
        <pre className="p-5 rounded-2xl bg-slate-950 border border-slate-800 font-mono text-xs text-slate-300 overflow-x-auto leading-relaxed">
{`// Verify signature in Node.js
const crypto = require('crypto');

function verifyWebhook(payload, signatureHeader, secret) {
  const expected = 'sha256=' + crypto.createHmac('sha256', secret).update(payload).digest('hex');
  return crypto.timingSafeEqual(Buffer.from(signatureHeader), Buffer.from(expected));
}`}
        </pre>
      </div>
    </div>
  );
}
