# Feature Audit: Our Project vs AiSensy
**Date:** 2026-06-08 | **Last Updated:** 2026-06-09 (2) | **Auditor:** Claude Code

This document compares our WhatsApp SaaS platform against AiSensy's complete feature set (audited by clicking through every menu and sub-menu).

---

## ✅ IMPLEMENTED — Parity with AiSensy

### Dashboard
| Feature | Our Implementation |
|---|---|
| Business API status / device management | `HomeController`, `MetaHealthController` |
| Plan display & gating | `Plans` model, plan middleware, `PlansController` |
| Setup guide / installer wizard | `/install` route, `SettingController` |
| Payment / billing | `PlansController`, `OrderController`, 4 payment gateways |

### Live Chat
| Feature | Our Implementation |
|---|---|
| Active / Intervened / Bot tabs | `ChatController`, `chat/index.blade.php` |
| Multi-agent inbox with real-time push | Socket.io server + `SocketPushService` |
| Chat notes in message timeline | `ChatNote` model, `storeNote`, sorted timeline merge |
| Agent assign / unassign / resolve | `chat.assign`, `chat.unassign`, `chat.resolve` routes |
| Contact attributes mid-chat | `saveAttribute` in `ChatController` |
| Conversation labels | `ConversationLabel` model, attach/detach routes |
| Supervisor global view + Take Over | `$isSupervisor` detection, unassign button |
| CSAT on resolve | Auto-sends `csat*`/`rating*`/`feedback*` template |
| Typing indicator | `chat.typing` route + socket push |
| Send media, polls, contacts, templates, catalogue | send-* routes in `ChatController`, catalogue picker dropdown |

### Contacts / CRM
| Feature | Our Implementation |
|---|---|
| Bulk import (CSV) | `ContactImportController` |
| Search, filter, add manually | `ContactController` |
| Tags + segmentation | `TagController`, `SegmentController` |
| Contact timeline | `ContactTimelineController` |
| Export contacts | `exportContact` route |
| Custom attributes | `ContactAttribute` model |

### Campaigns / Broadcasting
| Feature | Our Implementation |
|---|---|
| Broadcast + API + scheduled campaigns | `CampaignController`, `BlastController`, `StartBlast` command |
| Campaign pause / resume / delete | `campaign.pause`, `campaign.resume`, `campaign.delete` |
| Campaign analytics (funnel) | `AnalyticsController`, `MessageDeliveryEvent` |
| Retarget (resend to unread/undelivered) | `campaign.retarget` route |
| Campaign comparison | `CampaignCompareController` |
| A/B tests | `AbTestController`, `AbTest`, `AbVariant` models |
| Audience suppression list | `SuppressionController`, `SuppressionEntry` model |
| Campaign calendar (drag-reschedule) | `CampaignCalendarController` |

### Flows / Chatbot Builder
| Feature | Our Implementation |
|---|---|
| Visual flow builder | `FlowController`, `FlowEngine` service |
| Flow analytics | `FlowAnalyticsController` |
| Flow duplicate / toggle / delete | `flows.duplicate`, `flows.toggle` routes |
| Drip sequences (extra vs AiSensy) | `DripSequenceController`, 5 DB tables |

### Manage
| Feature | Our Implementation |
|---|---|
| Template messages (create, sync, status tabs) | `TemplateController`, `MetaTemplateService` |
| Template Library (browse Meta pre-approved templates) | `/templates/library` — standalone page; sync from Meta via `message_template_library` API; filter by Category / Industry / Use Case / Language / Search; WhatsApp-style bubble preview inline on cards; preview modal with phone mockup + live `{{N}}` variable customization; one-click "Use Template" to add to WABA |
| Opt-in / Opt-out management | `OptInSettingController`, `OptInService` |
| Auto-reply (off-hours, welcome) | `AutoreplyController` |
| User attributes (custom CRM fields) | `ContactAttribute` model |
| Quick / Canned replies | `QuickReplyController`, `QuickReply` model |
| Agent management + teams | `AgentController`, team CRUD, `Team` model |
| Tags management | `TagController` |
| Analytics (agent WFM, chats/day) | `AnalyticsController` with FRT / AHT / SLA per agent |

### Payments / SaaS Billing
| Feature | Our Implementation |
|---|---|
| Plans, checkout, trial | `PlansController`, `PaymentController` |
| Stripe, PayPal, Midtrans, Paymob | All four gateways in `app/Http/Controllers/Payments/` |
| Order management (admin) | `Admin/OrderController` |

### Developer Hub
| Feature | Our Implementation |
|---|---|
| API key generation | `generateNewApiKey` in `UserController` |
| REST API docs page | `/api-docs` via `RestapiController` — redesigned with Bootstrap pills nav, real credentials, accurate curl examples |
| Inbound webhook from Meta | `MetaWebhookController` (verify + receive) |
| Outbound API (9 message types) | `Api/ApiController`, `CheckApiKey` middleware |
| Contact enroll / unenroll / attributes via API | `Api/ContactEnrollController` |

### Admin Panel
| Feature | Our Implementation |
|---|---|
| User management | `Admin/ManageUsersController` |
| Plan management | Admin plans CRUD (`/admin/plans`) |
| Payment gateway config | `Admin/PaymentGatewayController` |
| Language management | `LanguageController` |
| Settings / server / SSL | `SettingController` |
| Support tickets (user + admin sides) | `TicketController` + `Admin/TicketController` |

### Integrations ✨ NEW
| Feature | Our Implementation |
|---|---|
| Integrations Hub | `/integrations` — hub page with 5 cards, plan-gated |
| Custom App integration | `/integrations/custom-app` — API key display, device webhook config, 4-tab code examples (OTP, text, enroll, inbound webhook) |
| Website Chat Widget | `/integrations/widget` — device selector, live preview, color/position/tooltip config, embed code generator |
| Public widget JS endpoint | `/w/{token}.js` — self-contained IIFE script served by token, no auth |
| Plan gating | `integration_custom_app`, `integration_website_widget` feature flags in `plan_data` |

### Webhook Ingestion ✨ NEW
| Feature | Our Implementation |
|---|---|
| Webhook Sources | `/webhooks` — create named endpoints for Shopify, WooCommerce, Razorpay, or Generic; unique token URL per source (`/wh/{token}`); phone/name field mapping via dot-notation |
| Webhook Triggers | Per-source automation rules; optional event filter (payload field + value match); actions: send approved template or start chatbot flow; variable mapping (template `{{N}}` → payload path) |
| Inbound Receiver | `POST /wh/{token}` — public, no auth, no CSRF; logs every payload; fires matching triggers as queued jobs |
| Processing Job | `ProcessWebhookJob` — extracts + normalises phone to E.164, sends template via Meta API or hands off to FlowEngine, upserts Conversation record |
| Audit Logs | `webhook_logs` — every webhook logged with status (received / processed / skipped / failed), resolved phone, error details, expandable payload viewer |

### Catalogue Management ✨ NEW
| Feature | Our Implementation |
|---|---|
| Catalogue list & sync | `/catalogue` — syncs from `/{business-id}/owned_product_catalogs` Meta API |
| Create catalogue | `POST /catalogue/create-catalogue` — via Meta Marketing API with vertical selector |
| Delete catalogue | `DELETE /catalogue/{id}` — removes from Meta + local DB |
| Link catalogue to device | `POST /catalogue/{id}/link` — calls `whatsapp_commerce_settings` endpoint |
| Product listing | `/catalogue/{id}` — grid view with image thumbnails, search, pagination |
| Add product | `POST /catalogue/{id}/products` — full Meta product fields (SKU, price, currency, image, availability, condition, brand) |
| Edit product | `PUT /catalogue/products/{id}` — updates on Meta + local DB |
| Delete product | `DELETE /catalogue/products/{id}` — removes from Meta + local DB |
| Sync products from Meta | `POST /catalogue/{id}/sync-products` — upserts all products from `/{catalog-id}/products` |
| Chat modal picker | Catalogue dropdown + product picker in live chat send modal (replaces manual ID input) |
| Business ID auto-fetch | Cached on device via `GET /{waba-id}?fields=owner` |

---

## 🟡 PARTIALLY IMPLEMENTED — Gaps Exist

| AiSensy Feature | Our Status | Specific Gap |
|---|---|---|
| **Live Chat Settings** (working hours day/time grid, auto-resolve timer) | Partial | `AutoreplyController` handles off-hours messages but no UI for a day-wise working hours grid or configurable auto-resolve timeout. *Another developer is working on this.* |
| **Campaign Settings** (auto-pause on Meta template category change) | Partial | No listener/job detecting Meta reclassifying Utility→Marketing and pausing affected campaigns |
| **User Attributes — plan-gated limit** | Partial | Custom attributes work but no enforcement of 5 free / 20 Pro count limits |
| **Notification Preferences** (browser push, per-device toggle, 5 devices) | Partial | Real-time via Socket.io only; no Web Push API / FCM, no per-device settings UI |
| **Messages History delivery funnel** | ✅ Done | `/messages-history` — 5-card funnel (Total → Dispatched → Delivered → Read → Failed with % bars); per-row delivery badge; filters by type/source/delivery/date range; `meta_message_id` + `delivery_status` stored on send, upgraded by Meta delivery webhook |
| **Meta Quality Rating alerts** | Partial | `MetaHealthController` exists + `CheckDeviceHealthJob` polls daily, but no email/notification when quality drops to YELLOW/RED |
| **Link tracking click analytics** | Partial | `TrackedLink`, `LinkClickController` exist but no per-campaign click-through rate shown in analytics UI |
| **SLA configuration UI** | Partial | `CheckSlaTimers` command runs but no admin UI to set SLA targets or breach thresholds per plan |
| **Conversation Labels UI** | ✅ Done | `/chat/labels` — create, edit, delete, drag-to-reorder, colour picker, live preview |

---

## 🔴 NOT IMPLEMENTED — Missing Features

Sorted by business impact:

### High Priority
| Feature | Notes |
|---|---|
| **Ads Manager (CTWA)** | No Meta Ads API integration. No create/fetch/sync ads, no CPL/CTR/spend metrics, no Click-to-WhatsApp ad tracking. AiSensy's biggest differentiator. |
| **Audience Manager** | No Website / Custom / Lookalike audience management for Meta ad targeting. Tied to Ads Manager. |
| **Abandoned Cart / E-commerce Automations** | Webhook Ingestion Engine now provides the trigger layer; `AutomationEngine` flow logic still needs implementation for cart-specific detection. |
| **WhatsApp Pay** | No native in-chat payment requests, no payment status tracking per conversation. The `Payments/` folder is for SaaS billing only. |

### Medium Priority
| Feature | Notes |
|---|---|
| **Wallet / Prepaid WCC Credit System** | No per-message cost deduction, no balance top-up, no per-country rate cards. |
| **Global Attributes** (reusable flow variables) | No implementation. AiSensy allows owner-defined global variables reusable across flows. |
| **AI Messages** (LLM in chatbot) | No LLM integration in `FlowEngine`. AiSensy charges ₹3,500 / 7,000 AI messages. |
| **Multi-project / Multi-number management** | Single-user model. No "All Projects" switcher concept like AiSensy. |

### Lower Priority
| Feature | Notes |
|---|---|
| **Mobile App** (iOS / Android) | Web-only. AiSensy has native iOS + Android apps. |
| **Browser Push Notifications** | Web Push API not implemented. Real-time via Socket.io only. |
| **Invoice PDF / GST billing** | Order model exists but no invoice PDF generation or GST field on billing address. |
| **Flow Industry Templates** | No pre-built bot templates for Real Estate, Ecommerce, Education, etc. |
| **WhatsApp Link Generator + QR Code** | ✅ Done — `/wa-link`: wa.me + api.whatsapp.com builder, pre-filled message, QR code with size/colour controls, PNG/SVG download, HTML button snippet. |

---

## Priority Roadmap

| Priority | Feature | Status |
|---|---|---|
| 1 | Integrations Hub (Custom App + Website Widget) | ✅ Done |
| 2 | Meta Catalogue Management | ✅ Done |
| 3 | Template Library (browse + preview + use Meta pre-approved templates) | ✅ Done |
| 4 | Webhook Ingestion Engine (Shopify / WooCommerce / Razorpay / Generic) | ✅ Done |
| 5 | Messages History delivery funnel (sent → delivered → read per-message) | ✅ Done |
| 6 | Live Chat Settings UI (working hours, auto-resolve, SLA) | 🔄 In progress (other developer) |
| 7 | WhatsApp Pay (in-chat native payments) | 🔴 Not started |
| 8 | Wallet / WCC Cost Accounting Engine | 🔴 Not started |
| 9 | Ads Manager + CTWA | 🔴 Not started — largest scope |
| 10 | AI Messages in FlowEngine | 🔴 Not started — requires LLM provider |
| 11 | Conversation Labels management page | ✅ Done |
| 12 | WhatsApp Link Generator + QR Code | ✅ Done |
