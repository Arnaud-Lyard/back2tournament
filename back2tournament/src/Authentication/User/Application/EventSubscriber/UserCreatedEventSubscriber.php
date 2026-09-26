<?php

declare(strict_types=1);

namespace App\Authentication\User\Application\EventSubscriber;

use App\Authentication\User\Domain\Event\UserCreatedEvent;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;

final class UserCreatedEventSubscriber implements EventSubscriberInterface
{
    private MailerInterface $mailer;
    private string $fromAddress;
    private string $frontUrl;

    public function __construct(
        MailerInterface $mailer,
        #[Autowire(env: 'MAILER_FROM_ADDRESS')] string $fromAddress,
        #[Autowire(env: 'FRONT_URL')] string $frontUrl,
    ) {
        $this->mailer = $mailer;
        $this->fromAddress = $fromAddress;
        $this->frontUrl = $frontUrl;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserCreatedEvent::class => 'sendVerificationEmail',
        ];
    }

    public function sendVerificationEmail(UserCreatedEvent $event): void
    {
        $verificationUrl = \sprintf(
            '%s/verify-email/%s',
            rtrim($this->frontUrl, '/'),
            rawurlencode($event->getVerificationToken())
        );

        $template = \sprintf('emails/verify_email.%s', $event->getLocale()->getValue());

        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($event->getEmail()->getValue())
            ->textTemplate($template.'.txt.twig')
            ->htmlTemplate($template.'.html.twig')
            ->context(['verificationUrl' => $verificationUrl]);

        $this->mailer->send($email);
    }
}
