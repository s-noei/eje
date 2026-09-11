<?php

namespace App\Http\Controllers;

/**
 * Port of map.php (world map viewer), map-country.php (country map popup) and the
 * map-{CC}.gif / map-{id}.gif image rewrites. The GD map generator (include/mapout.php)
 * is not ported: its source layers (map-bg.gif, regions/*.gif) are absent from the snapshot,
 * so the pre-rendered include/map/*.gif files are served as-is.
 */
class MapController extends GameController
{
    public function index()
    {
        $this->lang->addPhrases('map');
        $days = range($this->database->today - 1, 493, -1);

        return $this->page('pages.map.index', ['days' => $days], [
            'title' => $this->lang->getstr('title_map', 'title'), 'bar_title' => $this->lang->getstr('map_bartitle', 'map'),
            'coltype' => 1, 'hideups' => true, 'actiontype' => 'map',
        ]);
    }

    /** map-{CC}.html: popup window with the country map. */
    public function country(string $code)
    {
        return view('pages.map.country', ['code' => strtoupper($code)]);
    }

    /** map-{CC}.gif and map-{id}.gif → include/map/countries-map/{CountryID}.gif */
    public function image(string $id)
    {
        $counID = ctype_digit($id) ? (int) $id : (int) $this->database->value('SELECT CountryID FROM country WHERE shortName = ?', [strtoupper($id)], 0);
        $file = public_path("include/map/countries-map/{$counID}.gif");
        if (!$counID || !is_file($file)) {
            abort(404);
        }

        return response()->file($file, ['Content-Type' => 'image/gif']);
    }
}
