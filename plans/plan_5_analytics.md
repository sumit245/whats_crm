# Plan 5: Management, Analytics, & Compliance Dashboard

## Goal
Implement a comprehensive analytics and compliance monitoring suite. This includes Meta account health tracking, a per-message cost accounting engine with prepaid wallet support, a complete campaign broadcast funnel with conversion tracking, and team performance/CSAT metrics.

---

## Proposed Changes

### 5.1 Meta Compliance & Throttling

#### [NEW] `database/migrations/xxxx_add_compliance_fields_to_devices.php`
- Add columns to `devices`: `last_quality_check_at`, `quality_alert_sent_at`, `daily_limit_used`, `limit_reset_at`

#### [NEW] `app/Console/Commands/PollAccountHealth.php`
- Schedule to run hourly via cron
- Polls Meta Graph API for quality rating (`quality_rating`) and daily limit tier (`messaging_limit_tier`) for each connected device
- If `quality_rating` drops to `YELLOW` or `RED`, dispatch `SendQualityAlertNotification` (email/slack to client)

#### [NEW] `app/Services/ThrottlingEngine.php`
- `checkLimitAndThrottle(Device $device): bool`
  - Retrieves the `daily_limit_used` and maps `messaging_limit_tier` to numeric capacity (1k, 10k, 100k, unlimited)
  - If used >= 90% of limit, block/queue marketing campaigns (type = 'template' and category = 'MARKETING')
  - Allow OTPs/Utility messages to pass through to reserve capacity
- Called within `ProcessBlastJob.php` before dispatching to Meta Graph API

---

### 5.2 Cost Accounting Engine & Wallet

#### [NEW] `database/migrations/xxxx_create_wallets_and_transactions_table.php`
- `wallets`: `id`, `user_id`, `balance` (decimal), `currency`, `created_at`, `updated_at`
- `wallet_transactions`: `id`, `wallet_id`, `amount`, `type` (debit/credit), `description`, `reference_id` (blast_id / payment_id), `created_at`

#### [NEW] `database/migrations/xxxx_create_meta_rate_cards_table.php`
- Columns: `id`, `country_code`, `category` (MARKETING/UTILITY/AUTHENTICATION), `rate` (decimal), `effective_from`

#### [NEW] `app/Services/CostAccountingEngine.php`
- `calculateCost(string $receiverNumber, string $category): float`
  - Extracts country prefix from receiver number
  - Looks up current rate in `meta_rate_cards` table
  - Returns calculated cost per message
- `deductMessageCost(User $user, float $cost, int $blastId): bool`
  - Checks if user wallet balance >= cost
  - Debits wallet, creates transaction log, returns success/fail status

#### [MODIFY] `ProcessBlastJob.php`
- Call `CostAccountingEngine::calculateCost()`
- Attempt `deductMessageCost()`. If balance insufficient, fail the blast record with error "Insufficient Wallet Balance"

---

### 5.3 Broadcast Funnel Metrics

#### [MODIFY] [AnalyticsController.php](file:///var/www/html/whatsapp/app/Http/Controllers/AnalyticsController.php)
- Upgrade campaigns page to display a graphic conversion funnel:
  1. **Audience Size** (Total contacts in phonebook/segment)
  2. **Sent** (Messages successfully accepted by Meta API)
  3. **Delivered** (DLR Delivered Webhook matches)
  4. **Read** (DLR Read Webhook matches)
  5. **CTA Clicks** (Tracks CTR when URL quick replies are clicked in templates via redirect links)
  6. **Converted** (Optional: Webhook received from external system matching recipient number with conversion event)

#### [NEW] `database/migrations/xxxx_create_conversion_events_table.php`
- `id`, `campaign_id`, `receiver`, `event_type` (checkout_completed / lead_qualified), `created_at`

---

### 5.4 Agent Performance (WFM) & CSAT

#### [NEW] `database/migrations/xxxx_create_agent_sessions_table.php`
- `id`, `agent_id`, `conversation_id`, `first_message_at` (customer), `first_reply_at` (agent), `resolved_at`, `csat_rating` (integer 1-5), `csat_comment` (text)

#### [NEW] `app/Services/WfmTracker.php`
- `logFirstResponse(Conversation $conv, Agent $agent): void`
  - Calculates FRT: diff between `conv.last_message_at` (before reply) and current timestamp
- `logResolution(Conversation $conv): void`
  - Calculates AHT: total active time agent spent viewing/replying to chat before clicking "Resolve"
  - Triggers WhatsApp interactive CSAT survey template to recipient number

#### [MODIFY] [ChatController.php](file:///var/www/html/whatsapp/app/Http/Controllers/ChatController.php)
- Call `WfmTracker` methods inside the `send()` and `resolveChat()` actions

#### [NEW] `resources/themes/mpwa/views/pages/analytics/agent_performance.blade.php`
- Dashboard for supervisors showing:
  - Avg FRT and Avg AHT per agent / team
  - CSAT score distribution (star-ratings, counts)
  - Individual agent leaderboard

---

## Verification Plan

### Automated Tests
- `CostAccountingEngineTest`: Verify correct rate card lookup for different countries and categories, and wallet deduction math
- `WfmTrackerTest`: Verify correct response and handling time calculations

### Manual Verification
- Deplete wallet balance to 0, verify broadcasts are stopped/failed due to balance limits
- Trigger a mock 1-5 CSAT survey response webhook, verify CSAT metrics update in the supervisor dashboard
