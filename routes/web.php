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
use App\Http\Controllers\SuppressionController;
use App\Http\Controllers\SegmentController;
use App\Http\Controllers\FlowController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

require_once 'custom-route.php';

// Meta Cloud API Webhook — outside auth + localization, no CSRF
Route::get('/webhook/meta', [MetaWebhookController::class, 'verify'])->name('meta.webhook.verify');
Route::post('/webhook/meta', [MetaWebhookController::class, 'receive'])->name('meta.webhook.receive');

Route::group(['prefix' => LaravelLocalization::setLocale()], function () {

    if (env('ENABLE_INDEX') == 'no') {
        Route::get('/', fn () => Redirect::to('/login'));
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
        Route::get('/filemanager', fn () => redirect('/' . LaravelLocalization::getCurrentLocale() . '/laravel-filemanager'))->name('filemanager');

        // Devices (home)
        Route::get('/home', [HomeController::class, 'index'])->name('home');
        Route::post('/home', [HomeController::class, 'store'])->name('addDevice');
        Route::delete('/home', [HomeController::class, 'destroy'])->name('deleteDevice');
        Route::post('/home/setSessionSelectedDevice', [HomeController::class, 'setSelectedDeviceSession'])->name('home.setSessionSelectedDevice');
        Route::post('/home/sethook', [HomeController::class, 'setHook'])->name('setHook');

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
        Route::post('/contact/store', [ContactController::class, 'store'])->name('contact.store');
        Route::post('/contact/import', [ContactController::class, 'import'])->name('import'); // phonebook modal import
        Route::delete('/contact/delete/{contact:id}', [ContactController::class, 'destroy'])->name('contact.delete');
        Route::delete('/contact/delete-all/{id}', [ContactController::class, 'DestroyAll'])->name('deleteAll');
        Route::get('/contact/export/{id}', [ContactController::class, 'export'])->name('exportContact');
        Route::post('/tags', [TagController::class, 'store'])->name('tag.store');
        Route::delete('/tags', [TagController::class, 'destroy'])->name('tag.delete');
        Route::post('fetch-groups', [TagController::class, 'fetchGroups'])->name('fetch.groups');

        // Contact import (CSV/Excel)
        Route::get('/contacts/import', [ContactImportController::class, 'index'])->name('contacts.import');
        Route::post('/contacts/import/preview', [ContactImportController::class, 'preview'])->name('contacts.import.preview');
        Route::post('/contacts/import', [ContactImportController::class, 'import'])->name('contacts.import.store');

        // HSM Templates
        Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
        Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
        Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
        Route::post('/templates/sync', [TemplateController::class, 'sync'])->name('templates.sync');
        Route::get('/templates/{id}', [TemplateController::class, 'show'])->name('templates.show');
        Route::post('/templates/{id}/refresh', [TemplateController::class, 'refreshStatus'])->name('templates.refresh');
        Route::delete('/templates/{id}', [TemplateController::class, 'destroy'])->name('templates.destroy');

        // Campaigns
        Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns')->middleware('permissions');
        Route::get('/campaign/create', [CampaignController::class, 'create'])->name('campaign.create')->middleware('permissions');
        Route::post('/campaign/store', [CampaignController::class, 'store'])->name('campaign.store')->middleware('permissions');
        // 'blast' is the legacy name used by the campaign AJAX forms
        Route::post('/blast', [CampaignController::class, 'store'])->name('blast')->middleware('permissions');
        Route::post('/campaign/pause/{id}', [CampaignController::class, 'pause'])->name('campaign.pause')->middleware('permissions');
        Route::post('/campaign/resume/{id}', [CampaignController::class, 'resume'])->name('campaign.resume')->middleware('permissions');
        Route::delete('/campaign/delete/{id}', [CampaignController::class, 'destroy'])->name('campaign.delete')->middleware('permissions');
        Route::get('/campaign/show/{id}', [CampaignController::class, 'show'])->name('campaign.show')->middleware('permissions');
        Route::delete('/campaign/clear', [CampaignController::class, 'destroyAll'])->name('campaigns.delete.all')->middleware('permissions');
        Route::get('/campaign/blast/{campaign:id}', [BlastController::class, 'index'])->name('campaign.blasts')->middleware('permissions');

        // AJAX template fetching for campaigns
        Route::get('/campaign/templates/{deviceId}', [CampaignController::class, 'getTemplatesForDevice'])->name('campaign.templates');

        // Campaign retargeting & progress
        Route::post('/campaign/{id}/retarget', [CampaignController::class, 'retarget'])->name('campaign.retarget')->middleware('permissions');
        Route::get('/campaign/{id}/progress', [CampaignController::class, 'progress'])->name('campaign.progress');

        // Suppression list (Phase C)
        Route::get('/suppression', [SuppressionController::class, 'index'])->name('suppression.index');
        Route::post('/suppression', [SuppressionController::class, 'store'])->name('suppression.store');
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
        Route::post('/chat/start', [ChatController::class, 'start'])->name('chat.start');
        Route::get('/chat/{id}', [ChatController::class, 'show'])->name('chat.show');
        Route::get('/chat/{id}/messages', [ChatController::class, 'messages'])->name('chat.messages');
        Route::post('/chat/{id}/send', [ChatController::class, 'send'])->name('chat.send');
        Route::post('/chat/{id}/send-template', [ChatController::class, 'sendTemplate'])->name('chat.send.template');

        // Feature 3: Multi-agent live chat
        Route::post('/chat/{id}/typing',   [ChatController::class, 'typing'])->name('chat.typing');
        Route::post('/chat/{id}/notes',    [ChatController::class, 'storeNote'])->name('chat.notes.store');
        Route::post('/chat/{id}/attribute',[ChatController::class, 'saveAttribute'])->name('chat.attribute.save');
        Route::post('/chat/{id}/assign',   [ChatController::class, 'assign'])->name('chat.assign');
        Route::post('/chat/{id}/unassign', [ChatController::class, 'unassign'])->name('chat.unassign');
        Route::post('/chat/{id}/resolve',  [ChatController::class, 'resolve'])->name('chat.resolve');

        // Feature 3: Agent & Team management
        Route::get('/agents',              [\App\Http\Controllers\AgentController::class, 'index'])->name('agents.index');
        Route::post('/agents',             [\App\Http\Controllers\AgentController::class, 'store'])->name('agents.store');
        Route::put('/agents/{id}',         [\App\Http\Controllers\AgentController::class, 'update'])->name('agents.update');
        Route::delete('/agents/{id}',      [\App\Http\Controllers\AgentController::class, 'destroy'])->name('agents.destroy');
        Route::post('/agents/{id}/status', [\App\Http\Controllers\AgentController::class, 'setStatus'])->name('agents.status');
        Route::post('/teams',              [\App\Http\Controllers\AgentController::class, 'storeTeam'])->name('teams.store');
        Route::put('/teams/{id}',          [\App\Http\Controllers\AgentController::class, 'updateTeam'])->name('teams.update');
        Route::delete('/teams/{id}',       [\App\Http\Controllers\AgentController::class, 'destroyTeam'])->name('teams.destroy');

        // Preview / form helpers (still used by autoreply)
        Route::post('/preview-message', [ShowMessageController::class, 'index'])->name('previewMessage');
        Route::get('/form-message/{type}', [ShowMessageController::class, 'getFormByType'])->name('formMessage');
        Route::get('/form-message-edit/{type}', [ShowMessageController::class, 'showEdit'])->name('formMessageEdit');

        // REST API docs
        Route::get('/api-docs', RestapiController::class)->name('rest-api')->middleware('permissions');

        // Analytics
        Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('/analytics/campaign/{id}', [AnalyticsController::class, 'campaignDetail'])->name('analytics.campaign');

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

        Route::get('/permission-denied', fn () => view('theme::pages.permission'))->name('permission.denied');
    });

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
    Route::get('/install', [SettingController::class, 'install'])->name('setting.install_app');
    Route::post('/install', [SettingController::class, 'install'])->name('settings.install_app');
    Route::post('/settings/check_database_connection', [SettingController::class, 'test_database_connection'])->name('connectDB');
    Route::post('/settings/activate_license', [SettingController::class, 'activate_license'])->name('activateLicense');
});
