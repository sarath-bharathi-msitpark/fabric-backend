<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Buyer;
use App\Models\FabricRecord;
use App\Models\Style;
use App\Models\Supplier;
use App\Models\User;
use App\Policies\AlertPolicy;
use App\Policies\BuyerPolicy;
use App\Policies\FabricRecordPolicy;
use App\Policies\StylePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        FabricRecord::class => FabricRecordPolicy::class,
        Supplier::class => SupplierPolicy::class,
        Buyer::class => BuyerPolicy::class,
        Style::class => StylePolicy::class,
        User::class => UserPolicy::class,
        Alert::class => AlertPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
