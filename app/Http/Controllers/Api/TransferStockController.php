<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Models\TransferStock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\TransferStockStoreRequest;
use Illuminate\Support\Facades\Auth;

class TransferStockController extends Controller
{
    public function index(Request $request)
    {
        $transferStocks = TransferStock::with([
            'storeFrom',
            'storeTo',
            'productTransferStocks.product.unit',
            'sentBy',
            'receivedBy',
        ])->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $transferStocks->items(),
            'meta' => [
                'current_page' => $transferStocks->currentPage(),
                'last_page' => $transferStocks->lastPage(),
                'per_page' => $transferStocks->perPage(),
                'total' => $transferStocks->total(),
            ],
        ]);
    }

    public function store(TransferStockStoreRequest $request)
    {
        $data = $request->validated();
        $data['sent_by_id'] = Auth::id();
        $data['status'] = TransferStock::STATUS_BELUM_DIPERIKSA;

        $transferStock = TransferStock::create($data);

        foreach ($request->items as $item) {
            $transferStock->productTransferStocks()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
            ]);
        }

        $transferStock->load([
            'storeFrom',
            'storeTo',
            'productTransferStocks.product.unit',
            'sentBy',
            'receivedBy',
        ]);

        return response()->json([
            'success' => true,
            'data' => $transferStock,
        ], 201);
    }

    public function show(Request $request, TransferStock $transferStock)
    {
        $transferStock->load([
            'storeFrom',
            'storeTo',
            'productTransferStocks.product.unit',
            'sentBy',
            'receivedBy',
        ]);

        return response()->json([
            'success' => true,
            'data' => $transferStock,
        ]);
    }

    public function products(Request $request)
    {
        $products = Product::where('remaining', '1')
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);

        $products->load('unit:id,unit');

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }
}
