<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class MailerService
{
    /**
     * @var MailerInterface $mailer
     */
    private $mailer;

    private $mailerFrom;

    public function __construct(
        MailerInterface $mailer,
        string $mailerFrom
    ) {
        $this->mailer = $mailer;
        $this->mailerFrom = $mailerFrom;
    }

    /**
     * @throws Exception
     */
    public function sendNotificationEmail(string $to, string $message, string $title = null)
    {
        try {
            if($title == null)
            {
                $title = 'Měření teplot - oznámení';
            }
            $email = (new TemplatedEmail())
                ->from($this->mailerFrom)
                ->to(new Address($to))
                ->subject('Oznámení z webu: ' . $_SERVER['HTTP_HOST'])
                ->htmlTemplate('email/notifications/notification.html.twig')
                ->context([
                    'title'=>$title,
                    'message'=>$message,
                ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new Exception($e);
        }
    }

    /**
     * @throws Exception
     */
    public function sendPasswordChangeEmail(string $to, string $accountName, string $newPassword, string $title = null)
    {
        try {
            dump($this->mailerFrom);
            if($title == null)
            {
                $title = 'Změna uživatelského přístupu v aplikace Měření teplot';
            }
            $email = (new TemplatedEmail())
                ->from($this->mailerFrom)
                ->to(new Address($to))
                ->subject('Oznámení z webu: ' . $_SERVER['HTTP_HOST'])
                ->htmlTemplate('email/security/password-change.html.twig')
                ->context([
                    'title'=>$title,
                    'accountName'=>$accountName,
                    'newPassword'=>$newPassword
                ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new Exception($e);
        }
    }

    public function sendNewAccountEmail(string $to, string $accountName, string $password, string $title = null)
    {
        try {
            if($title == null) {
                $title = 'Vytvoření uživatelského přístupu do aplikace Měření teplot';
            }
            $email = (new TemplatedEmail())
                ->from($this->mailerFrom)
                ->to($to)
                ->subject('Oznámení z webu: '.$_SERVER['HTTP_HOST'])
                ->htmlTemplate('email/security/new-account.html.twig')
                ->context([
                    'title'=>$title,
                    'accountName'=>$accountName,
                    'password'=>$password
                ]);
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            throw new Exception($e);
        }
    }
}
