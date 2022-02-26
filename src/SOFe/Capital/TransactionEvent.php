<?php

declare(strict_types=1);

namespace SOFe\Capital;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\Event;
use pocketmine\player\Player;

final class TransactionEvent extends Event implements Cancellable {
    use CancellableTrait;

    /**
     * @param array<string, string> $labels
     * @param list<Player> $involvedPlayers
     */
    public function __construct(
        private AccountRef $src,
        private AccountRef $dest,
        private int $amount,
        private array $labels,
        private array $involvedPlayers,
    ) {
    }

    public function getSrc() : AccountRef {
        return $this->src;
    }

    public function getDest() : AccountRef {
        return $this->dest;
    }

    public function getAmount() : int {
        return $this->amount;
    }

    public function setAmount(int $amount) : void {
        $this->amount = $amount;
    }

    /**
     * @return array<string, string>
     */
    public function getLabels() : array {
        return $this->labels;
    }

    /**
     * @param array<string, string> $labels
     */
    public function setLabels(array $labels) : void {
        $this->labels = $labels;
    }

    /**
     * Returns the non-exhaustive list of players probably related to this transaction.
     * Used for hinting early account refresh.
     *
     * @return list<Player>
     */
    public function getInvolvedPlayers() : array {
        return $this->involvedPlayers;
    }
}
