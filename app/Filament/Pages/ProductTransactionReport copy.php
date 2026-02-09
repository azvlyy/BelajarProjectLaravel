<?php

namespace App\Filament\Pages;

use App\Models\ProductTransaction;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;

class ProductTransactionReport extends Page implements HasForms
{
    use InteractsWithForms;

    // 1. Pastikan property ini public agar bisa diakses Livewire
    public ?array $tableFilters = [
        'start_date' => null,
        'end_date' => null,
    ];

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Transaksi';

    protected static ?string $navigationGroup = 'Reports';
    
    protected static ?string $title = 'Laporan Transaksi';
    protected static string $view = 'filament.pages.product-transaction-report';

    public function mount(): void
    {
        // 2. Isi form dengan array kosong saat pertama kali dimuat
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')->label('Dari Tanggal'),
                DatePicker::make('end_date')->label('Sampai Tanggal'),
            ])
            // 3. Hubungkan form ke property array $tableFilters
            ->statePath('tableFilters') 
            ->columns(2);
    }

    public function submit(): void
    {
        // Fungsi ini dipanggil saat tombol Filter diklik
        // Form akan otomatis melakukan validasi dan update state
    }

    public function getReportData()
    {
        $query = ProductTransaction::query()->with(['produk']);

        // 4. Ambil data dari array tableFilters
        if ($this->tableFilters['start_date'] ?? null) {
            $query->whereDate('created_at', '>=', $this->tableFilters['start_date']);
        }
        if ($this->tableFilters['end_date'] ?? null) {
            $query->whereDate('created_at', '<=', $this->tableFilters['end_date']);
        }

        return $query->latest()->get();
    }
}
