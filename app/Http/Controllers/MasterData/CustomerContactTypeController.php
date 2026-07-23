<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Http\Requests\MasterData\StoreCustomerContactTypeRequest;
use App\Http\Requests\MasterData\UpdateCustomerContactTypeRequest;
use App\Models\CustomerContactType;
use Illuminate\Http\Request;

class CustomerContactTypeController extends Controller
{
    public function index(Request $request)
    {
        $q = CustomerContactType::query();

        if ($search = $request->query('search')) {
            $q->where(function ($q2) use ($search) {
                $q2->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $q->orderBy('name');

        if ($request->boolean('as_list')) {
            return response()->json($q->get());
        }

        $perPage = min((int) $request->query('per_page', 10), 100);

        return response()->json($q->paginate($perPage));
    }

    public function store(StoreCustomerContactTypeRequest $request)
    {
        $contactType = CustomerContactType::create($request->validated());

        return response()->json($contactType, 201);
    }

    public function show($id)
    {
        $contactType = CustomerContactType::findOrFail($id);

        return response()->json($contactType);
    }

    public function update(UpdateCustomerContactTypeRequest $request, $id)
    {
        $contactType = CustomerContactType::findOrFail($id);
        $contactType->update($request->validated());

        return response()->json($contactType->fresh());
    }

    public function destroy($id)
    {
        $contactType = CustomerContactType::findOrFail($id);
        $contactType->delete();

        return response()->json(null, 204);
    }
}
