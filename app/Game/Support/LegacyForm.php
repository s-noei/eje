<?php

namespace App\Game\Support;

use Illuminate\Support\ViewErrorBag;

/**
 * Port of the legacy Form class: gives templates $form->value('field') and
 * $form->error('field') backed by Laravel's old input / error bag.
 */
class LegacyForm
{
    protected array $errors = [];

    public int $num_errors = 0;

    public function __construct(?ViewErrorBag $bag = null)
    {
        $bag = $bag ?? session('errors');
        if ($bag instanceof ViewErrorBag) {
            foreach ($bag->getBag('default')->toArray() as $field => $msgs) {
                $this->errors[$field] = $msgs[0] ?? '';
            }
        }
        $this->num_errors = count($this->errors);
    }

    public function setValue(string $field, mixed $value): void
    {
        session()->flashInput([$field => $value] + (session()->getOldInput() ?? []));
    }

    public function setError(string $field, string $errmsg): void
    {
        $this->errors[$field] = $errmsg;
        $this->num_errors = count($this->errors);
    }

    public function value(string $field): string
    {
        $v = old($field);

        return $v === null ? '' : htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    public function error(string $field): string
    {
        return isset($this->errors[$field]) ? '<font size="2" color="#ff0000">'.$this->errors[$field].'</font>' : '';
    }

    public function getErrorArray(): array
    {
        return $this->errors;
    }
}
