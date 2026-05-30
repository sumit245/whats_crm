# Plan 2: AI Chatbot & Flow Builder

## Goal
Build a visual drag-and-drop flow builder that replaces the current keyword-only autoreply system with a full state-machine chatbot supporting triggers, actions, conditions, and human handoff.

---

## Proposed Changes

### 2.1 Database Schema

#### [NEW] `database/migrations/xxxx_create_chatbot_flows_table.php`
- `id`, `user_id`, `device_id`, `name`, `status` (active/draft), `trigger_type` (keyword/webhook/ad_click/all), `trigger_value`, `flow_json` (LONGTEXT — serialized React Flow graph), `created_at`, `updated_at`

#### [NEW] `database/migrations/xxxx_create_chatbot_sessions_table.php`
- `id`, `conversation_id`, `flow_id`, `current_node_id`, `variables` (JSON — session memory), `state` (bot_active/awaiting_input/human_assigned/completed), `fallback_count`, `created_at`, `updated_at`

#### [NEW] `app/Models/ChatbotFlow.php`
#### [NEW] `app/Models/ChatbotSession.php`

---

### 2.2 Flow Builder Frontend (React Flow)

#### [NEW] `resources/themes/mpwa/views/pages/flows/index.blade.php`
- List all flows with status toggle, edit, delete, duplicate

#### [NEW] `resources/themes/mpwa/views/pages/flows/editor.blade.php`
- Full-page canvas loading React Flow via CDN
- Node palette sidebar with draggable node types:
  - **Triggers**: Keyword Match, Webhook Received, Ad Click (`referral` payload)
  - **Actions**: Send Text, Send Image/Video, Send Template, Ask Input (with regex validation type), API Call (GET/POST to external URL), Add Tag, Remove Tag
  - **Logic**: IF/ELSE (condition on variable/tag/input), Random Split (A/B test), Delay (wait N seconds/minutes)
  - **Control**: Human Handoff, End Flow
- Connection validation (trigger → action/logic only, no orphan nodes)
- Save button serializes the graph to JSON and POSTs to backend

#### [NEW] `public/themes/mpwa/js/flow-editor.js`
- React Flow initialization, custom node renderers, save/load logic
- Variable picker dropdown for `{{session.order_id}}` style injection

---

### 2.3 Flow Execution Engine

#### [NEW] `app/Services/FlowEngine.php`
- `handleInbound(Conversation $conv, string $messageBody): void`
  1. Check if conversation has an active `ChatbotSession` in state `awaiting_input` → process input against current node's validation, advance to next node
  2. If no active session → find matching flow by keyword/trigger → create new session at trigger node → execute first action
  3. Walk the graph: execute action nodes sequentially, evaluate logic nodes, pause at input nodes
  4. On `human_handoff` node → set session state to `human_assigned`, stop processing
  5. On `fallback_count > 2` → auto-handoff

#### [MODIFY] [MetaWebhookController.php](file:///var/www/html/whatsapp/app/Http/Controllers/MetaWebhookController.php)
- In `storeInboundMessage()`, after storing the message, call `FlowEngine::handleInbound()` if no human agent is assigned
- Skip flow engine if session state is `human_assigned`

---

### 2.4 Human Handoff Protocol

#### [MODIFY] Chat view (`resources/themes/mpwa/views/pages/chat/index.blade.php`)
- Add "Resolve & Reactivate Bot" button on conversations with `human_assigned` state
- Add visual indicator showing bot status (🤖 Active / 👤 Human)

#### [NEW] `app/Http/Controllers/FlowController.php`
- CRUD routes for flows
- `resolveChat($conversationId)`: sets session state back to `completed`, re-enables bot

---

### 2.5 NLP Integration (Phase 2 — Optional)

#### [NEW] `app/Services/NlpService.php`
- Interface with `classify(string $text): string` returning intent name
- Default implementation: exact keyword match (current behavior)
- Pluggable: Dialogflow adapter, OpenAI adapter
- Flow trigger can be set to "Intent Match" with intent name

---

## Verification Plan

### Automated Tests
- `FlowEngineTest`: Create a flow JSON with trigger→message→input→condition→message, simulate inbound messages, assert correct node traversal and output messages
- `ChatbotSessionTest`: Verify session state transitions (bot_active → awaiting_input → human_assigned → completed)

### Manual Verification
- Build a 5-node flow in the visual editor, save, trigger via WhatsApp message
- Test human handoff: trigger fallback 3 times, verify chat switches to agent mode
- Verify "Resolve & Reactivate Bot" re-enables the flow
