<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SegmentEngine
{
    /**
     * Resolve a segment's rules into a collection of matching contacts.
     */
    public function resolve(Segment $segment): Collection
    {
        return $this->buildQuery($segment)->get();
    }

    /**
     * Return the query builder (so callers can further chain or paginate).
     */
    public function buildQuery(Segment $segment): Builder
    {
        $rules    = $segment->rules ?? ['operator' => 'AND', 'conditions' => []];
        $operator = strtoupper($rules['operator'] ?? 'AND') === 'OR' ? 'OR' : 'AND';

        $query = Contact::where('contacts.user_id', $segment->user_id);

        foreach ($rules['conditions'] ?? [] as $condition) {
            $method = $operator === 'OR' ? 'orWhere' : 'where';
            $this->applyCondition($query, $condition, $method);
        }

        return $query;
    }

    private function applyCondition(Builder $query, array $condition, string $method): void
    {
        $field = $condition['field'] ?? '';
        $op    = $condition['op'] ?? 'equals';
        $value = $condition['value'] ?? '';

        switch ($field) {
            case 'name':
                $this->applyStringOp($query, 'contacts.name', $op, $value, $method);
                break;

            case 'number':
                $this->applyStringOp($query, 'contacts.number', $op, $value, $method);
                break;

            case 'tag_name':
                $query->{$method . 'Has'}('tag', function (Builder $q) use ($op, $value) {
                    $this->applyStringOp($q, 'name', $op, $value, 'where');
                });
                break;

            case 'tag_id':
                $query->{$method}('contacts.tag_id', '=', (int) $value);
                break;

            case 'created_at':
                [$sqlOp, $resolvedValue] = $this->resolveDateOp($op, $value);
                $query->{$method}('contacts.created_at', $sqlOp, $resolvedValue);
                break;

            // ── Behavioral delivery rules ──────────────────────────────────
            case 'delivery_status':
                // value = "sent" | "delivered" | "read" | "failed" | "not_delivered" | "not_read"
                $this->applyDeliveryRule($query, $op, $value, $condition['campaign_id'] ?? null, $method);
                break;
        }
    }

    /**
     * Applies a delivery-status sub-query.
     * Contacts are matched by phone number against blasts.receiver.
     */
    private function applyDeliveryRule(Builder $query, string $op, string $value, ?int $campaignId, string $method): void
    {
        $negated = str_starts_with($value, 'not_');
        $status  = $negated ? substr($value, 4) : $value;

        $rankMap = ['sent' => 1, 'delivered' => 2, 'read' => 3];
        $rank    = $rankMap[$status] ?? 1;

        // Build a subquery: SELECT receiver FROM blasts WHERE reached the required rank
        $blastsSub = DB::table('blasts')
            ->select('blasts.receiver')
            ->where('blasts.status', 'success')
            ->when($campaignId, fn ($q) => $q->where('blasts.campaign_id', $campaignId))
            ->joinSub(
                DB::table('message_delivery_events')
                    ->selectRaw('blast_id, MAX(CASE status WHEN "read" THEN 3 WHEN "delivered" THEN 2 WHEN "sent" THEN 1 ELSE 0 END) as max_rank')
                    ->groupBy('blast_id'),
                'dlr',
                'dlr.blast_id', '=', 'blasts.id'
            )
            ->where('dlr.max_rank', '>=', $rank);

        if ($negated) {
            $query->{$method . 'NotIn'}('contacts.number', $blastsSub);
        } else {
            $query->{$method . 'In'}('contacts.number', $blastsSub);
        }
    }

    private function applyStringOp(Builder $query, string $column, string $op, string $value, string $method): void
    {
        match ($op) {
            'equals'       => $query->{$method}($column, '=', $value),
            'not_equals'   => $query->{$method}($column, '!=', $value),
            'contains'     => $query->{$method}($column, 'LIKE', "%{$value}%"),
            'starts_with'  => $query->{$method}($column, 'LIKE', "{$value}%"),
            'ends_with'    => $query->{$method}($column, 'LIKE', "%{$value}"),
            default        => $query->{$method}($column, 'LIKE', "%{$value}%"),
        };
    }

    private function resolveDateOp(string $op, string $value): array
    {
        $date = match (true) {
            str_ends_with($value, '_days') => now()->subDays((int) $value)->toDateTimeString(),
            default                        => $value,
        };

        $sqlOp = match ($op) {
            'before', 'older_than' => '<',
            'after',  'newer_than' => '>',
            default                => '<',
        };

        return [$sqlOp, $date];
    }
}
