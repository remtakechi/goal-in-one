<?php

namespace Tests\Unit;

use App\Services\TurnstileService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class TurnstileServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 全ての設定が正しく設定されている場合、isConfiguredがtrueを返すことを確認
     */
    public function test_is_configured_returns_true_when_all_settings_present(): void
    {
        Config::set('app.is_use_turnstile', true);
        Config::set('app.turnstile_site_key', 'test-site-key');
        Config::set('app.turnstile_secret_key', 'test-secret-key');

        $service = new TurnstileService();

        $this->assertTrue($service->isConfigured());
    }

    /**
     * is_use_turnstileがfalseの場合、isConfiguredがfalseを返すことを確認
     */
    public function test_is_configured_returns_false_when_turnstile_disabled(): void
    {
        Config::set('app.is_use_turnstile', false);
        Config::set('app.turnstile_site_key', 'test-site-key');
        Config::set('app.turnstile_secret_key', 'test-secret-key');

        $service = new TurnstileService();

        $this->assertFalse($service->isConfigured());
    }

    /**
     * site_keyが空の場合、isConfiguredがfalseを返すことを確認
     */
    public function test_is_configured_returns_false_when_site_key_missing(): void
    {
        Config::set('app.is_use_turnstile', true);
        Config::set('app.turnstile_site_key', '');
        Config::set('app.turnstile_secret_key', 'test-secret-key');

        $service = new TurnstileService();

        $this->assertFalse($service->isConfigured());
    }

    /**
     * secret_keyが空の場合、isConfiguredがfalseを返すことを確認
     */
    public function test_is_configured_returns_false_when_secret_key_missing(): void
    {
        Config::set('app.is_use_turnstile', true);
        Config::set('app.turnstile_site_key', 'test-site-key');
        Config::set('app.turnstile_secret_key', '');

        $service = new TurnstileService();

        $this->assertFalse($service->isConfigured());
    }

    /**
     * 全ての設定が欠けている場合、isConfiguredがfalseを返すことを確認
     */
    public function test_is_configured_returns_false_when_all_settings_missing(): void
    {
        Config::set('app.is_use_turnstile', false);
        Config::set('app.turnstile_site_key', '');
        Config::set('app.turnstile_secret_key', '');

        $service = new TurnstileService();

        $this->assertFalse($service->isConfigured());
    }

    /**
     * 検証が成功した場合、successがtrueのレスポンスを返すことを確認
     */
    public function test_verify_returns_success_when_validation_passes(): void
    {
        Config::set('app.turnstile_secret_key', 'test-secret-key');

        // モックレスポンスを作成
        $mockResponse = new Response(200, [], json_encode(['success' => true]));

        $service = $this->createServiceWithMockClient($mockResponse);

        $result = $service->verify('test-token');

        $this->assertTrue($result['success']);
    }

    /**
     * 検証が失敗した場合、successがfalseのレスポンスを返すことを確認
     */
    public function test_verify_returns_failure_when_validation_fails(): void
    {
        Config::set('app.turnstile_secret_key', 'test-secret-key');

        // モックレスポンスを作成
        $mockResponse = new Response(200, [], json_encode([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]));

        $service = $this->createServiceWithMockClient($mockResponse);

        $result = $service->verify('invalid-token');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error-codes', $result);
    }

    /**
     * 空のレスポンスが返された場合、successがfalseを返すことを確認
     */
    public function test_verify_returns_failure_when_response_is_empty(): void
    {
        Config::set('app.turnstile_secret_key', 'test-secret-key');

        // 空のレスポンスを作成
        $mockResponse = new Response(200, [], '');

        $service = $this->createServiceWithMockClient($mockResponse);

        $result = $service->verify('test-token');

        $this->assertFalse($result['success']);
    }

    /**
     * HTTP例外が発生した場合、successがfalseを返すことを確認
     */
    public function test_verify_returns_failure_on_http_exception(): void
    {
        Config::set('app.turnstile_secret_key', 'test-secret-key');
        Log::shouldReceive('error')->once();

        // HTTP例外をスローするモックハンドラーを作成
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Exception\RequestException(
                'Connection timeout',
                new \GuzzleHttp\Psr7\Request('POST', 'test')
            ),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $service = new TurnstileService();
        $this->setPrivateProperty($service, 'client', $mockClient);

        $result = $service->verify('test-token');

        $this->assertFalse($result['success']);
    }

    /**
     * タイムアウト例外が発生した場合、successがfalseを返すことを確認
     */
    public function test_verify_returns_failure_on_timeout_exception(): void
    {
        Config::set('app.turnstile_secret_key', 'test-secret-key');
        Log::shouldReceive('error')->once();

        // タイムアウト例外をスローするモックハンドラーを作成
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection timeout',
                new \GuzzleHttp\Psr7\Request('POST', 'test')
            ),
        ]);

        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $service = new TurnstileService();
        $this->setPrivateProperty($service, 'client', $mockClient);

        $result = $service->verify('test-token');

        $this->assertFalse($result['success']);
    }

    /**
     * 無効なJSONレスポンスが返された場合、適切に処理されることを確認
     */
    public function test_verify_handles_invalid_json_response(): void
    {
        Config::set('app.turnstile_secret_key', 'test-secret-key');

        // 無効なJSONレスポンスを作成
        $mockResponse = new Response(200, [], 'invalid json');

        $service = $this->createServiceWithMockClient($mockResponse);

        $result = $service->verify('test-token');

        // json_decodeが失敗するとnullが返され、empty()でtrueになるため、successはfalseになる
        $this->assertFalse($result['success']);
    }

    /**
     * モッククライアントを使用してTurnstileServiceを作成するヘルパーメソッド
     */
    private function createServiceWithMockClient(Response $mockResponse): TurnstileService
    {
        $mockHandler = new MockHandler([$mockResponse]);
        $handlerStack = HandlerStack::create($mockHandler);
        $mockClient = new Client(['handler' => $handlerStack]);

        $service = new TurnstileService();
        $this->setPrivateProperty($service, 'client', $mockClient);

        return $service;
    }

    /**
     * リフレクションを使用してprivateプロパティを設定するヘルパーメソッド
     */
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}

