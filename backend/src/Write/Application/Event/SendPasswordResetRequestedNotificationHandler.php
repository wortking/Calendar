<?php

declare(strict_types=1);

namespace App\Write\Application\Event;

use App\Write\Domain\Event\PasswordResetRequestedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class SendPasswordResetRequestedNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(PasswordResetRequestedEvent $event): void
    {
        $locale = $event->locale;

        $email = (new Email())
            ->from('no-reply@biciventa.com')
            ->to($event->email)
            ->subject($this->translator->trans('email.password_reset_requested.subject', [], 'messages', $locale))
            ->html(sprintf(
                '<h1>%s</h1><p>%s</p><p style="font-size: 32px; font-weight: bold; letter-spacing: 4px;">%s</p><p>%s</p>',
                $this->translator->trans('email.password_reset_requested.title', [], 'messages', $locale),
                $this->translator->trans('email.password_reset_requested.body', [], 'messages', $locale),
                $event->code,
                $this->translator->trans('email.password_reset_requested.footer', [], 'messages', $locale)
            ));

        $this->mailer->send($email);
    }
}
