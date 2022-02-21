<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    /**
     * @var MailerInterface $mailer
     */
    private $mailer;

    public function __construct(
        MailerInterface $mailer
    ) {
        $this->mailer = $mailer;
    }

    public function sendEmail(string $to, string $message)
    {
        $email = (new Email())
            ->from("no-reply@petrkruntorad.cz")
            ->to('petr@petrkruntorad.cz')
            ->subject('Oznámení z webu: '.$_SERVER['HTTP_HOST'])
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);
    }
}
