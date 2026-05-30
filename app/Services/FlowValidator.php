<?php

namespace App\Services;

class FlowValidator
{
    private const TERMINAL_TYPES = ['end_flow', 'human_handoff'];
    private const TRIGGER_TYPES  = ['trigger_keyword', 'trigger_all', 'trigger_referral', 'trigger_api'];

    /**
     * Validate Drawflow JSON structure.
     * Returns: ['valid' => bool, 'errors' => [], 'warnings' => []]
     */
    public function validate(array $flowJson): array
    {
        $errors   = [];
        $warnings = [];

        $nodes = $flowJson['drawflow']['Home']['data'] ?? null;

        if ($nodes === null) {
            return ['valid' => false, 'errors' => ['Invalid flow structure: missing drawflow.Home.data'], 'warnings' => []];
        }

        if (empty($nodes)) {
            return ['valid' => false, 'errors' => ['Flow has no nodes. Add at least a trigger and one action.'], 'warnings' => []];
        }

        // ── 1. Exactly one trigger node ───────────────────────────────────
        $triggerNodes = array_filter($nodes, fn ($n) => in_array($n['name'] ?? '', self::TRIGGER_TYPES));
        if (count($triggerNodes) === 0) {
            $errors[] = 'Flow must have exactly one trigger node (Keyword, Ad Click, API, or All Messages).';
        } elseif (count($triggerNodes) > 1) {
            $warnings[] = 'Flow has multiple trigger nodes. Only the first one will be used.';
        }

        // ── 2. Trigger node must have at least one outgoing connection ────
        foreach ($triggerNodes as $nodeId => $node) {
            $outputs = $node['outputs'] ?? [];
            $hasConn = false;
            foreach ($outputs as $out) {
                if (!empty($out['connections'])) { $hasConn = true; break; }
            }
            if (!$hasConn) {
                $errors[] = "Trigger node has no connections. Connect it to an action node.";
            }
        }

        // ── 3. Build reachability set from each trigger ───────────────────
        $allNodeIds   = array_keys($nodes);
        $reachable    = [];

        foreach ($triggerNodes as $triggerNodeId => $triggerNode) {
            $visited = [];
            $this->dfs((string) $triggerNodeId, $nodes, $visited);
            $reachable = array_merge($reachable, array_keys($visited));
        }

        $orphaned = array_diff($allNodeIds, $reachable);
        if (!empty($orphaned)) {
            $warnings[] = count($orphaned) . ' node(s) are not reachable from the trigger and will never execute.';
        }

        // ── 4. Condition nodes should have both outputs connected ─────────
        foreach ($nodes as $nodeId => $node) {
            if (($node['name'] ?? '') === 'condition') {
                $yes = !empty($node['outputs']['output_1']['connections'] ?? []);
                $no  = !empty($node['outputs']['output_2']['connections'] ?? []);
                if (!$yes) $warnings[] = "A condition node's 'Yes' branch is not connected.";
                if (!$no)  $warnings[] = "A condition node's 'No' branch is not connected.";
            }
        }

        // ── 5. Cycle detection — every path must reach a terminal ─────────
        foreach ($triggerNodes as $triggerNodeId => $triggerNode) {
            if ($this->hasCycle((string) $triggerNodeId, $nodes)) {
                $warnings[] = 'The flow may contain a loop. Ensure every path eventually reaches End Flow or Human Handoff.';
                break;
            }
        }

        return [
            'valid'    => empty($errors),
            'errors'   => array_values($errors),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /** Depth-first traversal to collect reachable node IDs. */
    private function dfs(string $nodeId, array $nodes, array &$visited, int $depth = 0): void
    {
        if ($depth > 100 || isset($visited[$nodeId])) return;
        $visited[$nodeId] = true;

        $node = $nodes[$nodeId] ?? null;
        if (!$node) return;

        foreach ($node['outputs'] ?? [] as $output) {
            foreach ($output['connections'] ?? [] as $conn) {
                $next = (string) ($conn['node'] ?? '');
                if ($next) $this->dfs($next, $nodes, $visited, $depth + 1);
            }
        }
    }

    /** Simple cycle detection using DFS with a recursion stack. */
    private function hasCycle(string $startId, array $nodes): bool
    {
        $visited = [];
        $stack   = [];
        return $this->dfsCycle($startId, $nodes, $visited, $stack);
    }

    private function dfsCycle(string $nodeId, array $nodes, array &$visited, array &$stack): bool
    {
        if (isset($stack[$nodeId])) return true;
        if (isset($visited[$nodeId])) return false;

        $visited[$nodeId] = true;
        $stack[$nodeId]   = true;

        $node = $nodes[$nodeId] ?? null;
        if ($node) {
            foreach ($node['outputs'] ?? [] as $output) {
                foreach ($output['connections'] ?? [] as $conn) {
                    $next = (string) ($conn['node'] ?? '');
                    if ($next && $this->dfsCycle($next, $nodes, $visited, $stack)) {
                        return true;
                    }
                }
            }
        }

        unset($stack[$nodeId]);
        return false;
    }
}
