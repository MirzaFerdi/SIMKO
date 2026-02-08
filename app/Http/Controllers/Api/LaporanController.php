<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LaporanController extends Controller
{
    public function rekap(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'jenis_rekap' => 'required|in:brand,produk,kategori,metode,pelanggan'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date . ' 23:59:59';
        $jenis     = $request->jenis_rekap;

        $data = [];

        // 2. Logic Switch Case berdasarkan jenis rekap
        switch ($jenis) {

            // A. REKAP PER BRAND (Ambil dari tabel Detail)
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

            // B. REKAP PER PRODUK (Ambil dari tabel Detail)
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

            // C. REKAP PER KATEGORI PELANGGAN (Ambil dari Header Transaksi)
            // Ini untuk melihat: Berapa omset dari pelanggan Umum vs Khusus
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

            // D. REKAP PER METODE PEMBAYARAN (Ambil dari Header Transaksi)
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

            // E. REKAP PER NAMA PELANGGAN (Top Customer)
            case 'pelanggan':
                $data = DB::table('transaksi')
                    ->whereBetween('transaksi.tanggal', [$startDate, $endDate])
                    ->whereNotNull('nama_pelanggan') // Hanya yang ada namanya
                    ->where('nama_pelanggan', '!=', 'Umum') // Opsional: Exclude 'Umum'
                    ->select(
                        'transaksi.nama_pelanggan',
                        DB::raw('COUNT(transaksi.id) as jumlah_transaksi'),
                        DB::raw('SUM(transaksi.total) as total_belanja') // Total belanja dia
                    )
                    ->groupBy('transaksi.nama_pelanggan')
                    ->orderByDesc('total_belanja')
                    ->limit(10) // Ambil Top 10 saja biar tidak berat
                    ->get();
                break;
        }

        return response()->json([
            'success' => true,
            'jenis_rekap' => $jenis,
            'periode' => "$request->start_date s/d $request->end_date",
            'data' => $data
        ]);
    }
}
