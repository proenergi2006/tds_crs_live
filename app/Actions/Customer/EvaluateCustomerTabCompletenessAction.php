<?php

namespace App\Actions\Customer;

use App\Enums\CustomerAddressType;
use App\Enums\CustomerPaymentTerm;
use App\Enums\CustomerReviewQuestionCode;
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
        return [
            'data_customer' => $this->evaluateDataCustomer($customer),
            'review'        => $this->evaluateReview($customer),
            'credit'        => $this->evaluateCredit($customer),
            'lcr'           => $this->evaluateLcr($customer),
        ];
    }

    private function evaluateDataCustomer(Customer $customer): bool
    {
        $corporateFilled = collect([
            $customer->company_name, $customer->phone, $customer->email,
            $customer->business_type, $customer->ownership_type, $customer->inco_terms,
        ])->every(fn ($v) => filled($v));

        $headOffice = $customer->addresses->firstWhere('address_type', CustomerAddressType::HeadOffice);
        $npwpAddress = $customer->addresses->firstWhere('address_type', CustomerAddressType::RegisteredNpwp);
        $addressFilled = $this->addressComplete($headOffice) && $this->addressComplete($npwpAddress);

        $pay = $customer->payment;
        $paymentFilled = $pay && collect([
            $pay->payment_schedule, $pay->payment_method, $pay->payment_term,
            $pay->bank_name, $pay->account_number, $pay->bank_address,
        ])->every(fn ($v) => filled($v))
            && ($pay->payment_term !== CustomerPaymentTerm::Credit || (filled($pay->payment_term_days) && filled($pay->payment_term_basis)));

        $contactFilled = $customer->contacts->isNotEmpty();

        $documentsFilled = collect(['nib', 'npwp'])->every(function (string $code) use ($customer) {
            $doc = $customer->documents->first(fn ($d) => $d->documentType?->code === $code);
            return $doc && filled($doc->file_path) && filled($doc->document_number);
        });

        return $corporateFilled && $addressFilled && $paymentFilled && $contactFilled && $documentsFilled;
    }

    private function addressComplete(?CustomerAddress $address): bool
    {
        if (!$address) {
            return false;
        }

        return collect([
            $address->address_line, $address->province_id, $address->regency_id,
            $address->district_id, $address->village_id, $address->postal_code,
        ])->every(fn ($v) => filled($v));
    }

    private function evaluateReview(Customer $customer): bool
    {
        $review = CustomerReview::where('id_customer', $customer->id_customer)->first();
        if (!$review) {
            return false;
        }

        $answeredCodes = collect($review->review_answers ?? [])
            ->filter(fn (array $item) => isset($item['answer']) && $item['answer'] !== '')
            ->pluck('question_code');

        return collect(CustomerReviewQuestionCode::cases())
            ->every(fn (CustomerReviewQuestionCode $code) => $answeredCodes->contains($code->value));
    }

    private function evaluateCredit(Customer $customer): bool
    {
        $request = $customer->creditRequest;

        $dataFilled = $request
            && $request->requested_limit > 0
            && filled($request->requested_top);

        $approved = $customer->latestVerification?->status === CustomerVerificationStatus::Approved;

        return $dataFilled && $approved;
    }

    private function evaluateLcr(Customer $customer): bool
    {
        $sites = $customer->lcr()->with('latestDocumentApproval')->get();

        return $sites->isNotEmpty()
            && $sites->every(fn (CustomerLcr $site) => $site->latestDocumentApproval?->status === DocumentApprovalStatus::Approved);
    }
}
