<?php

namespace App\Filament\Pages;

use App\Models\ApplicantDetail;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class MyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \UnitEnum | null $navigationGroup = 'Personal Data';

    protected static ?string $navigationLabel = 'Profil Saya';

    protected static ?string $title = 'Profil Saya';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.my-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $details = ApplicantDetail::where('user_id', Auth::id())->first();

        if ($details) {
            $this->form->fill($details->toArray());
        } else {
            $this->form->fill([
                'bank_account_name' => '',
                'bank_account_number' => '',
                'bank_name' => '',
            ]);
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Informasi Rekening Bank (Bisa Diedit)')
                    ->description('Silakan lengkapi rekening bank Anda untuk transfer pembayaran gaji.')
                    ->schema([
                        TextInput::make('bank_account_name')
                            ->label('Nama Pemegang Rekening')
                            ->required()
                            ->placeholder('Sesuai nama di buku tabungan'),

                        TextInput::make('bank_account_number')
                            ->label('Nomor Rekening')
                            ->required()
                            ->placeholder('Masukkan nomor rekening bank'),

                        TextInput::make('bank_name')
                            ->label('Nama Bank')
                            ->required()
                            ->placeholder('BCA / Mandiri / BRI / BNI dll'),
                    ])->columns(3),

                Section::make('Data Pelamar / Karyawan (Hanya Dibaca)')
                    ->description('Data diri Anda saat pendaftaran rekrutmen. Data ini tidak dapat diubah.')
                    ->schema([
                        TextInput::make('nickname')
                            ->label('Nama Panggilan')
                            ->disabled(),

                        TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->disabled(),

                        TextInput::make('nik')
                            ->label('NIK')
                            ->disabled(),

                        TextInput::make('religion')
                            ->label('Agama')
                            ->disabled(),

                        TextInput::make('gender')
                            ->label('Jenis Kelamin')
                            ->disabled(),

                        TextInput::make('marital_status')
                            ->label('Status Pernikahan')
                            ->disabled(),

                        DatePicker::make('join_date')
                            ->label('Tanggal Bergabung')
                            ->disabled(),

                        TextInput::make('admin_fee')
                            ->label('Biaya Admin Transfer')
                            ->prefix('Rp')
                            ->disabled(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Perubahan')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $formData = $this->form->getState();

        $details = ApplicantDetail::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'bank_account_name' => $formData['bank_account_name'],
                'bank_account_number' => $formData['bank_account_number'],
                'bank_name' => $formData['bank_name'],
            ]
        );

        Notification::make()
            ->title('Informasi rekening bank berhasil disimpan.')
            ->success()
            ->send();
    }
}
