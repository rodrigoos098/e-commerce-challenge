<?php

namespace Tests\Feature\Web;

use App\Http\Controllers\CartPageController;
use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CartRoutesTest extends TestCase
{
    public function test_cart_named_routes_keep_their_contracts(): void
    {
        $expectedRoutes = [
            'cart' => ['GET', 'cart', 'index'],
            'cart.items.add' => ['POST', 'cart/items', 'addItem'],
            'cart.items.update' => ['PUT', 'cart/items/{item}', 'updateItem'],
            'cart.items.remove' => ['DELETE', 'cart/items/{item}', 'removeItem'],
            'cart.clear' => ['DELETE', 'cart', 'clear'],
        ];

        foreach ($expectedRoutes as $name => [$method, $uri, $actionMethod]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertInstanceOf(LaravelRoute::class, $route);
            $this->assertSame([$method], $this->httpMethods($route));
            $this->assertSame($uri, $route->uri());
            $this->assertSame(CartPageController::class.'@'.$actionMethod, $route->getActionName());
        }
    }

    public function test_cart_routes_are_declared_only_once_in_web_routes_file(): void
    {
        $webRoutes = (string) file_get_contents(base_path('routes/web.php'));

        $patterns = [
            "/Route::get\('\\/cart', \[CartPageController::class, 'index'\]\)->name\('cart'\);/",
            "/Route::post\('\\/cart\\/items', \[CartPageController::class, 'addItem'\]\)->name\('cart.items.add'\);/",
            "/Route::put\('\\/cart\\/items\\/\{item\}', \[CartPageController::class, 'updateItem'\]\)->name\('cart.items.update'\);/",
            "/Route::delete\('\\/cart\\/items\\/\{item\}', \[CartPageController::class, 'removeItem'\]\)->name\('cart.items.remove'\);/",
            "/Route::delete\('\\/cart', \[CartPageController::class, 'clear'\]\)->name\('cart.clear'\);/",
        ];

        foreach ($patterns as $pattern) {
            $this->assertSame(1, preg_match_all($pattern, $webRoutes));
        }
    }

    /**
     * @return list<string>
     */
    private function httpMethods(LaravelRoute $route): array
    {
        return array_values(array_diff($route->methods(), ['HEAD']));
    }
}
