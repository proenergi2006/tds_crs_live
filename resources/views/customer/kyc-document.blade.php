<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>KYC {{ $customer->company_name ?? '-' }}</title>
  <style>
    @page { size: A4 portrait; margin: 18mm 16mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 10.5px; color: #222; line-height: 1.45; }

    h1 { font-size: 15px; margin-bottom: 2px; }
    h2 { font-size: 12px; margin: 14px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #cfd8dc; }

    table { width: 100%; border-collapse: collapse; }
    .kv td { vertical-align: top; padding: 2px 0; }
    .kv .label { width: 150px; color: #555; }
    .kv .colon { width: 10px; }

    .grid { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .grid th, .grid td { border: 1px solid #cfd8dc; padding: 4px 6px; text-align: left; vertical-align: top; }
    .grid th { background: #f2f5f6; font-weight: 700; }

    .muted { color: #777; font-style: italic; }
    .section { margin-bottom: 4px; }

    /* financial_review butuh list-nya balik lagi -- kena strip sama reset
       `* { margin:0; padding:0 }` di atas, mirip masalah Preflight di app. */
    .rich-text-content ul { list-style: disc; padding-left: 1.4em; margin: 4px 0; }
    .rich-text-content ol { list-style: decimal; padding-left: 1.4em; margin: 4px 0; }
    .rich-text-content li { margin: 2px 0; }
    .rich-text-content p { margin: 4px 0; }
  </style>
</head>
<body>
  <h1>Dokumen KYC -- {{ $customer->company_name ?? '-' }}</h1>
  <div class="muted">Kode Customer: {{ $customer->customer_code ?? '-' }}</div>

  {{-- Data Customer --}}
  <h2>1. Data Customer</h2>
  <table class="kv section">
    <tr>
      <td class="label">Nama Perusahaan</td><td class="colon">:</td><td>{{ $customer->company_name ?? '-' }}</td>
    </tr>
    <tr>
      <td class="label">Kode Customer</td><td class="colon">:</td><td>{{ $customer->customer_code ?? '-' }}</td>
    </tr>
    <tr>
      <td class="label">Alamat Perusahaan</td><td class="colon">:</td><td>{{ $customer->headOfficeAddress?->address_line ?: '-' }}</td>
    </tr>
    <tr>
      <td class="label">Telepon</td><td class="colon">:</td><td>{{ $customer->phone ?? '-' }}</td>
    </tr>
    <tr>
      <td class="label">Email</td><td class="colon">:</td><td>{{ $customer->email ?? '-' }}</td>
    </tr>
  </table>

  <table class="grid section">
    <thead>
      <tr><th>Nama Kontak</th><th>Jabatan</th><th>Telepon</th><th>Email</th></tr>
    </thead>
    <tbody>
      @forelse ($customer->contacts ?? [] as $contact)
        <tr>
          <td>{{ $contact->full_name ?? '-' }}</td>
          <td>{{ $contact->position ?? '-' }}</td>
          <td>{{ $contact->phone ?? $contact->mobile ?? '-' }}</td>
          <td>{{ $contact->email ?? '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="muted">Belum ada kontak tercatat.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Sales Review --}}
  <h2>2. Sales Review</h2>
  <table class="grid section">
    <thead>
      <tr><th>Pertanyaan</th><th>Jawaban</th></tr>
    </thead>
    <tbody>
      @forelse (($review->review_answers ?? []) as $answer)
        <tr>
          <td>{{ $answer['question'] ?? ($answer['question_code'] ?? '-') }}</td>
          <td>{{ $answer['answer'] ?? '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="2" class="muted">Belum ada Sales Review tercatat.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- LCR --}}
  <h2>3. LCR (Lokasi Survey)</h2>
  <table class="grid section">
    <thead>
      <tr><th>Nama Site</th><th>Status Approval</th><th>Lokasi</th></tr>
    </thead>
    <tbody>
      @forelse ($lcrSites as $site)
        <tr>
          <td>{{ $site->site_name ?? '-' }}</td>
          <td>{{ $site->latestDocumentApproval?->status?->label() ?? 'Belum diajukan' }}</td>
          <td>{{ collect([$site->survey_address, $site->survey_regency, $site->survey_province])->filter()->implode(', ') ?: '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="3" class="muted">Belum ada site LCR tercatat.</td></tr>
      @endforelse
    </tbody>
  </table>

  {{-- Credit Application --}}
  <h2>4. Credit Application</h2>
  @if ($submission)
    <table class="kv section">
      <tr>
        <td class="label">Credit Limit Diajukan</td><td class="colon">:</td><td>{{ $submission->credit_limit_request ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Credit Limit Disetujui</td><td class="colon">:</td><td>{{ $submission->credit_limit_approval ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">TOP Diajukan (hari)</td><td class="colon">:</td><td>{{ $submission->top_request ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">TOP Disetujui (hari)</td><td class="colon">:</td><td>{{ $submission->top_approval ?? '-' }}</td>
      </tr>
      <tr>
        <td class="label">Financial Review</td><td class="colon">:</td><td class="rich-text-content">{!! $submission->financial_review ?? '-' !!}</td>
      </tr>
    </table>
  @else
    <div class="muted section">Belum ada pengajuan credit tercatat.</div>
  @endif

  {{-- Penawaran Lookup --}}
  <h2>5. Penawaran Lookup</h2>
  <table class="grid section">
    <thead>
      <tr><th>Nomor Penawaran</th><th>Status</th><th>Total</th></tr>
    </thead>
    <tbody>
      @forelse ($penawarans as $penawaran)
        <tr>
          <td>{{ $penawaran->nomor_penawaran ?? '-' }}</td>
          <td>{{ $penawaran->status ?? '-' }}</td>
          <td>{{ $penawaran->total ?? '-' }}</td>
        </tr>
      @empty
        <tr><td colspan="3" class="muted">Belum ada Penawaran tercatat.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
