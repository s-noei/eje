<?php

namespace App\Http\Controllers\Lens;

use App\Game\Services\MultiHunter;
use Illuminate\Http\Request;

/** lens/include/multi/* */
class MultiController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null, ?int $page = null)
    {
        if (!$this->logged() || !$this->mod['at_multrack'] || $this->level() <= self::ACCESS_LOCAL) {
            return redirect(self::url('index'));
        }
        if ($request->filled('trackid')) {
            return redirect(self::url('multi', 'track', (int) $request->input('trackid'), (int) $request->input('lowcase', 75)));
        }
        $lowcase = $page ?: 75;
        $data = ['id' => (int) $id, 'lowcase' => $lowcase, 'detail' => $this->mod['at_multrack'] >= 2, 'hunter' => null];
        if ($id) {
            $data['hunter'] = new MultiHunter($this->database, (int) $id);
        }

        return $this->lensPage('lens.multi', $data, 'Multi tracker', 'multi');
    }
}
