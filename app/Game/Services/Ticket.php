<?php

namespace App\Game\Services;

/**
 * Port of include/ticket.php ($support).
 */
class Ticket
{
    public function __construct(protected GameDatabase $database)
    {
    }

    public function createTicket(int|string $citID, string $name, string $email, string $reason, int $priority, string $subject, string $msg, int $country = 0, ?string $proof = null): int
    {
        $time = time();
        $tID = $this->database->insertGetId('INSERT INTO ticket_head (by_id, by_name, by_email, reason, country, priority, subject, proof, started_time, last_reply) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$citID, substr($name, 0, 20), substr($email, 0, 30), substr($reason, 0, 15), $country, $priority, mb_substr($subject, 0, 100), $proof, $time, $time]);
        $this->database->exec('INSERT INTO ticket_posts (ticket_id, by_id, by_name, body, proof, timestamp) VALUES (?, ?, ?, ?, ?, ?)',
            [$tID, $citID, substr($name, 0, 30), $msg, $proof, $time]);

        return $tID;
    }
}
