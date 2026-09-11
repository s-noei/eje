<?php

namespace App\Http\Controllers\Lens;

use Illuminate\Http\Request;

/** lens/include/ip/* — citizens seen from an IP (reports table, repType login). */
class IpController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null, ?int $page = 1)
    {
        if (!$this->logged() || $this->level() <= self::ACCESS_LOCAL) {
            return redirect(self::url('index'));
        }
        if ($request->filled('trackid')) {
            return redirect(self::url('ip', 'tracker', trim((string) $request->input('trackid'))));
        }
        $page = max(1, (int) $page);
        $count = 20;
        $start = ($page - 1) * $count;
        $rows = [];
        if ($id) {
            $rows = $this->database->rows('SELECT reports.*, COUNT(reports.`Desc`) AS Times, citizens.name FROM reports JOIN citizens ON citizens.CitizenID = reports.citID
                WHERE reports.`Desc` = ? GROUP BY reports.citID ORDER BY Times DESC, reports.citID', [$id]);
        }

        return $this->lensPage('lens.ip', ['id' => $id, 'type' => $type ?: 'tracker', 'page' => $page, 'start' => $start, 'count' => $count,
            'rows' => array_slice($rows, $start, $count), 'total' => count($rows)], 'IP tracker', 'ip');
    }
}
