<?php

namespace App\Game\Services;

/**
 * Port of include/chat.php ($chat): the in-game chat box.
 */
class Chat
{
    public function __construct(protected GameDatabase $database)
    {
    }

    public function getChat(int|string $id = '', int $num = 0): array
    {
        $base = "SELECT * FROM chat_messages WHERE priority = '1'";
        if ($num) {
            return $this->database->rows($base.' ORDER BY timestamp DESC, chatID DESC LIMIT '.(int) $num);
        }
        if ($id) {
            return $this->database->rows($base.' AND chatID > ? ORDER BY timestamp DESC, chatID DESC', [$id]);
        }

        return $this->database->rows($base.' ORDER BY timestamp DESC, chatID DESC LIMIT 20');
    }

    public function getAnnounce(): ?array
    {
        return $this->database->row("SELECT * FROM chat_messages WHERE priority = '9' ORDER BY timestamp DESC, chatID DESC LIMIT 1");
    }

    public function addMessage(array $cit, string $message, int $priority = 1): void
    {
        $this->database->exec('INSERT INTO chat_messages (citID, name, Avatar, message, priority, timestamp) VALUES (?, ?, ?, ?, ?, ?)',
            [$cit['CitizenID'], $cit['name'], $cit['Avatar'], $message, $priority, time()]);
    }
}
