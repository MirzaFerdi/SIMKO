<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LaporanController extends Controller
{
    public function rekap(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date'           => 'required|date',
            'end_date'             => 'required|date|after_or_equal:start_date',
            'kategori_id'          => 'nullable|exists:kategori,id',
            'metode_pembayaran_id' => 'nullable|exists:metode_pembayaran,id',
            'brand_id'             => 'nullable|exists:brand,id',
            'produk_id'            => 'nullable|exists:produk,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date . ' 23:59:59';

        $query = Transaksi::with([
            'user',
            'kategori',
            'metodePembayaran',
            'detail.produk',
            'detail.brand'
        ])->whereBetween('tanggal', [$startDate, $endDate]);

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('metode_pembayaran_id')) {
            $query->where('metode_pembayaran_id', $request->metode_pembayaran_id);
        }

        if ($request->filled('brand_id')) {
            $query->whereHas('detail', function ($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }

        if ($request->filled('produk_id')) {
            $query->whereHas('detail', function ($q) use ($request) {
                $q->where('produk_id', $request->produk_id);
            });
        }

        $data = $query->orderByDesc('tanggal')
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

        return response()->json([
            'success'     => true,
            'periode'     => "$request->start_date s/d $request->end_date",
            'grand_total' => $data->sum('total'),
            'data'        => $data
        ]);
    }
}
