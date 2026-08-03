<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebhookSource extends Model
{
    protected $fillable = [
        'user_id', 'name', 'platform', 'token', 'secret',
        'phone_field', 'name_field', 'active',
    ];

    protected $casts = ['active' => 'boolean'];

    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function triggers()
    {
        return $this->hasMany(WebhookTrigger::class);
    }

    public function logs()
    {
        return $this->hasMany(WebhookLog::class)->latest('created_at');
    }

    public function inboundUrl(): string
    {
        return url('/wh/' . $this->token);
    }

    public static function platformLabel(string $platform): string
    {
        return match($platform) {
            'shopify'     => 'Shopify',
            'woocommerce' => 'WooCommerce',
            'razorpay'    => 'Razorpay',
            'generic'     => 'Generic / Custom',
            default       => ucfirst($platform),
        };
    }

    public static function platformPhoneDefault(string $platform): string
    {
        return match($platform) {
            'shopify'     => 'customer.phone',
            'woocommerce' => 'billing.phone',
            'razorpay'    => 'payload.payment.entity.contact',
            default       => 'phone',
        };
    }
}
