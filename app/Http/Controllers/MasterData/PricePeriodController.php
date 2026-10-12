<?php

namespace App\Http\Controllers\MasterData;

use App\Actions\PricePeriod\CreatePricePeriodFromLatestAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\MasterData\PricePeriodResource;
use App\Http\Resources\MasterData\ProductPriceResource;
use App\Models\PricePeriod;
use App\Models\ProductPrice;
use App\Support\ProductPrice\PricePeriodCompletenessQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class PricePeriodController extends Controller
{
    private array $fieldPermissions = [
        'cogs_price'    => 'product-price.manage',
        'price_list'    => 'product-price.verify',
        'price_list_pe' => 'product-price.verify',
        'margin_material_amount' => 'product-price.verify',
        'bm_price'      => 'product-price.verify',
        'om_price'      => 'product-price.verify',
        'ceo_price'     => 'product-price.verify',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->cant('price-period.view') && $user->cant('price-period.consume')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return PricePeriodResource::collection(app(PricePeriodCompletenessQuery::class)->grouped());
    }

    public function show(Request $request, PricePeriod $pricePeriod)
    {
        $user = $request->user();
        if ($user->cant('price-period.view') && $user->cant('price-period.consume')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return new PricePeriodResource($pricePeriod);
    }

    public function store(Request $request)
    {
        if ($request->user()->cant('price-period.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'price_period.start_date'        => 'required|date',
            'price_period.end_date'          => 'required|date|after_or_equal:price_period.start_date',
            'price_period.notes'             => 'nullable|string',
            'attachments'                    => 'nullable|array',
            'attachments.*'                  => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'duplicate_from_latest'          => 'nullable|boolean',
            'product_prices'                 => ['nullable', 'array', Rule::prohibitedIf(fn () => $request->boolean('duplicate_from_latest'))],
            'product_prices.*.branch_id'     => 'required|exists:cabangs,id_cabang',
            'product_prices.*.product_id'    => 'required|exists:produks,id_produk',
            'product_prices.*.price_list'    => 'nullable|numeric|min:0',
            'product_prices.*.price_list_pe' => 'nullable|numeric|min:0',
            'product_prices.*.bm_price'      => 'nullable|numeric|min:0',
            'product_prices.*.cogs_price'    => 'nullable|numeric|min:0',
            'product_prices.*.cogs_basis'    => 'required|in:loco,franco',
            'product_prices.*.margin_material_amount' => 'nullable|numeric|min:0',
            'product_prices.*.om_price'      => 'nullable|numeric|min:0',
            'product_prices.*.ceo_price'     => 'nullable|numeric|min:0',
            'product_prices.*.notes'         => 'nullable|string',
        ]);

        $forbidden = $this->checkFieldOwnership($request, $data['product_prices'] ?? []);
        if ($forbidden) {
            return $forbidden;
        }

        $existingPeriod = PricePeriod::where('start_date', $data['price_period']['start_date'])
            ->where('end_date', $data['price_period']['end_date'])
            ->first();

        if ($request->boolean('duplicate_from_latest')) {
            return $this->storeFromLatest($request, $data, $existingPeriod);
        }

        if ($existingPeriod?->isEnded()) {
            return $this->endedPeriodResponse();
        }

        $this->assertTierOrder($data['product_prices'] ?? []);

        if ($existingPeriod && !empty($data['product_prices'])) {
            $duplicate = $this->findDuplicateProductPrices($existingPeriod->id, $data['product_prices']);

            if ($duplicate) {
                return response()->json(['message' => $duplicate], 422);
            }
        }

        $attachments = $this->storeUploadedAttachments($request->file('attachments', []));

        [$period, $productPrices] = DB::transaction(function () use ($data, $attachments, $request, $existingPeriod) {
            if ($existingPeriod) {
                $period = $existingPeriod;

                if (!empty($attachments)) {
                    $period->update([
                        'attachments' => array_merge($period->attachments ?? [], $attachments),
                        'updated_by'  => $request->user()?->name ?? 'system',
                    ]);
                }
            } else {
                $period = PricePeriod::create([
                    'start_date'  => $data['price_period']['start_date'],
                    'end_date'    => $data['price_period']['end_date'],
                    'notes'       => $data['price_period']['notes'] ?? null,
                    'attachments' => $attachments,
                    'created_by'  => $request->user()?->name ?? 'system',
                ]);
            }

            $ids = collect($data['product_prices'] ?? [])->map(function ($row) use ($period, $request) {
                foreach (['price_list', 'price_list_pe', 'bm_price', 'cogs_price', 'margin_material_amount', 'om_price', 'ceo_price'] as $k) {
                    $row[$k] = $row[$k] ?? 0;
                }

                $row['price_period_id'] = $period->id;
                $row['created_by'] = $request->user()?->name ?? 'system';

                return ProductPrice::create($row)->id;
            });

            $productPrices = ProductPrice::whereIn('id', $ids)
                ->with(['pricePeriod', 'cabang', 'produk.ukuran.satuan'])
                ->get();

            return [$period->fresh(), $productPrices];
        });

        return response()->json([
            'price_period'          => new PricePeriodResource($period),
            'product_prices'        => ProductPriceResource::collection($productPrices),
            'reused_existing_period' => (bool) $existingPeriod,
        ], 201);
    }

    private function storeFromLatest(Request $request, array $data, ?PricePeriod $existingPeriod)
    {
        if ($existingPeriod) {
            return response()->json(['message' => 'Periode dengan tanggal ini sudah ada. Duplikasi hanya untuk periode baru.'], 422);
        }

        $action = app(CreatePricePeriodFromLatestAction::class);
        $source = $action->findSourcePeriod();

        if (!$source) {
            return response()->json(['message' => 'Belum ada periode harga untuk disalin.'], 422);
        }

        $attachments = $this->storeUploadedAttachments($request->file('attachments', []));

        try {
            $result = $action->execute($source, $data['price_period'], $attachments, $request->user()?->name ?? 'system');
        } catch (\Throwable $e) {
            foreach ($attachments as $attachment) {
                Storage::disk('public')->delete($attachment['path']);
            }

            throw $e;
        }

        return response()->json([
            'price_period'           => new PricePeriodResource($result['period']),
            'product_prices'         => ProductPriceResource::collection($result['product_prices']),
            'reused_existing_period' => false,
            'duplicated_from'        => [
                'id'    => $source->id,
                'label' => (new PricePeriodResource($source))->resolve($request)['label'],
            ],
            'duplicated_count'       => $result['duplicated_count'],
            'skipped_inactive_count' => $result['skipped_inactive_count'],
        ], 201);
    }

    private function findDuplicateProductPrices(int $periodId, array $rows): ?string
    {
        $combos = collect($rows)->map(fn ($row) => $row['branch_id'] . ':' . $row['product_id']);

        $existing = ProductPrice::where('price_period_id', $periodId)
            ->with(['cabang', 'produk'])
            ->get()
            ->filter(fn ($p) => $combos->contains($p->branch_id . ':' . $p->product_id));

        if ($existing->isEmpty()) {
            return null;
        }

        $labels = $existing
            ->map(fn ($p) => ($p->cabang->nama_cabang ?? $p->branch_id) . ' - ' . ($p->produk->nama_produk ?? $p->product_id))
            ->implode(', ');

        return "Periode ini sudah ada. Kombinasi Cabang + Produk berikut sudah punya harga di periode tersebut, silakan edit periode yang sudah ada: {$labels}.";
    }

    public function update(Request $request, PricePeriod $pricePeriod)
    {
        if ($request->user()->cant('price-period.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $period = $pricePeriod;

        if ($period->isEnded()) {
            return $this->endedPeriodResponse();
        }

        $data = $request->validate([
            'price_period.start_date'          => 'required|date',
            'price_period.end_date'            => 'required|date|after_or_equal:price_period.start_date',
            'price_period.notes'               => 'nullable|string',
            'attachments'                       => 'nullable|array',
            'attachments.*'                     => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'remove_attachments'                => 'nullable|array',
            'remove_attachments.*'              => 'integer',
            'product_prices'                    => 'nullable|array',
            'product_prices.*.id'               => 'nullable|integer',
            'product_prices.*.branch_id'        => 'required_without:product_prices.*.id|exists:cabangs,id_cabang',
            'product_prices.*.product_id'       => 'required_without:product_prices.*.id|exists:produks,id_produk',
            'product_prices.*.price_list'       => 'nullable|numeric|min:0',
            'product_prices.*.price_list_pe'    => 'nullable|numeric|min:0',
            'product_prices.*.bm_price'         => 'nullable|numeric|min:0',
            'product_prices.*.cogs_price'       => 'nullable|numeric|min:0',
            'product_prices.*.cogs_basis'       => 'nullable|in:loco,franco',
            'product_prices.*.margin_material_amount'    => 'nullable|numeric|min:0',
            'product_prices.*.om_price'         => 'nullable|numeric|min:0',
            'product_prices.*.ceo_price'        => 'nullable|numeric|min:0',
            'product_prices.*.notes'            => 'nullable|string',
        ]);

        $forbidden = $this->checkFieldOwnership($request, $data['product_prices'] ?? []);
        if ($forbidden) {
            return $forbidden;
        }

        $this->assertTierOrder(
            $data['product_prices'] ?? [],
            ProductPrice::where('price_period_id', $period->id)
                ->whereIn('id', collect($data['product_prices'] ?? [])->pluck('id')->filter()->all())
                ->get()
                ->keyBy('id')
        );

        $existing = $period->attachments ?? [];

        foreach ($request->input('remove_attachments', []) as $index) {
            if (isset($existing[$index])) {
                Storage::disk('public')->delete($existing[$index]['path']);
                unset($existing[$index]);
            }
        }

        $existing = array_values($existing);
        $newAttachments = $this->storeUploadedAttachments($request->file('attachments', []));

        $touchedProductPrices = DB::transaction(function () use ($period, $data, $existing, $newAttachments, $request) {
            $period->update([
                'start_date'  => $data['price_period']['start_date'],
                'end_date'    => $data['price_period']['end_date'],
                'notes'       => $data['price_period']['notes'] ?? null,
                'attachments' => array_merge($existing, $newAttachments),
                'updated_by'  => $request->user()?->name ?? 'system',
            ]);

            $touchedIds = [];

            foreach ($data['product_prices'] ?? [] as $row) {
                $rowId = $row['id'] ?? null;
                unset($row['id']);

                foreach ($row as $k => $v) {
                    if ($v === null) {
                        unset($row[$k]);
                    }
                }

                if ($rowId) {
                    $productPrice = ProductPrice::where('price_period_id', $period->id)->findOrFail($rowId);
                    $row['updated_by'] = $request->user()?->name ?? 'system';
                    $productPrice->fill($row);
                    $productPrice->syncReverificationFlag(ProductPrice::submitsVerifierColumns($row));
                    $productPrice->save();
                    $touchedIds[] = $productPrice->id;
                } else {
                    foreach (['price_list', 'price_list_pe', 'bm_price', 'cogs_price', 'margin_material_amount', 'om_price', 'ceo_price'] as $k) {
                        $row[$k] = $row[$k] ?? 0;
                    }

                    $row['price_period_id'] = $period->id;
                    $row['created_by'] = $request->user()?->name ?? 'system';
                    $touchedIds[] = ProductPrice::create($row)->id;
                }
            }

            return ProductPrice::whereIn('id', $touchedIds)
                ->with(['pricePeriod', 'cabang', 'produk.ukuran.satuan'])
                ->get();
        });

        return response()->json([
            'price_period'   => new PricePeriodResource($period),
            'product_prices' => ProductPriceResource::collection($touchedProductPrices),
        ]);
    }

    // Parameter harus bernama $id agar cocok dengan segmen {id} di route eksplisit.
    public function confirmPrices(Request $request, PricePeriod $id)
    {
        if ($request->user()->cant('product-price.verify')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $period = $id;

        if ($period->isEnded()) {
            return $this->endedPeriodResponse();
        }

        $invalid = ProductPrice::where('price_period_id', $period->id)
            ->where('needs_reverification', true)
            ->withInvalidTierOrder()
            ->with(['cabang', 'produk'])
            ->get();

        $skipped = $invalid
            ->map(fn ($p) => ($p->cabang->nama_cabang ?? $p->branch_id) . ' - ' . ($p->produk->nama_produk ?? $p->product_id))
            ->values()
            ->all();

        $confirmable = ProductPrice::where('price_period_id', $period->id)
            ->where('needs_reverification', true)
            ->whereNotIn('id', $invalid->pluck('id'));

        if ($invalid->isNotEmpty() && !$confirmable->exists()) {
            return response()->json([
                'message' => 'Urutan harga tidak valid pada: ' . implode(', ', $skipped) . '. Perbaiki sebelum dikonfirmasi.',
                'skipped' => $skipped,
            ], 422);
        }

        $confirmedCount = $confirmable->update([
            'needs_reverification' => false,
            'updated_by'           => $request->user()?->name ?? 'system',
            'updated_at'           => now(),
        ]);

        return response()->json([
            'confirmed_count' => $confirmedCount,
            'skipped_count'   => count($skipped),
            'skipped'         => $skipped,
            'price_period'    => new PricePeriodResource(
                app(PricePeriodCompletenessQuery::class)->grouped($period->id)->first()
            ),
        ]);
    }
    public function destroy(Request $request, PricePeriod $pricePeriod)
    {
        if ($request->user()->cant('price-period.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $period = $pricePeriod;

        if ($period->isEnded()) {
            return $this->endedPeriodResponse();
        }

        if ($period->productPrices()->exists()) {
            return response()->json(['message' => 'Periode masih memiliki data harga terkait, tidak bisa dihapus.'], 409);
        }

        foreach ($period->attachments ?? [] as $attachment) {
            Storage::disk('public')->delete($attachment['path']);
        }

        $period->delete();

        return response()->json(null, 204);
    }

    private function assertTierOrder(array $rows, ?\Illuminate\Support\Collection $existingRows = null): void
    {
        $messages = [];

        foreach ($rows as $i => $row) {
            $current = $existingRows?->get($row['id'] ?? null)?->only(ProductPrice::TIER_ORDER) ?? [];
            $submitted = array_filter(
                array_intersect_key($row, array_flip(ProductPrice::TIER_ORDER)),
                fn ($value) => $value !== null
            );

            foreach (ProductPrice::tierOrderErrors(array_merge($current, $submitted)) as $field => $message) {
                $messages["product_prices.{$i}.{$field}"] = $message;
            }
        }

        if ($messages) {
            throw ValidationException::withMessages($messages);
        }
    }

    private function endedPeriodResponse()
    {
        return response()->json(['message' => 'Periode harga sudah berakhir dan tidak dapat diubah.'], 422);
    }

    private function checkFieldOwnership(Request $request, array $rows)
    {
        foreach ($rows as $i => $row) {
            foreach ($this->fieldPermissions as $field => $permission) {
                if (array_key_exists($field, $row) && $row[$field] !== null && $request->user()->cant($permission)) {
                    $rowNumber = $i + 1;

                    return response()->json(['message' => "Anda tidak berwenang mengisi kolom {$field} pada baris {$rowNumber}."], 403);
                }
            }
        }

        return null;
    }

    private function storeUploadedAttachments(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $path = $file->store('price_periods', 'public');

            $attachments[] = [
                'path'              => $path,
                'original_filename' => $file->getClientOriginalName(),
                'uploaded_at'       => now()->toDateTimeString(),
            ];
        }

        return $attachments;
    }
}
