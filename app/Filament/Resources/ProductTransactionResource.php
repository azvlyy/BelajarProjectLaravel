<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTransactionResource\Pages;
use App\Filament\Resources\ProductTransactionResource\RelationManagers;
use App\Models\ProdukSize;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// models
use App\Models\ProductTransaction;
use App\Models\Produk;
use App\Models\PromoCode;

// new library
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid; // untuk membuat layout grid pada form
use Filament\Forms\Components\Toggle; // untuk membuat switch/toggle button
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\Action; // untuk custom action
use Filament\Notifications\Notification; // untuk costum notifikasi

class ProductTransactionResource extends Resource
{
    protected static ?string $model = ProductTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function canCreate():bool
    {
        return auth()->user()->isKasir();
    }
    
    public static function canEdit(\Illuminate\Database\Eloquent\Model $record):bool
    {
        return auth()->user()->isKasir();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record):bool
    {
        return auth()->user()->isSuperAdmin();
    }

        public static function canDeleteAny():bool
    {
        return auth()->user()->isSuperAdmin();
    }


    protected static function recalculate(Get $get, Set $set)
    {
        // validasi data produk dan jumlah produk
        if (!$get('produk_id') || !$get('quantity')) {
            $set('sub_total_amount', 0);
            $set('grand_total_amount', 0);
            return;
        }

        // mengambil data produk, jumlah produk, dan kode promo
        $produkId = $get('produk_id');
        $qty = (int) ($get('quantity') ?? 1);
        $promoCodeId = $get('promo_code_id');

        // mencari data produk berdasarkan id yg dipilih
        $produk = Produk::find($produkId);
        $hargaSatuan = $produk ? $produk->price : 0;

        // kalkulasi subtotal
        $subTotal = $hargaSatuan * $qty;

        // memeriksa apakah ada kode promo yg dipakai
        $diskon = 0;
        if ($promoCodeId) {
            $promo = PromoCode::find($promoCodeId);
            $diskon = $promo ? $promo->discount_amount : 0;
        }

        // kalkulasi grandtotal
        $grandTotal = $subTotal - $diskon;

        // output tampilan rupiah (supaya ada . di angka)
        $set('sub_total_amount', number_format($subTotal, 0, ',', '.'));
        $set('discount_amount', number_format($diskon, 0, ',', '.'));
        $set('grand_total_amount', number_format($grandTotal, 0, ',', '.'));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pembeli')
                    ->description('Lengkapi data identitas pembeli dan alamat pengiriman')
                    ->collapsible() // bisa dibuka tutup
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama')
                                    ->required()
                                    // hanya boleh huruf, spasi, dan titik
                                    ->regex('/^[A-Za-z\s.]+$/')
                                    ->maxlength(100),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->maxlength(255),
                                TextInput::make('phone')
                                    ->label('No. Telp')
                                    // hanya boleh angka, spasi, tanda +, - dan ()
                                    ->tel()
                                    ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                    ->required()
                                    ->minLength(10)
                                    ->maxlength(13),
                            ]),
                        Grid::make(3)
                            ->schema([
                                TextInput::make('city')
                                    ->label('Kota')
                                    ->columnSpan(2)
                                    ->required()
                                    ->maxlength(100),
                                TextInput::make('post_code')
                                    ->label('Kode Pos')
                                    ->columnSpan(1)
                                    ->required()
                                    ->numeric()
                                    ->length(5)
                            ]),
                        Grid::make(1)
                            ->schema([
                                Textarea::make('address')
                                    ->label('Alamat Lengkap')
                                    ->required()
                                    ->maxLength(500)
                            ]),
                    ]),
                Section::make('Informasi Produk')
                    ->collapsible()
                    // input tidak bisa diubah jika sudah bayar
                    ->disabled(fn ($record) => $record?->is_paid)
                    ->description('Pilih produk, tentukan ukuran, dan masukan jumlah pesanan')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('produk_id')
                                    ->relationship('produk', 'name')
                                    ->label('Produk')
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        // 1. Reset ukuran jadi kosong (Select an option)
                                        $set('shoe_size', null);

                                        // 2. Jalankan ulang hitung harga
                                        self::recalculate($get, $set);
                                    }),

                                Select::make('shoe_size')
                                    ->label('Ukuran Sepatu')
                                    // mengambil data size sesuai yg ada di produk
                                    ->options(function (Get $get) {
                                        $sizes = ProdukSize::getSize($get('produk_id'));

                                        // urutan ukuran/size dari kecil ke besar 
                                        if ($sizes) {
                                            asort($sizes);
                                        }

                                        return $sizes;
                                    })
                                    ->required(),
                            ]),

                        Grid::make(2)->schema([
                            TextInput::make('quantity')
                                ->label('Jumlah')
                                ->numeric()
                                ->default(1)
                                ->live()
                                ->minValue(1)
                                ->maxValue(function (Get $get) {
                                    $produkId = $get('produk_id');
                                    return $produkId ? Produk::find($produkId)?->stock : null;
                                })
                                ->validationMessages([
                                    'max' => 'Stok yang tersedia hanya :max'
                                ])
                                ->afterStateUpdated(fn(Get $get, Set $set) =>
                                    self::recalculate($get, $set)),

                            Select::make('promo_code_id')
                                ->label('Kode Promo')
                                ->relationship('promoCode', 'code')
                                ->live()
                                ->afterStateUpdated(fn(Get $get, Set $set) =>
                                    self::recalculate($get, $set))
                        ]),

                        Grid::make(3)->schema([
                            TextInput::make('sub_total_amount')
                                ->label('Sub Total')
                                ->prefix('Rp')
                                ->readOnly()
                                ->default(0)
                                // hapus titik otomatis sebelum dikirim ke database biar ga error
                                ->dehydrateStateUsing(
                                    fn($state) =>
                                    str_replace('.', '', $state)
                                ),

                            TextInput::make('discount_amount')
                                ->label('Potongan Diskon')
                                ->prefix('Rp')
                                ->readOnly()
                                ->default(0)
                                ->dehydrateStateUsing(
                                    fn($state) =>
                                    str_replace('.', '', $state)
                                ),

                            TextInput::make('grand_total_amount')
                                ->label('Total Bayar')
                                ->prefix('Rp')
                                ->readOnly()
                                ->extraInputAttributes([
                                    'style' => 'font-weight: bold;'
                                ])
                                ->default(0)
                                ->dehydrateStateUsing(
                                    fn($state) =>
                                    str_replace('.', '', $state)
                                ),
                        ]),
                    ]),
                Section::make('Informasi Transaksi')
                    ->collapsible()
                    // input tidak bisa diubah jika sudah bayar
                    ->disabled(fn ($record) => $record?->is_paid)
                    ->description('Kelola status pembayaran dan unggah bukti transfer')
                    ->columns(2)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('booking_trx_id')
                                    ->label('Kode Transaksi')
                                    ->default(fn() => ProductTransaction::generateUniqueTrxId())
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->extraInputAttributes([
                                        'style' => 'color: #505050; font-weight: bold;'
                                    ])
                                    ->required(),
                                Toggle::make('is_paid')
                                    ->label('Status Pembayaran')
                                    // hints (? sebagai (if) dan : sebagai (else) )
                                    ->helperText(fn($state) => $state
                                        ? 'Sudah Melakukan Pembayaran'
                                        : 'Belum Melakukan Pembayaran')
                                    ->columnSpan(1)
                                    ->live()
                                    ->onColor('success')
                                    ->offColor('danger')
                            ]),

                        FileUpload::make('proof')
                            ->label('Bukti Transaksi')
                            ->image()
                            ->nullable()
                            ->directory('produk/bukti transaksi')
                            ->columnSpanFull()
                            // akan muncul jika sudah melakukan pembayaran/is_paid = true
                            ->required(fn($get) => $get('is_paid'))
                            ->visible(fn($get) => $get('is_paid')),
                            
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('produk.thumbnail')
                    ->label('Gambar Produk')
                    ->size(70),

                TextColumn::make('booking_trx_id')
                    ->label('ID Booking'),

                TextColumn::make('name')
                    ->label('Nama Pembeli')
                    ->searchable(),

                TextColumn::make('grand_total_amount')
                    ->label('Total Bayar')
                    ->money('idr', locale: 'id'),

                TextColumn::make('is_paid')
                    ->label('Status Pembayaran')
                    ->badge()
                    // menampilkan teks sesuai status pembayaran
                    ->formatStateUsing(fn(bool $state): string => $state
                        ? 'Sudah Bayar'
                        : 'Belum Bayar')
                    ->color(fn(bool $state): string => $state
                        ? 'success'
                        : 'danger')
            ])
            ->filters([
                // filter berdasarkan status pembayaran
                SelectFilter::make('is_paid')
                    ->label('Status Pembayaran')
                    ->options([
                        true => 'Sudah Bayar',
                        false => 'Belum Bayar',
                    ])
            ])
            ->actions([ // opsi action dropdown
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->color('success')
                        ->icon('heroicon-o-check-circle')
                        // Tombol HILANG kalau is_paid sudah true
                        ->visible(fn($record) => 
                        !$record->is_paid && auth()->user()->isKasir() )
                        ->form([
                            FileUpload::make('proof')
                                ->label('Upload Bukti Pembayaran')
                                ->directory('produk/bukti transaksi')
                                ->image()
                                ->required(),
                        ])
                        ->action(function (array $data, $record): void {
                            $record->update([
                                'is_paid' => true, // Ubah jadi true karena boolean
                                'proof' => $data['proof'],
                            ]);

                            // Tampilkan notifikasi sukses
                            Notification::make()
                                ->title('Pembayaran Berhasil Diverifikasi')
                                ->success()
                                ->send();
                        })
                        // teks heading di modal
                        ->modalHeading('Verifikasi Pembayaran'),

                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),

                    Action::make('download_proof')
                        ->label('Download Proof')
                        ->icon('heroicon-o-arrow-down-tray')
                        // mengarahkan ke url baru bukti transaksi
                        ->url(fn(ProductTransaction $record) =>
                            $record->proof ? asset('storage/' . $record->proof) : null)
                        ->openUrlInNewTab()
                        // jika tidak ada bukti transaksi, maka action ini disembunyikan
                        ->visible(fn(ProductTransaction $record) =>
                            !empty($record->proof)),

                ])
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->tooltip('Opsi')
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductTransactions::route('/'),
            'create' => Pages\CreateProductTransaction::route('/create'),
            'edit' => Pages\EditProductTransaction::route('/{record}/edit'),
        ];
    }
}
