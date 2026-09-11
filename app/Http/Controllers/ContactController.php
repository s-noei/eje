<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Port of contact.php + include/tickets/{index,new,new/*,ticketproc,track,view}.php.
 * URLs: contact.html, contact-{go}.html, contact-{go}-{id}.html (go: new|track|view).
 */
class ContactController extends GameController
{
    public const STATS = ['<font color="red">Pending</font>', '<font color="brown">Waiting for your reply</font>', '<font color="blue">Closed</font>'];
    public const PRIORS = ['<font color="gray">Low</font>', '<font color="green">Medium</font>', '<font color="orange">High</font>', '<font color="red">Critical</font>'];
    public const SECTIONS = ['bugreport' => 'Report a bug', 'multi' => 'Report multiple accounts', 'abuse' => 'Report abuse', 'modreport' => 'Report a moderator',
        'supgame' => 'Game support', 'payment' => 'Payment issues', 'support' => 'Support eJahan', 'feedback' => 'Feedback'];
    public const SUBJECTS = [
        'multi' => ['pp' => 'Multiple accounts in PP Elections', 'cp' => 'Multiple accounts in CP Elections', 'cg' => 'Multiple accounts in Congress Elections', 'eco' => 'Multiple accounts for economical purposes'],
        'abuse' => ['spam' => 'Spamming report', 'insult' => 'Insult report', 'downvote' => 'Illegal downvote report', 'talatrade' => 'Trade Tala report'],
        'supgame' => ['lostacc' => 'Lost account', 'lostprop' => 'Lost properties', 'trophies' => 'Problem in my trophies', 'deleteacc' => 'Delete my account'],
        'payment' => ['paypal' => 'Paypal payment problem'],
        'support' => ['ads' => 'Put my advertisement', 'mod' => 'Moderation request'],
    ];

    public function index(Request $request, ?string $go = null, ?int $id = null)
    {
        $common = ['stats' => self::STATS, 'priors' => self::PRIORS, 'sections' => self::SECTIONS, 'errors' => []];
        $layout = ['title' => $this->lang->getstr('title_contact', 'title'), 'bar_title' => 'Contact eJahan team', 'actiontype' => 'contact'];
        $logged = $this->loggedIn();
        $citInfo = $this->citInfo;
        $db = $this->database;

        if ($go === 'new') {
            if (!$logged) {
                return redirect('/index.html');
            }
            if ($request->input('subnewticket')) {
                $section = (string) $request->input('section');
                $subject = (string) $request->input('subject');
                $priority = (int) $request->input('priority');
                $errors = [];
                if ($request->input('token') !== md5($section.$subject.$citInfo['CitizenID'].'Key4 TiCkEt') || $priority < 0 || $priority > 3) {
                    $errors[] = 'Cheating detected';
                }
                $proof = $request->file('proof');
                if ($proof && !($proof->isValid() && in_array($proof->getMimeType(), ['image/jpeg', 'image/pjpeg']) && $proof->getSize() < 1024 * 1024)) {
                    $errors[] = 'The file you specified is not correct.';
                }
                if ($errors) {
                    return $this->page('pages.contact.new-form', $common + ['errors' => $errors, 'section' => $section, 'subject' => $subject], $layout);
                }
                $fName = '';
                $tID = app(\App\Game\Services\Ticket::class)->createTicket($citInfo['CitizenID'], $citInfo['name'], $citInfo['email'] ?? '', $section, $priority, $subject,
                    (string) $request->input('desc'), (int) $citInfo['CountryID'], '');
                if ($proof) {
                    $fName = md5($tID.'PrOoF'.random_int(0, 70000)).'.jpg';
                    $proof->move(public_path('uploads/tickets'), $fName);
                    $fName = url('/uploads/tickets/'.$fName);
                    $db->exec('UPDATE ticket_posts SET proof = ? WHERE ticket_id = ?', [$fName, $tID]);
                }
                $db->exec('UPDATE ticket_head SET last_replier = ? WHERE ticket_id = ?', [$citInfo['name'], $tID]);
                $layout['bar_title'] .= ' - Ticket submitted';

                return $this->page('pages.contact.submitted', $common + ['tID' => $tID], $layout);
            }
            $section = (string) $request->input('section', '');
            if ($section && isset(self::SECTIONS[$section])) {
                $subject = trim((string) $request->input('sub-custom', ''));
                if ($subject === '') {
                    $subject = self::SUBJECTS[$section][$request->input('subject')] ?? '';
                }
                if ($subject !== '') {
                    return $this->page('pages.contact.new-form', $common + ['section' => $section, 'subject' => $subject], $layout);
                }
            }

            return $this->page('pages.contact.new', $common, $layout);
        }

        if ($go === 'track') {
            if (!$logged) {
                return redirect('/index.html');
            }
            $layout['bar_title'] = 'Track my tickets';

            return $this->page('pages.contact.track', $common + ['tickets' => $db->rows('SELECT * FROM ticket_head WHERE by_id = ? ORDER BY ticket_id DESC', [$citInfo['CitizenID']])], $layout);
        }

        if ($go === 'view') {
            if (!$logged) {
                return redirect('/index.html');
            }
            $tInfo = $db->row('SELECT * FROM ticket_head WHERE ticket_id = ? AND by_id = ?', [(int) $id, $citInfo['CitizenID']]);
            if (!$tInfo) {
                return redirect('/index.html');
            }
            $errors = [];
            if ($request->input('subclose')) {
                $db->exec("UPDATE ticket_head SET status = '2' WHERE ticket_id = ?", [$tInfo['ticket_id']]);

                return redirect($this->vars->getURL('contact', 'track'));
            }
            if ($request->filled('postID')) {
                $postID = (int) $request->input('postID');
                $rate = (int) $request->input('rate');
                if ($request->input('token') === md5($postID.'TiCK3T') && $rate > 0 && $rate < 6) {
                    $db->exec('UPDATE ticket_posts SET rate = ? WHERE post_id = ? AND ticket_id = ?', [$rate, $postID, $tInfo['ticket_id']]);
                }

                return redirect($request->getRequestUri());
            }
            if ($request->input('subreply')) {
                $proof = $request->file('proof');
                if ($proof && !($proof->isValid() && in_array($proof->getMimeType(), ['image/jpeg', 'image/pjpeg']) && $proof->getSize() < 1024 * 1024)) {
                    $errors[] = 'The file you specified is not correct.';
                } else {
                    $fName = '';
                    if ($proof) {
                        $fName = md5($tInfo['ticket_id'].'PrOoF'.random_int(0, 70000)).'.jpg';
                        $proof->move(public_path('uploads/tickets'), $fName);
                        $fName = url('/uploads/tickets/'.$fName);
                    }
                    $time = time();
                    $db->exec('INSERT INTO ticket_posts (ticket_id, by_id, by_name, body, proof, timestamp) VALUES (?, ?, ?, ?, ?, ?)',
                        [$tInfo['ticket_id'], $citInfo['CitizenID'], $citInfo['name'], (string) $request->input('message'), $fName, $time]);
                    $db->exec("UPDATE ticket_head SET status = '0', last_reply = ?, last_replier = ? WHERE ticket_id = ?", [$time, $citInfo['name'], $tInfo['ticket_id']]);

                    return redirect($request->getRequestUri());
                }
            }
            $layout['bar_title'] = 'View ticket';

            return $this->page('pages.contact.view', $common + ['errors' => $errors, 'tInfo' => $tInfo,
                'posts' => $db->rows('SELECT * FROM ticket_posts WHERE ticket_id = ? ORDER BY timestamp', [$tInfo['ticket_id']])], $layout);
        }

        return $this->page('pages.contact.index', $common, $layout);
    }
}
