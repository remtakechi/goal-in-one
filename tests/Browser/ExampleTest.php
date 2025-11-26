<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Vue.js SPAが正しく読み込まれ、"Goal in One"が表示されることを確認するテスト
     */
    public function testBasicExample(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    // Vueアプリのマウントとルーターリダイレクトを待機（'/' → '/dashboard' → '/login'）
                    ->waitForText('Goal in One', 10)
                    ->assertSee('Goal in One');
        });
    }
}
