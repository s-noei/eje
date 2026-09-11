<?php

namespace App\Http\Controllers\Lens;

class HomeController extends LensController
{
    public function index()
    {
        if (!$this->logged()) {
            return $this->lensPage('lens.login', [], 'Login');
        }

        return $this->lensPage('lens.home', [], 'Home', 'index');
    }

    public function about()
    {
        if (!$this->logged()) {
            return redirect(self::url('index'));
        }

        return $this->lensPage('lens.about', [], 'About Lens', 'about');
    }
}
