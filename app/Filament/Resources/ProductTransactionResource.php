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
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// models
use App\Models\ProductTransaction;
use App\Models\Produk;
use App\Models\PromoCode;

// new library
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;

class ProductTransactionResource extends Resource
{
    protected static ?string $model = ProductTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static function recalculate(Get $get, Set $set)
    { 
        // validasi data produk dan jumlah produk
        if (! $get ('produk_id') || ! $get ('quantity')) {
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
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(),
                        TextInput::make('phone')
                            ->label('No. Telp')
                            ->tel()
                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                            ->required(),
                    ]),
                    Grid::make(3)
                    ->schema([
                        TextInput::make('city')
                            ->label('Kota')
                            ->columnSpan(2)
                            ->required(),
                        TextInput::make('post_code')
                            ->label('Kode Pos')
                            ->columnSpan(1)
                            ->required(),
                    ]),
                    Grid::make(1)
                    ->schema([
                        Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->required(),
                    ]),
                ]),
            Section::make('Informasi Produk')
                ->collapsible()
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
                            ->afterStateUpdated(fn (Get $get, Set $set) => 
                                self::recalculate($get, $set)),

                        Select::make('shoe_size')
                            ->label('Ukuran Sepatu')
                            // mengambil data size sesuai yg ada di produk
                            ->options(function (Get $get){
                                return ProdukSize::getSize($get('produk_id'));
                            })
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => 
                                self::recalculate($get, $set)),

                        Select::make('promo_code_id')
                            ->label('Kode Promo')
                            ->relationship('promoCode', 'code')
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set) => 
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
                                fn ($state) => 
                                str_replace('.', '', $state)),

                        TextInput::make('discount_amount')
                            ->label('Potongan Diskon')
                            ->prefix('Rp')
                            ->readOnly()
                            ->default(0)
                            ->dehydrateStateUsing(
                                fn ($state) => 
                                str_replace('.', '', $state)),

                        TextInput::make('grand_total_amount')
                            ->label('Total Bayar')
                            ->prefix('Rp')
                            ->readOnly()
                            ->extraInputAttributes([
                            'style' => 'font-weight: bold;'
                        ])
                            ->default(0)
                            ->dehydrateStateUsing(
                                fn ($state) => 
                                str_replace('.', '', $state)),
                    ]),
                ]),
            Section::make('Informasi Transaksi')
                ->collapsible()
                ->description('Kelola status pembayaran dan unggah bukti transfer')
                ->columns(2)
                ->schema([
                    Grid::make(3)
                        ->schema([
                        TextInput::make('booking_trx_id')
                            ->label('Kode Transaksi')
                            ->default(fn () => ProductTransaction::generateUniqueTrxId())
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
                                ? 'Pembayaran Telah dikonfirmasi'
                                : 'Menunggu Pembayaran dari pembeli' )
                            ->columnSpan(1)
                            ->live()
                            ->onColor('success')
                            ->offColor('danger')
                            ->required(),
                            ]),

                    FileUpload::make('proof')
                        ->label('Bukti Transaksi')
                        ->image()
                        ->nullable()
                        ->directory('produk/bukti transaksi')
                        ->columnSpanFull()
                        ->visible(fn ($get) => $get('is_paid')),
                ])
        ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama'),

                TextColumn::make('produk.name')
                    ->label('Produk'),

                TextColumn::make('email')
                    ->label('Email'),

                TextColumn::make('phone')
                    ->label('No. Telp'),

                TextColumn::make('booking_trx_id')
                    ->label('Booking ID'),

                TextColumn::make('is_paid')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? 'Sudah Bayar'
                        : 'Belum Bayar')
                    ->color(fn (bool $state): string => $state
                        ? 'success'
                        : 'danger')
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                ->iconButton()
                ->tooltip('Edit'),
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
