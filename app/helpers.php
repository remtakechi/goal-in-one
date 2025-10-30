<?php

use App\Services\TurnstileService;

if (! function_exists('is_turnstile_configured')) {
    /**
     * Turnstileがサイト全体で有効かつ設定が不足していないかを判定
     * Bladeテンプレート等で使用可能
     *
     * @return bool
     */
    function is_turnstile_configured()
    {
        return app(TurnstileService::class)->isConfigured();
    }
}
