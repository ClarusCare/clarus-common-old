<?php

namespace Clarus\SecureChat\Tests\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Laravel\BrowserKitTesting\TestCase as BaseTestCase;

abstract class BrowserKitTestCase extends BaseTestCase
{
    /**
     * The base URL of the application.
     *
     * @var string
     */
    public $baseUrl = 'http://localhost';

    protected $routePrefix;

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../../../../../bootstrap/app.php';

        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

        $this->routePrefix = config('secure-chat.route_prefix');

        return $app;
    }

    public function getAccessToken()
    {
        $result = $this->call('POST', '/api/v3/oauth/access_token', [
            'username'   => 'admin@example.com',
            'password'   => 'password',
            'grant_type' => 'password',
        ]);

        $data = json_decode($result->getContent());

        return $data->access_token;
    }

    public function getLinkedProviderAccessToken()
    {
        $result = $this->call('POST', '/api/v3/oauth/access_token', [
            'username'   => 'linked_provider@example.com',
            'password'   => 'password',
            'grant_type' => 'password',
        ]);

        $data = json_decode($result->getContent());

        return $data->access_token;
    }

    public function getOfficeManagerAccessToken()
    {
        $result = $this->call('POST', '/api/v3/oauth/access_token', [
            'username'   => 'office_manager@example.com',
            'password'   => 'password',
            'grant_type' => 'password',
        ]);

        $data = json_decode($result->getContent());

        return $data->access_token;
    }

    public function getProviderAccessToken()
    {
        $result = $this->call('POST', '/api/v3/oauth/access_token', [
            'username'   => 'admin2@example.com',
            'password'   => 'password',
            'grant_type' => 'password',
        ]);

        $data = json_decode($result->getContent());

        return $data->access_token;
    }

    public function getUserAccessToken()
    {
        $result = $this->call('POST', '/api/v3/oauth/access_token', [
            'username'   => 'user@example.com',
            'password'   => 'password',
            'grant_type' => 'password',
        ]);

        $data = json_decode($result->getContent());

        return $data->access_token;
    }

    public function setupDatabase(): void
    {
        $tableNames = Schema::getConnection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tableNames as $name) {
            $statement = "DROP TABLE IF EXISTS {$name} CASCADE";
            DB::statement($statement);
        }

        Artisan::call('migrate');
        Artisan::call('db:seed');

        $this->migrated = true;
    }

    protected function isAuthorized(): void
    {
        $user = \App\Models\User::find(1);
        $this->actingAs($user);
    }
}
