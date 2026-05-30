# Plan 3: Multi-Agent Live Chat (Shared Inbox)

## Goal
Transform the current single-user chat into a multi-agent shared inbox with real-time WebSocket sync, intelligent routing, SLA timers, a contextual CRM pane, and supervisor controls.

---

## Proposed Changes

### 3.1 Database Schema Changes

#### [NEW] `database/migrations/xxxx_create_agents_table.php`
- `id`, `user_id` (FK), `team_id`, `role` (agent/supervisor/admin), `status` (online/offline/busy), `max_concurrent_chats`, `active_chat_count`, `last_seen_at`

#### [NEW] `database/migrations/xxxx_create_teams_table.php`
- `id`, `user_id` (account owner), `name` (e.g., "Finance", "Support"), `routing_rules` (JSON)

#### [MODIFY] `conversations` table — add columns:
- `assigned_agent_id` (nullable FK to agents), `assignment_source` (auto/manual), `assigned_at`, `first_response_at`, `resolved_at`, `sla_breached` (boolean)

#### [NEW] `database/migrations/xxxx_create_chat_notes_table.php`
- `id`, `conversation_id`, `agent_id`, `note` (text), `is_internal` (boolean — whisper note vs visible), `created_at`

#### [NEW] `database/migrations/xxxx_create_contact_attributes_table.php`
- `id`, `conversation_id`, `key` (e.g., "LTV", "Lifetime Orders"), `value`, `updated_at`

---

### 3.2 WebSocket Infrastructure (Laravel Reverb + Echo)

#### [NEW] Install & configure Laravel Reverb
- `composer require laravel/reverb` + `php artisan reverb:install`
- Configure private channels: `chat.{conversationId}`, `inbox.{userId}`, `presence.{userId}`

#### [NEW] `app/Events/NewMessageEvent.php`
- Broadcasts on `chat.{conversationId}` when inbound/outbound message is created
- Payload: formatted message object

#### [NEW] `app/Events/AgentTypingEvent.php`
- Broadcasts on `chat.{conversationId}` when an agent starts/stops typing
- Other agents see "Agent X is typing..."

#### [NEW] `app/Events/ConversationUpdatedEvent.php`
- Broadcasts on `inbox.{userId}` when assignment changes, new message arrives, or SLA breaches

#### [MODIFY] [ChatController.php](file:///var/www/html/whatsapp/app/Http/Controllers/ChatController.php)
- `send()`: After saving message, broadcast `NewMessageEvent`
- New endpoint `POST chat/{id}/typing`: broadcasts `AgentTypingEvent`

#### [MODIFY] [MetaWebhookController.php](file:///var/www/html/whatsapp/app/Http/Controllers/MetaWebhookController.php)
- `storeInboundMessage()`: broadcast `NewMessageEvent` after storing

#### [MODIFY] Chat frontend JS
- Replace HTTP polling with Laravel Echo WebSocket listeners
- Listen for `NewMessageEvent` to append messages in real-time
- Listen for `AgentTypingEvent` to show typing indicators

---

### 3.3 Intelligent Routing & Assignment

#### [NEW] `app/Services/ChatRouter.php`
- `assignConversation(Conversation $conv): Agent`
  1. Check team routing rules (JSON): `if intent == "Billing" → team "Finance"`
  2. Within the target team, find online agents sorted by `active_chat_count` ASC (round robin)
  3. Assign to the agent with lowest load under their `max_concurrent_chats`
  4. If no agent available → mark as `unassigned`, add to queue
- Called automatically when a new inbound conversation is created or bot hands off

#### [NEW] `app/Console/Commands/CheckSlaTimers.php`
- Runs every minute via scheduler
- Finds conversations where `assigned_at` is set but `first_response_at` is NULL and elapsed > 15 min
- Sets `sla_breached = true`, broadcasts alert event to supervisors

---

### 3.4 Contextual CRM Right Panel

#### [MODIFY] Chat view blade template
- Add a collapsible right panel (350px wide) showing:
  - Contact name, number, profile picture
  - Editable custom attributes (Name, LTV, Lifetime Orders) from `contact_attributes`
  - Active tags (from phonebook tags)
  - Internal notes timeline from `chat_notes`
  - Add Note form (text + internal toggle)
  - Conversation history summary (total messages, first contact date)

---

### 3.5 Supervisor Mode

#### [MODIFY] Chat view
- Supervisors see ALL conversations across agents (global inbox view)
- Filter by: agent, team, SLA status, unassigned
- "Take Over" button: reassigns `assigned_agent_id` to supervisor
- "Internal Note" mode: messages sent as whisper (visible to agents only, not sent to WhatsApp)

---

## Verification Plan

### Automated Tests
- `ChatRouterTest`: Verify round-robin distributes evenly, respects max concurrent, team rules
- `SlaTimerTest`: Verify breach detection after 15 min

### Manual Verification
- Open chat in two browser tabs as two agents, verify WebSocket sync and typing indicators
- Assign a chat, wait 15+ min, verify SLA breach flag appears
- Supervisor takes over a chat, verify reassignment and internal note visibility
