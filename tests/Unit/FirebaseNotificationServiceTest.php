<?php

namespace Tests\Unit;

use App\Services\Concrete\Firebase\FirebaseNotificationService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class FirebaseNotificationServiceTest extends TestCase
{
    protected function invokeIsPermanent(string $code, string $message): bool
    {
        $service = new FirebaseNotificationService();
        $method = new ReflectionMethod(FirebaseNotificationService::class, 'isPermanentTokenError');
        $method->setAccessible(true);

        return $method->invoke($service, $code, $message);
    }

    public function test_unregistered_is_permanent(): void
    {
        $this->assertTrue($this->invokeIsPermanent('UNREGISTERED', 'Requested entity was not found.'));
    }

    public function test_unavailable_is_temporary(): void
    {
        $this->assertFalse($this->invokeIsPermanent('UNAVAILABLE', 'The service is currently unavailable.'));
    }

    public function test_invalid_argument_payload_is_not_token_error(): void
    {
        $this->assertFalse($this->invokeIsPermanent('INVALID_ARGUMENT', 'Invalid JSON payload received.'));
    }

    public function test_invalid_argument_token_is_permanent(): void
    {
        $this->assertTrue($this->invokeIsPermanent(
            'INVALID_ARGUMENT',
            'The registration token is not a valid FCM registration token'
        ));
    }

    public function test_internal_error_is_temporary(): void
    {
        $this->assertFalse($this->invokeIsPermanent('INTERNAL', 'Internal error encountered.'));
    }
}
