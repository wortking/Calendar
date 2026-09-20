<?php

declare(strict_types=1);

namespace App\Write\Application\Event;

use App\Write\Domain\Event\EmailVerificationRequestedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class SendEmailVerificationNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
        #[Autowire(env: 'FRONTEND_URL')]
        private string $frontendUrl
    ) {}

    public function __invoke(EmailVerificationRequestedEvent $event): void
    {
        $locale = $event->locale;
        $verificationLink = sprintf('%s/verify-email?token=%s', rtrim($this->frontendUrl, '/'), $event->verificationToken);

        $temporaryPasswordHtml = null !== $event->temporaryPassword
            ? sprintf(
                '<p>%s</p>',
                $this->translator->trans('email.email_verification_requested.temporary_password', ['%password%' => $event->temporaryPassword], 'messages', $locale)
            )
            : '';

        $email = (new Email())
            ->from('no-reply@biciventa.com')
            ->to($event->email)
            ->subject($this->translator->trans('email.email_verification_requested.subject', [], 'messages', $locale))
            ->html(sprintf(
                '<h1>%s</h1><p>%s</p>%s<p><a href="%s">%s</a></p><p>%s</p>',
                $this->translator->trans('email.email_verification_requested.title', [], 'messages', $locale),
                $this->translator->trans('email.email_verification_requested.body', [], 'messages', $locale),
                $temporaryPasswordHtml,
                $verificationLink,
                $this->translator->trans('email.email_verification_requested.cta', [], 'messages', $locale),
                $this->translator->trans('email.email_verification_requested.footer', [], 'messages', $locale)
            ));

        $this->mailer->send($email);
    }
}
