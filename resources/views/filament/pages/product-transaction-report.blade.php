<x-filament-panels::page>
    <form wire:submit.prevent="submit" class="space-y-4 no-print">
        {{ $this->form }}
        <x-filament::button type="submit">
            Filter Data
        </x-filament::button>
    </form>

    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-4">
        <div class="flex justify-between items-center mb-4 no-print">
            <h3 class="text-lg font-bold">Hasil Laporan</h3>
            <x-filament::button color="danger" icon="heroicon-o-printer" onclick="window.print()">
                Generate Laporan
            </x-filament::button>
        </div>

        {{-- Bungkus Header dan Tabel dalam satu DIV utama --}}
        <div id="cetak-laporan" class="print-area">

            {{-- Header Laporan --}}
            <div class="header-cetak py-4 border-b-2 border-black mb-6 text-center">
                <h1 class="text-2xl font-bold uppercase">Laporan Transaksi Penjualan Sepatu</h1>
                <p class="text-xs italic">
                    Periode:
                    {{ $this->tableFilters['start_date'] ? \Carbon\Carbon::parse($this->tableFilters['start_date'])->format('d M Y') : 'Awal' }}
                    s/d
                    {{ $this->tableFilters['end_date'] ? \Carbon\Carbon::parse($this->tableFilters['end_date'])->format('d M Y') : 'Sekarang' }}
                </p>
            </div>

            {{-- Tabel Laporan --}}
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        <th class="border border-black p-2">Tanggal</th>
                        <th class="border border-black p-2">ID Booking</th>
                        <th class="border border-black p-2">Pelanggan</th>
                        <th class="border border-black p-2">Produk</th>
                        <th class="border border-black p-2 text-center">Jumlah</th>
                        <th class="border border-black p-2 text-right">Total Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalSemua = 0; @endphp
                    @foreach($this->getReportData() as $item)
                        <tr>
                            <td class="border border-black p-2">{{ $item->created_at->format('d/m/Y') }}</td>
                            <td class="border border-black p-2">{{ $item->booking_trx_id }}</td>
                            <td class="border border-black p-2">{{ $item->name }}</td>
                            <td class="border border-black p-2">{{ $item->produk->name ?? '-' }}</td>
                            <td class="border border-black p-2 text-center">{{ $item->quantity }}</td>
                            <td class="border border-black p-2 text-right">Rp
                                {{ number_format($item->grand_total_amount, 0, ',', '.') }}</td>
                        </tr>
                        @php $totalSemua += $item->grand_total_amount; @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold bg-gray-50">
                        <td colspan="5" class="border border-black p-2 text-right uppercase">Total :</td>
                        <td class="border border-black p-2 text-right text-primary-600">
                            Rp {{ number_format($totalSemua, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- CSS Khusus Cetak Yang Sudah Diperbaiki --}}
    <style>
        @media print {

            /* 1. Sembunyikan elemen yang gak perlu diprint */
            .no-print,
            form,
            nav,
            header,
            aside {
                display: none !important;
            }

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

            /* 2. Paksa tabel pakai garis hitam tipis */
            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                border: 1px solid black;
                padding: 5px;
            }

            /* 3. Atur kertas jadi landscape */
            @page {
                size: landscape;
                margin: 1cm;
            }
        }
    </style>
</x-filament-panels::page>