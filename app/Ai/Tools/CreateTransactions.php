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

class CreateTransactions implements Tool
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'create_transactions';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Create one or more transactions (expenses or income). Provide an array of transactions with required fields. Date MUST be in YYYY-MM-DD format (no relative dates like "today"). Category must match an existing category name for the user or global categories. Amount in SAR with up to 2 decimals. Max 50 transactions per call.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $transactions = $request->all()['transactions'] ?? [];

        if (empty($transactions)) {
            return json_encode(['ok' => false, 'summary' => 'No transactions provided', 'data' => []], JSON_UNESCAPED_UNICODE);
        }

        if (count($transactions) > 50) {
            return json_encode(['ok' => false, 'summary' => 'Maximum 50 transactions per call', 'data' => []], JSON_UNESCAPED_UNICODE);
        }

        $validator = Validator::make(['transactions' => $transactions], [
            'transactions' => 'required|array|min:1|max:50',
            'transactions.*.type' => 'required|in:expense,income',
            'transactions.*.amount' => 'required|numeric|min:0.01',
            'transactions.*.description' => 'nullable|string|max:255',
            'transactions.*.transaction_date' => 'required|date_format:Y-m-d',
            'transactions.*.category' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return json_encode([
                'ok' => false,
                'summary' => 'Validation failed: '.implode('; ', $validator->errors()->all()),
                'data' => [],
            ], JSON_UNESCAPED_UNICODE);
        }

        $createdIds = [];
        $errors = [];

        DB::transaction(function () use ($transactions, &$createdIds, &$errors) {
            foreach ($transactions as $index => $tx) {
                $type = $tx['type'];
                $amount = (float) $tx['amount'];
                $description = $tx['description'] ?? null;
                $date = $tx['transaction_date'];
                $categoryName = $tx['category'] ?? null;

                $categoryId = null;
                if ($categoryName) {
                    $category = Category::availableFor($this->user)->where('type', $type)
                        ->where(function ($q) use ($categoryName) {
                            $q->where('name', $categoryName)->orWhere('name_en', $categoryName);
                        })->first();

                    if (! $category) {
                        $errors[] = 'Transaction #'.($index + 1).": Category '{$categoryName}' not found for type '{$type}'";

                        return;
                    }
                    $categoryId = $category->id;
                }

                $created = $this->user->transactions()->create([
                    'type' => $type,
                    'amount' => $amount,
                    'description' => $description,
                    'transaction_date' => $date,
                    'category_id' => $categoryId,
                ]);

                $createdIds[] = $created->id;
            }
        });

        if (! empty($errors)) {
            return json_encode([
                'ok' => false,
                'summary' => 'Some transactions failed: '.implode('; ', $errors),
                'data' => ['created_ids' => $createdIds, 'errors' => $errors],
            ], JSON_UNESCAPED_UNICODE);
        }

        return json_encode([
            'ok' => true,
            'summary' => 'Created '.count($createdIds).' transaction(s)',
            'data' => ['created_ids' => $createdIds],
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
            'transactions' => $schema->array()
                ->min(1)
                ->max(50)
                ->items($schema->object([
                    'type' => $schema->string()->enum(['expense', 'income'])->required()->description('Transaction type'),
                    'amount' => $schema->number()->min(0.01)->required()->description('Amount in SAR'),
                    'description' => $schema->string()->max(255)->nullable()->description('Description (optional, defaults to "Quick transaction" or "معاملة سريعة")'),
                    'transaction_date' => $schema->string()->format('date')->required()->description('Date in YYYY-MM-DD format. Relative dates like "today" are NOT allowed.'),
                    'category' => $schema->string()->nullable()->description('Category name (must exist for user or globally). Optional - will auto-categorize if omitted.'),
                ]))
                ->required(),
        ];
    }
}
