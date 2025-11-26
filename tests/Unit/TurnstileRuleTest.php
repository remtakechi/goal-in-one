<?php

namespace Tests\Unit;

use App\Rules\TurnstileRule;
use App\Services\TurnstileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class TurnstileRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Turnstileが無効な場合、バリデーションをスキップすることを確認
     */
    public function test_validation_skips_when_turnstile_not_configured(): void
    {
        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(false);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failCalled = false;

        $rule->validate('cf-turnstile-response', 'test-token', function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertFalse($failCalled, 'failコールバックが呼ばれてはいけない');
    }

    /**
     * 空値の場合、バリデーションが失敗することを確認
     */
    public function test_validation_fails_when_value_is_empty(): void
    {
        Config::set('app.turnstile_error_msg', 'テストエラーメッセージ');

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failMessage = null;

        $rule->validate('cf-turnstile-response', '', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });

        $this->assertNotNull($failMessage, 'failコールバックが呼ばれるべき');
        $this->assertEquals('テストエラーメッセージ', $failMessage);
    }

    /**
     * null値の場合、バリデーションが失敗することを確認
     */
    public function test_validation_fails_when_value_is_null(): void
    {
        Config::set('app.turnstile_error_msg', 'テストエラーメッセージ');

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failMessage = null;

        $rule->validate('cf-turnstile-response', null, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });

        $this->assertNotNull($failMessage, 'failコールバックが呼ばれるべき');
    }

    /**
     * 文字列でない場合、バリデーションが失敗することを確認
     */
    public function test_validation_fails_when_value_is_not_string(): void
    {
        Config::set('app.turnstile_error_msg', 'テストエラーメッセージ');

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failMessage = null;

        $rule->validate('cf-turnstile-response', 12345, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });

        $this->assertNotNull($failMessage, 'failコールバックが呼ばれるべき');
    }

    /**
     * 2048文字を超える場合、バリデーションが失敗することを確認
     */
    public function test_validation_fails_when_value_exceeds_max_length(): void
    {
        Config::set('app.turnstile_error_msg', 'テストエラーメッセージ');

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failMessage = null;
        $longString = str_repeat('a', 2049); // 2049文字

        $rule->validate('cf-turnstile-response', $longString, function ($message) use (&$failMessage) {
            $failMessage = $message;
        });

        $this->assertNotNull($failMessage, 'failコールバックが呼ばれるべき');
    }

    /**
     * 検証が成功した場合、バリデーションを通過することを確認
     */
    public function test_validation_passes_when_verification_succeeds(): void
    {
        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);
        $mockService->shouldReceive('verify')
            ->once()
            ->with('valid-token')
            ->andReturn(['success' => true]);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failCalled = false;

        $rule->validate('cf-turnstile-response', 'valid-token', function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertFalse($failCalled, 'failコールバックが呼ばれてはいけない');
    }

    /**
     * 検証が失敗した場合、バリデーションが失敗することを確認
     */
    public function test_validation_fails_when_verification_fails(): void
    {
        Config::set('app.turnstile_error_msg', 'テストエラーメッセージ');

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);
        $mockService->shouldReceive('verify')
            ->once()
            ->with('invalid-token')
            ->andReturn(['success' => false, 'error-codes' => ['invalid-input-response']]);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failMessage = null;

        $rule->validate('cf-turnstile-response', 'invalid-token', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });

        $this->assertNotNull($failMessage, 'failコールバックが呼ばれるべき');
        $this->assertEquals('テストエラーメッセージ', $failMessage);
    }

    /**
     * successキーがない場合、バリデーションが失敗することを確認
     */
    public function test_validation_fails_when_success_key_missing(): void
    {
        Config::set('app.turnstile_error_msg', 'テストエラーメッセージ');

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);
        $mockService->shouldReceive('verify')
            ->once()
            ->with('test-token')
            ->andReturn(['error-codes' => ['unknown-error']]);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failMessage = null;

        $rule->validate('cf-turnstile-response', 'test-token', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });

        $this->assertNotNull($failMessage, 'failコールバックが呼ばれるべき');
    }

    /**
     * 設定されたエラーメッセージが使用されることを確認
     */
    public function test_configured_error_message_is_used(): void
    {
        // カスタムエラーメッセージを設定
        $customMessage = 'カスタムエラーメッセージ';
        $originalValue = Config::get('app.turnstile_error_msg');
        Config::set('app.turnstile_error_msg', $customMessage);

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failMessage = null;

        $rule->validate('cf-turnstile-response', '', function ($message) use (&$failMessage) {
            $failMessage = $message;
        });

        $this->assertNotNull($failMessage, 'failコールバックが呼ばれるべき');
        $this->assertEquals($customMessage, $failMessage);

        // 元の値を復元
        Config::set('app.turnstile_error_msg', $originalValue);
    }

    /**
     * 2048文字ちょうどの場合、バリデーションを通過することを確認（境界値テスト）
     */
    public function test_validation_passes_when_value_is_exactly_max_length(): void
    {
        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('isConfigured')->once()->andReturn(true);
        $mockService->shouldReceive('verify')
            ->once()
            ->with(str_repeat('a', 2048))
            ->andReturn(['success' => true]);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileRule();
        $failCalled = false;

        $rule->validate('cf-turnstile-response', str_repeat('a', 2048), function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertFalse($failCalled, 'failコールバックが呼ばれてはいけない');
    }
}

