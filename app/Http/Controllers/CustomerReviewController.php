<?php

namespace App\Http\Controllers;

use App\Enums\CustomerReviewQuestionCode;
use App\Models\Customer;
use App\Models\CustomerReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;

class CustomerReviewController extends Controller
{
    public function getReview(Request $request, Customer $customer): \Illuminate\Http\JsonResponse
    {
        if (!$this->canManageCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $review = CustomerReview::where('id_customer', $customer->id_customer)->first();

        if (!$review) {
            $reviewAnswers = collect(CustomerReviewQuestionCode::cases())
                ->map(fn(CustomerReviewQuestionCode $code) => [
                    'question_code' => $code->value,
                    'question'      => $code->question(),
                    'answer'        => null,
                    'order'         => $code->order(),
                    'field_type'    => $code->fieldType(),
                ])
                ->sortBy('order')
                ->values()
                ->all();

            return response()->json([
                'reviewed_at'        => null,
                'review_answers'     => $reviewAnswers,
                'review_attachments' => [],
            ]);
        }

        $reviewAnswers = collect($review->review_answers)
            ->map(function (array $item) {
                $code = CustomerReviewQuestionCode::tryFrom($item['question_code'] ?? '');

                if (!$code) {
                    return null;
                }

                return [
                    'question_code' => $code->value,
                    'question'      => $code->question(),
                    'answer'        => $item['answer'] ?? null,
                    'order'         => $code->order(),
                    'field_type'    => $code->fieldType(),
                ];
            })
            ->filter()
            ->sortBy('order')
            ->values()
            ->all();

        return response()->json([
            'reviewed_at'        => $review->reviewed_at,
            'review_answers'     => $reviewAnswers,
            'review_attachments' => $review->review_attachments,
        ]);
    }

    public function saveReview(Request $request, Customer $customer): \Illuminate\Http\JsonResponse
    {
        if (!$this->canManageCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($this->isLocked($customer)) {
            return response()->json(['message' => 'Data terkunci, verifikasi sedang berjalan.'], 409);
        }

        $data = $request->validate([
            'review_answers'                  => 'required|array',
            'review_answers.*.question_code'  => ['required', new Enum(CustomerReviewQuestionCode::class)],
            'review_answers.*.answer'         => 'nullable|string',
        ]);

        $reviewAnswers = collect($data['review_answers'])
            ->map(function (array $item) {
                $code = CustomerReviewQuestionCode::from($item['question_code']);

                return [
                    'question_code' => $code->value,
                    'question'      => $code->question(),
                    'answer'        => $item['answer'] ?? null,
                    'order'         => $code->order(),
                    'field_type'    => $code->fieldType(),
                ];
            })
            ->sortBy('order')
            ->values()
            ->all();

        $review = CustomerReview::updateOrCreate(
            ['id_customer' => $customer->id_customer],
            [
                'review_answers' => $reviewAnswers,
                'reviewed_at'    => now(),
            ]
        );

        return response()->json([
            'review_answers' => $review->review_answers,
            'reviewed_at'    => $review->reviewed_at,
        ]);
    }

    public function uploadReviewAttachment(Request $request, Customer $customer): \Illuminate\Http\JsonResponse
    {
        if (!$this->canManageCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($this->isLocked($customer)) {
            return response()->json(['message' => 'Data terkunci, verifikasi sedang berjalan.'], 409);
        }

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
        ]);

        $review = CustomerReview::firstOrCreate(['id_customer' => $customer->id_customer]);

        $file = $request->file('file');
        $path = $file->store("customer_verifications/{$customer->id_customer}/review", 'public');
        $originalName = $file->getClientOriginalName();

        $attachments = $review->review_attachments ?? [];
        $attachments[] = [
            'path'          => $path,
            'url'           => Storage::disk('public')->url($path),
            'original_name' => $originalName,
        ];

        $review->update(['review_attachments' => $attachments]);

        return response()->json([
            'index'         => array_key_last($attachments),
            'path'          => $path,
            'url'           => Storage::disk('public')->url($path),
            'original_name' => $originalName,
        ]);
    }

    public function deleteReviewAttachment(Request $request, Customer $customer, int $no): \Illuminate\Http\JsonResponse
    {
        if (!$this->canManageCustomer($request, $customer)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($this->isLocked($customer)) {
            return response()->json(['message' => 'Data terkunci, verifikasi sedang berjalan.'], 409);
        }

        $review = CustomerReview::where('id_customer', $customer->id_customer)->firstOrFail();

        $attachments = $review->review_attachments ?? [];

        if (!isset($attachments[$no])) {
            return response()->json(['message' => 'Attachment tidak ditemukan.'], 404);
        }

        Storage::disk('public')->delete($attachments[$no]['path']);

        array_splice($attachments, $no, 1);

        $review->update(['review_attachments' => array_values($attachments)]);

        return response()->json(['ok' => true]);
    }

    private function canManageCustomer(Request $request, Customer $customer): bool
    {
        $user = $request->user();

        return $user->can('customer.manage') && ($customer->id_user === $user->id || $user->can('customer.viewAny'));
    }

    private function isLocked(Customer $customer): bool
    {
        return $customer->isUnderReview();
    }
}
