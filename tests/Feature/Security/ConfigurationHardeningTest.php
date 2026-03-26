<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Providers\TelescopeServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ConfigurationHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<string, string|false>
     */
    private array $environmentBackup = [];

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
    }

    protected function tearDown(): void
    {
        foreach ($this->environmentBackup as $key => $value) {
            $this->setEnvironmentValue($key, $value);
        }

        parent::tearDown();
    }

    public function test_sanctum_stateful_domains_are_trimmed_and_filter_empty_values(): void
    {
        $this->rememberEnvironmentValue('SANCTUM_STATEFUL_DOMAINS');
        $this->setEnvironmentValue('SANCTUM_STATEFUL_DOMAINS', ' storefront.test , storefront.test:3000 ,, localhost ');

        $config = require base_path('config/sanctum.php');

        $this->assertSame([
            'storefront.test',
            'storefront.test:3000',
            'localhost',
        ], $config['stateful']);
    }

    public function test_telescope_is_disabled_by_default_outside_local_environment_when_not_explicitly_enabled(): void
    {
        $this->rememberEnvironmentValue('APP_ENV');
        $this->rememberEnvironmentValue('TELESCOPE_ENABLED');

        $this->setEnvironmentValue('APP_ENV', 'production');
        $this->unsetEnvironmentValue('TELESCOPE_ENABLED');

        $config = require base_path('config/telescope.php');

        $this->assertFalse($config['enabled']);
    }

    public function test_telescope_provider_registration_does_not_depend_on_environment_variables(): void
    {
        $this->rememberEnvironmentValue('APP_ENV');
        $this->rememberEnvironmentValue('TELESCOPE_ENABLED');

        $this->setEnvironmentValue('APP_ENV', 'production');
        $this->unsetEnvironmentValue('TELESCOPE_ENABLED');

        $providers = require base_path('bootstrap/providers.php');

        $this->assertContains(TelescopeServiceProvider::class, $providers);
    }

    public function test_telescope_allows_allowlisted_admins(): void
    {
        config()->set('telescope.allowed_emails', ['admin@example.com']);
        $this->registerTelescopeGate();

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('viewTelescope'));
    }

    public function test_telescope_denies_guests(): void
    {
        config()->set('telescope.allowed_emails', ['admin@example.com']);
        $this->registerTelescopeGate();

        $this->assertFalse(Gate::allows('viewTelescope'));
    }

    public function test_telescope_denies_customers_even_when_their_email_is_allowlisted(): void
    {
        config()->set('telescope.allowed_emails', ['customer@example.com']);
        $this->registerTelescopeGate();

        $customer = User::factory()->create(['email' => 'customer@example.com']);
        $customer->assignRole('customer');

        $this->assertFalse(Gate::forUser($customer)->allows('viewTelescope'));
    }

    public function test_telescope_denies_admins_who_are_not_allowlisted(): void
    {
        config()->set('telescope.allowed_emails', ['other-admin@example.com']);
        $this->registerTelescopeGate();

        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->assignRole('admin');

        $this->assertFalse(Gate::forUser($admin)->allows('viewTelescope'));
    }

    private function registerTelescopeGate(): void
    {
        $provider = new class($this->app) extends TelescopeServiceProvider
        {
            public function registerGateForTesting(): void
            {
                $this->gate();
            }
        };

        $provider->registerGateForTesting();
    }

    public function test_swagger_routes_require_authenticated_admins(): void
    {
        $config = require base_path('config/l5-swagger.php');

        $this->assertSame(['web', 'auth', 'role:admin'], $config['defaults']['routes']['middleware']['api']);
        $this->assertSame(['web', 'auth', 'role:admin'], $config['defaults']['routes']['middleware']['asset']);
        $this->assertSame(['web', 'auth', 'role:admin'], $config['defaults']['routes']['middleware']['docs']);
        $this->assertSame(['web', 'auth', 'role:admin'], $config['defaults']['routes']['middleware']['oauth2_callback']);
    }

    private function rememberEnvironmentValue(string $key): void
    {
        if (! array_key_exists($key, $this->environmentBackup)) {
            $value = getenv($key);

            $this->environmentBackup[$key] = $value === false ? false : $value;
        }
    }

    private function setEnvironmentValue(string $key, string|false $value): void
    {
        if ($value === false) {
            $this->unsetEnvironmentValue($key);

            return;
        }

        putenv($key.'='.$value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }

    private function unsetEnvironmentValue(string $key): void
    {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);
    }
}
