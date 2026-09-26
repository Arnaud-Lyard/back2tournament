<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Domain;

use App\Authentication\User\Domain\Entity\Email;
use App\Authentication\User\Domain\Entity\Locale;
use App\Authentication\User\Domain\Entity\Password;
use App\Authentication\User\Domain\Entity\User;
use App\Authentication\User\Domain\Entity\Username;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

final class UserSerializationTest extends KernelTestCase
{
    private function serializedUser(): array
    {
        self::bootKernel();

        /** @var SerializerInterface $serializer */
        $serializer = static::getContainer()->get('serializer');

        $user = User::registerUser(
            new Email('serialize@back2tournament.fr'),
            new Username('serialize_me'),
            ['ROLE_USER'],
            new Password('Password123!'),
            new Locale('fr'),
        );
        $user->setPassword('$2y$13$fakehashthatmustneverleak');

        return json_decode($serializer->serialize($user, 'json'), true, 512, JSON_THROW_ON_ERROR);
    }

    public function test_password_is_never_serialized(): void
    {
        $payload = $this->serializedUser();

        self::assertArrayNotHasKey('password', $payload);
        self::assertStringNotContainsString('fakehashthatmustneverleak', json_encode($payload));
    }

    public function test_verification_token_is_never_serialized(): void
    {
        $payload = $this->serializedUser();

        self::assertArrayNotHasKey('verificationToken', $payload);
    }

    public function test_still_exposes_the_fields_the_client_needs(): void
    {
        $payload = $this->serializedUser();

        self::assertSame('serialize@back2tournament.fr', $payload['email']);
        self::assertSame('serialize_me', $payload['username']);
        self::assertFalse($payload['verified']);
        self::assertNotEmpty($payload['id']);
        self::assertContains('ROLE_USER', $payload['roles']);
    }

    public function test_exposes_no_extra_field(): void
    {
        self::assertSame(
            ['id', 'email', 'username', 'roles', 'verified'],
            array_keys($this->serializedUser())
        );
    }
}
