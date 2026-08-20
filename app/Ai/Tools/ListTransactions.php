<?php

namespace App\Ai\Tools;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ListTransactions implements Tool
{
    public function __construct(
        protected User $user,
    ) {}

    /**
     * Get the name of the tool.
     */
    public function name(): string
    {
        return 'list_transactions';
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'List user transactions with optional filters. Returns transactions with id, date, type, category, amount, description. Also returns total_count and sum_amount. Use this tool to answer questions about spending, income, or to find specific transactions before updating/deleting.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $arguments = $request->all();

        $dateFrom = $arguments['date_from'] ?? null;
        $dateTo = $arguments['date_to'] ?? null;
        $type = $arguments['type'] ?? null;
        $category = $arguments['category'] ?? null;
        $minAmount = $arguments['min_amount'] ?? null;
        $maxAmount = $arguments['max_amount'] ?? null;
        $search = $arguments['search'] ?? null;
        $sort = $arguments['sort'] ?? 'date_desc';
        $limit = min(max((int) ($arguments['limit'] ?? 25), 1), 100);

        $query = $this->user->transactions()->with('category');

        if ($dateFrom) {
            $query->whereDate('transaction_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('transaction_date', '<=', $dateTo);
        }
        if ($type) {
            if (! in_array($type, ['expense', 'income'], true)) {
                return json_encode(['ok' => false, 'summary' => "Invalid type: {$type}. Must be 'expense' or 'income'.", 'data' => []], JSON_UNESCAPED_UNICODE);
            }
            $query->where('type', $type);
        }
        if ($category) {
            $categories = is_array($category) ? $category : [$category];
            $categoryIds = Category::availableFor($this->user)->where(function ($q) use ($categories) {
                $q->whereIn('name', $categories)->orWhereIn('name_en', $categories);
            })->pluck('id');
            if ($categoryIds->isEmpty()) {
                return json_encode(['ok' => false, 'summary' => 'No matching categories found for: '.implode(', ', $categories), 'data' => []], JSON_UNESCAPED_UNICODE);
            }
            $query->whereIn('category_id', $categoryIds);
        }
        if ($minAmount !== null) {
            if (! is_numeric($minAmount)) {
                return json_encode(['ok' => false, 'summary' => 'min_amount must be a number', 'data' => []], JSON_UNESCAPED_UNICODE);
            }
            $query->where('amount', '>=', $minAmount);
        }
        if ($maxAmount !== null) {
            if (! is_numeric($maxAmount)) {
                return json_encode(['ok' => false, 'summary' => 'max_amount must be a number', 'data' => []], JSON_UNESCAPED_UNICODE);
            }
            $query->where('amount', '<=', $maxAmount);
        }
        if ($search) {
            $query->where('description', 'like', "%{$search}%");
        }

        $sortColumn = 'transaction_date';
        $sortDirection = 'desc';
        match ($sort) {
            'date_asc' => [$sortColumn, $sortDirection] = ['transaction_date', 'asc'],
            'amount_desc' => [$sortColumn, $sortDirection] = ['amount', 'desc'],
            'amount_asc' => [$sortColumn, $sortDirection] = ['amount', 'asc'],
            default => [$sortColumn, $sortDirection] = ['transaction_date', 'desc'],
        };
        $query->orderBy($sortColumn, $sortDirection)->orderBy('created_at', $sortDirection);

        $totalCount = (clone $query)->count();
        $sumAmount = (clone $query)->sum('amount');

        $transactions = $query->limit($limit)->get()->map(function (Transaction $t) {
            return [
                'id' => $t->id,
                'date' => $t->transaction_date->format('Y-m-d'),
                'type' => $t->type,
                'category' => $t->category?->name,
                'category_en' => $t->category?->name_en,
                'category_color' => $t->category?->color,
                'amount' => (float) $t->amount,
                'description' => $t->description,
            ];
        })->values()->toArray();

        $truncated = $totalCount > $limit;

        return json_encode([
            'ok' => true,
            'summary' => "Found {$totalCount} transaction(s)".($truncated ? " (showing first {$limit})" : '').'. Total amount: '.number_format($sumAmount, 2).' SAR',
            'data' => [
                'transactions' => $transactions,
                'total_count' => $totalCount,
                'sum_amount' => (float) $sumAmount,
                'truncated' => $truncated,
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
            'date_from' => $schema->string()->format('date')->description('Start date in YYYY-MM-DD format'),
            'date_to' => $schema->string()->format('date')->description('End date in YYYY-MM-DD format'),
            'type' => $schema->string()->enum(['expense', 'income'])->description('Transaction type filter'),
            'category' => $schema->anyOf([
                $schema->string(),
                $schema->array()->items($schema->string()),
            ])->description('Category name(s) filter'),
            'min_amount' => $schema->number()->description('Minimum amount filter'),
            'max_amount' => $schema->number()->description('Maximum amount filter'),
            'search' => $schema->string()->description('Search in description'),
            'sort' => $schema->string()->enum(['date_desc', 'date_asc', 'amount_desc', 'amount_asc'])->description('Sort order'),
            'limit' => $schema->integer()->min(1)->max(100)->description('Max results (default 25, max 100)'),
        ];
    }
}
