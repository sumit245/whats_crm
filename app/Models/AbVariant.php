<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbVariant extends Model
{
    protected $table = 'ab_variants';

    protected $fillable = [
        'ab_test_id', 'label', 'message_type', 'message_body', 'template_id', 'campaign_id',
    ];

    public function test()
    {
        return $this->belongsTo(AbTest::class, 'ab_test_id');
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function template()
    {
        return $this->belongsTo(WabaTemplate::class, 'template_id');
    }

    /** Compute live metrics from the variant's campaign blasts */
    public function metrics(): array
    {
        if (!$this->campaign_id) {
            return ['total' => 0, 'sent' => 0, 'delivered' => 0, 'read' => 0, 'clicks' => 0,
                    'delivery_rate' => 0, 'read_rate' => 0, 'click_rate' => 0];
        }

        $blasts = \DB::table('blasts')->where('campaign_id', $this->campaign_id);
        $total  = $blasts->count();
        $sent   = (clone $blasts)->where('status', 'success')->count();

        if ($total === 0) {
            return ['total' => 0, 'sent' => 0, 'delivered' => 0, 'read' => 0, 'clicks' => 0,
                    'delivery_rate' => 0, 'read_rate' => 0, 'click_rate' => 0];
        }

        $blastIds = \DB::table('blasts')->where('campaign_id', $this->campaign_id)->pluck('id');

        $delivered = \DB::table('message_delivery_events')
            ->whereIn('blast_id', $blastIds)
            ->whereIn('status', ['delivered', 'read'])
            ->distinct('blast_id')->count('blast_id');

        $read = \DB::table('message_delivery_events')
            ->whereIn('blast_id', $blastIds)
            ->where('status', 'read')
            ->distinct('blast_id')->count('blast_id');

        $clicks = \DB::table('link_clicks')
            ->join('tracked_links', 'tracked_links.id', '=', 'link_clicks.tracked_link_id')
            ->where('tracked_links.campaign_id', $this->campaign_id)
            ->distinct('link_clicks.contact_number')->count('link_clicks.contact_number');

        return [
            'total'         => $total,
            'sent'          => $sent,
            'delivered'     => $delivered,
            'read'          => $read,
            'clicks'        => $clicks,
            'delivery_rate' => $total > 0 ? round($delivered / $total * 100, 1) : 0,
            'read_rate'     => $total > 0 ? round($read     / $total * 100, 1) : 0,
            'click_rate'    => $total > 0 ? round($clicks   / $total * 100, 1) : 0,
        ];
    }
}
