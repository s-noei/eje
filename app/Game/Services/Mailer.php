<?php

namespace App\Game\Services;

use Illuminate\Support\Facades\Mail;

/**
 * Port of include/mailer.php on top of Laravel Mail (replaces PHPMailer).
 */
class Mailer
{
    protected function send(string $to, string $subject, string $body, ?string $fromName = null): bool
    {
        try {
            Mail::html(nl2br($body), function ($m) use ($to, $subject, $fromName) {
                $m->to($to)->subject($subject)->from(config('ejahan.email.from_addr'), $fromName ?: config('ejahan.email.from_name'));
            });

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    protected function logo(): string
    {
        return "<img src='".rtrim(config('app.url'), '/')."/images/logo.gif' alt='eJahan - online social game'>\n";
    }

    public function sendActivation(string $user, string $email, string $link): bool
    {
        $body = $this->logo()."Dear {$user}!\n\nWelcome! You've just registered at eJahan with username {$user}.\n\n"
            ."To start playing in eJahan you need to activate your account.\nTo do so, please click on the link below or copy and paste it in your browser to activate your account:\n "
            .rtrim(config('app.url'), '/')."/activate-{$link}.html\n\nRegards,\neJahan Team,\n".config('app.url')."\n";

        return $this->send($email, "eJahan Activation for {$user}", $body, 'Ejahan Registration');
    }

    public function sendInvite(string $ref_name, string $invID, string $email, string $message): bool
    {
        $body = $this->logo()."Dear {$email}!\n\nWe invite you to join us in a new community!\n\n"
            ."Think! You can be a company manager, a congress member or even a president of your country in this virtual world!\n"
            ."We'll be so happy to see you in eJahan! This invitation is sended to you by <b>{$ref_name}</b>, who is a member of the game \n "
            ."To register in eJahan <b>FREE</b>, click on the link below, or copy and paste it in your browser \n "
            .rtrim(config('app.url'), '/')."/register-{$invID}.html\n\nRegards,\neJahan Team,\n".config('app.url')."\n\n<b>NOTE: This invite is valid for a week after sending.</b>\n";

        return $this->send($email, "eJahan Invitation for {$email}", $body);
    }

    public function sendWelcome(string $user, string $email, ?string $pass): bool
    {
        $body = $this->logo()."Dear {$user}!\n\nWelcome! You've just registered at eJahan and started to build the BEST community in this virtual world!\n"
            ."Your login information is:\n\nUsername: {$user}\n";
        if ($pass) {
            $body .= "Password: {$pass}\n";
        }
        $body .= "\nIf you ever lose or forget your password, a new password will be generated for you and sent to this email address, if you would like to change your "
            ."email address you can do so by going to your profile page after signing in.\n\nRegards,\neJahan Team,\n".config('app.url')."\n";

        return $this->send($email, "eJahan registration - {$user}!", $body);
    }

    public function sendNewPass(string $user, string $email, string $pass): bool
    {
        $body = "{$user},\n\nWe've generated a new password for you at your request, you can use this new password with your username to log in to eJahan.\n\n"
            ."Username: {$user}\nNew Password: {$pass}\n\nIt is recommended that you change your password to something that is easier to remember, which "
            ."can be done by going to the profile page after signing in.\n\nRegards,\neJahan Team,\n".config('app.url')."\n";

        return $this->send($email, 'eJahan - Your new password', $body, 'eJahan - Password recover service');
    }

    public function sendContact(string $name, string $email, string $reason, string $message): bool
    {
        try {
            Mail::html(nl2br($message), function ($m) use ($name, $email, $reason) {
                $m->to(config('ejahan.email.contact_addr'))->subject("Message from {$name} around {$reason}")->replyTo($email, $name);
            });

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }
}
