<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceController extends Controller
{
    private function paginate(Request $request)
    {
        $request->validate([
            'page' => 'required|integer|min:1',
            'pagination' => 'required|integer|min:1|max:100'
        ]);

        $page = (int) $request->input('page');
        $perPage = (int) $request->input('pagination');
        $offset = ($page - 1) * $perPage;
        return [$perPage, $offset, $page];
    }

    private function formatResponse($request, $dataQuery)
    {
        [$perPage, $offset, $page] = $this->paginate($request);
        $totalData = $dataQuery->count();
        $data = $dataQuery
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'total_data' => $totalData,
            'page' => $page,
            'per_page' => $perPage,
            'data_count' => count($data),
            'data' => $data
        ]);
    }

    public function produkClassicCars(Request $request)
    {
        $request->validate([
            'min_stock' => 'nullable|integer|min:0',
            'max_stock' => 'nullable|integer|min:0',
        ]);
        $minStock = $request->input('min_stock', 1700);
        $maxStock = $request->input('max_stock', 2800);
        $query = DB::table('products')
            ->where('productLine', 'Classic Cars')
            ->whereBetween('quantityInStock', [$minStock, $maxStock]);
        [$perPage, $offset, $page] = $this->paginate($request);
        $totalData = $query->count();
        $data = $query->offset($offset)->limit($perPage)->get();
        return response()->json([
            'total_data' => $totalData,
            'page' => $page,
            'per_page' => $perPage,
            'data_count' => count($data),
            'data' => $data
        ]);
    }



    public function produkEra60an(Request $request)
    {
        $request->validate([
            'tahun_awal' => 'nullable|integer|min:1900|max:' . date('Y'),
            'tahun_akhir' => 'nullable|integer|min:1900|max:' . date('Y'),
        ]);

        $tahunAwal = $request->input('tahun_awal', 1900);
        $tahunAkhir = $request->input('tahun_akhir', date('Y'));
        if ($tahunAwal > $tahunAkhir) {
            return response()->json([
                'message' => 'Tahun awal tidak boleh lebih besar dari tahun akhir.'
            ], 400);
        }
        $query = DB::table('products')
            ->where('productName', 'like', '%')
            ->whereBetween(DB::raw('CAST(SUBSTRING(productName, LOCATE("196", productName), 4) AS UNSIGNED)'), [$tahunAwal, $tahunAkhir]);
        return $this->formatResponse($request, $query);
    }


    public function orderDalam1Bulan(Request $request)
    {
        $request->validate([
            'bulan' => 'nullable|integer|min:1|max:12',
        ]);

        $bulan = $request->input('bulan');

        $query = DB::table('orders')
            ->whereRaw('ABS(DATEDIFF(orderDate, shippedDate)) <= 31');
        if (!is_null($bulan)) {
            $query->whereMonth('orderDate', $bulan);
        }
        return $this->formatResponse($request, $query);
    }


    public function orderTanpaPengiriman(Request $request)
    {
        $query = DB::table('orders')
            ->whereNull('shippedDate');

        return $this->formatResponse($request, $query);
    }

    public function pembayaran2004DiAtas5000(Request $request)
    {
        $request->validate([
            'tahun' => 'nullable|integer|min:1900|max:2100',
            'nominal' => 'nullable|numeric|min:0',
        ]);
        $tahun = $request->input('tahun', 2004);
        $nominal = $request->input('nominal', 5000);
        $query = DB::table('payments')
            ->whereYear('paymentDate', $tahun)
            ->where('amount', '>', $nominal);

        return $this->formatResponse($request, $query);
    }


    public function pembayaran2004BulanTertentu(Request $request)
    {
        $query = DB::table('payments')
            ->whereYear('paymentDate', 2004)
            ->whereIn(DB::raw('MONTH(paymentDate)'), [5, 7, 8, 11]);

        return $this->formatResponse($request, $query);
    }

    public function tujuhPembayaranTerendah2003()
    {
        $data = DB::table('payments')
            ->whereYear('paymentDate', 2003)
            ->orderBy('amount', 'asc')
            ->limit(7)
            ->get();

        return response()->json([
            'total_data' => DB::table('payments')->whereYear('paymentDate', 2003)->count(),
            'page' => 1,
            'per_page' => 7,
            'data_count' => count($data),
            'data' => $data
        ]);
    }

    public function pelangganTanpaState(Request $request)
    {
        $query = DB::table('customers')
            ->where(function ($q) {
                $q->whereNull('state')
                    ->orWhere('state', '');
            });

        return $this->formatResponse($request, $query);
    }

    public function pelangganCreditLimitTertinggi()
    {
        $data = DB::table('customers')
            ->orderBy('creditLimit', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_data' => DB::table('customers')->count(),
            'page' => 1,
            'per_page' => 5,
            'data_count' => count($data),
            'data' => $data
        ]);
    }

    public function pelangganAlamatKeduaSaja(Request $request)
    {
        $query = DB::table('customers')
            ->whereNotNull('addressLine2')
            ->where('addressLine2', '!=', '');

        return $this->formatResponse($request, $query);
    }
}
