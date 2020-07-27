<?php

namespace Laracasts\Cypress\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Artisan;
use Laracasts\Cypress\Tests\Support\TestUser;
use Laracasts\Cypress\CypressServiceProvider;

class CypressControllerTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [CypressServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
        $this->withFactories(__DIR__.'/support/factories');

        config(['auth.providers.users.model' => TestUser::class]);
    }

    /** @test */
    function it_logs_a_new_user_in()
    {
        $this->post(route('cypress.login'));

        $this->assertTrue(auth()->check());
    }

    /** @test */
    function it_logs_a_new_user_in_with_the_given_attributes()
    {
        $this->post(route('cypress.login'), [
            'name' => 'Frank'
        ]);

        $this->assertDatabaseHas('users', ['name' => 'Frank']);
    }

    /** @test */
    function it_logs_a_user_out()
    {
        $this->post(route('cypress.login'));

        $this->post(route('cypress.logout'));

        $this->assertFalse(auth()->check());
    }

    /** @test */
    function it_generates_an_eloquent_model_using_a_factory()
    {
        $response = $this->post(route('cypress.factory'), [
            'model' => TestUser::class,
            'attributes' => [
                'name' => 'John Doe'
            ]
        ]);

        $this->assertDatabaseHas('users', ['name' => 'John Doe']);

        $this->assertEquals('John Doe', $response->json()['name']);
    }

    /** @test */
    function it_generates_a_collection_of_eloquent_model_using_a_factory()
    {
        $response = $this->post(route('cypress.factory'), [
            'model' => TestUser::class,
            'times' => 2,
            'attributes' => [
                'name' => 'John Doe'
            ]
        ]);

        $this->assertEquals(2, TestUser::whereName('John Doe')->count());

        $this->assertCount(2, $response->json());
        $this->assertEquals('John Doe', $response->json()[0]['name']);
    }

    /** @test */
    function it_runs_an_artisan_command()
    {
        $called = false;

        Artisan::command('testing', function () use (&$called) {
            $called = true;
        });

        $this->post(route('cypress.artisan'), [
            'command' => 'testing'
        ]);

        $this->assertTrue($called);
    }
}
