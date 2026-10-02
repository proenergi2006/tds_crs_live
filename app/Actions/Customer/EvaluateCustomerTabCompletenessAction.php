<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Enums\CustomerPaymentTerm;
use App\Enums\CustomerReviewQuestionCode;
use App\Enums\CustomerTabStatus;
use App\Enums\CustomerVerificationStatus;
use App\Enums\DocumentApprovalStatus;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerLcr;
use App\Models\CustomerReview;

class EvaluateCustomerTabCompletenessAction
{
    public function execute(Customer $customer): array
    {
        return $this->toBooleans($this->evaluate($customer));
    }

    public function statuses(Customer $customer): array
    {
        return $this->toValues($this->evaluate($customer));
    }

    public function summary(Customer $customer): array
    {
        $statuses = $this->evaluate($customer);

        return [
            'tab_completeness' => $this->toBooleans($statuses),
            'tab_status' => $this->toValues($statuses),
        ];
    }

    private function evaluate(Customer $customer): array
    {
        return [
            'data_customer' => $this->dataCustomerStatus($customer),
            'review' => $this->reviewStatus($customer),
            'credit' => $this->creditStatus($customer),
            'lcr' => $this->lcrStatus($customer),
        ];
    }

    private function toBooleans(array $statuses): array
    {
        return collect($statuses)
            ->map(fn (CustomerTabStatus $status) => $status === CustomerTabStatus::Complete)
            ->all();
    }

    private function toValues(array $statuses): array
    {
        return collect($statuses)
            ->map(fn (CustomerTabStatus $status) => $status->value)
            ->all();
    }

    private function dataCustomerStatus(Customer $customer): CustomerTabStatus
    {
        $corporateFilled = collect([
            $customer->company_name,
            $customer->phone,
            $customer->email,
            $customer->business_type,
            $customer->ownership_type,
            $customer->inco_terms,
        ])->every(fn ($v) => filled($v));

        $headOffice = $customer->addresses->firstWhere('address_type', CustomerAddressType::HeadOffice);
        $npwpAddress = $customer->addresses->firstWhere('address_type', CustomerAddressType::RegisteredNpwp);
        $addressFilled = $this->addressComplete($headOffice) && $this->addressComplete($npwpAddress);

        $pay = $customer->payment;
        $paymentFilled = $pay && collect([
            $pay->payment_schedule,
            $pay->payment_method,
            $pay->payment_term,
            $pay->bank_name,
            $pay->account_number,
            $pay->bank_address,
        ])->every(fn ($v) => filled($v))
            && ($pay->payment_term !== CustomerPaymentTerm::Credit || (filled($pay->payment_term_days) && filled($pay->payment_term_basis)));

        $contactFilled = $customer->contacts->isNotEmpty();

        $documentsFilled = collect(['nib', 'npwp'])->every(function (string $code) use ($customer) {
            $doc = $customer->documents->first(fn ($d) => $d->documentType?->code === $code);
            return $doc && filled($doc->file_path) && filled($doc->document_number);
        });

        $complete = $corporateFilled && $addressFilled && $paymentFilled && $contactFilled && $documentsFilled;

        return $complete ? CustomerTabStatus::Complete : CustomerTabStatus::Empty;
    }

    private function addressComplete(?CustomerAddress $address): bool
    {
        if (!$address) {
            return false;
        }

        return collect([
            $address->address_line,
            $address->province_id,
            $address->regency_id,
            $address->district_id,
            $address->village_id,
            $address->postal_code,
        ])->every(fn ($v) => filled($v));
    }

    private function reviewStatus(Customer $customer): CustomerTabStatus
    {
        $review = CustomerReview::where('id_customer', $customer->id_customer)->first();
        if (!$review) {
            return CustomerTabStatus::Empty;
        }

        $answeredCodes = collect($review->review_answers ?? [])
            ->filter(fn (array $item) => isset($item['answer']) && $item['answer'] !== '')
            ->pluck('question_code');

        $complete = collect(CustomerReviewQuestionCode::cases())
            ->every(fn (CustomerReviewQuestionCode $code) => $answeredCodes->contains($code->value));

        return $complete ? CustomerTabStatus::Complete : CustomerTabStatus::Empty;
    }

    private function creditStatus(Customer $customer): CustomerTabStatus
    {
        $request = $customer->creditRequest;

        $dataFilled = $request
            && $request->requested_limit > 0
            && $request->requested_top !== null
            && $request->requested_qty > 0
            && $request->product_category !== null
            && $request->hasFinancialReview();

        if (!$dataFilled) {
            return CustomerTabStatus::Empty;
        }

        return match ($customer->latestVerification?->status) {
            CustomerVerificationStatus::Approved => CustomerTabStatus::Complete,
            CustomerVerificationStatus::Rejected => CustomerTabStatus::Rejected,
            default => CustomerTabStatus::InProgress,
        };
    }

    private function lcrStatus(Customer $customer): CustomerTabStatus
    {
        $sites = $customer->lcr()->with('latestDocumentApproval')->get();

        if ($sites->isEmpty()) {
            return CustomerTabStatus::Empty;
        }

        $anyRejected = $sites->contains(
            fn (CustomerLcr $site) => $site->latestDocumentApproval?->status === DocumentApprovalStatus::Rejected
        );

        if ($anyRejected) {
            return CustomerTabStatus::Rejected;
        }

        $allApproved = $sites->every(
            fn (CustomerLcr $site) => $site->latestDocumentApproval?->status === DocumentApprovalStatus::Approved
        );

        return $allApproved ? CustomerTabStatus::Complete : CustomerTabStatus::InProgress;
    }
}
