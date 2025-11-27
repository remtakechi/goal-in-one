<?php

namespace Tests\Browser\Pages\Auth;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page as BasePage;

class RegisterPage extends BasePage
{
    /**
     * ページのURL
     */
    public function url(): string
    {
        return '/register';
    }

    /**
     * 会員登録フォームが表示されていることを確認
     */
    public function assertRegisterFormVisible(Browser $browser): void
    {
        $browser->waitFor('@register-form', 10)
                ->assertVisible('@name-input')
                ->assertVisible('@email-input')
                ->assertVisible('@password-input')
                ->assertVisible('@password-confirmation-input')
                ->assertVisible('@terms-checkbox')
                ->assertVisible('@submit-button');
    }

    /**
     * 会員登録フォームに入力
     */
    public function fillRegisterForm(
        Browser $browser,
        string $name,
        string $email,
        string $password,
        string $password_confirmation,
        bool $agree_to_terms = true
    ): void {
        $browser->type('@name-input', $name)
                ->type('@email-input', $email)
                ->type('@password-input', $password)
                ->type('@password-confirmation-input', $password_confirmation);

        if ($agree_to_terms) {
            $browser->check('@terms-checkbox');
        }
    }

    /**
     * 送信ボタンをクリック
     */
    public function submitForm(Browser $browser): void
    {
        $browser->click('@submit-button');
    }
}
