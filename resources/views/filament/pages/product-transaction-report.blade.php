<x-filament-panels::page>
    {{-- 1. Form Filter: Menggunakan Livewire (wire:submit.prevent) agar filter tanpa reload --}}
    <form wire:submit.prevent="submit" class="space-y-4 no-print">
        {{ $this->form }}
        <x-filament::button type="submit">
            Filter Data
        </x-filament::button>
    </form>

    <div class="p-6 bg-white rounded-xl shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700 mt-4">
        <div class="flex justify-between items-center mb-4 no-print">
            <h3 class="text-lg font-bold">Hasil Laporan</h3>
            {{-- 2. Tombol Print: Memicu fungsi print bawaan browser --}}
            <x-filament::button color="danger" icon="heroicon-o-printer" onclick="window.print()">
                Generate Laporan
            </x-filament::button>
        </div>

        <div id="cetak-laporan" class="print-area">
            {{-- 3. Header Laporan: Menampilkan rentang tanggal yang dipilih secara dinamis --}}
            <div class="header-cetak py-4 border-b-2 border-black mb-6 text-center">
                <h1 class="text-2xl font-bold uppercase">Laporan Transaksi Penjualan Sepatu</h1>
                <p class="text-xs italic">
                    Periode:
                    {{ $this->tableFilters['start_date'] ? \Carbon\Carbon::parse($this->tableFilters['start_date'])->format('d M Y') : 'Awal' }}
                    s/d
                    {{ $this->tableFilters['end_date'] ? \Carbon\Carbon::parse($this->tableFilters['end_date'])->format('d M Y') : 'Sekarang' }}
                </p>
            </div>

            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        <th>Tanggal</th><th>ID Booking</th><th>Pelanggan</th><th>Produk</th>
                        <th class="text-center">Jumlah</th><th class="text-right">Total Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 4. Inisialisasi variabel total untuk menghitung grand total di bawah --}}
                    @php $totalSemua = 0; @endphp
                    {{-- 5. Looping data: Memanggil fungsi getReportData() dari file PHP (Backend) --}}
                    @foreach($this->getReportData() as $item)
                        <tr>
                            <td>{{ $item->created_at->format('d/m/Y') }}</td>
                            <td>{{ $item->booking_trx_id }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->produk->name ?? '-' }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">Rp {{ number_format($item->grand_total_amount, 0, ',', '.') }}</td>
                        </tr>
                        {{-- 6. Akumulasi total setiap baris transaksi --}}
                        @php $totalSemua += $item->grand_total_amount; @endphp
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold bg-gray-50">
                        <td colspan="5" class="text-right uppercase">Total :</td>
                        <td class="text-right text-primary-600">
                            Rp {{ number_format($totalSemua, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- 7. Media Print: CSS yang hanya aktif saat kertas dicetak --}}
    <style>
        @media print {
            /* Sembunyikan navigasi dan form agar tidak ikut terprint */
            .no-print, form, nav, header, aside { display: none !important; }

            /* Reset style pembungkus Filament agar latar belakang putih polos */
            .fi-main, .fi-main-ctn, .fi-page, div[class*='bg-white'] {
                background: transparent !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }

            /* Desain tabel khusus print agar garis hitam muncul jelas */
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid black; padding: 5px; }

            /* Atur orientasi kertas ke Landscape (mendatar) */
            @page { size: landscape; margin: 1cm; }
        }
    </style>
</x-filament-panels::page>