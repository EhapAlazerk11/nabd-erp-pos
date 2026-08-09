<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\NabdBridge\Http\Middleware\NabdTokenMiddleware;
use Modules\NabdBridge\Models\NabdApiToken;
use Tests\TestCase;

class NabdBridgeTest extends TestCase
{
    use WithFaker;

    private string $plainToken;

    private NabdApiToken $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the nabd_api_tokens table if it doesn't exist in the test SQLite DB
        if ( ! Schema::hasTable( 'nabd_api_tokens' ) ) {
            Schema::create( 'nabd_api_tokens', function ( Blueprint $table ): void {
                $table->id();
                $table->string( 'name' );
                $table->string( 'token', 64 )->unique();
                $table->string( 'plain_token', 64 )->nullable();
                $table->timestamp( 'last_used_at' )->nullable();
                $table->timestamp( 'expires_at' )->nullable();
                $table->timestamps();
            } );
        }

        ['plain' => $plain, 'hashed' => $hashed] = NabdApiToken::generateToken();
        $this->plainToken = $plain;
        $this->token = NabdApiToken::create( [
            'name' => 'Test Token',
            'token' => $hashed,
        ] );
    }

    protected function tearDown(): void
    {
        NabdApiToken::truncate();
        parent::tearDown();
    }

    // ── Token model ───────────────────────────────────────────────────────

    public function test_token_generation_produces_plain_and_hashed(): void
    {
        ['plain' => $plain, 'hashed' => $hashed] = NabdApiToken::generateToken();

        $this->assertNotEmpty( $plain );
        $this->assertNotEmpty( $hashed );
        $this->assertEquals( 64, strlen( $hashed ) );
        $this->assertEquals( hash( 'sha256', $plain ), $hashed );
    }

    public function test_non_expired_token_is_valid(): void
    {
        $token = NabdApiToken::create( [
            'name' => 'Valid Token',
            'token' => hash( 'sha256', 'test' ),
            'expires_at' => now()->addDay(),
        ] );

        $this->assertFalse( $token->isExpired() );
    }

    public function test_expired_token_is_detected(): void
    {
        $token = NabdApiToken::create( [
            'name' => 'Expired Token',
            'token' => hash( 'sha256', 'expired_test' ),
            'expires_at' => now()->subDay(),
        ] );

        $this->assertTrue( $token->isExpired() );
    }

    public function test_token_with_no_expiry_is_not_expired(): void
    {
        $token = NabdApiToken::create( [
            'name' => 'No Expiry',
            'token' => hash( 'sha256', 'no_expiry' ),
            'expires_at' => null,
        ] );

        $this->assertFalse( $token->isExpired() );
    }

    // ── Middleware unit tests ─────────────────────────────────────────────

    public function test_middleware_passes_with_valid_x_nabd_token_header(): void
    {
        $middleware = new NabdTokenMiddleware;
        $request = Request::create( '/api/nabd/ping', 'GET' );
        $request->headers->set( 'X-Nabd-Token', $this->plainToken );

        $passed = false;
        $middleware->handle( $request, function ( $req ) use ( &$passed ) {
            $passed = true;

            return response()->json( ['ok' => true] );
        } );

        $this->assertTrue( $passed, 'Middleware should have passed the request through.' );
    }

    public function test_middleware_passes_with_bearer_authorization_header(): void
    {
        $middleware = new NabdTokenMiddleware;
        $request = Request::create( '/api/nabd/ping', 'GET' );
        $request->headers->set( 'Authorization', 'Bearer ' . $this->plainToken );

        $passed = false;
        $middleware->handle( $request, function ( $req ) use ( &$passed ) {
            $passed = true;

            return response()->json( ['ok' => true] );
        } );

        $this->assertTrue( $passed, 'Middleware should accept Bearer token in Authorization header.' );
    }

    public function test_middleware_rejects_request_with_no_token(): void
    {
        $middleware = new NabdTokenMiddleware;
        $request = Request::create( '/api/nabd/ping', 'GET' );

        $response = $middleware->handle( $request, function ( $req ) {
            return response()->json( ['ok' => true] );
        } );

        $this->assertEquals( 401, $response->getStatusCode() );
        $content = json_decode( $response->getContent(), true );
        $this->assertEquals( 'error', $content['status'] );
    }

    public function test_middleware_rejects_invalid_token(): void
    {
        $middleware = new NabdTokenMiddleware;
        $request = Request::create( '/api/nabd/ping', 'GET' );
        $request->headers->set( 'X-Nabd-Token', 'definitely-not-a-valid-token' );

        $response = $middleware->handle( $request, function ( $req ) {
            return response()->json( ['ok' => true] );
        } );

        $this->assertEquals( 401, $response->getStatusCode() );
    }

    public function test_middleware_rejects_expired_token(): void
    {
        ['plain' => $plain, 'hashed' => $hashed] = NabdApiToken::generateToken();
        NabdApiToken::create( [
            'name' => 'Expired',
            'token' => $hashed,
            'expires_at' => now()->subHour(),
        ] );

        $middleware = new NabdTokenMiddleware;
        $request = Request::create( '/api/nabd/ping', 'GET' );
        $request->headers->set( 'X-Nabd-Token', $plain );

        $response = $middleware->handle( $request, function ( $req ) {
            return response()->json( ['ok' => true] );
        } );

        $this->assertEquals( 401, $response->getStatusCode() );
        $content = json_decode( $response->getContent(), true );
        $this->assertStringContainsString( 'expired', $content['message'] );
    }

    public function test_middleware_updates_last_used_at_on_success(): void
    {
        $this->assertNull( $this->token->fresh()->last_used_at );

        $middleware = new NabdTokenMiddleware;
        $request = Request::create( '/api/nabd/ping', 'GET' );
        $request->headers->set( 'X-Nabd-Token', $this->plainToken );

        $middleware->handle( $request, function ( $req ) {
            return response()->json( ['ok' => true] );
        } );

        $this->assertNotNull( $this->token->fresh()->last_used_at );
    }

    // ── Route file structure validation ──────────────────────────────────

    public function test_module_routes_file_exists(): void
    {
        $this->assertFileExists(
            base_path( 'modules/NabdBridge/Routes/api.php' ),
            'Module Routes/api.php must exist for NexoPOS to register the routes.'
        );
    }

    public function test_module_config_xml_exists_and_is_valid(): void
    {
        $configPath = base_path( 'modules/NabdBridge/config.xml' );
        $this->assertFileExists( $configPath );

        $xml = simplexml_load_file( $configPath );
        $this->assertNotFalse( $xml );
        $this->assertEquals( 'NabdBridge', (string) $xml->namespace );
    }

    public function test_module_migration_file_exists(): void
    {
        $this->assertFileExists(
            base_path( 'modules/NabdBridge/Migrations/CreateNabdApiTokensTable.php' )
        );
    }
}
