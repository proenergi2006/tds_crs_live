<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Resources\MasterData\ProductPriceResource;
use App\Models\PricePeriod;
use App\Models\ProductPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductPriceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->cant('price-period.view') && $user->cant('price-period.consume')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $q = ProductPrice::query()
            ->join('price_periods', 'price_periods.id', '=', 'product_prices.price_period_id')
            ->with(['pricePeriod', 'cabang', 'produk.ukuran.satuan'])
            ->select('product_prices.*');

        if ($branchId = $request->query('branch_id')) {
            $q->where('product_prices.branch_id', $branchId);
        }

        if ($productId = $request->query('product_id')) {
            $q->where('product_prices.product_id', $productId);
        }

        if ($pricePeriodId = $request->query('price_period_id')) {
            $q->where('product_prices.price_period_id', $pricePeriodId);
        }

        if ($s = $request->query('search')) {
            $q->where(function ($q2) use ($s) {
                $q2->whereHas('produk', fn ($q3) => $q3->where('nama_produk', 'like', "%{$s}%"))
                    ->orWhereHas('cabang', fn ($q4) => $q4->where('nama_cabang', 'like', "%{$s}%"));
            });
        }

        $q->orderBy('price_periods.start_date', 'desc')
            ->orderBy('price_periods.end_date', 'desc')
            ->orderBy('product_prices.created_at', 'desc');

        if ($request->boolean('as_list')) {
            return ProductPriceResource::collection($q->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return ProductPriceResource::collection($q->paginate($perPage));
    }

    public function check(Request $request)
    {
        $request->validate([
            'produk_id' => 'required|integer',
            'tanggal'   => 'required|date',
        ]);

        $produkId = $request->query('produk_id');
        $tanggal = $request->query('tanggal');

        $harga = DB::table('product_prices')
            ->join('price_periods', 'price_periods.id', '=', 'product_prices.price_period_id')
            ->where('product_prices.product_id', $produkId)
            ->whereDate('price_periods.start_date', '<=', $tanggal)
            ->whereDate('price_periods.end_date', '>=', $tanggal)
            ->orderByDesc('price_periods.end_date')
            ->orderByDesc('product_prices.created_at')
            ->select('product_prices.price_list')
            ->first();

        return response()->json([
            'price_list' => $harga?->price_list ?? null,
            'found'      => (bool) $harga,
        ]);
    }

    public function byDate(Request $request)
    {
        if ($request->user()->cant('price-period.view')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $awal  = $request->query('start_date');
        $akhir = $request->query('end_date') ?: $awal;
        $usePe = $request->boolean('pe');

        if (!$awal) {
            return response()->json([]);
        }

        $q = DB::table('product_prices')
            ->join('price_periods', 'price_periods.id', '=', 'product_prices.price_period_id')
            ->whereDate('price_periods.start_date', '<=', $akhir)
            ->whereDate('price_periods.end_date', '>=', $awal)
            ->orderByDesc('price_periods.end_date')
            ->orderByDesc('product_prices.created_at');

        if ($branchId = $request->query('branch_id')) {
            $q->where('product_prices.branch_id', $branchId);
        }

        $cols = $usePe
            ? ['product_prices.product_id', 'product_prices.price_list', 'product_prices.price_list_pe']
            : ['product_prices.product_id', 'product_prices.price_list'];
        $rows = $q->get($cols);

        $map = [];
        foreach ($rows as $row) {
            if (array_key_exists($row->product_id, $map)) {
                continue;
            }

            $map[$row->product_id] = ($usePe && (float) ($row->price_list_pe ?? 0) > 0)
                ? $row->price_list_pe
                : $row->price_list;
        }

        return response()->json($map);
    }

    public function store(Request $request)
    {
        if ($request->user()->cant('product-price.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'price_period_id' => 'nullable|exists:price_periods,id',
            'start_date'      => 'required_without:price_period_id|date',
            'end_date'        => 'required_without:price_period_id|date|after_or_equal:start_date',
            'branch_id'       => 'required|exists:cabangs,id_cabang',
            'product_id'      => 'required|exists:produks,id_produk',
            'price_list'      => 'nullable|numeric|min:0',
            'price_list_pe'   => 'nullable|numeric|min:0',
            'bm_price'        => 'nullable|numeric|min:0',
            'cogs_price'      => 'nullable|numeric|min:0',
            'cogs_basis'      => 'required|in:loco,franco',
            'margin_amount'   => 'nullable|numeric|min:0',
            'om_price'        => 'nullable|numeric|min:0',
            'ceo_price'       => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        $fieldPermissions = [
            'cogs_price'    => 'product-price.manage',
            'price_list'    => 'product-price.verify',
            'price_list_pe' => 'product-price.verify',
            'margin_amount' => 'product-price.verify',
            'bm_price'      => 'product-price.verify',
            'om_price'      => 'product-price.verify',
            'ceo_price'     => 'product-price.verify',
        ];

        foreach ($fieldPermissions as $field => $permission) {
            if ($request->filled($field) && $request->user()->cant($permission)) {
                return response()->json(['message' => "Anda tidak berwenang mengisi kolom {$field}."], 403);
            }
        }

        foreach (['price_list', 'price_list_pe', 'bm_price', 'cogs_price', 'margin_amount', 'om_price', 'ceo_price'] as $k) {
            $data[$k] = $data[$k] ?? 0;
        }

        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;
        unset($data['start_date'], $data['end_date']);

        $productPrice = DB::transaction(function () use ($data, $startDate, $endDate, $request) {
            if (!isset($data['price_period_id'])) {
                $data['price_period_id'] = PricePeriod::firstOrCreate(
                    ['start_date' => $startDate, 'end_date' => $endDate],
                    ['created_by' => $request->user()?->name ?? 'system']
                )->id;
            }

            $data['created_by'] = $request->user()?->name ?? 'system';

            return ProductPrice::create($data);
        });

        return new ProductPriceResource($productPrice->load(['pricePeriod', 'cabang', 'produk.ukuran.satuan']));
    }

    public function show(Request $request, $id)
    {
        if ($request->user()->cant('price-period.view')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $productPrice = ProductPrice::with(['pricePeriod', 'cabang', 'produk.ukuran.satuan'])->findOrFail($id);

        return new ProductPriceResource($productPrice);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        if ($user->cant('product-price.manage') && $user->cant('product-price.verify')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->merge(array_map(fn ($v) => $v === '' ? null : $v, $request->all()));

        $data = $request->validate([
            'price_period_id' => 'nullable|exists:price_periods,id',
            'start_date'      => 'required_without:price_period_id|date',
            'end_date'        => 'required_without:price_period_id|date|after_or_equal:start_date',
            'branch_id'       => 'required|exists:cabangs,id_cabang',
            'product_id'      => 'required|exists:produks,id_produk',
            'price_list'      => 'nullable|numeric|min:0',
            'price_list_pe'   => 'nullable|numeric|min:0',
            'bm_price'        => 'nullable|numeric|min:0',
            'cogs_price'      => 'nullable|numeric|min:0',
            'cogs_basis'      => 'nullable|in:loco,franco',
            'margin_amount'   => 'nullable|numeric|min:0',
            'om_price'        => 'nullable|numeric|min:0',
            'ceo_price'       => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        $fieldPermissions = [
            'cogs_price'    => 'product-price.manage',
            'price_list'    => 'product-price.verify',
            'price_list_pe' => 'product-price.verify',
            'margin_amount' => 'product-price.verify',
            'bm_price'      => 'product-price.verify',
            'om_price'      => 'product-price.verify',
            'ceo_price'     => 'product-price.verify',
        ];

        foreach ($fieldPermissions as $field => $permission) {
            if ($request->filled($field) && $request->user()->cant($permission)) {
                return response()->json(['message' => "Anda tidak berwenang mengisi kolom {$field}."], 403);
            }
        }

        $startDate = $data['start_date'] ?? null;
        $endDate = $data['end_date'] ?? null;
        unset($data['start_date'], $data['end_date']);

        $productPrice = DB::transaction(function () use ($data, $startDate, $endDate, $request, $id) {
            if (!isset($data['price_period_id']) && $startDate && $endDate) {
                $data['price_period_id'] = PricePeriod::firstOrCreate(
                    ['start_date' => $startDate, 'end_date' => $endDate],
                    ['created_by' => $request->user()?->name ?? 'system']
                )->id;
            }

            $data['updated_by'] = $request->user()?->name ?? 'system';

            $productPrice = ProductPrice::findOrFail($id);
            $productPrice->update($data);

            return $productPrice;
        });

        return new ProductPriceResource($productPrice->load(['pricePeriod', 'cabang', 'produk.ukuran.satuan']));
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->cant('product-price.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        ProductPrice::destroy($id);

        return response()->json(null, 204);
    }
}
