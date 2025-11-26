<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // テスト用にレート制限を無効化
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    /**
     * ユーザーが新規登録できることを確認
     */
    public function test_user_can_register()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'uuid',
                    'name',
                    'email',
                ],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => strtolower($userData['email']), // メールアドレスは小文字に正規化される
        ]);
    }

    /**
     * ユーザーがログインできることを確認
     */
    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'uuid',
                    'name',
                    'email',
                ],
                'token',
            ]);
    }

    /**
     * 無効な認証情報ではログインできないことを確認
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => 'WrongPassword123!',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
    }

    /**
     * 認証済みユーザーがログアウトできることを確認
     */
    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'ログアウトしました。',
            ]);
    }

    /**
     * 認証済みユーザーがプロフィールを取得できることを確認
     */
    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'uuid',
                    'name',
                    'email',
                ],
            ]);
    }

    /**
     * 未認証ユーザーが保護されたルートにアクセスできないことを確認
     */
    public function test_unauthenticated_user_cannot_access_protected_routes()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    /**
     * 登録には有効なデータが必要であることを確認
     */
    public function test_registration_requires_valid_data()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * 登録には一意のメールアドレスが必要であることを確認
     */
    public function test_registration_requires_unique_email()
    {
        $existingUser = User::factory()->create();

        $userData = [
            'name' => $this->faker->name,
            'email' => $existingUser->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * 登録にはパスワード確認が必要であることを確認
     */
    public function test_registration_requires_password_confirmation()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'DifferentPassword123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    // 名前のバリデーションテスト
    /**
     * 登録には名前の最小長が必要であることを確認
     */
    public function test_registration_requires_name_minimum_length()
    {
        $userData = [
            'name' => 'A', // 1文字のみ
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * 登録時に名前の前後の空白が削除されることを確認
     */
    public function test_registration_trims_whitespace_from_name()
    {
        $userData = [
            'name' => '  John Doe  ',
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe', // 前後の空白が削除される
        ]);
    }

    // メールアドレスのバリデーションテスト
    /**
     * 登録時にメールアドレスが小文字に正規化されることを確認
     */
    public function test_registration_normalizes_email_to_lowercase()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com', // 小文字に正規化される
        ]);
    }

    /**
     * 登録には有効なメールアドレス形式が必要であることを確認
     */
    public function test_registration_requires_valid_email_format()
    {
        $invalidEmails = [
            'notanemail',
            '@example.com',
            'user@',
            'user..name@example.com',
        ];

        foreach ($invalidEmails as $invalidEmail) {
            $userData = [
                'name' => $this->faker->name,
                'email' => $invalidEmail,
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ];

            $response = $this->postJson('/api/auth/register', $userData);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
        }
    }

    // パスワード強度のテスト
    /**
     * 登録には小文字を含むパスワードが必要であることを確認
     */
    public function test_registration_requires_password_with_lowercase()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'PASSWORD123!', // 小文字がない
            'password_confirmation' => 'PASSWORD123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 登録には大文字を含むパスワードが必要であることを確認
     */
    public function test_registration_requires_password_with_uppercase()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123!', // 大文字がない
            'password_confirmation' => 'password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 登録には数字を含むパスワードが必要であることを確認
     */
    public function test_registration_requires_password_with_numbers()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'PasswordAbc!', // 数字がない
            'password_confirmation' => 'PasswordAbc!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 登録には記号を含むパスワードが必要であることを確認
     */
    public function test_registration_requires_password_with_symbols()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123', // 記号がない
            'password_confirmation' => 'Password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 登録には最小長を満たすパスワードが必要であることを確認
     */
    public function test_registration_requires_password_minimum_length()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Pass1!', // 6文字のみ（最小長に満たない）
            'password_confirmation' => 'Pass1!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * 様々な有効なパスワード形式が受け入れられることを確認
     */
    public function test_registration_accepts_various_valid_passwords()
    {
        $validPasswords = [
            'Password123!',
            'MyP@ssw0rd',
            'Str0ng!Pass',
            'C0mpl3x&Secure',
        ];

        foreach ($validPasswords as $password) {
            $userData = [
                'name' => $this->faker->name,
                'email' => $this->faker->unique()->safeEmail,
                'password' => $password,
                'password_confirmation' => $password,
            ];

            $response = $this->postJson('/api/auth/register', $userData);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'user' => ['uuid', 'name', 'email'],
                    'token',
                ]);
        }
    }

    // トークンとレスポンスのテスト
    /**
     * 登録時に認証トークンが返されることを確認
     */
    public function test_registration_returns_auth_token()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure(['token']);

        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * 登録時にデータベースIDが公開されないことを確認
     */
    public function test_registration_does_not_expose_database_id()
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonMissing(['id'])
            ->assertJsonStructure(['user' => ['uuid']]);
    }

    /**
     * 登録時にパスワードがハッシュ化されることを確認
     */
    public function test_registration_hashes_password()
    {
        $plainPassword = 'Password123!';
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => $plainPassword,
            'password_confirmation' => $plainPassword,
        ];

        $this->postJson('/api/auth/register', $userData);

        $user = User::where('email', strtolower($userData['email']))->first();

        $this->assertNotNull($user);
        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }
}
