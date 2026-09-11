<?php

namespace App\Http\Controllers\Lens;

use Illuminate\Http\Request;

/** lens/include/company/* */
class CompanyController extends LensController
{
    public function index(Request $request, ?string $type = null, ?string $id = null)
    {
        if (!$this->logged() || !$this->mod['at_company']) {
            return redirect(self::url('index'));
        }
        $db = $this->database;
        $links = [['Stats', self::url('company')]];
        if ($this->mod['at_company'] >= 2) {
            $links[] = ['Tracker', self::url('company', 'tracker')];
            $links[] = ['Sales history', self::url('company', 'sales')];
        }
        if ($this->mod['at_company'] >= 3) {
            $links[] = ['Actions', self::url('company', 'actions')];
        }
        $base = ['links' => $links, 'type' => $type, 'id' => $id];
        $loadComp = fn () => $id ? $db->row('SELECT company.*, citizens.name AS ManagerName, region.rName AS RegionName, country.cName AS CountryName FROM company
            LEFT JOIN citizens ON citizens.CitizenID = company.ManagerID JOIN region ON company.RegionID = region.RegionID
            JOIN country ON region.CountryID = country.CountryID WHERE company.CompanyID = ?', [(int) $id]) : null;

        if (in_array($type, ['tracker', 'sales'], true) && $this->mod['at_company'] >= 2) {
            if ($request->filled('trackid')) {
                return redirect(self::url('company', $type, (int) $request->input('trackid')));
            }
            $comp = $loadComp();
            $data = ['comp' => $comp];
            if ($comp && $type === 'sales') {
                $data['sales'] = $db->rows('SELECT * FROM market_sells WHERE companyID = ? ORDER BY soldID DESC', [$comp['CompanyID']]);
            } elseif ($comp) {
                if ($request->input('submanager') && $this->mod['at_company'] == 3) {
                    $db->exec('UPDATE company SET ManagerID = ? WHERE CompanyID = ?', [(int) $request->input('manager'), $comp['CompanyID']]);

                    return redirect($request->getRequestUri());
                }
                $cid = $comp['CompanyID'];
                $data['money'] = $this->mod['at_viewmoney'] ? $db->rows('SELECT company_money.Amount, country.curName FROM company_money JOIN country ON country.curID = company_money.CurID WHERE CompID = ?', [$cid]) : null;
                $like1 = "%finance-$cid%";
                $like2 = "%company-$cid-finance%";
                $data['collectors'] = $db->rows("SELECT CitizenID, name FROM transactions JOIN citizens ON transactions.toID = citizens.CitizenID
                    WHERE (fromID = ? AND fromType LIKE 'company') AND (Page LIKE ? OR Page LIKE ?) AND toType = 'citizen' GROUP BY toID ORDER BY transactions.timestamp DESC", [$cid, $like1, $like2]);
                $data['investors'] = $db->rows("SELECT CitizenID, name FROM transactions JOIN citizens ON transactions.fromID = citizens.CitizenID
                    WHERE (toID = ? AND toType LIKE 'company') AND (Page LIKE ? OR Page LIKE ?) AND fromType = 'citizen' GROUP BY fromID ORDER BY transactions.timestamp DESC", [$cid, $like1, $like2]);
            }

            return $this->lensPage("lens.company.$type", $base + $data, 'Company tools', 'company');
        }

        if ($type === 'actions' && $this->mod['at_company'] >= 3) {
            if (!$this->canDoActions()) {
                return redirect(self::url('company'));
            }
            if ($request->filled('targetid')) {
                return redirect(self::url('company', 'actions', (int) $request->input('targetid')));
            }
            $comp = $loadComp();
            if ($comp && $request->isMethod('post')) {
                $cid = $comp['CompanyID'];
                $back = fn () => redirect($request->getRequestUri());
                if ($request->input('submanager')) {
                    $db->exec('UPDATE company SET ManagerID = ? WHERE CompanyID = ?', [(int) $request->input('manager'), $cid]);

                    return $back();
                }
                if ($request->input('subname')) {
                    $db->exec('UPDATE company SET Name = ? WHERE CompanyID = ?', [strip_tags((string) $request->input('name')), $cid]);

                    return $back();
                }
                if ($request->input('substock')) {
                    $db->exec('UPDATE company SET Stock = ? WHERE CompanyID = ?', [(float) $request->input('stock'), $cid]);

                    return $back();
                }
                if ($request->input('subloc')) {
                    $db->exec('UPDATE company SET RegionID = ? WHERE CompanyID = ?', [(int) $request->input('location'), $cid]);

                    return $back();
                }
                if ($request->input('subremavatar')) {
                    $db->exec("UPDATE company SET Avatar = 'no-avatar.gif' WHERE CompanyID = ?", [$cid]);

                    return $back();
                }
                if ($request->input('subremmsg')) {
                    $db->exec("UPDATE company SET company_message = '' WHERE CompanyID = ?", [$cid]);

                    return $back();
                }
            }

            return $this->lensPage('lens.company.actions', $base + ['comp' => $comp], 'Company tools', 'company');
        }

        $stats = [
            ['Total companies', $db->count('SELECT CompanyID FROM company')],
            ['Suspended companies<br>', $db->count('SELECT CompanyID FROM company WHERE ManagerID = 0')],
        ];

        return $this->lensPage('lens.company.home', $base + ['stats' => $stats], 'Company tools', 'company');
    }
}
