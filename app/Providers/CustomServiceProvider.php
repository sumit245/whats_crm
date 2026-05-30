<?php

namespace App\Providers;

use App\Services\Impl\MetaCloudApiService;
use App\Services\Impl\SimpleMessageService;
use App\Services\MessageService;
use App\Services\MetaTemplateService;
use App\Services\WhatsappService;
use Illuminate\Support\ServiceProvider;

class CustomServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(WhatsappService::class, MetaCloudApiService::class);
        $this->app->bind(MessageService::class, SimpleMessageService::class);
        $this->app->singleton(MetaTemplateService::class, MetaTemplateService::class);
    }

    public function boot()
    {
    }
}
