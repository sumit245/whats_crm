# Plan 4: Automation & E-Commerce Integrations

## Goal
Build a webhook ingestion engine, e-commerce workflow automations (abandoned cart, payment receipts), catalog messaging, and a CRM sync framework.

---

## Proposed Changes

### 4.1 Webhook Ingestion Engine

#### [NEW] `database/migrations/xxxx_create_webhook_endpoints_table.php`
- `id`, `user_id`, `device_id`, `name` (e.g., "Shopify Store"), `platform` (shopify/woocommerce/zapier/custom), `secret_token` (auto-generated UUID), `is_active`, `events_received` (counter), `last_received_at`

#### [NEW] `app/Models/WebhookEndpoint.php`

#### [NEW] `app/Http/Controllers/WebhookIngestionController.php`
- `POST /webhooks/ingest/{token}` — public endpoint, no auth
- Validates `token` against `webhook_endpoints` table
- Logs raw payload to `webhook_logs` table
- Dispatches `ProcessInboundWebhookJob` with normalized payload

#### [NEW] `app/Services/WebhookNormalizer.php`
- `normalize(string $platform, array $rawPayload): NormalizedEvent`
- Platform-specific parsers:
  - **Shopify**: `orders/create` → OrderPlaced, `carts/update` → CartUpdated, `checkouts/create` → CheckoutStarted
  - **WooCommerce**: Maps `woocommerce_order_status_changed` → equivalent events
  - **Generic/Zapier**: Pass-through with field mapping config
- Returns `NormalizedEvent` object: `type`, `contact_number`, `contact_name`, `data` (structured), `raw`

#### [NEW] `resources/themes/mpwa/views/pages/webhooks/index.blade.php`
- UI to create/manage webhook endpoints
- Shows generated URL with copy button
- Event log table with recent payloads

---

### 4.2 Abandoned Cart Recovery

#### [NEW] `database/migrations/xxxx_create_automation_workflows_table.php`
- `id`, `user_id`, `name`, `trigger_event` (e.g., "cart_updated"), `webhook_endpoint_id`, `template_id`, `delay_minutes`, `is_active`, `conditions` (JSON), `variable_mapping` (JSON)

#### [NEW] `app/Jobs/AbandonedCartCheckJob.php`
- Dispatched with delay when `cart_updated` event received
- After delay: checks if a corresponding `order_placed` event exists for same contact
- If no order → sends cart recovery template with dynamic variables (product image, cart total, checkout link)

#### [NEW] `app/Services/AutomationEngine.php`
- Event-driven: when `ProcessInboundWebhookJob` completes, checks `automation_workflows` for matching `trigger_event`
- Dispatches appropriate job (e.g., `AbandonedCartCheckJob`) with configured delay
- Supports variable mapping: `{{product_name}}` → `data.line_items[0].title`

#### [NEW] `resources/themes/mpwa/views/pages/automations/index.blade.php`
- List automations with on/off toggle
- Create form: select trigger event, template, delay, variable mapping

---

### 4.3 Catalog & Payment Integration

#### [NEW] `app/Services/MetaCatalogService.php`
- Send multi-product messages via Meta's interactive message API
- `sendProductList($device, $number, $catalogId, $productIds)`
- `sendSingleProduct($device, $number, $catalogId, $productId)`

#### [NEW] `app/Http/Controllers/PaymentWebhookController.php`
- `POST /webhooks/payment/{provider}` — handles Razorpay/Stripe webhooks
- On `payment.captured` / `charge.succeeded`: find contact by email/phone, send "Payment Successful" template

---

### 4.3 CRM Sync Framework

#### [NEW] `database/migrations/xxxx_create_crm_connections_table.php`
- `id`, `user_id`, `provider` (hubspot/salesforce), `access_token`, `refresh_token`, `config` (JSON), `sync_enabled`, `last_synced_at`

#### [NEW] `app/Services/CrmSync/CrmSyncInterface.php`
- `pushContact(Contact $contact): void`
- `pullContacts(): Collection`
- `updateStatus(string $externalId, string $status): void`

#### [NEW] `app/Services/CrmSync/HubspotSync.php`
- Implements interface using HubSpot API v3
- On tag added in platform → creates/updates HubSpot contact property

#### [NEW] `app/Observers/ContactObserver.php`
- Listens for tag changes on contacts
- If CRM connection active → dispatches `SyncContactToCrmJob`

---

## Verification Plan

### Automated Tests
- `WebhookNormalizerTest`: Feed sample Shopify/WooCommerce payloads, assert correct `NormalizedEvent` output
- `AbandonedCartCheckJobTest`: Simulate cart event, verify template sent after delay when no order exists

### Manual Verification
- Create a webhook endpoint, send a test Shopify payload via curl, verify event appears in logs
- Configure abandoned cart automation, trigger cart webhook, wait for delay, verify WhatsApp message sent
