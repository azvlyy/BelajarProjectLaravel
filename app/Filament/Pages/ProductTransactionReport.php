<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\ProductTransaction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\DatePicker;

class ProductTransactionReport extends Page implements HasForms
{

    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Reports';

    protected static string $view = 'filament.pages.product-transaction-report';

    // bikin variabel/properti untuk data filter tanggal
    public ?array $tableFilters = [
        'start_date' => null,
        'end_date' => null,
    ];

    // isi form dengan array kosong saat pertama kali dimuat
    public function mount():void
    {
        $this->form->fill();
    }

    public function form(Form $form):Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('Dari Tanggal'),
                DatePicker::make('end_date')
                    ->label('Sampai Tanggal'),
            ])
            ->statePath('tableFilters')
            ->columns(2);
    }


    // fungsi ini dipanggil saat tombol filter diklik
    public function submit():void
    {
        // form otomatis melakukan validasi dan update state
    }

    // mengambil data transaksi dari database sesuai filter tanggal yang dipilih
    public function getReportData()
    {
        $query = ProductTransaction::query()->with(['produk']);

        // mengambil data dari tablefilter
        if($this->tableFilters['start_date'] ?? null){
            $query->whereDate('created_at', '>=', $this->tableFilters['start_date']);
        }
        if($this->tableFilters['end_date'] ?? null){
            $query->whereDate('created_at', '<=', $this->tableFilters['end_date']);
        }

        return $query->latest()->get();
    }
    
}
