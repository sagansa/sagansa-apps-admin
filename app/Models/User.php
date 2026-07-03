<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Jetstream\HasProfilePhoto;
use Illuminate\Notifications\Notifiable;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;

class User extends Authenticatable implements FilamentUser
{
    protected $connection = 'mysql_auth';

    use HasRoles;
    use HasFactory;
    use Notifiable;
    use SoftDeletes;
    use HasApiTokens;
    use HasPanelShield;
    use HasProfilePhoto;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['profile_photo_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all of the vehicles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Get all of the utilityUsagesCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function utilityUsagesCreatedBy()
    {
        return $this->hasMany(UtilityUsage::class, 'created_by_id');
    }

    /**
     * Get all of the utilityUsagesApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function utilityUsagesApprovedBy()
    {
        return $this->hasMany(UtilityUsage::class, 'approved_by_id');
    }

    /**
     * Get all of the vehicleTaxes.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vehicleTaxes()
    {
        return $this->hasMany(VehicleTax::class);
    }

    /**

    /**
     * Get all of the closingCouriersCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function closingCouriersCreatedBy()
    {
        return $this->hasMany(ClosingCourier::class, 'created_by_id');
    }

    /**
     * Get all of the closingCouriersApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function closingCouriersApprovedBy()
    {
        return $this->hasMany(ClosingCourier::class, 'approved_by_id');
    }

    /**
     * Get all of the stores.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Get all of the advancePurchases.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function advancePurchases()
    {
        return $this->hasMany(AdvancePurchase::class);
    }

    /**
     * Get all of the products.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get all of the suppliers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    /**
     * Get all of the closingStoresTransferBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function closingStoresTransferBy()
    {
        return $this->hasMany(ClosingStore::class, 'transfer_by_id');
    }

    /**
     * Get all of the closingStoresCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function closingStoresCreatedBy()
    {
        return $this->hasMany(ClosingStore::class, 'created_by_id');
    }

    /**
     * Get all of the closingStoresApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function closingStoresApprovedBy()
    {
        return $this->hasMany(ClosingStore::class, 'approved_by_id');
    }

    /**
     * Get all of the dailySalariesCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dailySalariesCreatedBy()
    {
        return $this->hasMany(DailySalary::class, 'created_by_id');
    }

    /**
     * Get all of the dailySalariesApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dailySalariesApprovedBy()
    {
        return $this->hasMany(DailySalary::class, 'approved_by_id');
    }

    /**
     * Get all of the requestPurchases.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function requestPurchases()
    {
        return $this->hasMany(RequestPurchase::class);
    }

    /**
     * Get all of the productionsCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productionsCreatedBy()
    {
        return $this->hasMany(Production::class, 'created_by_id');
    }

    /**
     * Get all of the productionsApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productionsApprovedBy()
    {
        return $this->hasMany(Production::class, 'approved_by_id');
    }

    /**
     * Get all of the remainingStocksCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function remainingStocksCreatedBy()
    {
        return $this->hasMany(RemainingStock::class, 'created_by_id');
    }

    /**
     * Get all of the remainingStocksApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function remainingStocksApprovedBy()
    {
        return $this->hasMany(RemainingStock::class, 'approved_by_id');
    }

    /**
     * Get all of the selfConsumptionsCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function selfConsumptionsCreatedBy()
    {
        return $this->hasMany(SelfConsumption::class, 'created_by_id');
    }

    /**
     * Get all of the selfConsumptionsApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function selfConsumptionsApprovedBy()
    {
        return $this->hasMany(SelfConsumption::class, 'approved_by_id');
    }

    /**
     * Get all of the salesOrdersOrderedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salesOrdersOrderedBy()
    {
        return $this->hasMany(SalesOrder::class, 'ordered_by_id');
    }

    /**
     * Get all of the salesOrdersAssignedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function salesOrdersAssignedBy()
    {
        return $this->hasMany(SalesOrder::class, 'assigned_by_id');
    }

    /**
     * Get all of the transferStocksApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transferStocksApprovedBy()
    {
        return $this->hasMany(TransferStock::class, 'approved_by_id');
    }

    /**
     * Get all of the transferStocksReceivedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transferStocksReceivedBy()
    {
        return $this->hasMany(TransferStock::class, 'received_by_id');
    }

    /**
     * Get all of the transferStocksSentBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transferStocksSentBy()
    {
        return $this->hasMany(TransferStock::class, 'sent_by_id');
    }

    /**
     * Get all of the movementAssetResults.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function movementAssetResults()
    {
        return $this->hasMany(MovementAssetResult::class);
    }

    /**
     * Get all of the readinessesCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function readinessesCreatedBy()
    {
        return $this->hasMany(Readiness::class, 'created_by_id');
    }

    /**
     * Get all of the readinessesApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function readinessesApprovedBy()
    {
        return $this->hasMany(Readiness::class, 'approved_by_id');
    }

    /**
     * Get all of the hygienesCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hygienesCreatedBy()
    {
        return $this->hasMany(Hygiene::class, 'created_by_id');
    }

    /**
     * Get all of the hygienesApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hygienesApprovedBy()
    {
        return $this->hasMany(Hygiene::class, 'approved_by_id');
    }

    /**
     * Get all of the fuelServicesCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fuelServicesCreatedBy()
    {
        return $this->hasMany(FuelService::class, 'created_by_id');
    }

    /**
     * Get all of the fuelServicesApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fuelServicesApprovedBy()
    {
        return $this->hasMany(FuelService::class, 'approved_by_id');
    }

    /**
     * Get all of the invoicePurchasesCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoicePurchasesCreatedBy()
    {
        return $this->hasMany(InvoicePurchase::class, 'created_by_id');
    }

    /**
     * Get all of the invoicePurchasesApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function invoicePurchasesApprovedBy()
    {
        return $this->hasMany(InvoicePurchase::class, 'approved_by_id');
    }

    /**
     * Get all of the permitEmployeesCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permitEmployeesCreatedBy()
    {
        return $this->hasMany(PermitEmployee::class, 'created_by_id');
    }

    /**
     * Get all of the permitEmployeesApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permitEmployeesApprovedBy()
    {
        return $this->hasMany(PermitEmployee::class, 'approved_by_id');
    }

    /**
     * Get all of the materialGroups.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function materialGroups()
    {
        return $this->hasMany(MaterialGroup::class);
    }

    /**
     * Get all of the cashAdvances.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cashAdvances()
    {
        return $this->hasMany(CashAdvance::class);
    }

    /**
     * Get all of the deliveryAddresses.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function deliveryAddresses()
    {
        return $this->hasMany(DeliveryAddress::class);
    }

    /**
     * Get all of the utilityBills.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function utilityBills()
    {
        return $this->hasMany(UtilityBill::class);
    }

    /**
     * Get all of the storageStocksCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function storageStocksCreatedBy()
    {
        return $this->hasMany(StorageStock::class, 'created_by_id');
    }

    /**
     * Get all of the storageStocksApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function storageStocksApprovedBy()
    {
        return $this->hasMany(StorageStock::class, 'approved_by_id');
    }

    /**
     * Get all of the paymentReceipts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function paymentReceipts()
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    /**
     * Get all of the presencesCreatedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function presencesCreatedBy()
    {
        return $this->hasMany(Presence::class, 'created_by_id');
    }

    /**
     * Get all of the presencesApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function presencesApprovedBy()
    {
        return $this->hasMany(Presence::class, 'approved_by_id');
    }

    /**
     * Get all of the stockCards.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stockCards()
    {
        return $this->hasMany(StockCard::class);
    }

    /**
     * Get all of the transferCardsApprovedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transferCardsApprovedBy()
    {
        return $this->hasMany(TransferCard::class, 'approved_by_id');
    }

    /**
     * Get all of the transferCardsSentBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transferCardsSentBy()
    {
        return $this->hasMany(TransferCard::class, 'sent_by_id');
    }

    /**
     * Get all of the transferCardsReceivedBy.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transferCardsReceivedBy()
    {
        return $this->hasMany(TransferCard::class, 'received_by_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applicantDetail()
    {
        return $this->hasOne(ApplicantDetail::class);
    }

    public function workExperiences()
    {
        return $this->hasMany(WorkExperience::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function (User $user) {
            $user->assignRole('customer');
        });
    }


    /**
     * Override notifications to use the mysql connection (sagansa database).
     */
    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')->latest();
    }
}
