<?php

namespace App\Ai\Tools;

use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateTransactions implements Tool
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'update_transactions';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Update one or more existing transactions. Provide an array of updates, each with the transaction ID and fields to change. Only provided fields will be updated (partial update). Date MUST be in YYYY-MM-DD format. Category must match an existing category name. Max 50 updates per call. IMPORTANT: First use list_transactions to find the correct IDs before updating.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $updates = $request->all()['updates'] ?? [];

        if (empty($updates)) {
            return json_encode(['ok' => false, 'summary' => 'No updates provided', 'data' => []], JSON_UNESCAPED_UNICODE);
        }

        if (count($updates) > 50) {
            return json_encode(['ok' => false, 'summary' => 'Maximum 50 updates per call', 'data' => []], JSON_UNESCAPED_UNICODE);
        }

        $validator = Validator::make(['updates' => $updates], [
            'updates' => 'required|array|min:1|max:50',
            'updates.*.id' => 'required|integer|min:1',
            'updates.*.type' => 'nullable|in:expense,income',
            'updates.*.amount' => 'nullable|numeric|min:0.01',
            'updates.*.description' => 'nullable|string|max:255',
            'updates.*.transaction_date' => 'nullable|date_format:Y-m-d',
            'updates.*.category' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return json_encode([
                'ok' => false,
                'summary' => 'Validation failed: '.implode('; ', $validator->errors()->all()),
                'data' => [],
            ], JSON_UNESCAPED_UNICODE);
        }

        $updatedIds = [];
        $notFoundIds = [];
        $errors = [];

        DB::transaction(function () use ($updates, &$updatedIds, &$notFoundIds, &$errors) {
            foreach ($updates as $index => $update) {
                $id = (int) $update['id'];

                $transaction = $this->user->transactions()->where('id', $id)->first();

                if (! $transaction) {
                    $notFoundIds[] = $id;

                    continue;
                }

                $data = [];

                if (isset($update['type'])) {
                    $data['type'] = $update['type'];
                }
                if (isset($update['amount'])) {
                    $data['amount'] = (float) $update['amount'];
                }
                if (isset($update['description'])) {
                    $data['description'] = $update['description'];
                }
                if (isset($update['transaction_date'])) {
                    $data['transaction_date'] = $update['transaction_date'];
                }
                if (isset($update['category'])) {
                    $categoryName = $update['category'];
                    $type = $data['type'] ?? $transaction->type;

                    $category = Category::availableFor($this->user)->where('type', $type)
                        ->where(function ($q) use ($categoryName) {
                            $q->where('name', $categoryName)->orWhere('name_en', $categoryName);
                        })->first();

                    if (! $category) {
                        $errors[] = 'Update #'.($index + 1)." (ID {$id}): Category '{$categoryName}' not found for type '{$type}'";

                        return;
                    }
                    $data['category_id'] = $category->id;
                }

                if (! empty($data)) {
                    $transaction->update($data);
                }

                $updatedIds[] = $id;
            }
        });

        $summaryParts = [];
        if (! empty($updatedIds)) {
            $summaryParts[] = 'Updated '.count($updatedIds).' transaction(s)';
        }
        if (! empty($notFoundIds)) {
            $summaryParts[] = count($notFoundIds).' not found (not owned by user)';
        }
        if (! empty($errors)) {
            $summaryParts[] = 'Errors: '.implode('; ', $errors);
        }

        return json_encode([
            'ok' => empty($errors),
            'summary' => implode('; ', $summaryParts) ?: 'No changes made',
            'data' => [
                'updated' => $updatedIds,
                'not_found' => $notFoundIds,
                'errors' => $errors,
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
            'updates' => $schema->array()
                ->min(1)
                ->max(50)
                ->items($schema->object([
                    'id' => $schema->integer()->min(1)->required()->description('Transaction ID (must belong to current user)'),
                    'type' => $schema->string()->enum(['expense', 'income'])->nullable()->description('Transaction type'),
                    'amount' => $schema->number()->min(0.01)->nullable()->description('Amount in SAR'),
                    'description' => $schema->string()->max(255)->nullable()->description('Description'),
                    'transaction_date' => $schema->string()->format('date')->nullable()->description('Date in YYYY-MM-DD format. Relative dates like "today" are NOT allowed.'),
                    'category' => $schema->string()->nullable()->description('Category name (must exist for user or globally)'),
                ]))
                ->required(),
        ];
    }
}
