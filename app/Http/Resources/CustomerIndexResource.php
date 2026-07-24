<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_customer' => $this->id_customer,
            'customer_code' => $this->customer_code,
            'company_name' => $this->company_name,
            'customer_type' => $this->customer_type,
            'customer_status' => $this->customer_status,
            'email' => $this->email,
            'phone' => $this->phone,

            'marketing' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],

            'province' => $this->province?->name,
            'regency' => $this->regency?->name,
            'district' => $this->district?->name,
            'village' => $this->village?->name,
            'postal_code' => $this->postal_code,
            'company_address' => $this->company_address,

            'quotation_count' => $this->quotation_count,
            'has_lcr' => $this->has_lcr,

            'created_at' => $this->created_at,
        ];
    }
}
