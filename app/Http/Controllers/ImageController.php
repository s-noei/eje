<?php

namespace App\Http\Controllers;

use App\Game\Services\GameDatabase;
use App\Game\Support\Vars;

/**
 * Port of include/emblem.php (emblem-{id}.gif) and include/licimg.php (license-back-{id}-{c}.gif): GD-rendered images.
 */
class ImageController extends Controller
{
    public function __construct(protected GameDatabase $database, protected Vars $vars)
    {
    }

    private function font(string $name): string
    {
        return resource_path("fonts/$name.ttf");
    }

    private function gif($img)
    {
        ob_start();
        imagegif($img);
        $data = ob_get_clean();
        imagedestroy($img);

        return response($data, 200)->header('Content-Type', 'image/gif')->header('Cache-Control', 'public, max-age=3600');
    }

    private function loadFlag(string $flag)
    {
        $path = public_path('images/flags/l/'.$flag.'.gif');
        if (!is_file($path)) {
            return null;
        }

        return @imagecreatefromgif($path) ?: null;
    }

    public function emblem(int $id)
    {
        $cit = $this->database->getUserInfoFromID($id);
        if (!$cit) {
            abort(404);
        }
        $img = imagecreatefrompng(public_path('images/emblem/bg1.png'));
        if ($flag = $this->loadFlag((string) $cit['Flag'])) {
            if (imagesx($flag) == 64) {
                imagecopyresized($img, $flag, 5, -2, 0, 0, 48, 48, 64, 64);
            } else {
                imagecopymerge($img, $flag, 5, -2, 0, 0, 48, 48, 100);
            }
            imagedestroy($flag);
        }
        $title = imagecolorallocate($img, 150, 0, 0);
        $text = imagecolorallocate($img, 0, 0, 0);
        $url = imagecolorallocate($img, 100, 100, 100);
        imagettftext($img, 20, 0, 55, 22, $title, $this->font('comic'), (string) $cit['name']);
        imagettftext($img, 10, 0, 60, 40, $text, $this->font('tahoma'), "Citizen of eJahan, {$cit['cName']}");
        imagettftext($img, 7, -20, 225, 11, $url, $this->font('tahoma'), 'www.ejahan.org');

        return $this->gif($img);
    }

    public function licenseBack(int $id, int $c)
    {
        $comp = $this->database->getCompany($id);
        $flagPath = $this->database->getCountryFlagC($c, public_path('images/flags/l/'));
        $counN = $this->database->getCountryC($c);
        if (!$comp || !$flagPath || !$counN) {
            abort(404);
        }
        $img = imagecreatefrompng(public_path('images/license-box.png'));
        $text = imagecolorallocate($img, 0, 0, 0);
        $url = imagecolorallocate($img, 190, 190, 190);
        imagettftext($img, 12, 0, 10, 42, $text, $this->font('comic'), $this->vars->getShortText($comp['Name'], 25));
        imagettftext($img, 14, 0, 10, 22, $url, $this->font('tahoma'), 'www.ejahan.com');
        if (is_file($flagPath) && ($flag = @imagecreatefromgif($flagPath))) {
            imagecopyresized($img, $flag, 20, 60, 0, 0, 48, 48, imagesx($flag), imagesy($flag));
            imagedestroy($flag);
        }
        $comic = $this->font('comic');
        imagettftext($img, 8, 0, 10, 62, $text, $comic, 'License for:');
        imagettftext($img, 10, 0, 70, 88, $text, $comic, (string) $counN);
        foreach ([[131, 'Status:'], [151, 'Products:'], [171, 'Price:'], [191, '+ Taxes:']] as [$y, $t]) {
            imagettftext($img, 9, 0, 10, $y, $text, $comic, $t);
        }

        return $this->gif($img);
    }
}
