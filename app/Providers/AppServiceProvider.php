<?php

namespace App\Providers;

use App\Models\Area;
use App\Models\Asset;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\InventoryMovement;
use App\Models\Part;
use App\Models\PmSchedule;
use App\Models\SubArea;
use App\Models\SubAsset;
use App\Models\User;
use App\Models\WorkOrder;
use App\Policies\AreaPolicy;
use App\Policies\ChatConversationPolicy;
use App\Policies\ChatMessagePolicy;
use App\Policies\PartPolicy;
use App\Policies\PmSchedulePolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkOrderPolicy;
use App\Observers\InventoryMovementObserver;
use App\Observers\PartObserver;
use App\Observers\WorkOrderObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Area::class => AreaPolicy::class,
        SubArea::class => AreaPolicy::class,  // Same policy for all master data
        Asset::class => AreaPolicy::class,
        SubAsset::class => AreaPolicy::class,
        Part::class => PartPolicy::class,
        User::class => UserPolicy::class,
        PmSchedule::class => PmSchedulePolicy::class,
        WorkOrder::class => WorkOrderPolicy::class,
        ChatConversation::class => ChatConversationPolicy::class,
        ChatMessage::class => ChatMessagePolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Register observers
        Part::observe(PartObserver::class);
        WorkOrder::observe(WorkOrderObserver::class);
        InventoryMovement::observe(InventoryMovementObserver::class);
    }
}
