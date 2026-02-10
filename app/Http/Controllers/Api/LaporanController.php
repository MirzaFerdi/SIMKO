<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Transaksi;
use Illuminate\Support\Facades\Validator;

class LaporanController extends Controller
{
    // Method ini sekarang diakses via POST
    public function rekap(Request $request)
    {
        // 1. Validasi Input (Laravel otomatis membaca JSON Body)
        $validator = Validator::make($request->all(), [
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'jenis_rekap' => 'required|in:transaksi,brand,produk,kategori,metode,pelanggan'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date . ' 23:59:59';
        $jenis     = $request->jenis_rekap;

        $data = [];

        // 2. Logic Switch Case (TETAP SAMA SEPERTI SEBELUMNYA)
        switch ($jenis) {
            // A. REKAP PER BRAND
            case 'brand':
                $data = DB::table('transaksi_detail')
                    ->join('transaksi', 'transaksi_detail.transaksi_id', '=', 'transaksi.id')
                    ->join('brand', 'transaksi_detail.brand_id', '=', 'brand.id')
                    ->whereBetween('transaksi.tanggal', [$startDate, $endDate])
                    ->select(
                        'brand.nama_brand',
                        DB::raw('SUM(transaksi_detail.qty) as total_terjual'),
                        DB::raw('SUM(transaksi_detail.subtotal) as total_omset')
                    )
                    ->groupBy('brand.id', 'brand.nama_brand')
                    ->orderByDesc('total_omset')
                    ->get();
                break;

            // B. REKAP PER PRODUK
            case 'produk':
                $data = DB::table('transaksi_detail')
                    ->join('transaksi', 'transaksi_detail.transaksi_id', '=', 'transaksi.id')
                    ->join('produk', 'transaksi_detail.produk_id', '=', 'produk.id')
                    ->whereBetween('transaksi.tanggal', [$startDate, $endDate])
                    ->select(
                        'produk.nama_produk',
                        DB::raw('SUM(transaksi_detail.qty) as total_terjual'),
                        DB::raw('SUM(transaksi_detail.subtotal) as total_omset')
                    )
                    ->groupBy('produk.id', 'produk.nama_produk')
                    ->orderByDesc('total_terjual')
                    ->get();
                break;

            // C. REKAP PER KATEGORI
            case 'kategori':
                $data = DB::table('transaksi')
                    ->join('kategori', 'transaksi.kategori_id', '=', 'kategori.id')
                    ->whereBetween('transaksi.tanggal', [$startDate, $endDate])
                    ->select(
                        'kategori.nama_kategori',
                        DB::raw('COUNT(transaksi.id) as jumlah_transaksi'),
                        DB::raw('SUM(transaksi.total) as total_omset')
                    )
                    ->groupBy('kategori.id', 'kategori.nama_kategori')
                    ->get();
                break;

            // D. REKAP METODE
            case 'metode':
                $data = DB::table('transaksi')
                    ->join('metode_pembayaran', 'transaksi.metode_pembayaran_id', '=', 'metode_pembayaran.id')
                    ->whereBetween('transaksi.tanggal', [$startDate, $endDate])
                    ->select(
                        'metode_pembayaran.nama_metode',
                        DB::raw('COUNT(transaksi.id) as jumlah_transaksi'),
                        DB::raw('SUM(transaksi.total) as total_omset')
                    )
                    ->groupBy('metode_pembayaran.id', 'metode_pembayaran.nama_metode')
                    ->get();
                break;

            // E. REKAP PELANGGAN
            case 'pelanggan':
                $data = DB::table('transaksi')
                    ->whereBetween('transaksi.tanggal', [$startDate, $endDate])
                    ->whereNotNull('nama_pelanggan')
                    ->where('nama_pelanggan', '!=', 'Umum')
                    ->select(
                        'transaksi.nama_pelanggan',
                        DB::raw('COUNT(transaksi.id) as jumlah_transaksi'),
                        DB::raw('SUM(transaksi.total) as total_belanja')
                    )
                    ->groupBy('transaksi.nama_pelanggan')
                    ->orderByDesc('total_belanja')
                    ->limit(10)
                    ->get();
                break;

            case 'transaksi':
                $data = Transaksi::with([
                    'user',
                    'kategori',
                    'metodePembayaran',
                    'detail.produk',
                    'detail.brand'
                ])
                    ->whereBetween('tanggal', [$startDate, $endDate])
                    ->orderByDesc('tanggal')
                    ->get()
                    ->map(function ($transaksi) {
                        return [
                            'id'                 => $transaksi->id,
                            'tanggal'            => $transaksi->tanggal,
                            'nama_pelanggan'     => $transaksi->nama_pelanggan,
                            'kasir'              => $transaksi->user->username,
                            'kategori_pelanggan' => $transaksi->kategori->nama_kategori,
                            'nama_metode'        => $transaksi->metodePembayaran->nama_metode,
                            'total'              => $transaksi->total,
                            'status'             => $transaksi->status,

                            'items'              => $transaksi->detail->map(function ($item) {
                                return [
                                    'nama_produk' => $item->produk->nama_produk,
                                    'nama_brand'  => $item->brand->nama_brand,
                                    'qty'         => $item->qty,
                                    'harga'       => $item->harga,
                                    'subtotal'    => $item->subtotal
                                ];
                            })
                        ];
                    });
                break;
        }

        return response()->json([
            'success' => true,
            'jenis_rekap' => $jenis,
            'periode' => "$request->start_date s/d $request->end_date",
            'grand_total' => collect($data)->sum('total') ?? collect($data)->sum('total_omset') ?? 0,
            'data' => $data
        ]);
    }
}
