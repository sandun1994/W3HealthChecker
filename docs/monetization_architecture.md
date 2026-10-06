# Monetization Architecture

## 1. Overview
The W3HealthChecker monetization architecture delivers transparent, fair, and defensible SaaS subscription tiers. We explicitly reject dark patterns: there are no fake reviews, no deceptive ranking guarantees, no artificial score suppression, and no forced upfront credit card charges for basic utilities.

## 2. Subscription Tiers
Configured centrally in `config/plans.php`:

### Free Community ($0/month)
- Designed for solo webmasters, developers, and creators.
- **Includes**:
  - Full access to all 19 specialized audit tools.
  - Unlimited manual 7-pillar full audits.
  - 1 Monitored website on a weekly schedule.
  - 1 Personal Workspace project.
  - 14-day history retention.

### Pro Webmaster ($29/month)
- Designed for growing websites, indie makers, and in-house marketing teams.
- **Includes**:
  - Everything in Free.
  - Up to 10 Monitored websites on daily or weekly schedules.
  - Up to 5 Workspace projects.
  - Instant regression and score change notifications.
  - 90-day history retention and historical trendlines.
  - PDF executive report exports.

### Agency & Teams ($99/month)
- Designed for digital marketing agencies, SEO consultants, and engineering teams.
- **Includes**:
  - Everything in Pro.
  - Up to 50 Monitored websites.
  - Up to 25 Client workspaces and projects.
  - White-label customized branding on reports.
  - REST API v1 access and authentication keys.
  - Multi-user team collaboration.
  - 1-Year full history audit trail.

## 3. Subscription States
- `active`: Normal access with regular billing renewal.
- `trial`: Full tier features during promotional evaluation periods.
- `cancelled`: Subscription canceled by user. Access continues until the end of the paid period (`renews_at`).
- `past_due`: Grace period for transient card decline before downgrading.
- `expired`: Account returned to Free Community defaults.
