<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Customer {{ $customer->company_name ?? '-' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1cm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
            color: #2b2b2b;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }

        .header {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        table.logo-pair {
            width: auto;
            border-collapse: collapse;
        }
        .logo-cell {
            padding: 0 4px 0 0;
            vertical-align: middle;
        }
        .logo-cell img {
            height: 44px;
            width: auto;
        }
        .form-title {
            text-align: right;
            font-size: 11pt;
            font-weight: bold;
            color: #022c22;
            margin: 0;
        }

        .section-title {
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 3px 6px;
            margin-top: 8px;
            border-left: 3px solid #022c22;
            border-right: 1px solid #cbd5e1;
            border-top: 1px solid #cbd5e1;
            text-transform: uppercase;
        }

        .section-content {
            padding: 6px 12px;
            border: 1px solid #cbd5e1;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        table.data-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            color: #475569;
            width: 22%;
        }
        .value {
            color: #0f172a;
            width: 28%;
        }

        table.grid-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            margin-bottom: 4px;
        }
        table.grid-table th, table.grid-table td {
            border: 1px solid #cbd5e1;
            padding: 3px 4px;
            text-align: left;
            font-size: 8pt;
        }
        table.grid-table th {
            background-color: #f8fafc;
            color: #334155;
            font-weight: bold;
        }

        .checkbox-mark {
            font-family: 'DejaVu Sans', sans-serif;
            color: #0f172a;
            margin-right: 2px;
        }

        .col-container {
            width: 100%;
            margin-bottom: 2px;
        }
        .col-half {
            width: 49%;
            display: inline-block;
            vertical-align: top;
        }

        .signature-section {
            page-break-inside: avoid;
        }
        .sig-box {
            border: 1px solid #cbd5e1;
            padding: 6px;
            background-color: #fafafa;
        }
        .sig-title {
            font-weight: bold;
            font-size: 8.5pt;
            color: #1e293b;
            margin-bottom: 25px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 2px;
        }
        .sig-line {
            border-bottom: 1px dotted #94a3b8;
            margin-bottom: 4px;
        }
        .sig-area {
            display: block;
            min-height: 40px;
        }

        .muted {
            color: #666;
            font-style: italic;
        }
        .field-other-note {
            margin-top: 2px;
            font-size: 7.5pt;
            font-style: italic;
            color: #64748b;
        }
    </style>
</head>
<body>

@php
    $dash = fn ($v) => $v === null ? '' : $v;
    $regionName = fn ($r) => $r?->name ?? null;
    $formatAddressLine = function ($address) use ($regionName) {
        if (!$address) {
            return null;
        }

        $provincePostal = trim(collect([$regionName($address->province), $address->postal_code])->filter()->implode(' '));

        return collect([
            $address->address_line,
            $regionName($address->village),
            $regionName($address->district),
            $regionName($address->regency),
            $provincePostal !== '' ? $provincePostal : null,
        ])->filter()->implode(', ');
    };
    $renderCheckboxOption = function (string $label, bool $checked) {
        $mark = $checked ? '&#9745;' : '&#9744;';
        $labelWeight = $checked ? 'bold' : 'normal';

        return '<span class="checkbox-mark">' . $mark . '</span> <span style="font-weight: ' . $labelWeight . ';">' . e($label) . '</span>';
    };
@endphp

<div class="header">
    <table>
        <tr>
            <td>
                <table class="logo-pair">
                    <tr>
                        <td class="logo-cell">
                            @if ($logoLeft)
                                <img src="{{ $logoLeft }}" alt="Logo TDS">
                            @endif
                        </td>
                        <td class="logo-cell">
                            @if ($logoRight)
                                <img src="{{ $logoRight }}" alt="Logo Crushed Stone">
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
            <td style="text-align: right;">
                <div class="form-title">CUSTOMER APPLICATION FORM</div>
            </td>
        </tr>
    </table>
</div>

<div class="section-title">1. Corporate Details</div>
<div class="section-content">
    <table class="data-table">
        <tr>
            <td class="label" width="20%">Company Name:</td>
            <td class="value" colspan="3"><b>{{ $dash($customer->company_name) }}</b></td>
        </tr>
        <tr>
            <td class="label" width="20%">Holding / Parent:</td>
            <td class="value" colspan="3"><b>{{ $dash($customer->parent_company) }}</b></td>
        </tr>
        <tr>
            <td class="label">Website:</td>
            <td class="value">{{ $dash($customer->website) }}</td>
            <td class="label">Ownership:</td>
            <td class="value">{{ $dash($customer->ownership_type === 'Other' ? $customer->ownership_type_other : $customer->ownership_type) }}</td>
        </tr>
        <tr>
            <td class="label">Email:</td>
            <td class="value">{{ $dash($customer->email) }}</td>
            <td class="label">Business Type:</td>
            <td class="value">{{ $dash($customer->business_type === 'Other' ? $customer->business_type_other : $customer->business_type) }}</td>
        </tr>
        <tr>
            <td class="label">Phone / Fax:</td>
            <td class="value">{{ $dash($customer->phone) }} / {{ $dash($customer->fax) }}</td>
            <td class="label">Incoterms:</td>
            <td class="value">{{ $customer->inco_terms ? ($customer->inco_terms->value === 'Other' ? $dash($customer->inco_terms_other) : $customer->inco_terms->label()) : '' }}</td>
        </tr>
    </table>
</div>

<div class="section-title">2. Addresses</div>
<div class="section-content">
    <table class="data-table">
        <tr>
            <td class="label">Head Office:</td>
            <td colspan="3" class="value">{{ $formatAddressLine($headOfficeAddress) }}</td>
        </tr>
        <tr>
            <td class="label">NPWP Address:</td>
            <td colspan="3" class="value">{{ $formatAddressLine($npwpAddress) }}</td>
        </tr>
    </table>

    @php
        $otherAddressTypeLabels = [
            \App\Enums\CustomerAddressType::Billing->value => 'Billing',
            \App\Enums\CustomerAddressType::Correspondence->value => 'Correspondence',
        ];
    @endphp
    @if ($otherAddresses->isNotEmpty())
        <table class="grid-table">
            <thead>
                <tr>
                    <th>Address Type</th>
                    <th>Address</th>
                    <th>Province</th>
                    <th>City/Regency</th>
                    <th>District</th>
                    <th>Sub-district</th>
                    <th>Postal Code</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($otherAddresses as $a)
                    <tr>
                        <td>{{ $otherAddressTypeLabels[$a->address_type->value] ?? $a->address_type->label() }}</td>
                        <td>{{ $dash($a->address_line) }}</td>
                        <td>{{ $dash($regionName($a->province)) }}</td>
                        <td>{{ $dash($regionName($a->regency)) }}</td>
                        <td>{{ $dash($regionName($a->district)) }}</td>
                        <td>{{ $dash($regionName($a->village)) }}</td>
                        <td>{{ $dash($a->postal_code) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="section-title">3. Person In Charge (PIC)</div>
@php
    $picGroups = [
        'director'    => 'Director / Owner',
        'procurement' => 'Procurement',
        'finance'     => 'Finance',
        'site_pic'    => 'Site PIC',
    ];
@endphp
<div class="section-content">
    <table class="grid-table">
        <thead>
            <tr>
                <th style="width: 18%;">PIC Type</th>
                <th style="width: 18%;">Name</th>
                <th style="width: 16%;">Position</th>
                <th style="width: 15%;">Phone</th>
                <th style="width: 15%;">Mobile</th>
                <th style="width: 18%;">Email</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($picGroups as $code => $groupLabel)
                @php $rows = $contactsByType->get($code, collect()); @endphp
                @if ($rows->isEmpty())
                    <tr>
                        <td>{{ $groupLabel }}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                @else
                    @foreach ($rows as $c)
                        <tr>
                            <td>{{ $groupLabel }}</td>
                            <td>{{ $dash($c->full_name) }}</td>
                            <td>{{ $dash($c->position) }}</td>
                            <td>{{ $dash($c->phone) }}</td>
                            <td>{{ $dash($c->mobile) }}</td>
                            <td>{{ $dash($c->email) }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>
</div>

<div class="section-title">4. Payment Term &amp; Banking Detail</div>
@php
    $pay = $customer->payment;
    $pricingOptions = ['Discount Pricelist', 'Quotation'];
    $paymentMethodOptions = ['SKBDN', 'Bank Guarantee', 'Cover Cek-Giro', 'Transfer'];
    $paymentTermOptions = ['CBD' => 'Cash Before Delivery (CBD)', 'COD' => 'Cash on Delivery (COD)', 'CREDIT' => 'Credit'];
    $termBasisOptions = ['days_after_delivery' => 'After Delivery', 'days_after_invoice_received' => 'After Invoice Received'];
    $creditFacilityOptions = [true => 'YES', false => 'NO'];
@endphp
<div class="section-content">
@if (!$pay)
    <div class="muted">No payment data available.</div>
@else
    <div class="col-container">
        <div class="col-half">
            <table class="data-table">
                <tr>
                    <td class="label" style="width: 40%;">Pricing Method:</td>
                    <td class="value" style="width: 60%;">
                        @foreach ($pricingOptions as $opt)
                            {!! $renderCheckboxOption($opt, $pay->calculate_method === $opt) !!}<br>
                        @endforeach
                        @if ($pay->calculate_method && !in_array($pay->calculate_method, $pricingOptions, true))
                            <div class="field-other-note">Other: {{ $pay->calculate_method }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Payment Method:</td>
                    <td class="value">
                        @foreach ($paymentMethodOptions as $opt)
                            {!! $renderCheckboxOption($opt, $pay->payment_method === $opt) !!}<br>
                        @endforeach
                        @if ($pay->payment_method === 'Other' && $pay->payment_method_other)
                            <div class="field-other-note">Other: {{ $pay->payment_method_other }}</div>
                        @elseif ($pay->payment_method && !in_array($pay->payment_method, $paymentMethodOptions, true))
                            <div class="field-other-note">Other: {{ $pay->payment_method }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
        <div class="col-half">
            <table class="data-table">
                <tr>
                    <td class="label" style="width: 20%;">Payment Term:</td>
                    <td class="value">
                        @foreach ($paymentTermOptions as $code => $label)
                            {!! $renderCheckboxOption($label, $pay->payment_term?->value === $code) !!}<br>
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td class="label">Term Days:</td>
                    <td class="value">{{ $dash($pay->payment_term_days) }} days</td>
                </tr>
                <tr>
                    <td class="label">Term Basis:</td>
                    <td class="value">
                        @foreach ($termBasisOptions as $code => $label)
                            {!! $renderCheckboxOption($label, $pay->payment_term_basis?->value === $code) !!}<br>
                        @endforeach
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="col-container">
        <div class="col-half">
            <table class="data-table">
                <tr>
                    <td class="label" style="width: 40%;">Bank Name:</td>
                    <td class="value" style="width: 60%;">{{ $dash($pay->bank_name) }}</td>
                </tr>
                <tr>
                    <td class="label" style="width: 20%;">Bank Account:</td>
                    <td class="value">{{ $dash($pay->account_number) }}</td>
                </tr>
                <tr>
                    <td class="label">Bank Address:</td>
                    <td class="value">{{ $dash($pay->bank_address) }}</td>
                </tr>
                <tr>
                    <td class="label">Currency:</td>
                    <td class="value">{{ $pay->currency ?: 'IDR' }}</td>
                </tr>
            </table>
        </div>
        <div class="col-half">
            <table class="data-table">
                <tr>
                    <td class="label">Have Credit Facility or Bank Loan?</td>
                    <td class="value">
                        @foreach ($creditFacilityOptions as $value => $label)
                            {!! $renderCheckboxOption($label, $pay->credit_facility === (bool) $value) !!}&nbsp;&nbsp;&nbsp;
                        @endforeach
                    </td>
                </tr>
                <tr>
                    <td class="label">Creditor <i>(if has credit facility)</i>:</td>
                    <td class="value">{{ $dash($pay->creditor) }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="data-table">
        <tr>
            <td class="label" style="width: 20%;">Notes:</td>
            <td class="value">{{ $dash($pay->extra_notes) }}</td>
        </tr>
    </table>
@endif
</div>

<div class="signature-section">
    <div class="section-title">Signatures</div>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td class="sig-box" style="width: 50%; vertical-align: top;">
                <div class="sig-title">Customer Representative</div>
                <table class="data-table">
                    <tr><td class="sig-area"></td></tr>
                    <tr><td class="label" style="width: 30%;">Name:</td></tr>
                    <tr><td class="label">Date:</td></tr>
                </table>
                <div class="sig-line"></div>
                <div style="font-size: 7pt; color: #64748b; text-align: center;">Authorized Signature &amp; Stamp</div>
            </td>
            <td class="sig-box" style="width: 50%; vertical-align: top;">
                <div class="sig-title">Sales Person (Tridaya Selaras)</div>
                <table class="data-table">
                    <tr><td class="sig-area"></td></tr>
                    <tr><td class="label" style="width: 30%;">Name:</td></tr>
                    <tr><td class="label">Date:</td></tr>
                </table>
                <div class="sig-line"></div>
                <div style="font-size: 7pt; color: #64748b; text-align: center;">Authorized Signature</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
