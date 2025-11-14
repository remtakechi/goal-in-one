<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * A basic browser test example.
     * 
     * This test verifies that the Vue.js SPA loads correctly and displays
     * the "Goal in One" text. Since this is a SPA, we need to wait for
     * JavaScript to load and Vue to render the content.
     */
    public function testBasicExample(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    // Wait for Vue app to mount and router to redirect
                    // The '/' route redirects to '/dashboard', which requires auth,
                    // so it will redirect to '/login' where "Goal in One" is visible
                    ->waitForText('Goal in One', 10)
                    ->assertSee('Goal in One');
        });
    }
}
