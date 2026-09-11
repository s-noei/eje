<?php

namespace App\Game\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Port of include/Paypal.php (PayPal Standard buy-now + IPN validation).
 */
class Paypal
{
    protected array $fields = ['rm' => '2', 'cmd' => '_xclick'];
    public array $ipnData = [];
    public string $lastError = '';

    public function gatewayUrl(): string
    {
        return config('ejahan.paypal.sandbox') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
    }

    public function addField(string $field, mixed $value): void
    {
        $this->fields[$field] = $value;
    }

    /** HTML of an auto-submitting form (what legacy submitPayment() echoed). */
    public function paymentForm(): string
    {
        $html = '<form method="post" name="paypal_form" action="'.e($this->gatewayUrl()).'">';
        foreach ($this->fields as $name => $value) {
            $html .= '<input type="hidden" name="'.e($name).'" value="'.e((string) $value).'">';
        }
        $html .= '<center><br/><br/>If you are not automatically redirected to PayPal within 5 seconds...<br/><br/>'
            .'<input type="submit" value="Click Here"></center></form>'
            .'<script type="text/javascript">document.paypal_form.submit();</script>';

        return $html;
    }

    /** Validate an IPN callback by posting it back to PayPal. */
    public function validateIpn(array $post): bool
    {
        $this->ipnData = $post;
        try {
            $resp = Http::asForm()->timeout(15)->post($this->gatewayUrl(), ['cmd' => '_notify-validate'] + $post);
            $ok = str_contains($resp->body(), 'VERIFIED');
        } catch (\Throwable $e) {
            $ok = false;
            $this->lastError = $e->getMessage();
        }
        Log::channel('single')->info('paypal-ipn', ['valid' => $ok, 'data' => $post]);

        return $ok;
    }
}
