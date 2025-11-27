<?php

namespace Tests\Browser\Auth;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Browser\Pages\Auth\RegisterPage;

class RegisterPageTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * 画面: /register
     * 操作: ページにアクセスする
     * 期待: 入力項目が表示されている
     */
    public function test_displays_register_form_elements()
    {
        $this->browse(function (Browser $browser) {
            // --- ページオブジェクト使用 ---
            $register_page = new RegisterPage();

            // --- ページにアクセス ---
            $browser->visit($register_page);

            // --- 期待結果確認：フォーム要素が表示されている ---
            $register_page->assertRegisterFormVisible($browser);

            // --- テキスト確認 ---
            $browser->waitForText('お名前', 10)
                    ->assertSee('メールアドレス')
                    ->assertSee('パスワード')
                    ->assertSee('パスワード確認')
                    ->assertSee('利用規約');
        });
    }

    /**
     * 画面: /register
     * 操作: 正しい情報を入力して送信ボタンを押下する
     * 期待: usersに会員データが作成され、ログインした状態で/dashboardに遷移している
     */
    public function test_successful_registration_creates_user_and_redirects_to_dashboard()
    {
        $this->browse(function (Browser $browser) {
            // --- テストデータ準備 ---
            $test_name = 'テストユーザー太郎';
            $test_email = 'test.user@example.com';
            $test_password = 'Password123!';

            // --- ページオブジェクト使用 ---
            $register_page = new RegisterPage();

            // --- ページにアクセス ---
            $browser->visit($register_page);

            // --- フォームに入力 ---
            $register_page->fillRegisterForm(
                $browser,
                $test_name,
                $test_email,
                $test_password,
                $test_password,
                true
            );

            // --- 送信ボタンを押下 ---
            $register_page->submitForm($browser);

            // --- 期待結果確認：/dashboardに遷移している ---
            $browser->waitForLocation('/dashboard', 10)
                    ->assertPathIs('/dashboard');

            // --- 期待結果確認：usersテーブルにデータが作成されている ---
            $this->assertDatabaseHas('users', [
                'name' => $test_name,
                'email' => $test_email,
            ]);
        });
    }
}
