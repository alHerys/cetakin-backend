<?php

namespace App\Http\Controllers\Review;

use App\Http\Controllers\Controller;
use App\Services\TransactionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(private TransactionService $transactionService) {}

    public function shopReviews(string $id): JsonResponse
    {
        $reviews = $this->transactionService->getShopReviews($id);

        return $this->paginated($reviews, 'Reviews retrieved successfully.');
    }

    public function myReviews(): JsonResponse
    {
        $reviews = $this->transactionService->getUserReviews(auth()->user());

        return $this->paginated($reviews, 'Reviews retrieved successfully.');
    }

    public function myReview(string $id): JsonResponse
    {
        $review = $this->transactionService->getUserReview(auth()->user(), $id);

        return $this->success($review, 'Review retrieved successfully.');
    }

    public function storePrintReview(string $id): JsonResponse
    {
        request()->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['sometimes', 'nullable', 'string'],
        ]);

        $review = $this->transactionService->submitPrintReview(
            auth()->user(),
            $id,
            request()->only('rating', 'comment')
        );

        return $this->success($review, 'Review submitted successfully.', 201);
    }

    public function storeAtkReview(string $id): JsonResponse
    {
        request()->validate([
            'rating'  => ['required', 'integer', 'between:1,5'],
            'comment' => ['sometimes', 'nullable', 'string'],
        ]);

        $review = $this->transactionService->submitAtkReview(
            auth()->user(),
            $id,
            request()->only('rating', 'comment')
        );

        return $this->success($review, 'Review submitted successfully.', 201);
    }
}
