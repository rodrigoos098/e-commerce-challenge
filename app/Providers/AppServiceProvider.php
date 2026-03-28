<?php

namespace App\Providers;

use App\Events\OrderCancelled;
use App\Events\OrderCreated;
use App\Events\OrderDelivered;
use App\Events\OrderPaymentConfirmed;
use App\Events\OrderShipped;
use App\Listeners\ProcessOrderListener;
use App\Listeners\QueueOrderCancelledNotification;
use App\Listeners\QueueOrderCreatedNotification;
use App\Listeners\QueueOrderDeliveredNotification;
use App\Listeners\QueueOrderPaymentConfirmedNotification;
use App\Listeners\QueueOrderShippedNotification;
use App\Models\Order;
use App\Repositories\CartRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\StockMovementRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class, CategoryRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
        $this->app->bind(StockMovementRepositoryInterface::class, StockMovementRepository::class);

        if (class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Event::listen(OrderCreated::class, ProcessOrderListener::class);
        Event::listen(OrderCreated::class, QueueOrderCreatedNotification::class);
        Event::listen(OrderPaymentConfirmed::class, QueueOrderPaymentConfirmedNotification::class);
        Event::listen(OrderCancelled::class, QueueOrderCancelledNotification::class);
        Event::listen(OrderShipped::class, QueueOrderShippedNotification::class);
        Event::listen(OrderDelivered::class, QueueOrderDeliveredNotification::class);

        Relation::morphMap([
            'order' => Order::class,
        ]);
    }
}
