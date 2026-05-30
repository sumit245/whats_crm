# Plan 1: Marketing & Broadcasting Engine Upgrade

## Goal
Upgrade the existing broadcast system from a sequential artisan command to a production-grade queuing system with rate limiting, retries, suppression lists, and audience segmentation.

---

## Proposed Changes

### 1.1 Template Status Webhooks

#### [MODIFY] [MetaWebhookController.php](file:///var/www/html/whatsapp/app/Http/Controllers/MetaWebhookController.php)
- Add handler for `template_status_update` events inside the `receive()` method
- When Meta sends Approved/Rejected/Paused, update `waba_templates.status` in real-time
- Flash a notification to the user via a `template_status_notifications` table

#### [NEW] `database/migrations/xxxx_create_template_status_notifications_table.php`
- Columns: `user_id`, `template_id`, `old_status`, `new_status`, `read_at`

---

### 1.2 Queue-Based Blast Processing

#### [NEW] `app/Jobs/ProcessBlastJob.php`
- Laravel Queue job that processes a single blast record
- Sends via `MetaCloudApiService`, updates blast status
- On `HTTP 429`: releases back to queue with exponential delay (`$this->release(2 ** $this->attempts() * 10)`)
- Max 5 attempts before marking as `failed`

#### [MODIFY] [CampaignController.php](file:///var/www/html/whatsapp/app/Http/Controllers/CampaignController.php)
- `store()`: After creating blast records, dispatch `ProcessBlastJob` for each blast with a staggered delay
- Use `Bus::batch()` for the campaign so we can track completion percentage

#### [MODIFY] [StartBlast.php](file:///var/www/html/whatsapp/app/Console/Commands/StartBlast.php)
- Refactor to dispatch `ProcessBlastJob` jobs instead of processing inline
- Becomes a lightweight scheduler that finds `waiting` campaigns and dispatches batches

#### [NEW] `config/queue.php` changes
- Configure Redis queue connection with `retry_after`, `block_for`
- Add `broadcasts` queue with appropriate worker concurrency

---

### 1.3 Opt-Out Suppression List

#### [NEW] `database/migrations/xxxx_create_suppression_list_table.php`
- Columns: `user_id`, `number`, `reason` (user_optout / meta_block / manual), `created_at`

#### [NEW] `app/Models/SuppressionEntry.php`

#### [MODIFY] `ProcessBlastJob.php`
- Before sending, check `suppression_list` for the receiver number
- If found, mark blast as `suppressed` (new status) and skip

#### [NEW] Blade view: `resources/themes/mpwa/views/pages/suppression/index.blade.php`
- UI to view/manage suppressed numbers, add manually, import CSV

---

### 1.4 Dynamic Audience Segmentation

#### [NEW] `database/migrations/xxxx_create_segments_table.php`
- Columns: `user_id`, `name`, `rules` (JSON), `contact_count`, `last_computed_at`
- Rules format: `[{"field": "tag", "op": "contains", "value": "VIP"}, {"field": "last_contacted", "op": "<", "value": "30d"}]`

#### [NEW] `app/Models/Segment.php`
#### [NEW] `app/Services/SegmentEngine.php`
- Parses rule JSON, builds Eloquent query dynamically
- Returns contact IDs matching criteria

#### [MODIFY] Campaign Create page
- Add option to select a Segment instead of (or in addition to) a Phonebook
- Segment resolves to contacts at dispatch time

---

### 1.5 Behavioral Retargeting

#### [NEW] `resources/themes/mpwa/views/pages/campaign/retarget.blade.php`
- After a campaign completes, show a "Retarget" button
- Options: "Not Delivered", "Delivered but Not Read", "Read but No Click"
- Creates a new campaign pre-populated with the filtered contact list

#### [MODIFY] [AnalyticsController.php](file:///var/www/html/whatsapp/app/Http/Controllers/AnalyticsController.php)
- `campaignDetail()`: Return per-blast delivery status breakdown with receiver numbers
- New method `retargetAudience($campaignId, $filter)`: returns contacts matching DLR filter

---

## Verification Plan

### Automated Tests
- `php artisan test --filter=ProcessBlastJobTest` — verify retry on 429, suppression skip
- `php artisan test --filter=SegmentEngineTest` — verify rule parsing and query building

### Manual Verification
- Create a campaign, observe jobs dispatched to Redis queue
- Verify blast with suppressed number shows `suppressed` status
- Verify template status webhook updates template in UI
