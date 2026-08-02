<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Data Customer {{ $customer->company_name ?? '-' }}</title>
  <style>
    @page { size: A4 portrait; margin: 28mm 24mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 10.5px; color: #111; line-height: 1.4; }

    /* @page margin kadang diabaikan dompdf. Padding di page-wrap ini jaga-jaga --
       sama polanya kayak vendorpos/preview.blade.php. */
    .page-wrap { padding: 8mm 6mm; }

    .header-logos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .header-logos td { vertical-align: middle; padding: 0; }
    .header-logos img { height: 46px; width: auto; }
    .header-logos .logo-left { text-align: left; width: 30%; }
    .header-logos .header-title { text-align: center; width: 40%; font-size: 15px; font-weight: 700; letter-spacing: 0.04em; color: #1e3a8a; }
    .header-logos .logo-right { text-align: right; width: 30%; }
    .header-rule { border-bottom: 2px solid #1e3a8a; margin-bottom: 12px; }

    .section { margin-top: 14px; }
    .section-header {
      background: #1e3a8a; color: #fff; font-size: 12px; font-weight: 700;
      padding: 5px 8px; text-transform: uppercase; letter-spacing: 0.03em;
    }
    .section-body { border: 1px solid #1e3a8a; border-top: none; padding: 8px; }

    .block-title { font-weight: 700; font-size: 11px; color: #1e3a8a; margin: 10px 0 2px; }
    .block-title:first-child { margin-top: 0; }

    table.grid { width: 100%; border-collapse: collapse; margin-top: 4px; }
    table.grid th, table.grid td { border: 1px solid #b7c0cc; padding: 4px 6px; text-align: left; vertical-align: top; }
    table.grid th { background: #eef1f5; font-weight: 700; font-size: 10px; color: #111; }
    .muted { color: #666; font-style: italic; }

    table.field-grid { width: 100%; border-collapse: separate; border-spacing: 0 4px; margin-bottom: 4px; }
    table.field-grid td { padding: 6px 8px; vertical-align: top; }
    table.field-grid .field-label { font-weight: 700; width: 20%; }
    table.field-grid .field-value { width: 30%; border: 1px solid #b7c0cc; border-radius: 3px; background: #fff; }
    table.field-grid .field-value.field-value-options { border: none; background: transparent; padding-left: 0; }
    .field-opt { padding: 1px 0; }
    .field-opt.checked { font-weight: 700; color: #1e3a8a; }
    .field-other-note { margin-top: 3px; font-size: 9.5px; font-style: italic; color: #666; }

    table.sig-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.sig-table th {
      border: 1px solid #1e3a8a; background: #eef2fb; padding: 6px; font-size: 11px;
      font-weight: 700; text-align: center; color: #1e3a8a;
    }
    table.sig-table .sig-space { border: 1px solid #1e3a8a; border-top: none; height: 60px; }
    table.sig-table .sig-name {
      border: 1px solid #1e3a8a; border-top: none; padding: 6px; text-align: left;
      font-weight: 700; font-size: 10.5px; color: #111;
    }
    table.sig-table .sig-title {
      border: 1px solid #1e3a8a; border-top: none; padding: 4px 6px 8px; text-align: left;
      font-size: 11px; color: #1e5fbf;
    }
  </style>
</head>
<body>
<div class="page-wrap">

  <table class="header-logos">
    <tr>
      <td class="logo-left">
        @if ($logoLeft)
          <img src="{{ $logoLeft }}" alt="Logo">
        @endif
      </td>
      <td class="header-title">CUSTOMER APPLICATION FORM</td>
      <td class="logo-right">
        @if ($logoRight)
          <img src="{{ $logoRight }}" alt="Crushed Stone">
        @endif
      </td>
    </tr>
  </table>
  <div class="header-rule"></div>

  @php
    $dash = fn ($v) => $v === null || $v === '' ? '-' : $v;
    $regionName = fn ($r) => $r?->name ?? null;
  @endphp

  {{-- 1. Corporate Details --}}
  <div class="section">
    <div class="section-header">1. Corporate Details</div>
    <div class="section-body">
        <table class="field-grid">
            <tr>
                <td class="field-label">Company Name</td>
                <td class="field-value">{{ $dash($customer->company_name) }}</td>
            </tr>
            <tr>
                <td class="field-label">Holding / Parent Company</td>
                <td class="field-value">{{ $dash($customer->parent_company) }}</td>
            </tr>
            <tr>
                <td class="field-label">Phone</td>
                <td class="field-value">{{ $dash($customer->phone) }}</td>
            </tr>
            <tr>
                <td class="field-label">Fax</td>
                <td class="field-value">{{ $dash($customer->fax) }}</td>
            </tr>
            <tr>
                <td class="field-label">Email</td>
                <td class="field-value">{{ $dash($customer->email) }}</td>
            </tr>
            <tr>
                <td class="field-label">Website</td>
                <td class="field-value" style="font-style: underline">{{ $dash($customer->website) }}</td>
            </tr>
            <tr>
                <td class="field-label">Business Type</td>
                <td class="field-value">{{ $dash($customer->business_type === 'Other' ? $customer->business_type_other : $customer->business_type) }}</td>
            </tr>
            <tr>
                <td class="field-label">Ownership</td>
                <td class="field-value">{{ $dash($customer->ownership_type === 'Other' ? $customer->ownership_type_other : $customer->ownership_type) }}</td>
            </tr>
            <tr>
                <td class="field-label">Incoterms</td>
                <td class="field-value">{{ $customer->inco_terms ? ($customer->inco_terms->value === 'Other' ? $dash($customer->inco_terms_other) : $customer->inco_terms->label()) : '-' }}</td>
            </tr>
        </table>
    </div>
  </div>

  {{-- 2. Addresses --}}
  <div class="section">
    <div class="section-header">2. Addresses</div>
    <div class="section-body">
      @php
        $headOfficeAddressRows = [
          ['label' => 'Address', 'value' => $customer->company_address],
          ['label' => 'Province', 'value' => $regionName($customer->province)],
          ['label' => 'City/Regency', 'value' => $regionName($customer->regency)],
          ['label' => 'District', 'value' => $regionName($customer->district)],
          ['label' => 'Sub-district', 'value' => $regionName($customer->village)],
          ['label' => 'Postal Code', 'value' => $customer->postal_code],
        ];
        $npwpAddressRows = [
          ['label' => 'Address', 'value' => $npwpAddress->address_line ?? null],
          ['label' => 'Province', 'value' => $regionName($npwpAddress->province ?? null)],
          ['label' => 'City/Regency', 'value' => $regionName($npwpAddress->regency ?? null)],
          ['label' => 'District', 'value' => $regionName($npwpAddress->district ?? null)],
          ['label' => 'Sub-district', 'value' => $regionName($npwpAddress->village ?? null)],
          ['label' => 'Postal Code', 'value' => $npwpAddress->postal_code ?? null],
        ];
        $otherAddressTypeLabels = [
          \App\Enums\CustomerAddressType::Billing->value => 'Billing',
          \App\Enums\CustomerAddressType::Correspondence->value => 'Correspondence',
        ];
      @endphp

      <div class="block-title">Head Office Address</div>
      <table class="field-grid">
        @foreach ($headOfficeAddressRows as $row)
          <tr>
            <td class="field-label">{{ $row['label'] }}</td>
            <td class="field-value">{{ $dash($row['value']) }}</td>
          </tr>
        @endforeach
      </table>

      <div class="block-title">Registered NPWP Address</div>
      <table class="field-grid">
        @foreach ($npwpAddressRows as $row)
          <tr>
            <td class="field-label">{{ $row['label'] }}</td>
            <td class="field-value">{{ $dash($row['value']) }}</td>
          </tr>
        @endforeach
      </table>

      @if ($otherAddresses->isNotEmpty())
        <div class="block-title">Other Addresses</div>
        <table class="grid">
          <thead>
            <tr>
              <th>Address Type</th><th>Address</th><th>Province</th><th>City/Regency</th>
              <th>District</th><th>Sub-district</th><th>Postal Code</th>
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
  </div>

  {{-- 3. Person In Charge --}}
  <div class="section">
    <div class="section-header">3. Person In Charge</div>
    <div class="section-body">
      @php
        $picGroups = [
          'director'    => 'Director / Owner',
          'procurement' => 'Procurement',
          'finance'     => 'Finance',
          'site_pic'    => 'Site PIC',
        ];
      @endphp
      <table class="grid">
        <thead>
          <tr><th>PIC Type</th><th>Name</th><th>Position</th><th>Phone</th><th>Mobile</th><th>Email</th></tr>
        </thead>
        <tbody>
          @foreach ($picGroups as $code => $groupLabel)
            @php $rows = $contactsByType->get($code, collect()); @endphp
            @if ($rows->isEmpty())
              <tr>
                <td>{{ $groupLabel }}</td>
                <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
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
  </div>

  {{-- 4. Payment Term & Banking Detail --}}
  <div class="section">
    <div class="section-header">4. Payment Term &amp; Banking Detail</div>
    <div class="section-body">
      @php
        $pay = $customer->payment;
        $pricingOptions = ['Discount Pricelist', 'Quotation'];
        $paymentMethodOptions = ['SKBDN', 'Bank Guarantee', 'Cover Cek-Giro', 'Transfer'];
        $paymentTermOptions = ['CBD' => 'Cash Before Delivery (CBD)', 'COD' => 'Cash on Delivery (COD)', 'CREDIT' => 'Credit'];
        $termBasisOptions = ['days_after_delivery' => 'After Delivery', 'days_after_invoice_received' => 'After Invoice Received'];
        $creditFacilityOptions = [true => 'YES', false => 'NO'];
        $checked = fn (bool $isChecked) => $isChecked ? '&#9745;' : '&#9744;';
      @endphp
      @if (!$pay)
        <div class="muted">No payment data available.</div>
      @else
        <table class="field-grid">
          <tr>
            <td class="field-label">Pricing Method Calculation</td>
            <td class="field-value field-value-options">
              @foreach ($pricingOptions as $opt)
                <div class="field-opt {{ $pay->calculate_method === $opt ? 'checked' : '' }}">
                  {!! $checked($pay->calculate_method === $opt) !!} {{ $opt }}
                </div>
              @endforeach
              @if ($pay->calculate_method && !in_array($pay->calculate_method, $pricingOptions, true))
                <div class="field-other-note">Other: {{ $pay->calculate_method }}</div>
              @endif
            </td>
            <td class="field-label">Payment Method</td>
            <td class="field-value field-value-options">
              @foreach ($paymentMethodOptions as $opt)
                <div class="field-opt {{ $pay->payment_method === $opt ? 'checked' : '' }}">
                  {!! $checked($pay->payment_method === $opt) !!} {{ $opt }}
                </div>
              @endforeach
              @if ($pay->payment_method === 'Other' && $pay->payment_method_other)
                <div class="field-other-note">Other: {{ $pay->payment_method_other }}</div>
              @elseif ($pay->payment_method && !in_array($pay->payment_method, $paymentMethodOptions, true))
                <div class="field-other-note">Other: {{ $pay->payment_method }}</div>
              @endif
            </td>
          </tr>
          <tr>
            <td class="field-label" rowspan="2">Payment Term</td>
            <td class="field-value field-value-options" rowspan="2">
              @foreach ($paymentTermOptions as $code => $label)
                <div class="field-opt {{ $pay->payment_term?->value === $code ? 'checked' : '' }}">
                  {!! $checked($pay->payment_term?->value === $code) !!} {{ $label }}
                </div>
              @endforeach
            </td>
            <td class="field-label">Term Days</td>
            <td class="field-value">{{ $dash($pay->payment_term_days) }} days</td>
          </tr>
          <tr>
            <td class="field-label">Term Basis</td>
            <td class="field-value field-value-options">
              @foreach ($termBasisOptions as $code => $label)
                <div class="field-opt {{ $pay->payment_term_basis?->value === $code ? 'checked' : '' }}">
                  {!! $checked($pay->payment_term_basis?->value === $code) !!} {{ $label }}
                </div>
              @endforeach
            </td>
          </tr>
        </table>

        <table class="field-grid">
          <tr>
            <td class="field-label">Bank Name</td>
            <td class="field-value">{{ $dash($pay->bank_name) }}</td>
            <td class="field-label">Currency</td>
            <td class="field-value">{{ $dash($pay->currency) }}</td>
          </tr>
          <tr>
            <td class="field-label">Bank Address</td>
            <td class="field-value">{{ $dash($pay->bank_address) }}</td>
            <td class="field-label">Account Number</td>
            <td class="field-value">{{ $dash($pay->account_number) }}</td>
          </tr>
          <tr>
            <td class="field-label">Have Credit Facility or Bank Loan?</td>
            <td class="field-value field-value-options">
              @foreach ($creditFacilityOptions as $value => $label)
                <div class="field-opt {{ $pay->credit_facility === (bool) $value ? 'checked' : '' }}">
                  {!! $checked($pay->credit_facility === (bool) $value) !!} {{ $label }}
                </div>
              @endforeach
            </td>
            <td class="field-label">The creditor(s) who provide the loan / credit facility</td>
            <td class="field-value">{{ $dash($pay->creditor) }}</td>
          </tr>
          <tr>
            <td class="field-label">Notes</td>
            <td class="field-value" colspan="3">{{ $dash($pay->extra_notes) }}</td>
          </tr>
        </table>
      @endif
    </div>
  </div>

  {{-- Signatures --}}
  <div class="section">
    <div class="section-header">Signatures</div>
    <div class="section-body">
      <table class="sig-table">
        <thead>
          <tr>
            <th>Customer Representative</th>
            <th>Sales Person</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="sig-space">&nbsp;</td>
            <td class="sig-space">&nbsp;</td>
          </tr>
          <tr>
            <td class="sig-name">Name: </td>
            <td class="sig-name">Name: {{ $dash($customer->user->name ?? null) }}</td>
          </tr>
          <tr>
            <td class="sig-title">Date: </td>
            <td class="sig-title">Date: </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</body>
</html>
