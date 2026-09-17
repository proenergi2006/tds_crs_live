<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreProductPriceRequest;
use App\Http\Requests\MasterData\UpdateProductPriceRequest;
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

        if ($user->cant('price-period.view')) {
            $q->where('product_prices.bm_price', '>', 0)
                ->where('product_prices.om_price', '>', 0)
                ->where('product_prices.ceo_price', '>', 0)
                ->whereColumn('product_prices.price_list', '>=', 'product_prices.bm_price')
                ->whereColumn('product_prices.bm_price', '>=', 'product_prices.om_price')
                ->whereColumn('product_prices.om_price', '>=', 'product_prices.ceo_price');
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

    public function store(StoreProductPriceRequest $request)
    {
        if ($request->user()->cant('product-price.manage')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $fieldPermissions = [
            'cogs_material_price'  => 'product-price.manage',
            'cogs_transport_price' => 'product-price.manage',
            'price_list'           => 'product-price.verify',
            'price_list_pe'        => 'product-price.verify',
            'margin_amount'        => 'product-price.verify',
            'bm_price'             => 'product-price.verify',
            'om_price'             => 'product-price.verify',
            'ceo_price'            => 'product-price.verify',
        ];

        foreach ($fieldPermissions as $field => $permission) {
            if ($request->filled($field) && $request->user()->cant($permission)) {
                return response()->json(['message' => "Anda tidak berwenang mengisi kolom {$field}."], 403);
            }
        }

        $data = $this->computeCogsPrice($data);

        foreach (['price_list', 'price_list_pe', 'bm_price', 'margin_amount', 'om_price', 'ceo_price'] as $k) {
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

    public function update(UpdateProductPriceRequest $request, $id)
    {
        $user = $request->user();
        if ($user->cant('product-price.manage') && $user->cant('product-price.verify')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        $fieldPermissions = [
            'cogs_material_price'  => 'product-price.manage',
            'cogs_transport_price' => 'product-price.manage',
            'price_list'           => 'product-price.verify',
            'price_list_pe'        => 'product-price.verify',
            'margin_amount'        => 'product-price.verify',
            'bm_price'             => 'product-price.verify',
            'om_price'             => 'product-price.verify',
            'ceo_price'            => 'product-price.verify',
        ];

        foreach ($fieldPermissions as $field => $permission) {
            if ($request->filled($field) && $request->user()->cant($permission)) {
                return response()->json(['message' => "Anda tidak berwenang mengisi kolom {$field}."], 403);
            }
        }

        $data = $this->computeCogsPrice($data);

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

    private function computeCogsPrice(array $data): array
    {
        if (!array_key_exists('cogs_material_price', $data) && !array_key_exists('cogs_transport_price', $data)) {
            return $data;
        }

        $basis = $data['cogs_basis'] ?? null;

        if ($basis === 'loco') {
            $data['cogs_transport_price'] = null;
        }

        $material = (float) ($data['cogs_material_price'] ?? 0);
        $transport = (float) ($data['cogs_transport_price'] ?? 0);
        $data['cogs_price'] = $material + $transport;

        return $data;
    }
}
