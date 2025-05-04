<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaxiTripController extends Controller
{
    public function getRecap(Request $request)
    {
        set_time_limit(0);

        $request->validate([
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date',
            'page' => 'required|integer|min:1',
            'paginasi' => 'required|integer|min:1|max:100',
        ]);

        $tanggal_mulai = $request->input('tanggal_awal');
        $tanggal_akhir = $request->input('tanggal_akhir');
        $page = $request->input('page');
        $per_page = $request->input('paginasi');
        $offset = ($page - 1) * $per_page;
        $startDate = \Carbon\Carbon::parse($tanggal_mulai);
        $endDate = \Carbon\Carbon::parse($tanggal_akhir);

        // Hitung bulan-bulannya
        $months = [];
        while ($startDate->lte($endDate)) {
            $months[] = $startDate->format('F Y');
            $startDate->addMonth();
        }

        // Cek total data dulu
        $countQuery = "
            SELECT COUNT(DISTINCT CEIL((DATEDIFF(lpep_pickup_datetime, ?) + 1) / 7)) AS total_weeks
            FROM (
                SELECT lpep_pickup_datetime FROM 2018_taxi_trips WHERE lpep_pickup_datetime BETWEEN ? AND ?
                UNION ALL
                SELECT lpep_pickup_datetime FROM 2019_taxi_trips WHERE lpep_pickup_datetime BETWEEN ? AND ?
                UNION ALL
                SELECT lpep_pickup_datetime FROM 2020_taxi_trips WHERE lpep_pickup_datetime BETWEEN ? AND ?
            ) AS combined
        ";

        $countResult = DB::selectOne($countQuery, [
            $tanggal_mulai,
            $tanggal_mulai, $tanggal_akhir,
            $tanggal_mulai, $tanggal_akhir,
            $tanggal_mulai, $tanggal_akhir
        ]);

        $total = $countResult->total_weeks ?? 0;

        if ($total == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data pada rentang tanggal tersebut.',
                'data' => [],
                'total' => 0,
                'total_pages' => 0,
                'current_page' => $page,
                'per_page' => $per_page,
                'has_next_page' => false,
                'has_previous_page' => false
            ]);
        }

        $total_pages = ceil($total / max($per_page, 1));

        // Tambahkan pengecekan kalau page > total_pages
        if ($page > $total_pages) {
            return response()->json([
                'success' => false,
                'message' => "Total page ($total_pages) tidak sampai dengan paginasi yang anda minta (page $page).",
                'data' => [],
                'total' => $total,
                'total_pages' => $total_pages,
                'current_page' => $page,
                'per_page' => $per_page,
                'has_next_page' => false,
                'has_previous_page' => false
            ]);
        }

        // Kalau page masih valid baru ambil data
        $query = "
            SELECT
                CEIL((DATEDIFF(lpep_pickup_datetime, ?) + 1) / 7) AS week,
                COUNT(*) AS total_transactions,
                SUM(tip_amount) AS total_tips,
                SUM(CASE WHEN payment_type = 1 THEN 1 ELSE 0 END) AS Credit_Card,
                SUM(CASE WHEN payment_type = 2 THEN 1 ELSE 0 END) AS Cash,
                SUM(CASE WHEN payment_type = 3 THEN 1 ELSE 0 END) AS No_Charge,
                SUM(CASE WHEN payment_type = 4 THEN 1 ELSE 0 END) AS Dispute,        
                SUM(CASE WHEN payment_type = 5 THEN 1 ELSE 0 END) AS Unknown,        
                SUM(CASE WHEN payment_type = 6 THEN 1 ELSE 0 END) AS Voided_trip,   
                GROUP_CONCAT(DISTINCT CONCAT(pickup_zone.zone, ',', dropoff_zone.zone) SEPARATOR ';') AS pickup_dropoff_locations
            FROM (
                SELECT lpep_pickup_datetime, tip_amount, payment_type, PULocationID, DOLocationID
                FROM 2018_taxi_trips WHERE lpep_pickup_datetime BETWEEN ? AND ?
                UNION ALL
                SELECT lpep_pickup_datetime, tip_amount, payment_type, PULocationID, DOLocationID
                FROM 2019_taxi_trips WHERE lpep_pickup_datetime BETWEEN ? AND ?
                UNION ALL
                SELECT lpep_pickup_datetime, tip_amount, payment_type, PULocationID, DOLocationID
                FROM 2020_taxi_trips WHERE lpep_pickup_datetime BETWEEN ? AND ?
            ) AS combined
            LEFT JOIN taxi_zones AS pickup_zone ON pickup_zone.LocationID = combined.PULocationID
            LEFT JOIN taxi_zones AS dropoff_zone ON dropoff_zone.LocationID = combined.DOLocationID
            GROUP BY week
            ORDER BY week ASC
            LIMIT ? OFFSET ?
        ";

        $results = DB::select($query, [
            $tanggal_mulai,
            $tanggal_mulai, $tanggal_akhir,
            $tanggal_mulai, $tanggal_akhir,
            $tanggal_mulai, $tanggal_akhir,
            $per_page, $offset
        ]);

        
        $data = [];

        foreach ($results as $row) {
            $locations = $row->pickup_dropoff_locations
                ? explode(';', $row->pickup_dropoff_locations)
                : [];
        
            $pickupList = [];
            $dropoffList = [];
        
            foreach ($locations as $location) {
                list($pickup, $dropoff) = explode(',', $location);
                $pickupList[] = $pickup;
                $dropoffList[] = $dropoff;
            }
        
            // Hapus lokasi duplikat
            $pickupList = array_unique($pickupList);
            $dropoffList = array_unique($dropoffList);
        
            $pickupString = implode(',', $pickupList);
            $dropoffString = implode(',', $dropoffList);
        
            $weekDate = \Carbon\Carbon::parse($tanggal_mulai)->addDays(($row->week - 1) * 7);
            $bulan = $weekDate->format('F Y');
            $minggu = 'Minggu ke-' . $weekDate->weekOfMonth;
        
            if (!isset($data[$bulan])) {
                $data[$bulan] = [];
            }
        
            $data[$bulan][$minggu] = [
                'total_transactions' => (int) $row->total_transactions,
                'payment_types' => [
                    'Credit Card' => (int) $row->Credit_Card,
                    'Cash' => (int) $row->Cash,
                    'No Charge' => (int) $row->No_Charge,
                    'Dispute' => (int) $row->Dispute,
                    'Unknown' => (int) $row->Unknown,
                    'Voided trip' => (int) $row->Voided_trip
                ],
                'total_tips' => (float) $row->total_tips,
                'lokasi_jemput_turun' => [
                    'pickup' => $pickupString,
                    'dropoff' => $dropoffString
                ],
            ];
        }
        


    return response()->json([
        'success' => true,
        'total_months' => count($months),
        'data' => $data,
        'total_weeks' => $total,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page,
        'has_next_page' => $page < $total_pages,
        'has_previous_page' => $page > 1
        ]);
    }
}
?>
