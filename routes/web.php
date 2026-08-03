<?php

use App\Http\Controllers\Admin\ManageUsersController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\AutoreplyController;
use App\Http\Controllers\BlastController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\MessagesHistoryController;
use App\Http\Controllers\MetaHealthController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\MetaWebhookController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Payments\PaymobController;
use App\Http\Controllers\PickindexController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\RestapiController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShowMessageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\User\TicketController as UserTicketController;
use App\Http\Controllers\ContactTimelineController;
use App\Http\Controllers\LinkClickController;
use App\Http\Controllers\OptInSettingController;
use App\Http\Controllers\SuppressionController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\AgentInvitationController;
use App\Http\Controllers\FlowController;
use App\Http\Controllers\FlowAnalyticsController;
use App\Http\Controllers\DripSequenceController;
use App\Http\Controllers\CampaignCalendarController;
use App\Http\Controllers\AbTestController;
use App\Http\Controllers\CampaignCompareController;
use App\Http\Controllers\ChatSettingController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\WaLinkController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\InboundWebhookController;
use App\Http\Controllers\Ads\AdDashboardController;
use App\Http\Controllers\Ads\AdChannelsController;
use App\Http\Controllers\Ads\AdCampaignsController;
use App\Http\Controllers\Ads\AdCreativesController;
use App\Http\Controllers\Ads\AdAnalyticsController;
use App\Http\Controllers\Ads\AdAudiencesController;
use App\Http\Controllers\Ads\AdAbTestController;
use App\Http\Controllers\Ads\AdOAuthController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

require_once 'custom-route.php';

// Meta Cloud API Webhook — outside auth + localization, no CSRF
Route::get('/webhook/meta', [MetaWebhookController::class, 'verify'])->name('meta.webhook.verify');
Route::post('/webhook/meta', [MetaWebhookController::class, 'receive'])->name('meta.webhook.receive');

// Ads Manager OAuth callbacks — no locale prefix so redirect URIs are stable
// Register these exact URLs in Meta App / LinkedIn Developer App dashboards:
//   Meta:     {APP_URL}/ads/oauth/meta/callback
//   LinkedIn: {APP_URL}/ads/oauth/linkedin/callback
Route::middleware('auth')->prefix('ads/oauth')->name('ads.oauth.')->group(function () {
    Route::get('/meta/redirect',        [AdOAuthController::class, 'metaRedirect'])    ->name('meta.redirect');
    Route::get('/meta/callback',        [AdOAuthController::class, 'metaCallback'])    ->name('meta.callback');
    Route::post('/meta/setup',          [AdOAuthController::class, 'metaSetup'])       ->name('meta.setup');
    Route::get('/linkedin/redirect',    [AdOAuthController::class, 'linkedinRedirect'])->name('linkedin.redirect');
    Route::get('/linkedin/callback',    [AdOAuthController::class, 'linkedinCallback'])->name('linkedin.callback');
    Route::post('/linkedin/setup',      [AdOAuthController::class, 'linkedinSetup'])   ->name('linkedin.setup');
});

// Public widget script — served by token, no auth required
Route::get('/w/{token}.js', [IntegrationController::class, 'serveWidget'])->name('widget.serve');

// Click tracking redirect — public, no auth, no CSRF
Route::get('/t/{slug}', [LinkClickController::class, 'redirect'])->name('link.click');

// Inbound webhooks — public, no auth, no CSRF (token in URL is the secret)
Route::post('/wh/{token}', [InboundWebhookController::class, 'receive'])->name('webhook.inbound');

Route::group(['prefix' => LaravelLocalization::setLocale()], function () {

    if (env('ENABLE_INDEX') == 'no') {
        Route::get('/', fn() => Redirect::to('/login'));
    } else {
        Route::get('/', [IndexController::class, 'index'])->name('index');
    }

    Route::middleware('2fa')->group(function () {
        Route::get('/2fa', [TwoFactorController::class, 'showVerify'])->name('2fa.verify');
        Route::post('/2fa', [TwoFactorController::class, 'verifyLogin'])->name('2fa.verify');
    });

    Route::middleware('auth', '2fa')->group(function () {

        // File manager
        Route::group(['prefix' => 'laravel-filemanager'], function () {
            \UniSharp\LaravelFilemanager\Lfm::routes();
        });
        Route::get('/file-manager', [FileManagerController::class, 'index'])->name('file-manager');
        Route::get('/filemanager', fn() => redirect('/' . LaravelLocalization::getCurrentLocale() . '/laravel-filemanager'))->name('filemanager');

        // Devices (home) — owner only
        Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('no.agent');
        Route::post('/home', [HomeController::class, 'store'])->name('addDevice')->middleware('no.agent');
        Route::delete('/home', [HomeController::class, 'destroy'])->name('deleteDevice')->middleware('no.agent');
        Route::post('/home/setSessionSelectedDevice', [HomeController::class, 'setSelectedDeviceSession'])->name('home.setSessionSelectedDevice')->middleware('no.agent');
        Route::post('/home/sethook', [HomeController::class, 'setHook'])->name('setHook')->middleware('no.agent');

        // Auto-reply
        Route::get('/autoreply', [AutoreplyController::class, 'index'])->name('autoreply')->middleware('permissions');
        Route::post('/autoreply', [AutoreplyController::class, 'store'])->name('autoreply')->middleware('permissions');
        Route::get('/autoreply-edit/{id}', [AutoreplyController::class, 'edit'])->name('autoreply.edit')->middleware('permissions');
        Route::post('/autoreply-edit', [AutoreplyController::class, 'editUpdate'])->name('autoreply.edit.update')->middleware('permissions');
        Route::delete('/autoreply', [AutoreplyController::class, 'destroy'])->name('autoreply.delete')->middleware('permissions');
        Route::post('auto-reply/update/{autoreply:id}', [AutoreplyController::class, 'update'])->name('autoreply.update')->middleware('permissions');

        // Phonebook / contacts
        Route::get('/phonebook', [TagController::class, 'index'])->name('phonebook');
        Route::get('/get-phonebook', [TagController::class, 'getPhonebook'])->name('getPhonebook');
        Route::delete('/clear-phonebook', [TagController::class, 'clearPhonebook'])->name('clearPhonebook');
        Route::get('get-contact/{id}', [ContactController::class, 'getContactByTagId']);
        Route::get('/contacts/search',  [ContactController::class, 'search'])->name('contacts.search');
        Route::post('/contact/store', [ContactController::class, 'store'])->name('contact.store');
        Route::post('/contact/import', [ContactController::class, 'import'])->name('import'); // phonebook modal import
        Route::delete('/contact/delete/{contact:id}', [ContactController::class, 'destroy'])->name('contact.delete');
        Route::delete('/contact/delete-all/{id}', [ContactController::class, 'DestroyAll'])->name('deleteAll');
        Route::get('/contact/export/{id}', [ContactController::class, 'export'])->name('exportContact');
        Route::post('/tags', [TagController::class, 'store'])->name('tag.store');
        Route::delete('/tags', [TagController::class, 'destroy'])->name('tag.delete');
        Route::post('fetch-groups', [TagController::class, 'fetchGroups'])->name('fetch.groups');

        // Contact import (CSV/Excel)
        Route::get('/contacts',                   [ContactTimelineController::class, 'index'])->name('contacts.directory');
        Route::get('/contacts/{number}/timeline', [ContactTimelineController::class, 'show'])->name('contact.timeline');
        Route::get('/contacts/import', [ContactImportController::class, 'index'])->name('contacts.import');
        Route::post('/contacts/import/preview', [ContactImportController::class, 'preview'])->name('contacts.import.preview');
        Route::post('/contacts/import', [ContactImportController::class, 'import'])->name('contacts.import.store');

        // HSM Templates
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::post('/templates/sync', [TemplateController::class, 'sync'])->name('templates.sync');
        Route::get('/templates/library',        [TemplateController::class, 'library'])      ->name('templates.library');
        Route::post('/templates/library/sync', [TemplateController::class, 'librarySync'])  ->name('templates.library.sync');
        Route::get('/templates/library/fetch', [TemplateController::class, 'libraryFetch'])->name('templates.library.fetch');
        Route::post('/templates/library/add',  [TemplateController::class, 'addFromLibrary'])->name('templates.library.add');
        Route::get('/templates/{id}', [TemplateController::class, 'show'])->name('templates.show');
        Route::post('/templates/{id}/refresh', [TemplateController::class, 'refreshStatus'])->name('templates.refresh');
        Route::delete('/templates/{id}', [TemplateController::class, 'destroy'])->name('templates.destroy');

        // Campaigns — owner only
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns')->middleware('permissions', 'no.agent');
        Route::get('/campaign/create', [CampaignController::class, 'create'])->name('campaign.create')->middleware('permissions', 'no.agent');
        Route::post('/campaign/store', [CampaignController::class, 'store'])->name('campaign.store')->middleware('permissions', 'no.agent');
        // 'blast' is the legacy name used by the campaign AJAX forms
        Route::post('/blast', [CampaignController::class, 'store'])->name('blast')->middleware('permissions', 'no.agent');
        Route::post('/campaign/pause/{id}', [CampaignController::class, 'pause'])->name('campaign.pause')->middleware('permissions', 'no.agent');
        Route::post('/campaign/resume/{id}', [CampaignController::class, 'resume'])->name('campaign.resume')->middleware('permissions', 'no.agent');
        Route::delete('/campaign/delete/{id}', [CampaignController::class, 'destroy'])->name('campaign.delete')->middleware('permissions', 'no.agent');
        Route::get('/campaign/show/{id}', [CampaignController::class, 'show'])->name('campaign.show')->middleware('permissions', 'no.agent');
        Route::delete('/campaign/clear', [CampaignController::class, 'destroyAll'])->name('campaigns.delete.all')->middleware('permissions', 'no.agent');
        Route::get('/campaign/blast/{campaign:id}', [BlastController::class, 'index'])->name('campaign.blasts')->middleware('permissions', 'no.agent');

        // AJAX template fetching for campaigns
        Route::get('/campaign/templates/{deviceId}', [CampaignController::class, 'getTemplatesForDevice'])->name('campaign.templates');

        // Campaign retargeting & progress
        Route::post('/campaign/{id}/retarget', [CampaignController::class, 'retarget'])->name('campaign.retarget')->middleware('permissions');
        Route::get('/campaign/{id}/progress', [CampaignController::class, 'progress'])->name('campaign.progress');

        // Opt-in / Opt-out settings
        Route::get('/opt-in', [OptInSettingController::class, 'show'])->name('optin.show');
        Route::post('/opt-in', [OptInSettingController::class, 'update'])->name('optin.update');

        // Suppression list (Phase C)
        Route::get('/suppression', [SuppressionController::class, 'index'])->name('suppression.index');
        Route::post('/suppression', [SuppressionController::class, 'store'])->name('suppression.store');
        Route::post('/suppression/import', [SuppressionController::class, 'import'])->name('suppression.import');
        Route::delete('/suppression/{id}', [SuppressionController::class, 'destroy'])->name('suppression.destroy');

        // Segments (Phase E)
        Route::get('/segments', [SegmentController::class, 'index'])->name('segments.index');
        Route::get('/segments/create', [SegmentController::class, 'create'])->name('segments.create');
        Route::post('/segments', [SegmentController::class, 'store'])->name('segments.store');
        Route::delete('/segments/{id}', [SegmentController::class, 'destroy'])->name('segments.destroy');
        Route::post('/segments/preview', [SegmentController::class, 'preview'])->name('segments.preview');

        // Message test
        Route::get('/message/test', [MessagesController::class, 'index'])->name('messagetest');
        Route::post('/message/test', [MessagesController::class, 'store'])->name('messagetest')->middleware('permissions');

        // Chatbot Flow Builder (Feature 2)
        Route::get('/flows', [FlowController::class, 'index'])->name('flows.index');
        Route::get('/flows/create', [FlowController::class, 'create'])->name('flows.create');
        Route::get('/flows/{id}/edit', [FlowController::class, 'edit'])->name('flows.edit');
        Route::post('/flows', [FlowController::class, 'store'])->name('flows.store');
        Route::put('/flows/{id}', [FlowController::class, 'update'])->name('flows.update');
        Route::post('/flows/{id}/toggle', [FlowController::class, 'toggleStatus'])->name('flows.toggle');
        Route::delete('/flows/{id}', [FlowController::class, 'destroy'])->name('flows.destroy');
        Route::post('/flows/{id}/duplicate', [FlowController::class, 'duplicate'])->name('flows.duplicate');
        Route::get('/flows/{id}/analytics', [FlowAnalyticsController::class, 'show'])->name('flows.analytics');

        // Drip Sequences
        Route::get('/drip',                          [DripSequenceController::class, 'index'])->name('drip.index');
        Route::get('/drip/create',                   [DripSequenceController::class, 'create'])->name('drip.create');
        Route::post('/drip',                         [DripSequenceController::class, 'store'])->name('drip.store');
        Route::get('/drip/{id}/edit',                [DripSequenceController::class, 'edit'])->name('drip.edit');
        Route::put('/drip/{id}',                     [DripSequenceController::class, 'update'])->name('drip.update');
        Route::delete('/drip/{id}',                  [DripSequenceController::class, 'destroy'])->name('drip.destroy');
        Route::get('/drip/{id}/enrollments',         [DripSequenceController::class, 'enrollments'])->name('drip.enrollments');
        Route::post('/drip/{id}/enroll',             [DripSequenceController::class, 'enroll'])->name('drip.enroll');
        Route::patch('/drip/enrollment/{id}/cancel', [DripSequenceController::class, 'cancelEnrollment'])->name('drip.cancel');

        // Campaign Calendar
        Route::get('/calendar',              [CampaignCalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events',       [CampaignCalendarController::class, 'events'])->name('calendar.events');
        Route::patch('/calendar/{id}/reschedule', [CampaignCalendarController::class, 'reschedule'])->name('calendar.reschedule');

        // A/B Tests
        Route::get('/ab-tests',              [AbTestController::class, 'index'])->name('ab.index');
        Route::get('/ab-tests/create',       [AbTestController::class, 'create'])->name('ab.create');
        Route::post('/ab-tests',             [AbTestController::class, 'store'])->name('ab.store');
        Route::get('/ab-tests/{id}',         [AbTestController::class, 'show'])->name('ab.show');
        Route::post('/ab-tests/{id}/launch', [AbTestController::class, 'launch'])->name('ab.launch');
        Route::delete('/ab-tests/{id}',      [AbTestController::class, 'destroy'])->name('ab.destroy');

        // Chat: bot handoff controls (Feature 2)
        Route::post('/chat/{id}/resolve-bot', [ChatController::class, 'resolveBot'])->name('chat.resolve.bot');
        Route::get('/chat/{id}/bot-status', [ChatController::class, 'botStatus'])->name('chat.bot.status');

        // Template notification mark-read endpoints (Phase A)
        Route::post('/notifications/template/{id}/read', function ($id) {
            \App\Models\TemplateStatusNotification::where('user_id', auth()->id())->find($id)?->markRead();
            return response()->json(['ok' => true]);
        })->name('notifications.template.read');
        Route::post('/notifications/template/read-all', function () {
            \App\Models\TemplateStatusNotification::where('user_id', auth()->id())->whereNull('read_at')->update(['read_at' => now()]);
            return response()->json(['ok' => true]);
        })->name('notifications.template.read.all');

        // Chat
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::get('/chat/settings',  [ChatSettingController::class, 'show'])  ->name('chat.settings');
        Route::post('/chat/settings', [ChatSettingController::class, 'update'])->name('chat.settings.update');
        Route::post('/chat/start', [ChatController::class, 'start'])->name('chat.start');
        // Conversation labels (must be before /{id} wildcard)
        Route::get('/chat/labels',                   [ChatController::class, 'labelsIndex'])  ->name('chat.labels.index');
        Route::post('/chat/labels/reorder',          [ChatController::class, 'labelsReorder'])->name('chat.labels.reorder');
        Route::post('/chat/labels',                  [ChatController::class, 'labelsStore'])  ->name('chat.labels.store');
        Route::put('/chat/labels/{labelId}',         [ChatController::class, 'labelsUpdate']) ->name('chat.labels.update');
        Route::delete('/chat/labels/{labelId}',      [ChatController::class, 'labelsDestroy'])->name('chat.labels.destroy');
        Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat.show');
        Route::get('/chat/{id}/messages', [ChatController::class, 'messages'])->name('chat.messages');
        Route::post('/chat/{id}/send', [ChatController::class, 'send'])->name('chat.send');
        Route::post('/chat/{id}/send-template', [ChatController::class, 'sendTemplate'])->name('chat.send.template');
        Route::post('/chat/{id}/upload-media',  [ChatController::class, 'uploadMedia'])->name('chat.upload.media');
        Route::post('/chat/{id}/send-media',    [ChatController::class, 'sendMedia'])->name('chat.send.media');
        Route::post('/chat/{id}/send-poll',     [ChatController::class, 'sendPoll'])->name('chat.send.poll');
        Route::post('/chat/{id}/send-contact',  [ChatController::class, 'sendContact'])->name('chat.send.contact');
        Route::post('/chat/{id}/send-catalog',  [ChatController::class, 'sendCatalog'])->name('chat.send.catalog');
        Route::post('/chat/{id}/send-meeting',  [ChatController::class, 'sendMeetingLink'])->name('chat.send.meeting');

        // Quick Replies
        Route::resource('/quick-replies', \App\Http\Controllers\QuickReplyController::class)->only(['index','store','destroy']);

        // Feature 3: Multi-agent live chat
        Route::post('/chat/{id}/typing',   [ChatController::class, 'typing'])->name('chat.typing');
        Route::post('/chat/{id}/notes',                [ChatController::class, 'storeNote'])     ->name('chat.notes.store');
        Route::put('/chat/{id}/notes/{noteId}',       [ChatController::class, 'updateNote'])     ->name('chat.notes.update');
        Route::delete('/chat/{id}/notes/{noteId}',    [ChatController::class, 'destroyNote'])    ->name('chat.notes.destroy');
        Route::post('/chat/{id}/notes/upload',        [ChatController::class, 'uploadNoteMedia'])->name('chat.notes.upload');
        Route::get('/chat/{id}/notes/{noteId}/print', [ChatController::class, 'printNote'])      ->name('chat.notes.print');
        Route::post('/chat/{id}/attribute',[ChatController::class, 'saveAttribute'])->name('chat.attribute.save');
        Route::post('/chat/{id}/assign',   [ChatController::class, 'assign'])->name('chat.assign');
        Route::post('/chat/{id}/unassign', [ChatController::class, 'unassign'])->name('chat.unassign');
        Route::post('/chat/{id}/resolve',  [ChatController::class, 'resolve'])->name('chat.resolve');
        Route::post('/chat/{id}/labels/{labelId}',   [ChatController::class, 'attachLabel'])->name('chat.labels.attach');
        Route::delete('/chat/{id}/labels/{labelId}', [ChatController::class, 'detachLabel'])->name('chat.labels.detach');

        // Feature 3: Agent & Team management — owner only
        Route::get('/agents',              [\App\Http\Controllers\AgentController::class, 'index'])->name('agents.index')->middleware('no.agent');
        Route::post('/agents',             [\App\Http\Controllers\AgentController::class, 'store'])->name('agents.store')->middleware('no.agent');
        Route::put('/agents/{id}',         [\App\Http\Controllers\AgentController::class, 'update'])->name('agents.update')->middleware('no.agent');
        Route::delete('/agents/{id}',      [\App\Http\Controllers\AgentController::class, 'destroy'])->name('agents.destroy')->middleware('no.agent');
        Route::post('/agents/{id}/status', [\App\Http\Controllers\AgentController::class, 'setStatus'])->name('agents.status');
        Route::post('/agents/{id}/invite', [\App\Http\Controllers\AgentController::class, 'sendInvite'])->name('agents.invite')->middleware('no.agent');
        Route::post('/teams',              [\App\Http\Controllers\AgentController::class, 'storeTeam'])->name('teams.store');
        Route::put('/teams/{id}',          [\App\Http\Controllers\AgentController::class, 'updateTeam'])->name('teams.update');
        Route::delete('/teams/{id}',       [\App\Http\Controllers\AgentController::class, 'destroyTeam'])->name('teams.destroy')->middleware('no.agent');

        // Preview / form helpers (still used by autoreply)
        Route::post('/preview-message', [ShowMessageController::class, 'index'])->name('previewMessage');
        Route::get('/form-message/{type}', [ShowMessageController::class, 'getFormByType'])->name('formMessage');
        Route::get('/form-message-edit/{type}', [ShowMessageController::class, 'showEdit'])->name('formMessageEdit');

        // REST API docs
        Route::get('/api-docs', RestapiController::class)->name('rest-api')->middleware('permissions');

        // Integrations Hub
        Route::get('/integrations',                      [IntegrationController::class, 'index'])          ->name('integrations.index');
        Route::get('/integrations/custom-app',           [IntegrationController::class, 'customApp'])      ->name('integrations.custom-app')->middleware('no.agent');
        Route::get('/integrations/widget',               [IntegrationController::class, 'widget'])         ->name('integrations.widget')->middleware('no.agent');
        Route::post('/integrations/widget/activate',     [IntegrationController::class, 'activateWidget']) ->name('integrations.widget.activate')->middleware('no.agent');
        Route::post('/integrations/widget/configure',    [IntegrationController::class, 'configureWidget'])->name('integrations.widget.configure')->middleware('no.agent');

        // WhatsApp Link Generator
        Route::get('/wa-link', [WaLinkController::class, 'index'])->name('wa-link.index');

        // Webhook Ingestion — management (sources + triggers)
        Route::prefix('webhooks')->name('webhooks.')->middleware('no.agent')->group(function () {
            Route::get('/',                                           [WebhookController::class, 'index'])         ->name('index');
            Route::post('/',                                          [WebhookController::class, 'store'])         ->name('store');
            Route::delete('/{id}',                                    [WebhookController::class, 'destroy'])       ->name('destroy');
            Route::post('/{id}/toggle',                               [WebhookController::class, 'toggleActive'])  ->name('toggle');
            Route::get('/{id}',                                       [WebhookController::class, 'show'])          ->name('show');
            Route::post('/{id}/triggers',                             [WebhookController::class, 'storeTrigger'])  ->name('triggers.store');
            Route::delete('/{id}/triggers/{tid}',                     [WebhookController::class, 'destroyTrigger'])->name('triggers.destroy');
            Route::post('/{id}/triggers/{tid}/toggle',                [WebhookController::class, 'toggleTrigger']) ->name('triggers.toggle');
        });

        // Catalogue Management
        Route::prefix('catalogue')->name('catalogue.')->middleware('no.agent')->group(function () {
            Route::get('/',                        [CatalogueController::class, 'index'])           ->name('index');
            Route::get('/{id}',                    [CatalogueController::class, 'show'])            ->name('show');
            Route::post('/sync',                   [CatalogueController::class, 'sync'])            ->name('sync');
            Route::post('/create-catalogue',       [CatalogueController::class, 'createCatalogue']) ->name('create-catalogue');
            Route::post('/{id}/sync-products',     [CatalogueController::class, 'syncProducts'])    ->name('sync-products');
            Route::post('/{id}/link',              [CatalogueController::class, 'linkCatalogue'])   ->name('link');
            Route::delete('/{id}',                 [CatalogueController::class, 'destroyCatalogue'])->name('destroy');
            Route::post('/{id}/products',          [CatalogueController::class, 'createProduct'])   ->name('products.create');
            Route::put('/products/{productId}',    [CatalogueController::class, 'updateProduct'])   ->name('products.update');
            Route::delete('/products/{productId}', [CatalogueController::class, 'destroyProduct'])  ->name('products.destroy');
            Route::get('/{id}/products-json',      [CatalogueController::class, 'productsJson'])    ->name('products-json');
        });

        // ── Ads Manager ──────────────────────────────────────────────────────────
        Route::prefix('ads')->name('ads.')->middleware('permissions')->group(function () {
            Route::get('/', [AdDashboardController::class, 'index'])->name('dashboard');

            // Channels
            Route::prefix('channels')->name('channels.')->group(function () {
                Route::get('/',                         [AdChannelsController::class, 'index'])        ->name('index');
                Route::post('/',                        [AdChannelsController::class, 'store'])        ->name('store');
                Route::delete('/{channel}',             [AdChannelsController::class, 'destroy'])     ->name('destroy');
                Route::post('/{channel}/verify',        [AdChannelsController::class, 'verify'])      ->name('verify');
                Route::post('/{channel}/sync-metadata', [AdChannelsController::class, 'syncMetadata'])->name('sync-metadata');
            });

            // Campaigns
            Route::prefix('campaigns')->name('campaigns.')->group(function () {
                Route::get('/',                         [AdCampaignsController::class, 'index'])      ->name('index');
                Route::get('/create',                   [AdCampaignsController::class, 'create'])     ->name('create');
                Route::post('/',                        [AdCampaignsController::class, 'store'])      ->name('store');
                Route::get('/{campaign}',               [AdCampaignsController::class, 'show'])       ->name('show');
                Route::patch('/{campaign}',             [AdCampaignsController::class, 'update'])     ->name('update');
                Route::delete('/{campaign}',            [AdCampaignsController::class, 'destroy'])    ->name('destroy');
                Route::post('/{campaign}/launch',                          [AdCampaignsController::class, 'launch'])         ->name('launch');
                Route::post('/{campaign}/pause',                           [AdCampaignsController::class, 'pause'])          ->name('pause');
                Route::post('/{campaign}/sync-metrics',                    [AdCampaignsController::class, 'syncMetrics'])    ->name('sync-metrics');
                Route::post('/{campaign}/add-placement',                   [AdCampaignsController::class, 'addPlacement'])   ->name('add-placement');
                Route::post('/{campaign}/placements/{placement}/retry',    [AdCampaignsController::class, 'retryPlacement']) ->name('placement.retry');
                Route::post('/{campaign}/placements/{placement}/creative', [AdCampaignsController::class, 'assignCreative'])->name('placement.assign-creative');
            });

            // Creatives
            Route::prefix('creatives')->name('creatives.')->group(function () {
                Route::get('/',                         [AdCreativesController::class, 'index'])  ->name('index');
                Route::post('/',                        [AdCreativesController::class, 'store'])  ->name('store');
                Route::get('/{creative}',               [AdCreativesController::class, 'show'])   ->name('show');
                Route::match(['put','patch'], '/{creative}', [AdCreativesController::class, 'update'])->name('update');
                Route::delete('/{creative}',            [AdCreativesController::class, 'destroy'])->name('destroy');
                Route::get('/{creative}/preview',       [AdCreativesController::class, 'preview'])->name('preview');
            });

            // Analytics
            Route::get('/analytics',                    [AdAnalyticsController::class, 'index'])    ->name('analytics');
            Route::get('/analytics/export',             [AdAnalyticsController::class, 'export'])   ->name('analytics.export');
            Route::post('/analytics/sync-all',          [AdAnalyticsController::class, 'syncAll'])  ->name('analytics.sync-all');
            Route::get('/analytics/{placement}',        [AdAnalyticsController::class, 'placement'])->name('analytics.placement');

            // Audiences
            Route::prefix('audiences')->name('audiences.')->group(function () {
                Route::get('/',                   [AdAudiencesController::class, 'index'])  ->name('index');
                Route::post('/',                  [AdAudiencesController::class, 'store'])  ->name('store');
                Route::delete('/{audience}',      [AdAudiencesController::class, 'destroy'])->name('destroy');
                Route::post('/{audience}/sync',   [AdAudiencesController::class, 'sync'])   ->name('sync');
            });

            // A/B Tests
            Route::prefix('ab-tests')->name('ab-tests.')->group(function () {
                Route::get('/',                         [AdAbTestController::class, 'index'])  ->name('index');
                Route::get('/create',                   [AdAbTestController::class, 'create']) ->name('create');
                Route::post('/',                        [AdAbTestController::class, 'store'])  ->name('store');
                Route::get('/{abTest}',                 [AdAbTestController::class, 'show'])   ->name('show');
                Route::post('/{abTest}/launch',         [AdAbTestController::class, 'launch']) ->name('launch');
                Route::post('/{abTest}/decide',         [AdAbTestController::class, 'decide']) ->name('decide');
                Route::delete('/{abTest}',              [AdAbTestController::class, 'destroy'])->name('destroy');
            });
        });

        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/campaign/{id}', [AnalyticsController::class, 'campaignDetail'])->name('analytics.campaign');
        Route::get('/analytics/campaign/{id}/links', [AnalyticsController::class, 'campaignLinks'])->name('analytics.campaign.links');

        // Campaign Comparison
        Route::get('/campaigns/compare', [CampaignCompareController::class, 'index'])->name('campaigns.compare');
        Route::post('/campaigns/compare/data', [CampaignCompareController::class, 'compare'])->name('campaigns.compare.data');

        // API Health
        Route::get('/meta/health', [MetaHealthController::class, 'index'])->name('meta.health');
        Route::post('/meta/health/refresh/{deviceId}', [MetaHealthController::class, 'refresh'])->name('meta.health.refresh');

        // User settings
        Route::get('/user/settings', [UserController::class, 'settings'])->name('user.settings');
        Route::post('/user/change-password', [UserController::class, 'changePasswordPost'])->name('changePassword');
        Route::post('/user/setting/apikey', [UserController::class, 'generateNewApiKey'])->name('generateNewApiKey');
        Route::post('/user/setting/deletehistory', [UserController::class, 'deleteHistory'])->name('deleteHistory');
        Route::post('/user/settings/2fa', [UserController::class, 'toggleTwoFactor'])->name('user.settings.2fa');
        Route::get('/user/2fa_setup', [TwoFactorController::class, 'showSetup'])->name('user.2fa_setup');
        Route::post('/user/2fa/verify', [TwoFactorController::class, 'verify'])->name('user.2fa.verify');

        // Admin settings
        Route::get('/admin/settings', [SettingController::class, 'index'])->name('admin.settings')->middleware('admin');
        Route::post('/settings/server', [SettingController::class, 'setServer'])->name('setServer')->middleware('admin');
        Route::post('/settings/generate-ssl', [SettingController::class, 'generateSslCertificate'])->name('generateSsl')->middleware('admin');
        Route::post('/settings/setenvall', [SettingController::class, 'setEnvAll'])->name('setEnvAll')->middleware('admin');
        Route::get('/admin/cronjob', [SettingController::class, 'cronJob'])->name('cronjob')->middleware('admin');

        // Tickets
        Route::get('tickets', [UserTicketController::class, 'index'])->name('user.tickets.index');
        Route::post('tickets/{ticket}/reply', [UserTicketController::class, 'reply'])->name('user.tickets.reply');
        Route::post('tickets/store', [UserTicketController::class, 'store'])->name('user.tickets.store');
        Route::get('tickets/create', [UserTicketController::class, 'create'])->name('user.tickets.create');
        Route::get('tickets/{ticket}', [UserTicketController::class, 'show'])->name('user.tickets.show');
        Route::get('/admin/tickets', [AdminTicketController::class, 'index'])->name('admin.tickets.index')->middleware('admin');
        Route::get('/admin/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('admin.tickets.show')->middleware('admin');
        Route::post('/admin/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('admin.tickets.reply')->middleware('admin');
        Route::post('/admin/tickets/{ticket}/close', [AdminTicketController::class, 'close'])->name('admin.tickets.close')->middleware('admin');
        Route::post('/admin/tickets/{ticket}/reopen', [AdminTicketController::class, 'reopen'])->name('admin.tickets.reopen')->middleware('admin');

        // Plans
        Route::get('/admin/plans', [PlansController::class, 'index'])->name('admin.plans.index')->middleware('admin');
        Route::get('/admin/plans/create', [PlansController::class, 'create'])->name('admin.plans.create')->middleware('admin');
        Route::post('/admin/plans', [PlansController::class, 'store'])->name('admin.plans.store')->middleware('admin');
        Route::get('/admin/plans/{plan}/edit', [PlansController::class, 'edit'])->name('admin.plans.edit')->middleware('admin');
        Route::put('/admin/plans/{plan}', [PlansController::class, 'update'])->name('admin.plans.update')->middleware('admin');
        Route::delete('/admin/plans/{plan}', [PlansController::class, 'destroy'])->name('admin.plans.destroy')->middleware('admin');

        // Welcome page editor
        Route::get('/admin/pickindex', [PickindexController::class, 'editSettings'])->name('admin.index.edit')->middleware('admin');
        Route::post('/admin/pickindex', [PickindexController::class, 'updateSettings'])->name('admin.index.update')->middleware('admin');
        Route::post('/admin/pickindexcolor', [PickindexController::class, 'updateColor'])->name('admin.index.color')->middleware('admin');
        Route::post('/admin/pickindexenable', [PickindexController::class, 'enableIndex'])->name('admin.index.enable')->middleware('admin');

        // Languages
        Route::get('/admin/languages', [LanguageController::class, 'index'])->name('languages.index')->middleware('admin');
        Route::get('/admin/languages/{lang}/edit', [LanguageController::class, 'edit'])->name('languages.edit')->middleware('admin');
        Route::post('/admin/languages/{lang}', [LanguageController::class, 'update'])->name('languages.update')->middleware('admin');
        Route::post('/admin/languages/add/new', [LanguageController::class, 'add'])->name('languages.add')->middleware('admin');
        Route::delete('/admin/languages/{lang}', [LanguageController::class, 'destroy'])->name('languages.destroy')->middleware('admin');


        // User management
        Route::get('/admin/manage-users', [ManageUsersController::class, 'index'])->name('admin.manage-users')->middleware('admin');
        Route::post('/admin/user/store', [ManageUsersController::class, 'store'])->name('user.store')->middleware('admin');
        Route::post('/admin/user/updatePlan/{id}', [ManageUsersController::class, 'updatePlan'])->name('admin.users.updatePlan')->middleware('admin');
        Route::delete('/admin/user/delete/{id}', [ManageUsersController::class, 'delete'])->name('user.delete')->middleware('admin');
        Route::get('admin/user/edit', [ManageUsersController::class, 'edit'])->name('user.edit')->middleware('admin');
        Route::post('admin/user/update', [ManageUsersController::class, 'update'])->name('user.update')->middleware('admin');

        // Payments
        Route::get('/checkout/{planId}', [PaymentController::class, 'checkout'])->name('payments.checkout');
        Route::post('/checkout/{planId}', [PaymentController::class, 'process'])->name('payments.process');
        Route::get('/trial/{planId}', [PaymentController::class, 'trial'])->name('payments.trial');
        Route::post('/trial/{planId}', [PaymentController::class, 'trialProcess'])->name('payments.process.trial');
        Route::post('/payment/callback', [PaymentController::class, 'callback'])->name('payments.callback');
        Route::get('/payment/callback', [PaymentController::class, 'callback']);
        Route::post('/payments/paymob/process', [PaymobController::class, 'process'])->name('payments.paymob.process');
        Route::post('/payments/paymob/callback', [PaymobController::class, 'callback'])->name('payments.paymob.callback');
        Route::get('/admin/orders', [OrderController::class, 'index'])->name('admin.orders.index')->middleware('admin');
        Route::get('/admin/payments', [PaymentGatewayController::class, 'index'])->name('admin.payments.index')->middleware('admin');
        Route::post('/admin/payments/update', [PaymentGatewayController::class, 'update'])->name('admin.payments.update')->middleware('admin');

        // Message history
        Route::get('/messages-history', [MessagesHistoryController::class, 'index'])->name('messages.history');
        Route::post('/resend-message', [MessagesHistoryController::class, 'resend'])->name('resend.message');
        Route::post('/delete-messages', [MessagesHistoryController::class, 'deleteAll'])->name('delete.messages');

        Route::get('/permission-denied', fn() => view('theme::pages.permission'))->name('permission.denied');
    });

    // Agent invitation accept (public — no auth required)
    Route::get('/agent/accept/{token}',  [AgentInvitationController::class, 'show'])  ->name('agent.invite.show');
    Route::post('/agent/accept/{token}', [AgentInvitationController::class, 'accept'])->name('agent.invite.accept');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [LoginController::class, 'index'])->name('login');
        Route::get('/register', [RegisterController::class, 'index'])->name('register');
        Route::post('/register', [RegisterController::class, 'store'])->name('register');
        Route::post('/login', [LoginController::class, 'store'])->name('login')->middleware('throttle:5,1');
        Route::get('password/reset', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('password/email', [PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
        Route::post('password/reset', [PasswordResetController::class, 'reset'])->name('password.update');
    });

    Route::match(['get', 'post'], '/logout', LogoutController::class)->name('logout');
    // Route::get('/install', [SettingController::class, 'install'])->name('setting.install_app');
    // Route::post('/install', [SettingController::class, 'install'])->name('settings.install_app');
    // Route::post('/settings/check_database_connection', [SettingController::class, 'test_database_connection'])->name('connectDB');
    // Route::post('/settings/activate_license', [SettingController::class, 'activate_license'])->name('activateLicense');
});
