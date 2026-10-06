/**
 * W3HealthChecker Privacy-Preserving Analytics & Event Dispatcher
 * Tracks high-intent conversion and usage metrics locally with zero third-party tracking bloat.
 * Readily supports forwarders to Plausible, GA4, or PostHog if configured.
 */

class AnalyticsService {
    constructor() {
        this.storageKey = 'w3_analytics_events';
        this.sessionKey = 'w3_session_id';
        this.sessionId = this.getOrCreateSessionId();
    }

    getOrCreateSessionId() {
        try {
            let sid = sessionStorage.getItem(this.sessionKey);
            if (!sid) {
                sid = 'sess_' + Math.random().toString(36).substring(2, 12) + '_' + Date.now();
                sessionStorage.setItem(this.sessionKey, sid);
            }
            return sid;
        } catch {
            return 'sess_anon_' + Date.now();
        }
    }

    track(eventName, properties = {}) {
        const payload = {
            event: eventName,
            properties,
            path: window.location.pathname,
            referrer: document.referrer || null,
            sessionId: this.sessionId,
            timestamp: new Date().toISOString()
        };

        // Emit CustomEvent for external listeners (GA4, GTM, Plausible if injected)
        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('w3_analytics_event', { detail: payload }));
            
            // Console log in development
            if (process.env.NODE_ENV !== 'production' && false) {
                console.debug('[W3Analytics]', eventName, payload);
            }
        }

        // Store last 50 events in localStorage for diagnostic funnel insights
        try {
            const raw = localStorage.getItem(this.storageKey);
            const events = raw ? JSON.parse(raw) : [];
            events.push(payload);
            if (events.length > 50) events.shift();
            localStorage.setItem(this.storageKey, JSON.stringify(events));
        } catch {
            // Silently ignore storage quota or incognito block
        }

        return payload;
    }

    // High-intent shortcut trackers
    trackPageView(pageName) {
        return this.track('page_view', { page: pageName, title: document.title });
    }

    trackScanInitiated(url, source = 'scanner') {
        return this.track('scan_initiated', { target_url: url, source });
    }

    trackScanCompleted(domain, score, issuesCount) {
        return this.track('scan_completed', { domain, overall_score: score, issues_count: issuesCount });
    }

    trackToolExecuted(toolSlug, targetUrl) {
        return this.track('tool_executed', { tool: toolSlug, target_url: targetUrl });
    }

    trackConversion(fromTool, targetUrl) {
        return this.track('tool_to_scanner_converted', { from_tool: fromTool, target_url: targetUrl });
    }

    trackReportShared(domain, platform) {
        return this.track('report_shared', { domain, platform });
    }

    trackGuideRead(guideSlug, readTime) {
        return this.track('guide_read', { guide: guideSlug, read_time: readTime });
    }
}

export const analytics = new AnalyticsService();
