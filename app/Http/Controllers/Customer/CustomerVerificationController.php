<?php

namespace App\Http\Controllers\Customer;

use App\Enums\DocumentApprovalStatus;
use App\Enums\DocumentApprovalStepStatus;
use App\Http\Controllers\Controller;
use App\Models\ApprovalTemplate;
use App\Models\Customer;
use App\Models\CustomerReview;
use App\Models\CustomerReviewAttachment;
use App\Models\CustomerVerification;
use App\Models\DocumentApproval;
use App\Models\DocumentApprovalStep;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CustomerVerificationController extends Controller
{
    /**
     * Kode template approval untuk modul ini (approval_templates.code) —
     * dipakai di seluruh method C1–C6 di controller ini.
     */
    private const APPROVAL_TEMPLATE_CODE = 'customer_verification';

    /**
     * id_role template customer_verification (diverifikasi ke tabel roles
     * asli via tinker, cocok dengan ApprovalTemplateCustomerVerificationSeeder
     * & plan Prioritas A/B — JANGAN diambil dari CLAUDE.md role-ID table, itu
     * eksplisit ditandai belum lengkap/stale).
     *
     * (CA-Amend, pivot 2026-07-10) 2 step formal: Admin Finance -> BM.
     * Marketing bukan lagi step approval formal (lihat createDocumentApprovalCycle()
     * & saveReview()) sehingga STEP_MARKETING/ROLE_MARKETING dihapus dari sini.
     *
     * (Prioritas H, 2026-07-13) STEP_ADMIN_FINANCE/STEP_BM (step_order literal)
     * DIHAPUS — step_order sekarang selalu di-resolve dinamis saat runtime dari
     * `approval_template_steps` (lihat activeApprovalTemplate()/
     * resolveStepOrderForRole() di bawah), supaya reorder step lewat CRUD
     * master data (Prioritas H4, di luar scope H1-H3) benar-benar mengubah
     * behavior. ROLE_ADMIN_FINANCE/ROLE_BM TETAP hardcoded — itu representasi
     * "role method ini", bukan bagian yang dijadikan dinamis (keputusan final,
     * lihat batasan Prioritas H di plan).
     */
    private const ROLE_ADMIN_FINANCE = 9;
    private const ROLE_BM            = 8;

    /**
     * (CA4, dulu C1) Buat 1 siklus document_approvals + 2 document_approval_steps
     * (semua pending, Admin Finance -> BM) untuk verification ini. Dipanggil
     * dari `saveReview()` saat Marketing forward (bukan lagi dari
     * updateByToken() — lihat CA3) — caller WAJIB memastikan tidak ada siklus
     * `in_progress` lain yang masih berjalan untuk verification yang sama
     * sebelum memanggil method ini (guard double-forward ada di saveReview()).
     *
     * Dipanggil baik untuk siklus pertama (belum pernah forward) maupun
     * siklus baru pasca-reject (multi-cycle, morphMany) — tidak ada
     * perbedaan logic di antara keduanya, method ini selalu membuat siklus
     * baru yang fresh.
     *
     * Template + step sudah di-seed di Prioritas A / dikoreksi CA1
     * (ApprovalTemplateCustomerVerificationSeeder) dan seharusnya selalu ada.
     * Kalau ternyata tidak ada/tidak lengkap, throw supaya aksi forward
     * di-rollback secara eksplisit — daripada silently membiarkan baris
     * customer_verifications ter-set disposisi_result=0 tanpa ada approval
     * cycle sama sekali (row itu akan hilang dari semua antrean review, silent
     * data loss yang lebih berbahaya daripada forward gagal dengan pesan
     * jelas).
     */
    private function createDocumentApprovalCycle(CustomerVerification $cv): void
    {
        $template = $this->activeApprovalTemplate();

        if (!$template || $template->steps->count() < 2) {
            Log::error('Approval template customer_verification tidak ditemukan/tidak lengkap saat forward verification.', [
                'id_verification' => $cv->id_verification,
                'id_customer'     => $cv->id_customer,
                'template_found'  => (bool) $template,
                'steps_count'     => $template?->steps->count(),
            ]);
            throw new \RuntimeException('Approval template customer_verification belum ter-setup dengan benar.');
        }

        // (Prioritas H1) Iterasi step apa adanya sesuai master data (jumlah &
        // urutan berapapun), bukan hardcode [1, 2] — $template->steps sudah
        // terurut step_order asc lewat relasi ApprovalTemplate::steps().
        $approval = $cv->documentApprovals()->create([
            'id_template'        => $template->id_template,
            'status'             => DocumentApprovalStatus::InProgress,
            'current_step_order' => $template->steps->min('step_order'),
            'started_at'         => now(),
        ]);

        foreach ($template->steps as $step) {
            DocumentApprovalStep::create([
                'id_approval'      => $approval->id_approval,
                'id_template_step' => $step->id_step,
                'step_order'       => $step->step_order,
                'status'           => DocumentApprovalStepStatus::Pending,
            ]);
        }
    }

    /**
     * (Prioritas H1) Template approval aktif untuk domain customer_verification
     * (APPROVAL_TEMPLATE_CODE), berikut steps-nya (sudah terurut step_order
     * lewat relasi ApprovalTemplate::steps()). Dipakai oleh
     * createDocumentApprovalCycle(), pendingStepQuery(), dan entry-point
     * method (saveEvaluation()/bmVerify()) supaya query lookup template ini
     * tidak diduplikasi di banyak tempat.
     */
    private function activeApprovalTemplate(): ?ApprovalTemplate
    {
        return ApprovalTemplate::with('steps')
            ->where('code', self::APPROVAL_TEMPLATE_CODE)
            ->first();
    }

    /**
     * (Prioritas H1/H2) Resolve step_order di $template yang id_role-nya
     * cocok dengan $idRole. Dipakai supaya method domain-spesifik
     * (saveEvaluation()/bmVerify()) dan queue (pendingStepQuery()) tidak
     * perlu tahu step_order literal — cukup tahu role mereka sendiri
     * (ROLE_ADMIN_FINANCE/ROLE_BM). Return null kalau template tidak punya
     * step dengan role tersebut (mis. admin mengganti role step lewat CRUD
     * master data tanpa ada step baru untuk role lama) — caller wajib
     * menangani null ini secara defensif (silent no-op + Log::warning,
     * bukan throw/500 baru).
     */
    private function resolveStepOrderForRole(ApprovalTemplate $template, int $idRole): ?int
    {
        return $template->steps->firstWhere('id_role', $idRole)?->step_order;
    }

    /**
     * (CA5/CA6, dulu C2/C3/C4) Tandai satu step document_approval_steps sebagai
     * approved/rejected untuk siklus approval yang sedang `in_progress` milik
     * verification ini, lalu majukan/tutup document_approvals induknya:
     *
     * - approved & bukan step terakhir -> current_step_order maju ke step
     *   berikutnya yang benar-benar ada di template (bukan blind +1),
     *   document_approvals tetap in_progress.
     * - approved & step terakhir (step_order === max step_order milik
     *   template cycle ini) -> document_approvals ditutup status=approved,
     *   completed_at=now(), current_step_order=null.
     * - rejected (di step manapun) -> document_approvals ditutup
     *   status=rejected, completed_at=now(), current_step_order=null.
     *
     * (Prioritas H1, 2026-07-13) "Step terakhir" & "step berikutnya" DULU
     * hardcode (`$stepOrder >= 2`, `$stepOrder + 1`) — sekarang di-resolve
     * dinamis dari `$approval->template->steps` (di-eager-load di bawah),
     * supaya reorder/tambah step lewat CRUD master data (Prioritas H4, di
     * luar scope H1-H3) benar-benar mengubah behavior tanpa perlu ubah kode
     * ini lagi.
     *
     * Sengaja mencari siklus yang `in_progress` (bukan sekadar approval
     * terbaru) supaya tidak menyentuh siklus lama yang sudah closed. Kalau
     * tidak ketemu approval/step yang cocok (mis. baris yang belum sempat
     * dapat document_approvals — seharusnya tidak terjadi untuk data yang
     * sudah lewat Prioritas B/C1, tapi dijaga defensif), catat warning dan
     * return null TANPA throw — efek samping & kolom lama di method pemanggil
     * tetap harus jalan seperti sebelumnya, supaya rewire ini tidak
     * menjatuhkan behavior existing yang sudah berjalan.
     */
    private function advanceApprovalStep(
        CustomerVerification $cv,
        int $stepOrder,
        DocumentApprovalStepStatus $status,
        ?string $note
    ): ?DocumentApproval {
        $approval = $cv->documentApprovals()
            ->with('template.steps')
            ->where('status', DocumentApprovalStatus::InProgress)
            ->latest('id_approval')
            ->first();

        if (!$approval) {
            Log::warning('Tidak ada document_approvals berstatus in_progress untuk verification ini saat mencoba advance step approval.', [
                'id_verification' => $cv->id_verification,
                'step_order'      => $stepOrder,
                'target_status'   => $status->value,
            ]);
            return null;
        }

        $step = $approval->steps()->where('step_order', $stepOrder)->first();

        if (!$step) {
            Log::warning('document_approval_steps untuk step_order ini tidak ditemukan pada document_approvals in_progress.', [
                'id_verification' => $cv->id_verification,
                'id_approval'     => $approval->id_approval,
                'step_order'      => $stepOrder,
            ]);
            return null;
        }

        $step->update([
            'status'        => $status,
            'actor_id'      => auth()->id(),
            'acted_at'      => now(),
            'decision_note' => $note,
        ]);

        if ($status === DocumentApprovalStepStatus::Rejected) {
            $approval->update([
                'status'             => DocumentApprovalStatus::Rejected,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $templateSteps = $approval->template?->steps ?? collect();

        if ($templateSteps->isEmpty()) {
            // Defensif: seharusnya tidak terjadi (template sudah divalidasi
            // punya >=2 step saat cycle dibuat, lihat createDocumentApprovalCycle()),
            // tapi kalau terjadi (mis. template dihapus di tengah cycle
            // in_progress) treat sebagai final step daripada membiarkan cycle
            // macet in_progress selamanya tanpa cara untuk close.
            Log::warning('Template/steps tidak ditemukan untuk document_approvals ini saat menentukan step terakhir — cycle ditutup sebagai approved untuk mencegah macet.', [
                'id_verification' => $cv->id_verification,
                'id_approval'     => $approval->id_approval,
                'step_order'      => $stepOrder,
            ]);

            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $maxStepOrder = $templateSteps->max('step_order');

        if ($stepOrder === $maxStepOrder) {
            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        // Step berikutnya = step_order riil berikutnya yang ada di template
        // (bukan blind +1) — mengakomodasi step_order non-kontigu hasil
        // reorder/tambah step lewat CRUD master data.
        $nextStepOrder = $templateSteps->pluck('step_order')
            ->filter(fn ($order) => $order > $stepOrder)
            ->sort()
            ->first();

        if ($nextStepOrder === null) {
            // Defensif: bukan max step_order tapi tidak ada step berikutnya
            // (data template tidak konsisten) — log supaya ketahuan, treat
            // sebagai final step supaya cycle tidak macet in_progress tanpa
            // current_step_order yang valid.
            Log::warning('Step ini bukan step_order maksimum tapi step berikutnya tidak ditemukan (data template_step tidak konsisten) — cycle ditutup sebagai approved untuk mencegah macet.', [
                'id_verification' => $cv->id_verification,
                'id_approval'     => $approval->id_approval,
                'step_order'      => $stepOrder,
                'max_step_order'  => $maxStepOrder,
            ]);

            $approval->update([
                'status'             => DocumentApprovalStatus::Approved,
                'current_step_order' => null,
                'completed_at'       => now(),
            ]);

            return $approval;
        }

        $approval->update([
            'current_step_order' => $nextStepOrder,
        ]);

        return $approval;
    }

    /**
     * (C6) Query dasar "antrean pending" untuk 1 step tertentu di sistem
     * approval baru: verification yang punya document_approvals in_progress
     * DENGAN current_step_order = step yang diminta (bukan sekadar step yang
     * status-nya masih 'pending' -- semua step yang belum pernah disentuh
     * juga nominal 'pending', termasuk step 2/3 milik siklus yang baru saja
     * dibuat lewat C1 dan masih di step 1; filter current_step_order inilah
     * yang membuat query ini benar2 "actionable now di step ini", bukan
     * "belum pernah diputuskan"), lalu dicocokkan lagi ke step
     * document_approval_steps pada step_order & id_role (via id_template_step)
     * yang diminta sebagai double-check konsistensi data.
     *
     * (Prioritas H1, 2026-07-13) Signature diubah: sebelumnya $stepOrder
     * dikirim hardcoded literal dari caller (self::STEP_ADMIN_FINANCE/
     * self::STEP_BM) — sekarang di-resolve sendiri oleh method ini dari
     * template customer_verification AKTIF (APPROVAL_TEMPLATE_CODE), by
     * $idRole. Pragmatis: resolve sekali dari 1 template "aktif" (bukan
     * per-cycle/per-document_approval), karena domain ini hanya punya 1
     * template aktif untuk saat ini (lihat batasan Prioritas H di plan) — kalau
     * template tidak punya step untuk role ini, return query kosong (bukan
     * throw) supaya queue tampil 0 row alih-alih 500.
     */
    private function pendingStepQuery(int $idRole)
    {
        $template  = $this->activeApprovalTemplate();
        $stepOrder = $template ? $this->resolveStepOrderForRole($template, $idRole) : null;

        if ($stepOrder === null) {
            Log::warning('Tidak menemukan step_order untuk role ini di template customer_verification aktif saat membangun antrean pending.', [
                'id_role' => $idRole,
            ]);

            return CustomerVerification::query()->whereNull('id_verification');
        }

        return CustomerVerification::query()
            ->whereHas('documentApprovals', function ($approvalQuery) use ($stepOrder, $idRole) {
                $approvalQuery->where('status', DocumentApprovalStatus::InProgress)
                    ->where('current_step_order', $stepOrder)
                    ->whereHas('steps', function ($stepQuery) use ($stepOrder, $idRole) {
                        $stepQuery->where('step_order', $stepOrder)
                            ->where('status', DocumentApprovalStepStatus::Pending)
                            ->whereHas('templateStep', function ($templateStepQuery) use ($idRole) {
                                $templateStepQuery->where('id_role', $idRole);
                            });
                    });
            });
    }

    /**
     * (CA7, pivot 2026-07-10) Query "antrean Marketing": Marketing bukan lagi
     * step formal di document_approval_steps, jadi query ini TIDAK memakai
     * pendingStepQuery(). Kandidat = verification yang customer-nya sudah
     * submit (is_evaluated=1) DAN salah satu dari:
     *  - belum pernah punya siklus document_approvals sama sekali (belum
     *    pernah di-forward), ATAU
     *  - siklus TERBARU (latestDocumentApproval, one-of-many by id_approval)
     *    berstatus `rejected` (perlu direview/diedit ulang & di-forward lagi).
     * Verification dengan siklus terbaru `in_progress`/`approved` TIDAK masuk
     * ke sini (sedang/sudah diproses Admin Finance/BM).
     */
    private function marketingQueueQuery()
    {
        return CustomerVerification::query()
            ->where('is_evaluated', 1)
            ->where(function ($w) {
                $w->whereDoesntHave('documentApprovals')
                    ->orWhereHas('latestDocumentApproval', function ($approvalQuery) {
                        $approvalQuery->where('status', DocumentApprovalStatus::Rejected);
                    });
            });
    }

    /**
     * (Task 5, RBAC Fase 2) `customer_verifications` tidak punya kolom
     * ownership langsung (tidak ada id_user/user_id di tabel/model ini,
     * sudah diverifikasi ke `App\Models\CustomerVerification` — beda dengan
     * `Customer` yang punya `id_user`). Ownership untuk keperluan
     * `customer.viewOwn`/scope manage di controller ini diturunkan lewat
     * relasi `id_customer` -> `customers.id_user`. Query langsung (bukan
     * lazy-load relasi `customer` yang di banyak method di-load dengan kolom
     * terbatas) supaya tidak salah baca id_user yang kebetulan tidak
     * ter-select di eager-load lain.
     */
    private function verificationOwnerId(CustomerVerification $customerVerification): ?int
    {
        $ownerId = Customer::where('id_customer', $customerVerification->id_customer)->value('id_user');

        return $ownerId !== null ? (int) $ownerId : null;
    }

    // GET /api/customer-verifications
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->cant('customer.viewAny') && $user->cant('customer.viewOwn')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $perPage = (int) $request->query('per_page', 10);
        $search  = trim((string) $request->query('search', ''));

        $q = CustomerVerification::query()
            ->with([
                'customer:id_customer,id_user,kode_pelanggan,nama_perusahaan,alamat_perusahaan,email,telepon,fax'
            ]);

        if ($user->cant('customer.viewAny')) {
            $q->whereHas('customer', function ($c) use ($user) {
                $c->where('id_user', $user->id);
            });
        }

        if ($search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('token_verification', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('nama_perusahaan', 'like', "%{$search}%")
                            ->orWhere('kode_pelanggan', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $rows = $q->orderByDesc('id_verification')->paginate($perPage);

        // badge "List Link" = customer butuh update & belum dibuatkan link
        $needUpdateCount = Customer::query()
            ->where('need_update', 1)
            ->where('is_generated_link', 0)
            ->count();

        return response()->json([
            'data'               => $rows->items(),
            'current_page'       => $rows->currentPage(),
            'last_page'          => $rows->lastPage(),
            'total'              => $rows->total(),
            'need_update_count'  => $needUpdateCount,
            'total_verification' => (int) $rows->total(),
        ]);
    }

    // GET /api/customer-verifications/{customerVerification}
    public function show(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($customerVerification) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $customerVerification->loadMissing('customer:id_customer,nama_perusahaan');
    }

    public function getAdminEvaluation(Request $request, $id)
    {
        $cv = CustomerVerification::with('customer')->findOrFail($id);

        $user = $request->user();

        $allowed = $user->can('customer.viewAny')
            || ($user->can('customer.viewOwn') && $this->verificationOwnerId($cv) === $user->id);

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customer = $cv->customer;

        return response()->json([
            'top_text'             => $customer->top_payment ? $customer->top_payment . ' Hari' : '-',
            'credit_limit_request' => $customer->credit_limit_diajukan ?? '-',
            'financial_review'     => trim(($customer->jenis_payment ?? '-') . ' — ' . ($customer->jenis_net ?? '-')),
            'potential_volume'     => '-', // Jika belum ada field, isi dummy atau tambahkan nanti
        ]);
    }


    // GET /api/verify/{token}  (PUBLIC – dipakai form)
    public function showByToken(string $token)
    {
        $row = CustomerVerification::with([
            'customer:id_customer,nama_perusahaan,id_provinsi,id_kabupaten,postal_code,telepon,fax,email,alamat_perusahaan'
        ])->where('token_verification', $token)->firstOrFail();

        // Tentukan status lifecycle token. Tidak menolak/error untuk status
        // expired/used — hanya menyertakan field 'status', keputusan tampilan
        // ada di frontend (task terpisah).
        //
        // "used" dideteksi dari is_evaluated (bukan is_active) karena is_active
        // tetap 1 setelah submit — is_active murni mengontrol eligibility masuk
        // pipeline review (lihat reviewIndex dkk), bukan lagi dipakai untuk
        // menandai token sudah terpakai. is_evaluated di-set atomik bareng
        // submit di updateByToken(), jadi truthy berarti sudah pernah submit.
        $isExpired = $row->expired_at !== null && $row->expired_at->lte(now());
        $isUsed    = (int) $row->is_evaluated === 1;

        if ($isExpired) {
            $status = 'expired';
        } elseif ($isUsed) {
            $status = 'used';
        } else {
            $status = 'active';
        }

        $data = $row->toArray();
        $data['status'] = $status;

        return response()->json($data);
    }

    // POST /api/customer-verifications
    public function store(Request $request)
    {
        if ($request->user()->cant('customer.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'id_customer'        => ['required', 'exists:customers,id_customer'],
            'token_verification' => ['nullable', 'string', 'size:17', 'unique:customer_verifications,token_verification'],

            'is_evaluated' => ['nullable', 'integer', 'in:0,1'],
            'is_reviewed'  => ['nullable', 'integer', 'in:0,1'],
            'is_active'    => ['nullable', 'integer', 'in:0,1'],

            'legal_data'     => ['nullable', 'string'],
            'legal_summary'  => ['nullable', 'string'],
            'legal_result'   => ['nullable', 'integer'],
            'legal_tgl_proses' => ['nullable', 'date'],
            'legal_pic'      => ['nullable', 'string', 'max:50'],

            'finance_data'    => ['nullable', 'string'],
            'finance_summary' => ['nullable', 'string'],
            'finance_result'  => ['nullable', 'integer'],
            'finance_tgl_proses' => ['nullable', 'date'],
            'finance_pic'     => ['nullable', 'string', 'max:50'],

            'logistik_data'    => ['nullable', 'string'],
            'logistik_summary' => ['nullable', 'string'],
            'logistik_result'  => ['nullable', 'integer'],
            'logistik_tgl_proses' => ['nullable', 'date'],
            'logistik_pic'     => ['nullable', 'string', 'max:50'],

            'sm_summary'  => ['nullable', 'string'],
            'sm_result'   => ['nullable', 'integer'],
            'sm_tgl_proses' => ['nullable', 'date'],
            'sm_pic'      => ['nullable', 'string', 'max:50'],

            'om_summary'  => ['nullable', 'string'],
            'om_result'   => ['nullable', 'integer'],
            'om_tgl_proses' => ['nullable', 'date'],
            'om_pic'      => ['nullable', 'string', 'max:50'],

            'cfo_summary' => ['nullable', 'string'],
            'cfo_result'  => ['nullable', 'integer'],
            'cfo_tgl_proses' => ['nullable', 'date'],
            'cfo_pic'     => ['nullable', 'string', 'max:50'],

            'ceo_summary' => ['nullable', 'string'],
            'ceo_result'  => ['nullable', 'integer'],
            'ceo_tgl_proses' => ['nullable', 'date'],
            'ceo_pic'     => ['nullable', 'string', 'max:50'],

            'disposisi_result' => ['nullable', 'integer'],
            'is_approved'      => ['nullable', 'integer', 'in:0,1'],
            'role_approve'     => ['nullable', 'integer'],
            'tanggal_approved' => ['nullable', 'date'],
            'jenis_datanya'    => ['nullable', 'integer'],
            'finance_data_kyc' => ['nullable', 'string'],
        ]);

        if (empty($data['token_verification'])) {
            $data['token_verification'] = strtoupper(Str::random(17));
        }

        $verification = CustomerVerification::create($data);

        // (opsional) tandai customer sudah dibuatkan link
        Customer::where('id_customer', $data['id_customer'])
            ->update(['is_generated_link' => 1]);

        return response()->json(
            $verification->loadMissing('customer:id_customer,nama_perusahaan'),
            201
        );
    }

    // PUT/PATCH /api/customer-verifications/{customerVerification}  (INTERNAL, tanpa captcha)
    public function update(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($this->verificationOwnerId($customerVerification) === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'id_customer'        => ['sometimes', 'exists:customers,id_customer'],
            'token_verification' => [
                'sometimes',
                'string',
                'size:17',
                Rule::unique('customer_verifications', 'token_verification')
                    ->ignore($customerVerification->id_verification, 'id_verification'),
            ],

            'is_evaluated' => ['sometimes', 'integer', 'in:0,1'],
            'is_reviewed'  => ['sometimes', 'integer', 'in:0,1'],
            'is_active'    => ['sometimes', 'integer', 'in:0,1'],

            'legal_data'     => ['sometimes', 'nullable', 'string'],
            'legal_summary'  => ['sometimes', 'nullable', 'string'],
            'legal_result'   => ['sometimes', 'nullable', 'integer'],
            'legal_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'legal_pic'      => ['sometimes', 'nullable', 'string', 'max:50'],

            'finance_data'    => ['sometimes', 'nullable', 'string'],
            'finance_summary' => ['sometimes', 'nullable', 'string'],
            'finance_result'  => ['sometimes', 'nullable', 'integer'],
            'finance_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'finance_pic'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'logistik_data'    => ['sometimes', 'nullable', 'string'],
            'logistik_summary' => ['sometimes', 'nullable', 'string'],
            'logistik_result'  => ['sometimes', 'nullable', 'integer'],
            'logistik_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'logistik_pic'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'sm_summary'  => ['sometimes', 'nullable', 'string'],
            'sm_result'   => ['sometimes', 'nullable', 'integer'],
            'sm_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'sm_pic'      => ['sometimes', 'nullable', 'string', 'max:50'],

            'om_summary'  => ['sometimes', 'nullable', 'string'],
            'om_result'   => ['sometimes', 'nullable', 'integer'],
            'om_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'om_pic'      => ['sometimes', 'nullable', 'string', 'max:50'],

            'cfo_summary' => ['sometimes', 'nullable', 'string'],
            'cfo_result'  => ['sometimes', 'nullable', 'integer'],
            'cfo_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'cfo_pic'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'ceo_summary' => ['sometimes', 'nullable', 'string'],
            'ceo_result'  => ['sometimes', 'nullable', 'integer'],
            'ceo_tgl_proses' => ['sometimes', 'nullable', 'date'],
            'ceo_pic'     => ['sometimes', 'nullable', 'string', 'max:50'],

            'disposisi_result' => ['sometimes', 'nullable', 'integer'],
            'is_approved'      => ['sometimes', 'integer', 'in:0,1'],
            'role_approve'     => ['sometimes', 'nullable', 'integer'],
            'tanggal_approved' => ['sometimes', 'nullable', 'date'],
            'jenis_datanya'    => ['sometimes', 'nullable', 'integer'],
            'finance_data_kyc' => ['sometimes', 'nullable', 'string'],
        ]);

        $customerVerification->update($data);

        return $customerVerification->fresh()->loadMissing('customer:id_customer,nama_perusahaan');
    }

    public function upload(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($this->verificationOwnerId($customerVerification) === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'file'  => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'field' => 'required|string|in:akta_file,npwp_file,nib_file,other_file',
        ]);

        $path = $request->file('file')->store(
            "customer_verifications/{$customerVerification->id_verification}/{$request->field}",
            'public'
        );

        // simpan path ke legal_data.files[field][]
        $legal = json_decode($customerVerification->legal_data ?? '{}', true) ?: [];
        $legal['files'][$request->field][] = $path;
        $customerVerification->update(['legal_data' => json_encode($legal)]);

        return response()->json(['path' => Storage::url($path)]);
    }

    // ====== PUBLIC UPLOAD (no auth, by token) ======
    public function uploadByToken(Request $request, string $token)
    {
        $cv = CustomerVerification::where('token_verification', $token)
            ->where('is_active', 1)->firstOrFail();

        // (opsional) kalau mau pakai captcha juga di upload, tambahkan validasi di sini

        $request->validate([
            'file'  => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'field' => 'required|string|in:akta_file,npwp_file,nib_file,other_file',
        ]);

        $path = $request->file('file')->store(
            "customer_verifications/{$cv->id_verification}/{$request->field}",
            'public'
        );

        $legal = json_decode($cv->legal_data ?? '{}', true) ?: [];
        $legal['files'][$request->field][] = $path;
        $cv->update(['legal_data' => json_encode($legal)]);

        return response()->json(['path' => Storage::url($path)]);
    }

    public function updateByToken(Request $request, string $token)
    {
        // 1) Validasi payload dasar.
        // (Prioritas redesain CustomerUpdateForm, 2026-07-13) Validasi
        // captcha (captcha_key/captcha_text vs Cache::get('captcha:'.$key))
        // DIHAPUS dari sini — form baru tidak lagi memakai captcha. Endpoint
        // GET /api/captcha TETAP ada (tidak dihapus), cuma tidak lagi
        // dipanggil dari form ini.
        $request->validate([
            'legal_data'    => 'required|string',
            'finance_data'  => 'required|string',
            'logistik_data' => 'required|string',
        ]);

        // 2) Ambil verification + customer
        $cv = CustomerVerification::where('token_verification', $token)
            ->where('is_active', 1)
            ->firstOrFail();

        // 2a-guard) Enforce single-use berdasarkan is_evaluated, bukan
        // is_active (is_active dipertahankan tetap 1 agar row tetap eligible
        // masuk pipeline review reviewIndex/reviewStats dkk — lihat catatan
        // di penutup transaction di bawah). Tolak submit ulang kalau data
        // ini sudah pernah disubmit sebelumnya.
        if ((int) $cv->is_evaluated === 1) {
            return response()->json([
                'message' => 'Data verifikasi ini sudah pernah disubmit sebelumnya.',
            ], 409);
        }

        $legal    = json_decode($request->legal_data, true)    ?: [];
        $finance  = json_decode($request->finance_data, true)  ?: [];
        $logistik = json_decode($request->logistik_data, true) ?: [];

        // 2a) Validasi agreement
        $agreement = Arr::get($legal, 'agreement');
        if (empty(Arr::get($agreement, 'updated_by')) || empty(Arr::get($agreement, 'agree'))) {
            return response()->json(['message' => 'Agreement wajib diisi.'], 422);
        }

        // 2b) Potong data dari struktur form
        $corp      = Arr::get($legal,   'corporate',  []);
        $reg       = Arr::get($legal,   'registered', []);
        $delivery  = Arr::get($legal,   'delivery',   []);
        $inv       = Arr::get($finance, 'invoice',    []);
        $pay       = Arr::get($finance, 'payment',    []);
        $supply    = Arr::get($logistik, 'supply',     []);
        $lg        = Arr::get($logistik, 'logistic',   []);

        // 2c) Helper konversi
        $intOrNull = function ($v) {
            if ($v === '' || $v === null) return null;
            if (is_string($v)) {
                // ambil angka depan, buang huruf (contoh "8 KL" -> 8)
                if (preg_match('/^\d+/', $v, $m)) return (int) $m[0];
                return is_numeric($v) ? (int)$v : null;
            }
            return is_numeric($v) ? (int)$v : null;
        };
        $boolToInt = fn($v) => $v ? 1 : 0;

        // mapping kode yg kolomnya bertipe integer
        $mapTipeBisnis = [
            'Agriculture & Forestry / Horticulture' => 1,
            'Business & Information'                => 2,
            'Construction / Utilities / Contracting' => 3,
            'Education'                              => 4,
            'Finance & Insurance'                    => 5,
            'Food & hospitality'                     => 6,
            'Gaming'                                  => 7,
            'Health Services'                         => 8,
            'Motor Vehicle'                           => 9,
            'Other'                                   => 99,
        ];
        $mapOwnership = [
            'Affiliation'     => 1,
            'National Private' => 2,
            'Foreign Private'  => 3,
            'Joint Venture'    => 4,
            'BUMN / BUMD'      => 5,
            'Foundation'       => 6,
            'Personal'         => 7,
            'Other'            => 99,
        ];
        $mapEnv = ['Industri' => 1, 'Pemukiman' => 2, 'Other' => 9];
        $mapStorage = ['Indoor' => 1, 'Outdoor' => 2, 'Other' => 9];
        $mapHours = ['08.00 - 17.00' => 1, '24 Hours' => 2, 'Other' => 9];
        // (Redesain CustomerUpdateForm, 2026-07-13) "Quantity Checking" —
        // dulu "Volume Measurement" dengan 3 opsi (PRO ENERGY'S TANK
        // LORRY/Flowmeter/Other). Diganti total jadi 7 opsi baru, kolom DB
        // yang dipakai tetap sama (customer_logistik.logistik_volume). Tidak
        // ada opsi "Other" lagi di 7 opsi ini — logistik_volume_other tetap
        // ada di skema tapi tidak diisi/divalidasi dari mapping ini.
        $mapVolume = [
            'Weighbridge (Truck Scale)'    => 1,
            'Platform Scale'               => 2,
            'Volume Measurement'           => 3,
            'Truck Counting'               => 4,
            'Delivery Order Verification'  => 5,
            'Net Weight Verification'      => 6,
            'Sampling'                     => 7,
        ];
        $mapQuality = ['DENSITY' => 1, 'OTHER' => 2]; // else null
        $mapSchedule = ['Every Day' => 1, 'Other' => 9];
        $mapPayMethod = ['Cash' => 1, 'Transfer' => 2, 'Cheque / Giro' => 3, 'Bank Guarantee' => 4, 'Other' => 9];
        // (Redesain CustomerUpdateForm, 2026-07-13) "Supply Scheme Details"
        // — dulu teks bebas diparse lewat $intOrNull, sekarang dropdown
        // tetap: Delivery / Self Pickup.
        $mapSupplyScheme = ['Delivery' => 1, 'Self Pickup' => 2];
        // (Redesain CustomerUpdateForm, 2026-07-13) "Inco Terms" — dulu teks
        // bebas diparse lewat $intOrNull, sekarang dropdown Incoterms
        // standar (field TETAP di Section 4/Supply Scheme, tidak
        // dipindah/rename).
        $mapIncoterms = [
            'EXW' => 1,
            'FOB' => 2,
            'CIF' => 3,
            'CFR' => 4,
            'DDP' => 5,
            'DAP' => 6,
            'FCA' => 7,
            'CPT' => 8,
        ];

        $tipeBisnisCode = $mapTipeBisnis[Arr::get($corp, 'tipe_bisnis', '')] ?? null;
        $ownershipCode  = $mapOwnership[Arr::get($corp, 'ownership', '')] ?? null;

        // 3) Transaksi write multi tabel
        DB::transaction(function () use (
            $cv,
            $corp,
            $reg,
            $delivery,
            $inv,
            $pay,
            $supply,
            $lg,
            $agreement,
            $intOrNull,
            $boolToInt,
            $tipeBisnisCode,
            $ownershipCode,
            $mapEnv,
            $mapStorage,
            $mapHours,
            $mapVolume,
            $mapQuality,
            $mapSchedule,
            $mapPayMethod,
            $mapSupplyScheme,
            $mapIncoterms,
            $legal,
            $finance,
            $logistik
        ) {
            // ---------- customers ----------
            DB::table('customers')
                ->where('id_customer', $cv->id_customer)
                ->update([
                    'nama_perusahaan'       => Arr::get($corp, 'nama'),
                    'alamat_perusahaan'     => Arr::get($corp, 'alamat'),

                    'id_provinsi'           => $intOrNull(Arr::get($corp, 'id_provinsi')),
                    'id_kabupaten'          => $intOrNull(Arr::get($corp, 'id_kabupaten')),
                    'postal_code'           => Arr::get($corp, 'postal_code'),

                    'telepon'               => Arr::get($corp, 'telepon'),
                    'fax'                   => Arr::get($corp, 'fax'),
                    'email'                 => Arr::get($corp, 'email'),
                    'website_customer'      => Arr::get($corp, 'website'),

                    'tipe_bisnis'           => $tipeBisnisCode, // smallint
                    'tipe_bisnis_lain'      => Arr::get($corp, 'tipe_bisnis_lain'),
                    'ownership'             => $ownershipCode,  // smallint
                    'ownership_lain'        => Arr::get($corp, 'ownership_lain'),
                    'induk_perusahaan'      => Arr::get($corp, 'holding'),

                    'kecamatan_customer'    => Arr::get($corp, 'kecamatan'),
                    'kelurahan_customer'    => Arr::get($corp, 'kelurahan'),

                    // (Redesain CustomerUpdateForm, 2026-07-13) NIB
                    // menggantikan SIUP/TDP — reuse pola Arr::get() yang
                    // sama seperti field corporate lain di atas.
                    'nib'                   => Arr::get($corp, 'nib_number'),
                    'nib_file'              => Arr::get($corp, 'nib_file'),

                    'lastupdate_time'       => now(),
                    'lastupdate_by'         => Arr::get($agreement ?? [], 'updated_by'),
                    'count_update'          => DB::raw('COALESCE(count_update,0)+1'),
                ]);

            // ---------- customer_contacts ----------
            DB::table('customer_contacts')->updateOrInsert(
                ['id_customer' => $cv->id_customer],
                [
                    'pic_invoice_name'     => Arr::get($inv, 'pic.name'),
                    'pic_invoice_position' => Arr::get($inv, 'pic.position'),
                    'pic_invoice_telp'     => Arr::get($inv, 'pic.telephone'),
                    'pic_invoice_mobile'   => Arr::get($inv, 'pic.mobile'),
                    'pic_invoice_email'    => Arr::get($inv, 'pic.email'),
                    'invoice_delivery_addr_primary'   => Arr::get($inv, 'delivery_address'),
                    'invoice_delivery_addr_secondary' => null,
                    'product_delivery_address'        => json_encode([
                        'alamat1' => Arr::get($delivery, 'alamat1'),
                        'alamat2' => Arr::get($delivery, 'alamat2'),
                        'alamat3' => Arr::get($delivery, 'alamat3'),
                    ], JSON_UNESCAPED_UNICODE),
                ]
            );

            // ---------- customer_payment ----------
            $payUpdate = [
                'email_billing'          => Arr::get($reg, 'email'),
                'alamat_billing'         => Arr::get($reg, 'alamat'),
                'prov_billing'           => $intOrNull(Arr::get($reg, 'id_provinsi')),
                'kab_billing'            => $intOrNull(Arr::get($reg, 'id_kabupaten')),
                'postalcode_billing'     => Arr::get($corp, 'postal_code'),
                'telp_billing'           => Arr::get($corp, 'telepon'),
                'fax_billing'            => Arr::get($corp, 'fax'),

                'payment_schedule'       => $mapSchedule[Arr::get($pay, 'schedule', '')] ?? null,
                'payment_schedule_other' => Arr::get($pay, 'schedule_other'),
                'payment_method'         => $mapPayMethod[Arr::get($pay, 'payment_method', '')] ?? null,
                'payment_method_other'   => Arr::get($pay, 'payment_method_other'),
                'invoice'                => $boolToInt(Arr::get($pay, 'invoice_tax')),
                'ket_extra'              => Arr::get($pay, 'note'),
                'kecamatan_billing'      => Arr::get($corp, 'kecamatan'),
                'kelurahan_billing'      => Arr::get($corp, 'kelurahan'),

                'calculate_method'       => Arr::get($pay, 'pricing_method'),
                'bank_name'              => Arr::get($pay, 'bank_name'),
                'curency'                => Arr::get($pay, 'currency'),
                'bank_address'           => Arr::get($pay, 'bank_address'),
                'account_number'         => Arr::get($pay, 'account_number'),
                'credit_facility'        => $boolToInt(Arr::get($pay, 'has_credit')),
                'creditor'               => Arr::get($pay, 'creditor_name'),
            ];
            // FK jangan diisi 0 → jika null, hapus supaya tak menabrak constraint NOT NULL
            if (is_null($payUpdate['prov_billing'])) unset($payUpdate['prov_billing']);
            if (is_null($payUpdate['kab_billing']))  unset($payUpdate['kab_billing']);

            DB::table('customer_payment')->updateOrInsert(
                ['id_customer' => $cv->id_customer],
                $payUpdate
            );

            // ---------- customer_logistik ----------
            $logistikUpdate = [
                'logistik_area'          => Arr::get($lg, 'area'),
                'logistik_bisnis'        => Arr::get($lg, 'security_env'),
                'logistik_env'           => $mapEnv[Arr::get($lg, 'env', '')] ?? null,
                'logistik_env_other'     => Arr::get($lg, 'env_other'),
                'logistik_storage'       => $mapStorage[Arr::get($lg, 'storage', '')] ?? null,
                'logistik_storage_other' => Arr::get($lg, 'storage_other'),
                'logistik_hour'          => $mapHours[Arr::get($lg, 'operating_hours', '')] ?? null,
                'logistik_hour_other'    => Arr::get($lg, 'operating_hours_other'),
                'logistik_volume'        => $mapVolume[Arr::get($lg, 'volume_measurement', '')] ?? null,
                'logistik_volume_other'  => Arr::get($lg, 'volume_measurement_other'),
                'logistik_quality'       => $mapQuality[Arr::get($lg, 'quality_density') ? 'DENSITY'
                    : (Arr::get($lg, 'quality_other_enabled') ? 'OTHER' : '')] ?? null,
                'logistik_quality_other' => Arr::get($lg, 'quality_other'),
                'logistik_truck'         => $intOrNull(Arr::get($lg, 'max_truck_capacity')), // "8 KL" -> 8
                'logistik_truck_other'   => Arr::get($lg, 'max_truck_capacity_other'),

                // ⬇️ yang bikin error: pastikan integer/NULL
                // (Redesain CustomerUpdateForm, 2026-07-13) supply_shceme &
                // nico sekarang dropdown tetap (bukan lagi teks bebas
                // diparse $intOrNull) — lihat $mapSupplyScheme/$mapIncoterms.
                'supply_shceme'          => $mapSupplyScheme[Arr::get($supply, 'scheme_details', '')] ?? null,
                'specify_product'        => $intOrNull(Arr::get($supply, 'specify_product')), // pakai ini jika kolom INT
                'volume_per_month'       => $intOrNull(Arr::get($supply, 'volume_per_month')),
                'nico'                   => $mapIncoterms[Arr::get($supply, 'inco_terms', '')] ?? null,

                // kalau kolom2 di atas ternyata TEXT/VARCHAR, cukup ganti ke Arr::get(..., '')
                // dan hapus $intOrNull
                'operational_hour_from'  => Arr::get($supply, 'operational_from'),
                'operational_hour_to'    => Arr::get($supply, 'operational_to'),
            ];
            DB::table('customer_logistik')->updateOrInsert(
                ['id_customer' => $cv->id_customer],
                $logistikUpdate
            );

            // ---------- simpan snapshot JSON + flag verifikasi (satu titik update) ----------
            // is_evaluated sebelumnya di-set dua kali (DB::table(...) di atas lalu
            // $cv->update() di bawah) — dikonsolidasi jadi satu titik di sini.
            // is_active TIDAK diubah di sini (tetap 1) — is_active murni menandai
            // eligibility row masuk pipeline review (reviewIndex/reviewStats/antrean
            // Admin/BM/OM), bukan lagi dipakai untuk menandai token sudah terpakai.
            // Single-use sekarang ditegakkan lewat is_evaluated (lihat guard di awal
            // method ini), supaya row yang baru submit tetap muncul di antrean review
            // seperti sebelum commit 1bfb1f5.
            $cv->update([
                'legal_data'    => json_encode($legal,    JSON_UNESCAPED_UNICODE),
                'finance_data'  => json_encode($finance,  JSON_UNESCAPED_UNICODE),
                'logistik_data' => json_encode($logistik, JSON_UNESCAPED_UNICODE),
                'is_evaluated'  => 1,
            ]);

            // Reset is_verified di titik SUBMIT (bukan di generate()) — siklus
            // verifikasi baru sedang berjalan, jadi status Verified sebelumnya
            // (kalau ada, dari siklus lama) sudah tidak berlaku lagi sampai
            // disetujui ulang lewat step BM di sistem approval baru (lihat
            // bmVerify(), C4/C5).
            DB::table('customers')
                ->where('id_customer', $cv->id_customer)
                ->update(['is_verified' => 0]);

            // (CA3, pivot 2026-07-10) Siklus document_approvals TIDAK LAGI
            // dibuat di sini. Submit customer hanya mengisi data & menandai
            // is_evaluated=1 (siap direview Marketing) -- siklus approval
            // 2-step (Admin Finance -> BM) baru dibuat saat Marketing forward
            // lewat saveReview() (lihat CA4). Guard single-use di atas
            // (is_evaluated check) TIDAK disentuh oleh perubahan ini.
        });

        return response()->json(['message' => 'Data berhasil diperbarui.']);
    }


    // DELETE /api/customer-verifications/{customerVerification}
    public function destroy(Request $request, CustomerVerification $customerVerification)
    {
        $user = $request->user();

        $allowed = $user->can('customer.manage')
            && ($this->verificationOwnerId($customerVerification) === $user->id || $user->can('customer.viewAny'));

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $customerVerification->delete();
        return response()->noContent();
    }


    /**
     * (CA7, pivot 2026-07-10, dulu C6) "unreviewed" = antrean Marketing
     * sesungguhnya (lihat marketingQueueQuery()): sudah submit tapi belum
     * pernah di-forward, atau siklus terakhirnya rejected. Acceptance: stats
     * cocok dengan isi reviewIndex() default.
     */
    public function reviewStats()
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $unreviewed = $this->marketingQueueQuery()->count();

        // "reviewed" = sudah pernah di-forward dan siklus TERBARUnya sedang
        // berjalan/sudah disetujui (in_progress/approved) -- kebalikan dari
        // marketingQueueQuery(). Info sekunder untuk tab "reviewed" di FE,
        // tidak dipakai acceptance criteria CA7 (yang dicek hanya kecocokan
        // unreviewed vs list default).
        $reviewed = CustomerVerification::query()
            ->where('is_evaluated', 1)
            ->whereHas('latestDocumentApproval', function ($approvalQuery) {
                $approvalQuery->whereIn('status', [
                    DocumentApprovalStatus::InProgress,
                    DocumentApprovalStatus::Approved,
                ]);
            })
            ->count();

        return response()->json([
            'unreviewed' => $unreviewed,
            'reviewed'   => $reviewed,
        ]);
    }

    /**
     * (CA7, pivot 2026-07-10, dulu C6) Default list ("unreviewed") sekarang
     * berdasarkan marketingQueueQuery() (belum pernah forward ATAU siklus
     * terakhir rejected), bukan step document_approval_steps (Marketing sudah
     * bukan step formal). Tab "reviewed" (kalau FE masih mengirimnya) tetap
     * didukung, dipetakan ke siklus terbaru in_progress/approved.
     */
    public function reviewIndex(Request $r)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $per    = (int) $r->query('per_page', 25);
        $q      = trim((string) $r->query('q', ''));
        // baca 'tab' (fallback ke 'status' untuk kompatibilitas lama)
        $status = $r->query('tab', $r->query('status', 'unreviewed'));

        $baseQuery = $status === 'reviewed'
            ? CustomerVerification::query()->where('is_evaluated', 1)->whereHas('latestDocumentApproval', function ($approvalQuery) {
                $approvalQuery->whereIn('status', [
                    DocumentApprovalStatus::InProgress,
                    DocumentApprovalStatus::Approved,
                ]);
            })
            : $this->marketingQueueQuery();

        $rows = $baseQuery
            ->with(['customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax'])
            ->when($q !== '', function ($w) use ($q) {
                $w->whereHas('customer', function ($c) use ($q) {
                    $c->where('nama_perusahaan', 'like', "%{$q}%")
                        ->orWhere('alamat_perusahaan', 'like', "%{$q}%")
                        ->orWhere('kode_pelanggan', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id_verification')
            ->paginate($per);

        return response()->json([
            'data'         => $rows->items(),
            'current_page' => $rows->currentPage(),
            'last_page'    => $rows->lastPage(),
            'total'        => $rows->total(),
        ]);
    }


    public function setReviewed(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $row = \App\Models\CustomerVerification::findOrFail($id);
        $row->update(['is_reviewed' => 1]);
        return response()->json(['ok' => true]);
    }

    // public function reviewShow(int $id)
    // {
    //     $row = \App\Models\CustomerVerification::with([
    //         'customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax,email'
    //     ])->findOrFail($id);

    //     return response()->json([
    //         'id_verification'    => $row->id_verification,
    //         'token_verification' => $row->token_verification,
    //         'is_evaluated'       => (int) $row->is_evaluated,
    //         'is_reviewed'        => (int) $row->is_reviewed,
    //         'is_active'          => (int) $row->is_active,

    //         'customer'           => $row->customer,                 // ringkasan customer
    //         'legal'              => json_decode($row->legal_data    ?? '[]', true) ?: [],
    //         'finance'            => json_decode($row->finance_data  ?? '[]', true) ?: [],
    //         'logistik'           => json_decode($row->logistik_data ?? '[]', true) ?: [],
    //     ]);
    // }

    public function reviewShow(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::with([
            'customer:id_customer,nama_perusahaan,alamat_perusahaan,telepon,fax,email'
        ])->findOrFail($id);

        return response()->json([
            'customer'     => $cv->customer,
            'disposisi_result' => (int) $cv->disposisi_result,
            'is_reviewed'     => (int) $cv->is_reviewed,
            'stage_text'      => match ((int) $cv->disposisi_result) {
                0 => 'Marketing/Draft',
                1 => 'Admin',
                2 => 'Logistik',
                3 => 'BM',
                4 => 'OM',
                default => '-',
            },
            'legal'        => json_decode($cv->legal_data    ?? '[]', true) ?: [],
            'finance'      => json_decode($cv->finance_data  ?? '[]', true) ?: [],
            'logistik'     => json_decode($cv->logistik_data ?? '[]', true) ?: [],
            'review_form'  => (function () use ($cv) {
                $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
                return $kyc['form'] ?? [];
            })(),
            // 'review_files' => $kyc['files'] ?? [],
        ]);
    }

    /**
     * (Prioritas E1 gap, F2 blocker) Timeline seluruh siklus
     * `document_approvals` untuk 1 verifikasi, terbaru dulu, tiap siklus
     * beserta step-nya (nama step, actor, waktu, catatan keputusan) — dipakai
     * frontend detail page untuk render approval timeline. Read-only, tidak
     * ada logic bisnis baru di sini.
     */
    public function approvalTimeline(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);

        $cycles = $cv->documentApprovals()
            ->with([
                'steps' => function ($q) {
                    $q->orderBy('step_order');
                },
                'steps.templateStep:id_step,step_name,step_order,id_role',
                'steps.actor:id,name',
            ])
            ->orderByDesc('id_approval')
            ->get();

        return response()->json([
            'data' => $cycles->map(function (DocumentApproval $cycle) {
                return [
                    'id_approval'        => $cycle->id_approval,
                    'status'             => $cycle->status,
                    'current_step_order' => $cycle->current_step_order,
                    'started_at'         => $cycle->started_at,
                    'completed_at'       => $cycle->completed_at,
                    'steps'              => $cycle->steps->map(function (DocumentApprovalStep $step) {
                        return [
                            'step_order'    => $step->step_order,
                            'step_name'     => $step->templateStep->step_name ?? null,
                            'status'        => $step->status,
                            'actor_id'      => $step->actor_id,
                            'actor_name'    => $step->actor->name ?? null,
                            'acted_at'      => $step->acted_at,
                            'decision_note' => $step->decision_note,
                        ];
                    }),
                ];
            }),
        ]);
    }

    public function evaluationShow(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv  = CustomerVerification::findOrFail($id);
        $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];

        return response()->json([
            'jenis_datanya'     => (int) ($cv->jenis_datanya ?? 0),
            'disposisi_result'  => (int) ($cv->disposisi_result ?? 0),
            'evaluation'        => $kyc['evaluation'] ?? [],
        ]);
    }

    public function evaluationAdmin(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::with('customer')->findOrFail($id);
        $cust = $cv->customer;

        $form = [
            'top_text'             => $cust->top_payment ? $cust->top_payment . ' Hari' : null,
            'potential_volume'     => $cust->potential_volume ?? null,
            'credit_limit_request' => $cust->credit_limit_diajukan ?? null,
            'financial_review'     => $cust->financial_review ?? null,
        ];

        return response()->json(['form' => $form]);
    }



    public function evaluationSave(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);

        $data = $r->validate([
            'jenis_data'         => 'required|in:before,after',
            'financial_review'   => 'nullable|string',
            'logistic_summary'   => 'nullable|string',
            'logistic_result'    => 'nullable|string',
            'assessment_result'  => 'nullable|integer|in:1,2,3',

            'approval'                 => 'nullable|array',
            'approval.cl_approved'     => 'nullable|string',
            'approval.payment_type'    => 'nullable|in:CREDIT,CASH',
            'approval.top_days'        => 'nullable|integer',
            'approval.top_term'        => 'nullable|string|max:50',
            'approval.group_company'   => 'nullable|string|max:200',
            'approval.docs'            => 'nullable|array',
            'approval.jenis_net'       => 'nullable|string|max:100',
            'approval.dokumen_lainnya' => 'nullable|string|max:255',
            'approval.credit_limit'    => 'nullable|numeric',
        ]);

        $jenisInt = $data['jenis_data'] === 'after' ? 2 : 1;

        // Update evaluation data di json
        $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
        $evaluation = $kyc['evaluation'] ?? [];

        $evaluation['jenis_data']        = $data['jenis_data'];
        $evaluation['financial_review']  = $data['financial_review'] ?? null;
        $evaluation['logistic_summary']  = $data['logistic_summary'] ?? null;
        $evaluation['logistic_result']   = $data['logistic_result'] ?? null;
        $evaluation['assessment_result'] = $data['assessment_result'] ?? null;

        if ($data['jenis_data'] === 'after') {
            $approval = $data['approval'] ?? [];
            $evaluation['approval'] = [
                'cl_approved'   => $approval['cl_approved'] ?? null,
                'payment_type'  => $approval['payment_type'] ?? null,
                'top_days'      => $approval['top_days'] ?? null,
                'top_term'      => $approval['top_term'] ?? null,
                'group_company' => $approval['group_company'] ?? null,
                'jenis_net'     => $approval['jenis_net'] ?? null,
                'docs'          => $approval['docs'] ?? [],
            ];

            // ✅ Sementara jenis_net dikomentari karena tabel customers.jenis_net bertipe integer
            DB::table('customers')->where('id_customer', $cv->id_customer)->update([
                'dokumen_lainnya' => $approval['dokumen_lainnya'] ?? null,
                'credit_limit'    => $approval['credit_limit'] ?? null,
                'jenis_payment'   => $approval['payment_type'] ?? null,
                'top_payment'     => $approval['top_days'] ?? null,
                // 'jenis_net'       => $approval['jenis_net'] ?? null, // ❌ Komentar karena string tidak bisa masuk kolom integer
                'lastupdate_time' => now(),
            ]);
        } else {
            unset($evaluation['approval']);
        }

        $kyc['evaluation'] = $evaluation;

        $cv->update([
            'jenis_datanya'     => $jenisInt,
            'finance_data_kyc'  => $kyc,
        ]);

        return response()->json(['ok' => true]);
    }



    public function evaluationUploadFile(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);

        $r->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar',
            'name' => 'required|string|max:200',
            'kind' => 'required|in:other_doc,approval_file',
        ]);

        $file = $r->file('file');
        $path = $file->store("customer_verifications/{$cv->id_verification}/evaluation", 'public');

        $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
        $kyc['evaluation'] = $kyc['evaluation'] ?? [];

        $payload = [
            'name' => $r->input('name'),
            'path' => $path,
            'url'  => \Storage::disk('public')->url($path),
        ];

        if ($r->input('kind') === 'other_doc') {
            $kyc['evaluation']['other_doc'] = $payload;
        } else {
            $list = $kyc['evaluation']['approval_files'] ?? [];
            $list[] = $payload;
            $kyc['evaluation']['approval_files'] = $list;
        }

        $cv->update(['finance_data_kyc' => $kyc]);

        return response()->json($payload);
    }




    public function saveReviewData(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);
        $payload = $r->validate([
            'review_form' => 'required|array',
        ]);

        $kyc = $cv->finance_data_kyc ?? [];
        $kyc['form'] = $payload['review_form'];

        $cv->update([
            'finance_data_kyc' => $kyc,
        ]);

        return response()->json(['ok' => true]);
    }

    public function uploadReviewFile(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);

        $r->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar'
        ]);

        $path = $r->file('file')->store("customer_verifications/{$cv->id_verification}/review", 'public');

        $kyc = $cv->finance_data_kyc ?? [];
        $kyc['files'] = $kyc['files'] ?? [];
        $kyc['files'][] = [
            'name' => $r->file('file')->getClientOriginalName(),
            'path' => $path,
            'url'  => Storage::disk('public')->url($path),
        ];

        $cv->update(['finance_data_kyc' => $kyc]);

        return response()->json(end($kyc['files']));
    }


    public function getReview(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $review = CustomerReview::where('id_verification', $id)->first();

        $attachments = [];
        if ($review) {
            $attachments = DB::table('customer_review_attchment')   // <-- perbaiki nama tabel
                ->where('id_review', $review->id_review)
                ->where('id_verification', $id)
                ->orderBy('no_urut')
                ->get()
                ->map(function ($r) {
                    $r->url = Storage::disk('public')->url($r->review_attach);
                    return $r;
                });
        }

        return response()->json([
            'review'      => $review,
            'attachments' => $attachments,
        ]);
    }

    public function saveReview(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // data inti review
        $data = $r->validate([
            'review_result'            => 'nullable|integer',
            'review_pic'               => 'nullable|string|max:50',
            'review_summary'           => 'nullable|string',
            'jenis_asset'              => 'nullable|string|max:500',
            'kelengkapan_dok_tagihan'  => 'nullable|string|max:500',
            'alur_proses_periksaan'    => 'nullable|string|max:500',
            'jadwal_penerimaan'        => 'nullable|string|max:500',
            'background_bisnis'        => 'nullable|string|max:500',
            'lokasi_depo'              => 'nullable|string|max:500',
            'opportunity_bisnis'       => 'nullable|string|max:500',

            'review1'  => 'nullable|string|max:500',
            'review2'  => 'nullable|string|max:500',
            'review3'  => 'nullable|string|max:500',
            'review4'  => 'nullable|string|max:500',
            'review5'  => 'nullable|string|max:500',
            'review6'  => 'nullable|string|max:500',
            'review7'  => 'nullable|string|max:500',
            'review8'  => 'nullable|string|max:500',
            'review9'  => 'nullable|string|max:500',
            'review10' => 'nullable|string|max:500',
            'review11' => 'nullable|string|max:500',
            'review12' => 'nullable|string|max:500',
            'review13' => 'nullable|string|max:500',
            'review14' => 'nullable|string|max:500',
            'review15' => 'nullable|string|max:500',
            'review16' => 'nullable|string|max:500',

            // sumber nilai CL diajukan (boleh kirim salah satu)
            'credit_limit_diajukan'    => 'nullable|string',
            'review_form'              => 'nullable|array', // bila kamu tetap kirim nested form dari FE
            // control hapus attachment lama (mirip delete all sebelum insert)
            'reset_attachments'        => 'sometimes|boolean',
        ]);

        // helper: ambil CL dari payload (prioritas: field langsung, fallback dari review_form.detail.credit_limit_proposed)
        $rawCL = $data['credit_limit_diajukan']
            ?? data_get($data, 'review_form.detail.credit_limit_proposed')
            ?? ($data['review1'] ?? null);

        // normalisasi ke integer (ambil angka saja, "10.000.000" -> 10000000)
        $creditLimit = null;
        if (!is_null($rawCL)) {
            $creditLimit = (int) preg_replace('/\D+/', '', (string) $rawCL);
        }
        $cv = CustomerVerification::select('id_verification', 'id_customer')->findOrFail($id);

        // (CA4, pivot 2026-07-10) Guard eksplisit terhadap double-forward:
        // saveReview() sekarang JUGA berperan sebagai titik pembuatan siklus
        // document_approvals (Marketing bukan lagi step approval formal,
        // method ini SELALU berarti "forward"). Kalau verification ini masih
        // punya siklus `in_progress` yang berjalan, forward ulang ditolak
        // (validation error) supaya tidak membuat siklus kedua yang tumpang
        // tindih dengan siklus yang sedang berjalan. Forward ulang SETELAH
        // siklus terakhir `rejected` tetap diperbolehkan (multi-cycle by
        // design, lihat CustomerVerification::documentApprovals() morphMany).
        $hasInProgressCycle = $cv->documentApprovals()
            ->where('status', DocumentApprovalStatus::InProgress)
            ->exists();

        if ($hasInProgressCycle) {
            return response()->json([
                'message' => 'Verifikasi ini masih memiliki siklus persetujuan yang sedang berjalan (Admin Finance/BM belum memutuskan) — tidak bisa forward ulang.',
            ], 422);
        }

        DB::transaction(function () use ($cv, $data, $creditLimit, $r) {

            // 1) upsert review
            $row = CustomerReview::updateOrCreate(
                ['id_verification' => $cv->id_verification],
                array_merge($data, [
                    'review_tanggal' => now(),
                    'review_pic'     => $data['review_pic'] ?? (auth()->user()->name ?? null),
                ])
            );

            // 2) set reviewed & disposisi_result = 0 (sesuai SQL lama, kolom
            // lama tetap ditulis untuk kompatibilitas tampilan lama — lihat
            // catatan coexistence di laporan Hephaestus)
            CustomerVerification::where('id_verification', $cv->id_verification)
                ->update([
                    'is_reviewed'      => 1,
                    'disposisi_result' => 0,
                ]);

            // 2b) (CA4, pivot 2026-07-10) Marketing bukan lagi step approval
            // formal -- saveReview() (forward) sekarang membuat/membuat-ulang
            // siklus document_approvals 2-step (Admin Finance -> BM, keduanya
            // pending, current_step_order=1). Guard double-forward sudah
            // dicek di atas SEBELUM transaction ini, jadi di titik ini aman
            // untuk selalu membuat siklus baru (baik forward pertama kali,
            // maupun forward ulang pasca-reject).
            $this->createDocumentApprovalCycle($cv);

            $affected = DB::table('customers')
                ->where('id_customer', $cv->id_customer)
                ->update(['credit_limit_diajukan' => $creditLimit]);

            // 3) update credit_limit_diajukan di customers bila ada nilainya
            if ($affected === 0) {
                \Log::warning('CL not updated', [
                    'id_customer' => $cv->id_customer,
                    'credit_limit' => $creditLimit
                ]);
            }

            // 4) OPSIONAL: hapus semua attachment lama (meniru "DELETE FROM ... WHERE id_review = ?")
            if ($r->boolean('reset_attachments')) {
                CustomerReviewAttachment::where('id_review', $row->id_review)
                    ->where('id_verification', $cv->id_verification)
                    ->delete();
            }
        });

        return response()->json([
            'ok' => true,
            'message' => 'Review tersimpan & diforward ke Admin Finance.',
        ]);
    }

    public function uploadReviewAttachment(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $review = CustomerReview::firstOrCreate(
            ['id_verification' => $id],
            ['review_tanggal' => now()]
        );

        $r->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,zip,rar'
        ]);

        $file = $r->file('file');
        $path = $file->store("customer_verifications/{$id}/review", 'public');
        $ori  = $file->getClientOriginalName();

        // pakai tabel yang benar
        $next = ((int) DB::table('customer_review_attchment')
            ->where('id_review', $review->id_review)
            ->where('id_verification', $id)
            ->max('no_urut')) + 1;

        \Log::info('UPL-ATTCH', [
            'id_ver' => $id,
            'path'   => $path,
            'ori'    => $ori,
            'next'   => $next,
        ]);


        DB::table('customer_review_attchment')->insert([
            'id_review'         => $review->id_review,
            'id_verification'   => $id,
            'no_urut'           => $next,
            'review_attach'     => $path,
            'review_attach_ori' => $ori,
        ]);

        return response()->json([
            'no_urut' => $next,
            'name'    => $ori,
            'path'    => $path,
            'url'     => Storage::disk('public')->url($path),
        ]);
    }



    public function deleteReviewAttachment(int $id, int $no)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $review = CustomerReview::where('id_verification', $id)->firstOrFail();
        $att = CustomerReviewAttachment::where('id_review', $review->id_review)
            ->where('id_verification', $id)
            ->where('no_urut', $no)
            ->firstOrFail();

        // hapus file fisik (opsional)
        if ($att->review_attach) {
            Storage::disk('public')->delete($att->review_attach);
        }
        $att->delete();

        return response()->json(['ok' => true]);
    }

    public function approve(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::findOrFail($id);
        $cv->update(['disposisi_result' => 1]); // tandai disetujui
        return response()->json(['ok' => true, 'message' => 'Persetujuan dikirim.']);
    }

    /**
     * (CA8, pivot 2026-07-10, dulu C6) Rewire: antrean Admin Finance sekarang
     * berdasarkan document_approval_steps step 1 (Admin Finance jadi step
     * pertama di model 2-step baru, dulu step 2) berstatus pending (role
     * id_role=9), bukan is_active/is_reviewed/disposisi_result.
     */
    public function reviewAdminIndex(Request $r)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $per = (int) $r->query('per_page', 25);
        $q   = trim((string) $r->query('q', ''));

        $rows = $this->pendingStepQuery(self::ROLE_ADMIN_FINANCE)
            ->with(['customer:id_customer,kode_pelanggan,nama_perusahaan,alamat_perusahaan,telepon,fax'])
            ->when($q !== '', function ($w) use ($q) {
                $w->whereHas('customer', function ($c) use ($q) {
                    $c->where('nama_perusahaan', 'like', "%{$q}%")
                        ->orWhere('alamat_perusahaan', 'like', "%{$q}%")
                        ->orWhere('kode_pelanggan', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id_verification')
            ->paginate($per);

        return response()->json([
            'data'         => $rows->items(),
            'current_page' => $rows->currentPage(),
            'last_page'    => $rows->lastPage(),
            'total'        => $rows->total(),
        ]);
    }

    public function reviewAdminStats()
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $queue = $this->pendingStepQuery(self::ROLE_ADMIN_FINANCE)->count();

        return response()->json(['queue' => $queue]);
    }

    // OPSIONAL: untuk melanjutkan tahap (misal dari admin -> logistik)
    public function setDisposisi(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $r->validate([
            'to'   => 'required|integer|in:1,2,3,4', // 1=Admin,2=Logistik,3=BM,4=OM
        ]);

        $row = CustomerVerification::findOrFail($id);
        $row->update(['disposisi_result' => $data['to']]);

        return response()->json(['ok' => true, 'disposisi_result' => (int)$data['to']]);
    }
    public function getEvaluation(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv  = \App\Models\CustomerVerification::findOrFail($id);
        $kyc = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];

        return response()->json([
            'evaluation' => $kyc['evaluation'] ?? [
                'top'                    => 'CREDIT 30 days After Invoice Receive',
                'potential_volume'       => null,
                'potential_unit'         => 'Liter',
                'credit_limit_proposed'  => null,
                'jenis_data'             => 'Sebelum Persetujuan Komite',
                'financial_review'       => '',
            ],
            // kirim juga disposisi agar FE bisa lock
            'disposisi_result' => (int) ($cv->disposisi_result ?? 0),
        ]);
    }

    public function saveEvaluation(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Validasi sesuai payload FE (approval di dalam form)
        $payload = $r->validate([
            // (Prioritas C-Amend gap-fix, 2026-07-10) reject path Admin
            // Finance -- pola sama persis dengan bmVerify(): 'decision'
            // nullable APPROVE/REJECT (default APPROVE bila tidak dikirim),
            // 'notes' dipakai untuk decision_note reject supaya nama field
            // konsisten antara kedua endpoint step approval ini.
            'decision'                           => ['nullable', 'in:APPROVE,REJECT'],
            'notes'                              => ['nullable', 'string'],

            'form'                               => ['required', 'array'],
            'form.top_text'                      => ['nullable', 'string'],
            'form.potential_volume'              => ['nullable', 'string'],
            'form.jenis_data'                    => ['required', 'in:SEBELUM,SETELAH'],
            'form.financial_review'              => ['nullable', 'string'],

            'form.evaluation_numbers'            => ['nullable', 'array'],
            'form.evaluation_numbers.*'          => ['nullable', 'string'],

            'form.kyc_rows'                      => ['nullable', 'array'],
            'form.kyc_rows.*.label'              => ['nullable', 'string'],
            'form.kyc_rows.*.name'               => ['nullable', 'string'],
            'form.kyc_rows.*.path'               => ['nullable', 'string'],
            'form.kyc_rows.*.url'                => ['nullable', 'string'],

            'form.approval'                      => ['nullable', 'array'],
            'form.approval.approval_credit_limit' => ['nullable', 'string'],
            'form.approval.payment_type'         => ['nullable', 'in:CREDIT,CASH'],
            'form.approval.top_days'             => ['nullable', 'string'],
            'form.approval.top_basis'            => ['nullable', 'string'],
            'form.approval.group_company'        => ['nullable', 'string'],

            'form.approval.docs'                 => ['nullable', 'array'],
            'form.approval.docs.customer_db'     => ['boolean'],
            'form.approval.docs.siup'            => ['boolean'],
            'form.approval.docs.notarial'        => ['boolean'],
            'form.approval.docs.lcr'             => ['boolean'],
            'form.approval.docs.npwp'            => ['boolean'],
            'form.approval.docs.finstat'         => ['boolean'],
            'form.approval.docs.top'             => ['boolean'],
            'form.approval.docs.customer_review' => ['boolean'],
            'form.approval.docs.others'          => ['boolean'],
            'form.approval.docs_others_text'     => ['nullable', 'string'],

            'form.approval.other_document'       => ['nullable', 'string'],
            'form.approval.logistik_summary'     => ['nullable', 'string'],
            'form.approval.logistik_result'      => ['nullable', 'string'],
            'form.approval.assessment_result'    => ['nullable', 'string'],
        ]);

        // default APPROVE, sama persis pola $data['decision'] ?? 'APPROVE' di bmVerify()
        $decision = $payload['decision'] ?? 'APPROVE';
        $notes    = $payload['notes'] ?? null;

        $form     = $payload['form'];
        $approval = $form['approval'] ?? [];

        // --- Sanitasi seperti script lama ---
        $creditLimitRaw = $approval['approval_credit_limit'] ?? null;
        $creditLimit    = is_null($creditLimitRaw) ? null : (int) preg_replace('/\D+/', '', (string) $creditLimitRaw);

        $summaryRaw = (string) ($form['financial_review'] ?? '');
        $summary    = nl2br(e($summaryRaw), false);

        $dokumenLainnya = e((string) ($approval['other_document'] ?? ''));

        // --- finance_data (nomor & checklist dokumen) ---
        $arrData = [];
        foreach ((array) ($form['evaluation_numbers'] ?? []) as $no) {
            if ($no !== null && $no !== '') {
                $arrData[] = ['nomor' => e((string) $no)];
            }
        }
        $docs = (array) ($approval['docs'] ?? []);
        if (!empty($docs)) {
            $checked = [];
            foreach ($docs as $k => $v) if ($v) $checked[] = $k;
            if ($checked) $arrData[] = ['nomor' => implode(',', $checked)];
            if ($dokumenLainnya !== '') $arrData[] = $dokumenLainnya;
        }

        $incomingKyc = (array) ($form['kyc_rows'] ?? []);

        DB::transaction(function () use ($id, $form, $approval, $arrData, $incomingKyc, $summary, $creditLimit, $decision, $notes) {

            // Kunci baris
            $cv = \App\Models\CustomerVerification::lockForUpdate()->findOrFail($id);

            // Hapus file KYC lama yang tidak dipertahankan
            $oldKyc    = json_decode($cv->finance_data_kyc ?? '[]', true) ?: [];
            $keepPaths = collect($incomingKyc)->pluck('path')->filter()->values()->all();
            foreach ($oldKyc as $row) {
                $oldPath = $row['filenya'] ?? ($row['path'] ?? null);
                if ($oldPath && !in_array($oldPath, $keepPaths, true)) {
                    \Storage::disk('public')->delete($oldPath);
                }
            }

            // Normalisasi KYC simpan
            $kycSaved = [];
            foreach ($incomingKyc as $idx => $row) {
                $kycSaved[$idx] = [
                    'id_detail'       => $idx,
                    'nama_file'       => e((string) ($row['label'] ?? $row['name'] ?? '')),
                    'filenya'         => (string) ($row['path'] ?? ''),
                    'file_upload_ori' => (string) ($row['name'] ?? basename($row['path'] ?? '')),
                ];
            }

            // Map jenis data (tetap disimpan kalau perlu)
            $jenisDataInt = ($form['jenis_data'] ?? 'SEBELUM') === 'SETELAH' ? 2 : 1;

            // (Prioritas C-Amend gap-fix, 2026-07-10) nilai kolom lama sekarang
            // bergantung ke $decision, bukan selalu dipaksa "lolos":
            // - APPROVE (default, perilaku existing tidak berubah): finance_result=1,
            //   disposisi_result=2.
            // - REJECT: finance_result=0 (konsisten dengan konvensi sm_result=0
            //   untuk reject di bmVerify() -- sibling _result column di
            //   controller yang sama), disposisi_result=0 (kembali ke
            //   Marketing/Draft di level kolom lama, selaras dengan sistem baru
            //   yang membuka lagi antrean Marketing lewat marketingQueueQuery()
            //   begitu cycle document_approvals ditutup rejected).
            if ($decision === 'REJECT') {
                $financeResult = 0;
                $disp          = 0;
            } else {
                $financeResult = 1;
                $disp          = 2;
            }

            // Update verification -- data evaluasi (KYC, ringkasan finansial,
            // dokumen, dst.) tetap ditulis apa adanya baik APPROVE maupun
            // REJECT: ini kerja/catatan Admin Finance yang sudah diinput,
            // tidak ada alasan bisnis untuk dibuang hanya karena hasil
            // keputusannya reject.
            $cv->update([
                'finance_data'        => json_encode($arrData, JSON_UNESCAPED_UNICODE),
                'jenis_datanya'       => $jenisDataInt,
                'finance_summary'     => $summary,
                'finance_result'      => $financeResult,
                'finance_tgl_proses'  => now(),
                'finance_pic'         => auth()->user()->name ?? null,
                'finance_data_kyc'    => json_encode($kycSaved, JSON_UNESCAPED_UNICODE),
                'disposisi_result'    => $disp,
            ]);

            // (CA5, pivot 2026-07-10, dulu C3; gap-fix 2026-07-10 menambah
            // reject) Approve -> step Admin Finance approved, current_step_order
            // maju ke step berikutnya (BM) di dalam advanceApprovalStep().
            // Reject -> step Admin Finance rejected, cycle document_approvals
            // ditutup rejected sepenuhnya di dalam advanceApprovalStep() (tidak
            // perlu kode eksplisit "kirim balik ke Marketing" -- queue
            // Marketing di CA7 sudah otomatis menangkap ini via
            // marketingQueueQuery()).
            //
            // (Prioritas H2, 2026-07-13) step_order literal (dulu
            // self::STEP_ADMIN_FINANCE) sekarang di-resolve dinamis: cari
            // step di template aktif yang id_role-nya cocok ROLE_ADMIN_FINANCE.
            // Kalau tidak ketemu (mis. admin ganti role step ini lewat CRUD
            // tanpa update kode), silent no-op + Log::warning -- perilaku
            // defensif yang sama seperti saat advanceApprovalStep() tidak
            // menemukan approval/step in_progress, TIDAK ada guard/error baru.
            $adminFinanceTemplate  = $this->activeApprovalTemplate();
            $adminFinanceStepOrder = $adminFinanceTemplate
                ? $this->resolveStepOrderForRole($adminFinanceTemplate, self::ROLE_ADMIN_FINANCE)
                : null;

            if ($adminFinanceStepOrder !== null) {
                $this->advanceApprovalStep(
                    $cv,
                    $adminFinanceStepOrder,
                    $decision === 'REJECT' ? DocumentApprovalStepStatus::Rejected : DocumentApprovalStepStatus::Approved,
                    $decision === 'REJECT' ? $notes : ($summary !== '' ? $summary : null)
                );
            } else {
                Log::warning('Tidak menemukan step_order untuk role Admin Finance di template customer_verification aktif saat saveEvaluation.', [
                    'id_verification' => $cv->id_verification,
                ]);
            }

            // Jika SETELAH komite, sinkron ke customers (opsional, tetap dipertahankan)
            if ($jenisDataInt === 2) {
                $payType  = $approval['payment_type'] ?? null; // CREDIT/CASH
                $topDays  = $approval['top_days']   ?? null;
                $topBasis = $approval['top_basis']  ?? null;

                $updateCustomer = [
                    'dokumen_lainnya' => $approval['other_document'] ?? null,
                    'credit_limit'    => $creditLimit,
                    'jenis_payment'   => $payType,
                ];

                if ($payType === 'CREDIT') {
                    $updateCustomer['top_payment'] = $topDays;
                    $updateCustomer['jenis_net']   = $topBasis;
                } else {
                    $updateCustomer['top_payment'] = null;
                    $updateCustomer['jenis_net']   = null;
                }

                \DB::table('customers')
                    ->where('id_customer', $cv->id_customer)
                    ->update($updateCustomer);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function logistikShow(int $id)
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $cv = CustomerVerification::with('customer')->findOrFail($id);

        // business_type ambil dari legal/corporate kalau ada
        $legal = json_decode($cv->legal_data ?? '{}', true);
        $businessType = $legal['corporate']['ownership'] ?? ($legal['corporate']['tipe_bisnis_text'] ?? '-');

        return response()->json([
            'customer'      => $cv->customer ? [
                'nama_perusahaan'   => $cv->customer->nama_perusahaan,
                'alamat_perusahaan' => $cv->customer->alamat_perusahaan,
            ] : null,
            'business_type' => $businessType,
            'form' => [
                'logistik_summary'  => $cv->logistik_summary ?? '',
                'logistik_result'   => $cv->logistik_result_text ?? ($cv->logistik_result ?? ''), // sesuaikan nama kolom Anda
                'assessment_result' => $cv->assessment_result ?? '',
            ],
        ]);
    }

    /**
     * (CA8, pivot 2026-07-10, dulu C6) Rewire: antrean BM sekarang berdasarkan
     * document_approval_steps step 2 (FINAL di model 2-step baru, dulu step 3)
     * berstatus pending (role id_role=8), bukan is_reviewed/disposisi_result.
     */
    public function reviewBmIndex(Request $r)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $q       = trim((string) $r->query('q', ''));
        $perPage = (int) ($r->query('per_page', 25));

        $rows = $this->pendingStepQuery(self::ROLE_BM)
            ->with(['customer']) // pastikan relasi ada
            ->when($q, function ($qq) use ($q) {
                $qq->whereHas('customer', function ($c) use ($q) {
                    $c->where('nama_perusahaan', 'ilike', "%{$q}%")
                        ->orWhere('alamat_perusahaan', 'ilike', "%{$q}%")
                        ->orWhere('kode_pelanggan', 'ilike', "%{$q}%");
                });
            })
            ->orderByDesc('id_verification')
            ->paginate($perPage);

        return response()->json($rows);
    }

    public function reviewBmStats()
    {
        if (auth()->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $queue = $this->pendingStepQuery(self::ROLE_BM)->count();
        return response()->json(['queue' => $queue]);
    }

    /**
     * Simpan verifikasi BM (step 2, FINAL, di model 2-step baru — pivot
     * 2026-07-10):
     * - bm_notes: catatan BM
     * - bm_decision: APPROVE / REJECT (REVISE dihapus total, tidak ada lagi
     *   konsep "kirim balik untuk revisi" di sistem approval baru — reject di
     *   step manapun menutup siklus, verifikasi kembali ke Marketing untuk
     *   diedit & di-forward ulang sebagai siklus baru, lihat CA4/CA7)
     * Default: APPROVE -> bm_result=1, disposisi=4 (OM, kolom lama)
     */
    public function bmVerify(Request $r, int $id)
    {
        if ($r->user()->cant('verification.customer')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $r->validate([
            'notes'    => ['nullable', 'string'],
            'decision' => ['nullable', 'in:APPROVE,REJECT'],
        ]);

        return DB::transaction(function () use ($id, $data) {
            /** @var \App\Models\CustomerVerification $cv */
            $cv = CustomerVerification::lockForUpdate()->findOrFail($id);

            // default approval
            $decision = $data['decision'] ?? 'APPROVE';

            $updates = [
                'bm_notes'       => $data['notes'] ?? null,
                'bm_decision'    => $decision,
                'bm_tgl_proses'  => now(),
                'bm_pic'         => auth()->user()->name ?? null,
            ];

            if ($decision === 'APPROVE') {
                $updates['sm_result']       = 1;  // OK
                $updates['disposisi_result'] = 4;  // ke OM
            } else { // REJECT
                $updates['sm_result']       = 0;
                // tetap di BM untuk diproses/arsip (atau set ke 0 sesuai kebijakan)
                $updates['disposisi_result'] = 3;
            }

            $cv->update($updates);

            // ===== (CA6, pivot 2026-07-10, dulu C4/C5) sistem approval baru: document_approvals / document_approval_steps =====
            // BM adalah step FINAL di model 2-step baru (Marketing bukan
            // step formal lagi, Logistik & OM sudah dihapus dari alur, lihat
            // Prioritas D).
            //
            // (Prioritas H2, 2026-07-13) step_order literal `2` (dulu
            // hardcoded langsung, bukan bahkan lewat konstanta) sekarang
            // di-resolve dinamis: cari step di template aktif yang
            // id_role-nya cocok ROLE_BM. Kalau tidak ketemu, silent no-op +
            // Log::warning di kedua branch (APPROVE/REJECT) -- perilaku
            // defensif yang sama seperti saat advanceApprovalStep() tidak
            // menemukan approval/step in_progress, TIDAK ada guard/error
            // baru.
            $bmTemplate  = $this->activeApprovalTemplate();
            $bmStepOrder = $bmTemplate
                ? $this->resolveStepOrderForRole($bmTemplate, self::ROLE_BM)
                : null;

            if ($bmStepOrder === null) {
                Log::warning('Tidak menemukan step_order untuk role BM di template customer_verification aktif saat bmVerify.', [
                    'id_verification' => $cv->id_verification,
                    'decision'        => $decision,
                ]);
            }

            if ($decision === 'APPROVE') {
                $approval = $bmStepOrder !== null
                    ? $this->advanceApprovalStep(
                        $cv,
                        $bmStepOrder,
                        DocumentApprovalStepStatus::Approved,
                        $data['notes'] ?? null
                    )
                    : null;

                // (C5 lama, dipertahankan) Efek samping yang dulu dipicu oleh
                // method OM (sekarang dihapus, lihat Prioritas D1.1) saat
                // approve direlokasi ke sini: HANYA terpicu kalau cycle-nya
                // BENAR-BENAR ditutup approved (bukan cuma "advanceApprovalStep
                // mengembalikan non-null", yang juga bisa berarti cycle masih
                // in_progress karena maju ke step berikutnya -- relevan sejak
                // step_order jadi dinamis/bisa di-reorder lewat CRUD, Prioritas H).
                if ($approval?->status === DocumentApprovalStatus::Approved) {
                    $cv->update([
                        'is_approved'      => 1,
                        'tanggal_approved' => now(),
                        'role_approve'     => 4,
                    ]);

                    DB::table('customers')
                        ->where('id_customer', $cv->id_customer)
                        ->update(['is_verified' => 1]);
                }
            } else { // REJECT
                if ($bmStepOrder !== null) {
                    $this->advanceApprovalStep(
                        $cv,
                        $bmStepOrder,
                        DocumentApprovalStepStatus::Rejected,
                        $data['notes'] ?? null
                    );
                }
                // Efek samping approve (is_approved/tanggal_approved/role_approve/
                // customers.is_verified) SENGAJA tidak terpicu di jalur reject ini.
            }

            return response()->json(['ok' => true]);
        });
    }
}
