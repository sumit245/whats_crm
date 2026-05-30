## 1. WhatsApp Marketing & Broadcasting Engine

This module cannot be a simple script; it must be a distributed queuing system. In 2026, Meta strictly enforces Quality Ratings and Frequency Capping (Error 131049). If your platform blasts messages too fast or to low-quality numbers, Meta will throttle your tier limits or ban the number.

### 1.1. Template Management & Meta Sync

Before a user can send a broadcast, they must create pre-approved templates. Your platform must communicate bidirectionally with Meta’s Graph API.

* **Template Builder UI:** A visual builder supporting Text, Media (Images/Video/PDF), Interactive elements (Quick Replies, URL buttons, Copy-Code buttons), and Carousel templates.
* **Variable Injection:** Support for dynamic variables `{{1}}`, `{{2}}`. Your UI must prompt the user to map these variables to database columns (e.g., `{{1}}` = First Name).
* **API Sync Engine:** When a user clicks "Submit," your backend sends a POST request to Meta’s `message_templates` endpoint. You must listen to Meta's webhooks for `template_status_update` (Approved, Rejected, Paused) and reflect this in your UI in real-time.

### 1.2. The Broadcast Queuing System (The Core Engine)

You cannot send 100,000 API requests simultaneously. Meta's Cloud API has throughput limits (typically starting at 80 Messages Per Second, scaling to 1,000 MPS for Tier 4).

* **Message Broker (Kafka / RabbitMQ):** When a user launches a 100k-contact broadcast, your backend must immediately acknowledge the request, break the list into individual message payloads, and push them into a distributed message queue.
* **Rate Limiter / Throttling:** Your worker nodes consuming the queue must strictly adhere to the allowed MPS rate. If Meta returns an `HTTP 429 Too Many Requests` error, your workers must implement **Exponential Backoff** to pause and retry without dropping messages.
* **Pre-Flight Checks:** Before dispatching, the worker checks if the contact has explicitly opted out (maintaining an internal suppression list) to protect the sender's Quality Rating.

### 1.3. Smart Retargeting & Segmentation

* **Dynamic Audiences:** Build a segmentation engine that queries your database based on event triggers. For example: `Select users WHERE last_purchase < 30_days AND tags CONTAINS 'VIP'`.
* **Behavioral Retargeting:** Use Meta’s Delivery Reports (DLR) webhooks to track statuses (`sent`, `delivered`, `read`). If a user launches a campaign, your platform should allow them to instantly create a sub-segment of "Users who received the message but did not click the CTA," enabling a secondary automated broadcast 48 hours later.

---

## 2. Agentic AI Chatbot & Flow Builder

The 2026 standard has moved beyond rigid keyword bots to "Memory-Rich AI." Your Flow Builder needs a visual canvas combined with contextual NLP.

### 2.1. Drag-and-Drop Canvas Architecture

* **Node-Based State Machine:** The frontend (using libraries like React Flow) allows users to connect nodes. Each node represents a state.
* **Node Types Required:**
* *Trigger Nodes:* Keyword match, Click-to-WhatsApp Ad (reading the `referral` payload), API Webhook.
* *Action Nodes:* Send Message, Send Catalog, Ask for Input (with Regex validation for email/phone).
* *Logic Nodes:* IF/ELSE conditions (e.g., IF user tag = 'Premium' route to Node B), Randomizer (for A/B testing messages), Delay blocks.


* **JSON Serialization:** When the user hits save, the visual graph must be serialized into a highly optimized JSON object representing the state machine, which the backend engine can parse instantly upon receiving a user message.

### 2.2. Contextual AI & NLP Integration

* **Intent Recognition:** Instead of just exact keyword matches, integrate an NLP layer (like Dialogflow or a fine-tuned LLM). If a user types "Where is my stuff?", the NLP maps this to the "Order_Tracking" intent and triggers the corresponding flow.
* **Memory Variables:** The bot must remember context. If it asks for an order number, it temporarily stores it in the user's session cache (Redis) to use in API calls to the e-commerce backend.

### 2.3. The Fallback & Human Handoff Protocol

* **Frictionless Transition:** If the bot hits a `fallback_count > 2` (cannot understand the user), or the user clicks a "Talk to Agent" button, the bot must immediately change the conversation state in the database from `bot_active` to `human_assigned`.
* **Muting the Bot:** Once handed off, the bot engine must ignore all incoming webhooks from that specific phone number until the human agent clicks "Resolve and Reactivate Bot."

---

## 3. Multi-Agent Live Chat (The Shared Inbox)

This is the operational heart for support teams. It requires real-time bidirectional syncing so agents never overwrite each other.

### 3.1. WebSockets & Real-Time Sync

* **Architecture:** The inbox cannot rely on page refreshes. Use WebSockets (e.g., Socket.io) to push Meta's incoming message webhooks directly to the frontend client in milliseconds.
* **Collision Detection:** If Agent A begins typing in Chat #104, a WebSocket event must broadcast to all other agents viewing that chat, displaying "Agent A is typing..." to prevent duplicate responses.

### 3.2. Intelligent Routing & Queue Management

* **Rule-Based Assignment:** Build an assignment engine. When a chat is handed off from the bot, it checks rules: *If intent = "Billing", route to "Finance Team".*
* **Round Robin Distribution:** To balance workloads, the system checks which agents are currently marked "Online" and distributes incoming chats evenly based on current active ticket loads.
* **SLA Timers:** Implement visual timers on the chat list. If an inbound message hasn't been replied to within 15 minutes, it flags red and alerts a supervisor.

### 3.3. Contextual Agent CRM

* **Right-Panel Architecture:** Next to the chat window, render a persistent pane pulling data from the database. It must show:
* Custom Attributes (Name, LTV, Lifetime Orders).
* Active Tags.
* Recent Chat History / Notes left by previous agents.


* **Intervention Mode:** Supervisors must have a global view. They should be able to click into an active chat handled by a junior agent, switch to "Internal Note" mode to whisper advice, or click "Take Over" to instantly reassign the chat to themselves.

---

## 4. Automation & E-commerce Integrations Layer

A standalone API tool is useless without data from external systems. This module requires a robust Event-Driven Architecture.

### 4.1. Webhook Ingestion Engine

* **Agnostic Endpoints:** Your platform must generate unique Webhook URLs for clients to paste into Shopify, WooCommerce, or Zapier.
* **Payload Normalization:** When Shopify sends a `cart/update` webhook, your backend must catch it, parse the proprietary Shopify JSON, and normalize it into a standard internal format before triggering a WhatsApp message.

### 4.2. E-Commerce Specific Workflows

* **Abandoned Cart Recovery:**
* *Logic:* Shopify webhook received -> Check if cart has phone number -> Wait X minutes (delay queue) -> Check if order was completed. If not -> Trigger WhatsApp Cart Recovery Template.
* *Dynamic UI:* The template must dynamically inject the primary product image URL, the cart total, and a direct checkout link parameterized with UTM tags.


* **Catalog & Payment Routing:** Integrate Meta’s Native Catalogs. Allow bots to send multi-product interactive messages. Integrate Razorpay/Stripe webhooks so that when an invoice is paid, the system automatically sends a "Payment Successful" WhatsApp receipt.

### 4.3. CRM Bi-Directional Sync

* Create native OAuth apps for Hubspot and Salesforce. When a tag is added in your platform (e.g., `Lead_Qualified`), an API call must immediately update the corresponding Lead Status in Hubspot, and vice versa.

---

## 5. Management, Analytics, & Compliance Dashboard

Businesses need deep visibility into API costs and Meta compliance to avoid platform bans.

### 5.1. Meta Compliance & Account Health

* **Quality Rating Monitor:** Meta rates numbers as Green (High), Yellow (Medium), or Red (Low). Your dashboard must poll Meta's Graph API for this status. If it drops to Yellow, trigger an automated email alert to the client to stop broadcasting immediately.
* **Tier Tracking:** Visually display the client's current tier (e.g., 10k messages/day). Track outbound volumes and show a progress bar. If they hit 90% of their daily limit, throttle non-essential marketing messages to reserve capacity for critical OTPs/Utility messages.

### 5.2. Telemetry & Financial Analytics

* **Cost Accounting Engine:** Since Meta charges different rates based on destination country and conversation category (Marketing vs Utility), your database must log every outbound message against Meta's current rate card. The dashboard should show a real-time burn rate of their pre-paid wallet.
* **Funnel Metrics:** For broadcasts, display a funnel: `Audience Size -> Sent -> Delivered (Delivery Rate %) -> Read (Open Rate %) -> Button Clicks (CTR %) -> Converted (via external webhook sync)`.

### 5.3. Agent Performance (WFM)

* Calculate and display metrics for the live chat team:
* *Average First Response Time (FRT):* Timestamp of user's first message to agent's first reply.
* *Average Handling Time (AHT):* Time from assignment to ticket resolution.
* *CSAT Scores:* Trigger an automated 1-5 star rating template the moment an agent clicks "Resolve Chat," and aggregate the data per agent.