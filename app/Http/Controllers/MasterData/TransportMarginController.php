<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterData\TransportMarginRowResource;
use App\Models\PricePeriod;
use App\Models\ProductPrice;
use Illuminate\Http\Request;

class TransportMarginController extends Controller
{
    private const PERMISSION = 'product-price.manage-transport-margin';

    public function index(Request $request)
    {
        if ($request->user()->cant(self::PERMISSION)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $period = $request->filled('price_period_id')
            ? PricePeriod::find($request->query('price_period_id'))
            : $this->periodCoveringToday();

        if (!$period) {
            return response()->json(['data' => [], 'period' => null]);
        }

        $rows = ProductPrice::query()
            ->where('price_period_id', $period->id)
            ->where('cogs_basis', 'franco')
            ->with(['pricePeriod', 'cabang', 'produk.ukuran'])
            ->get()
            ->sortBy(fn (ProductPrice $row) => [$row->cabang->nama_cabang, $row->produk->nama_produk])
            ->values();

        return response()->json([
            'data'   => TransportMarginRowResource::collection($rows)->resolve(),
            'period' => $this->periodPayload($period),
        ]);
    }

    public function periods(Request $request)
    {
        if ($request->user()->cant(self::PERMISSION)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $periods = PricePeriod::query()
            ->withCount([
                'productPrices as franco_count' => fn ($q) => $q->where('cogs_basis', 'franco'),
                'productPrices as filled_count' => fn ($q) => $q->where('cogs_basis', 'franco')
                    ->whereNotNull('margin_transport_amount'),
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('end_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PricePeriod $period) => $this->periodPayload($period) + [
                'franco_count' => $period->franco_count,
                'filled_count' => $period->filled_count,
            ]);

        return response()->json(['data' => $periods]);
    }

    public function update(Request $request, ProductPrice $productPrice)
    {
        if ($request->user()->cant(self::PERMISSION)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'margin_transport_amount' => ['present', 'nullable', 'numeric', 'min:0'],
        ]);

        if ($productPrice->cogs_basis !== 'franco') {
            return response()->json(['message' => 'Margin transport hanya berlaku untuk baris franco.'], 422);
        }

        if ($productPrice->pricePeriod->isEnded()) {
            return response()->json(['message' => 'Periode harga sudah berakhir dan tidak dapat diubah.'], 422);
        }

        $productPrice->update([
            'margin_transport_amount' => $validated['margin_transport_amount'],
            'updated_by'              => $request->user()->name ?? 'system',
        ]);

        return response()->json((new TransportMarginRowResource(
            $productPrice->load(['pricePeriod', 'cabang', 'produk.ukuran'])
        ))->resolve());
    }

    private function periodCoveringToday(): ?PricePeriod
    {
        return PricePeriod::query()
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();
    }

    private function periodPayload(PricePeriod $period): array
    {
        return [
            'id'         => $period->id,
            'start_date' => $period->start_date->format('Y-m-d'),
            'end_date'   => $period->end_date->format('Y-m-d'),
            'is_ended'   => $period->isEnded(),
        ];
    }
}
