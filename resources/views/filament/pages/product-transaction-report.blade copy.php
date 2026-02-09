<x-filament-panels::page>
    {{-- Form Filter --}}
    <form wire:submit.prevent="submit" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">
            Filter Data
        </x-filament::button>
    </form>

    <hr class="my-6">

    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex justify-between items-center mb-4 no-print">
            <h3 class="text-lg font-bold">Hasil Laporan</h3>
            <x-filament::button color="danger" icon="heroicon-o-printer" onclick="window.print()">
                Cetak PDF / Print
            </x-filament::button>
        </div>

        {{-- Header Khusus Cetak --}}
        <div class="header-cetak py-4 border-b-2 border-black mb-6 text-center">
            <h1 class="text-2xl font-bold uppercase">Laporan Transaksi Penjualan</h1>
            <p class="text-sm">Toko Sepatu Berkah - Sistem Manajemen Stok & Transaksi</p>
            <p class="text-xs italic">
                Periode:
                {{ $this->tableFilters['start_date'] ? \Carbon\Carbon::parse($this->tableFilters['start_date'])->format('d M Y') : 'Awal' }}
                s/d
                {{ $this->tableFilters['end_date'] ? \Carbon\Carbon::parse($this->tableFilters['end_date'])->format('d M Y') : 'Sekarang' }}
            </p>
        </div>

        {{-- Tabel Laporan --}}
        <div class="overflow-x-auto print-area">
            <table class="w-full text-left border-collapse border border-gray-300 text-sm">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        <th class="border border-gray-300 p-2">ID Booking</th>
                        <th class="border border-gray-300 p-2">Pelanggan</th>
                        <th class="border border-gray-300 p-2">Produk</th>
                        <th class="border border-gray-300 p-2">Jumlah</th>
                        <th class="border border-gray-300 p-2">Total Bayar</th>
                        <th class="border border-gray-300 p-2">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalSemua = 0; @endphp
                    @foreach($this->getReportData() as $item)
                        <tr>
                            <td class="border border-gray-300 p-2">{{ $item->booking_trx_id }}</td>
                            <td class="border border-gray-300 p-2">{{ $item->name }}</td>
                            <td class="border border-gray-300 p-2">{{ $item->produk->name ?? '-' }}</td>
                            <td class="border border-gray-300 p-2 text-center">{{ $item->quantity }}</td>
                            <td class="border border-gray-300 p-2">Rp
                                {{ number_format($item->grand_total_amount, 0, ',', '.') }}</td>
                            <td class="border border-gray-300 p-2">{{ $item->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @php $totalSemua += $item->grand_total_amount; @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold bg-gray-50">
                        <td colspan="4" class="border border-gray-300 p-2 text-right">TOTAL PENDAPATAN:</td>
                        <td colspan="2" class="border border-gray-300 p-2 text-primary-600">
                            Rp {{ number_format($totalSemua, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- CSS Khusus Cetak --}}
    <style>
        @media print {

            /* 1. Sembunyikan semua elemen UI Filament */
            .fi-sidebar,
            .fi-topbar,
            .fi-header,
            .fi-btn,
            .fi-section-header,
            form,
            .no-print {
                display: none !important;
            }

            /* 2. Hilangkan background, border, dan shadow pada pembungkus utama Filament */
            .fi-main,
            .fi-main-ctn,
            .fi-page,
            .fi-section,
            .fi-card,
            div[class*='bg-white'],
            div[class*='shadow-sm'],
            div[class*='rounded-xl'] {
                background: transparent !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* 3. Atur area print agar memenuhi halaman */
            .print-area {
                position: absolute;
                top: 0;
                left: 0;
                width: 100% !important;
            }

            /* 4. Pastikan tabel terlihat jelas dengan garis hitam solid */
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                color: black !important;
            }

            th,
            td {
                border: 1px solid black !important;
                padding: 8px !important;
            }

            @page {
                size: landscape;
                margin: 1cm;
            }
        }
    </style>
</x-filament-panels::page>