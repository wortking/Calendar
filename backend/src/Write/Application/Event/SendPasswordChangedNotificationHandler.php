<?php

declare(strict_types=1);

namespace App\Write\Application\Event;

use App\Write\Domain\Event\PasswordChangedEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class SendPasswordChangedNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private TranslatorInterface $translator
    ) {}

    public function __invoke(PasswordChangedEvent $event): void
    {
        $locale = $event->locale;

        $email = (new Email())
            ->from('no-reply@biciventa.com')
            ->to($event->email)
            ->subject($this->translator->trans('email.password_changed.subject', [], 'messages', $locale))
            ->html(sprintf(
                '<h1>%s</h1><p>%s</p><p>%s</p>',
                $this->translator->trans('email.password_changed.title', [], 'messages', $locale),
                $this->translator->trans('email.password_changed.body', [], 'messages', $locale),
                $this->translator->trans('email.password_changed.warning', [], 'messages', $locale)
            ));

        $this->mailer->send($email);
    }
}
