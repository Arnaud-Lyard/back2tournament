<?php

declare(strict_types=1);

namespace App\Tests\Authentication\User\Application;

use App\Authentication\User\Application\EventSubscriber\UserCreatedEventSubscriber;
use App\Authentication\User\Domain\Entity\Email;
use App\Authentication\User\Domain\Entity\Locale;
use App\Authentication\User\Domain\Event\UserCreatedEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

final class UserCreatedEventSubscriberTest extends TestCase
{
    private const USER_ID = '11111111-1111-4111-8111-111111111111';
    private const TOKEN = '22222222-2222-4222-8222-222222222222';

    public function test_the_mail_is_written_in_the_signup_language(): void
    {
        $sent = $this->send(new Locale('en'));

        self::assertSame('emails/verify_email.en.txt.twig', $sent->getTextTemplate());
        self::assertSame('emails/verify_email.en.html.twig', $sent->getHtmlTemplate());
        self::assertSame('jane@example.com', $sent->getTo()[0]->getAddress());
        self::assertSame('no-reply@back2tournament.fr', $sent->getFrom()[0]->getAddress());
    }

    public function test_the_link_opens_the_front_verification_page(): void
    {
        $sent = $this->send(new Locale('fr'));

        self::assertSame('emails/verify_email.fr.html.twig', $sent->getHtmlTemplate());
        self::assertSame(
            ['verificationUrl' => 'https://front.example/verify-email/'.self::TOKEN],
            $sent->getContext(),
        );
    }

    private function send(Locale $locale): TemplatedEmail
    {
        $sent = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(static function (RawMessage $message) use (&$sent): void {
                $sent = $message;
            });

        $subscriber = new UserCreatedEventSubscriber($mailer, 'no-reply@back2tournament.fr', 'https://front.example/');
        $subscriber->sendVerificationEmail(
            new UserCreatedEvent(self::USER_ID, new Email('jane@example.com'), self::TOKEN, $locale)
        );

        self::assertInstanceOf(TemplatedEmail::class, $sent);

        return $sent;
    }
}
