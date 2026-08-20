<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DeleteTransactions implements Tool
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'delete_transactions';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Delete one or more transactions by ID. Only transactions owned by the current user can be deleted. Max 50 IDs per call. IMPORTANT: First use list_transactions to find the correct IDs before deleting. There is NO "delete all" — explicit IDs are required.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $ids = $request->all()['ids'] ?? [];

        if (empty($ids)) {
            return json_encode(['ok' => false, 'summary' => 'No IDs provided', 'data' => []], JSON_UNESCAPED_UNICODE);
        }

        if (count($ids) > 50) {
            return json_encode(['ok' => false, 'summary' => 'Maximum 50 IDs per call', 'data' => []], JSON_UNESCAPED_UNICODE);
        }

        $validator = Validator::make(['ids' => $ids], [
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return json_encode([
                'ok' => false,
                'summary' => 'Validation failed: '.implode('; ', $validator->errors()->all()),
                'data' => [],
            ], JSON_UNESCAPED_UNICODE);
        }

        $deletedIds = [];
        $notFoundIds = [];

        DB::transaction(function () use ($ids, &$deletedIds, &$notFoundIds) {
            $transactions = $this->user->transactions()->whereIn('id', $ids)->get();

            foreach ($transactions as $tx) {
                $tx->delete();
                $deletedIds[] = $tx->id;
            }

            $foundIds = $transactions->pluck('id')->toArray();
            $notFoundIds = array_diff($ids, $foundIds);
        });

        $summaryParts = [];
        if (! empty($deletedIds)) {
            $summaryParts[] = 'Deleted '.count($deletedIds).' transaction(s)';
        }
        if (! empty($notFoundIds)) {
            $summaryParts[] = count($notFoundIds).' not found (not owned by user)';
        }

        return json_encode([
            'ok' => true,
            'summary' => implode('; ', $summaryParts) ?: 'No transactions deleted',
            'data' => [
                'deleted_ids' => $deletedIds,
                'not_found' => array_values($notFoundIds),
            ],
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the tool's schema definition.
     *
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ids' => $schema->array()
                ->min(1)
                ->max(50)
                ->items($schema->integer()->min(1))
                ->required()
                ->description('Array of transaction IDs to delete (must belong to current user)'),
        ];
    }
}
