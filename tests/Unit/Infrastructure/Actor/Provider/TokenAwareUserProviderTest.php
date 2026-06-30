<?php

declare(strict_types=1);

namespace Yammi\AuditLog\Tests\Unit\Infrastructure\Actor\Provider;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Auth\User as BaseUser;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yammi\AuditLog\Domain\Audit\Enum\ActorType;
use Yammi\AuditLog\Domain\Audit\ValueObject\Actor;
use Yammi\AuditLog\Infrastructure\Actor\Provider\TokenAwareUserProvider;
use Yammi\AuditLog\Tests\Support\FixedActorProvider;

final class TokenAwareUserProviderTest extends TestCase
{
    public function test_it_appends_a_sanctum_token_name_to_the_user_label(): void
    {
        $user = new class extends BaseUser
        {
            public function currentAccessToken(): object
            {
                return (object) ['name' => 'mobile-app'];
            }
        };

        $provider = $this->provider(new FixedActorProvider(Actor::user('5', 'Jane Doe')), $user);

        $this->assertSame('Jane Doe via mobile-app', $provider->resolve()?->displayLabel());
    }

    public function test_it_appends_a_passport_client_name_to_the_user_label(): void
    {
        $user = new class extends BaseUser
        {
            public function token(): object
            {
                return (object) ['client' => (object) ['name' => 'partner-portal']];
            }
        };

        $provider = $this->provider(new FixedActorProvider(Actor::user('5', 'Jane Doe')), $user);

        $this->assertSame('Jane Doe via partner-portal', $provider->resolve()?->displayLabel());
    }

    public function test_a_user_without_a_token_keeps_its_label(): void
    {
        $provider = $this->provider(new FixedActorProvider(Actor::user('5', 'Jane Doe')), new BaseUser);

        $this->assertSame('Jane Doe', $provider->resolve()?->displayLabel());
    }

    public function test_a_user_actor_with_no_authenticated_user_keeps_its_label(): void
    {
        $provider = $this->provider(new FixedActorProvider(Actor::user('5', 'Jane Doe')), null);

        $this->assertSame('Jane Doe', $provider->resolve()?->displayLabel());
    }

    public function test_a_non_user_actor_is_passed_through_untouched(): void
    {
        $user = new class extends BaseUser
        {
            public function currentAccessToken(): object
            {
                return (object) ['name' => 'mobile-app'];
            }
        };

        $provider = $this->provider(new FixedActorProvider(Actor::job('App\\Jobs\\ProcessOrder')), $user);

        $this->assertSame(ActorType::Job, $provider->resolve()?->type);
    }

    public function test_a_null_actor_stays_null(): void
    {
        $provider = $this->provider(new FixedActorProvider(null), null);

        $this->assertNull($provider->resolve());
    }

    public function test_it_is_fail_soft_when_resolving_the_token_throws(): void
    {
        $guard = $this->createStub(Guard::class);
        $guard->method('user')->willThrowException(new RuntimeException('auth boom'));

        $auth = $this->createStub(AuthFactory::class);
        $auth->method('guard')->willReturn($guard);

        $provider = new TokenAwareUserProvider(new FixedActorProvider(Actor::user('5', 'Jane Doe')), $auth);

        $this->assertSame('Jane Doe', $provider->resolve()?->displayLabel());
    }

    private function provider(FixedActorProvider $inner, ?Authenticatable $user): TokenAwareUserProvider
    {
        $guard = $this->createStub(Guard::class);
        $guard->method('user')->willReturn($user);

        $auth = $this->createStub(AuthFactory::class);
        $auth->method('guard')->willReturn($guard);

        return new TokenAwareUserProvider($inner, $auth);
    }
}
