<?php

declare(strict_types=1);

namespace SOFe\Capital\Analytics;

use AssertionError;
use Generator;
use pocketmine\player\Player;
use SOFe\Capital\AccountQueryMetric;
use SOFe\Capital\Config\ConfigInterface;
use SOFe\Capital\Config\ConfigTrait;
use SOFe\Capital\Config\DynamicCommand;
use SOFe\Capital\Config\Parser;
use SOFe\Capital\Config\Raw;
use SOFe\Capital\Di\Context;
use SOFe\Capital\Di\FromContext;
use SOFe\Capital\Di\Singleton;
use SOFe\Capital\Di\SingletonArgs;
use SOFe\Capital\Di\SingletonTrait;
use SOFe\Capital\ParameterizedLabelSelector;
use SOFe\Capital\Schema;
use SOFe\Capital\TransactionQueryMetric;
use SOFe\InfoAPI\PlayerInfo;


final class Config implements Singleton, FromContext, ConfigInterface {
    use SingletonArgs, SingletonTrait, ConfigTrait;

    /**
     * @param array<string, SingleQuery<Player>> $singleQueries
     * @param list<ConfigTop> $topQueries
     */
    public function __construct(
        public array $singleQueries,
        public array $topQueries,
    ) {
    }

    public static function parse(Parser $config, Context $di, Raw $raw) : Generator {
        /** @var Schema\Config $schemaConfig */
        $schemaConfig = yield from $raw->awaitConfigInternal(Schema\Config::class);
        $schema = $schemaConfig->schema;

        $analytics = $config->enter("analytics", <<<'EOT'
            Settings related to statistics display.
            EOT);

        $playerInfosConfig = $analytics->enter("player-infos", <<<'EOT'
            The InfoAPI infos for a player.
            An info is a number related to the wealth or activities of a player,
            e.g. the total amount of money, amount of money in a specific currency,
            average spending per day, total amount of money earned from a specific source, etc.

            After setting up infos, you can use them in the info-commands section below.
            EOT, $isNew);

        if ($isNew) {
            $playerInfosConfig->enter("money", "This is an example info that displays the total money of a player.");
        }

        $singleQueries = [];

        foreach ($playerInfosConfig->getKeys() as $key) {
            $infoConfig = $playerInfosConfig->enter($key, null);
            $singleQueries[$key] = self::parseSingleQuery($infoConfig, $schema);
        }

        $topPlayersConfig = $analytics->enter("top-player-commands", <<<'EOT'
            A top-player command lets you create commands that discover the top players in a certain category.
            It provides the answer to questions like "who is the richest player?" or
            "who spent the most money last week?".
            EOT, $isNew);

        if ($isNew) {
            $topPlayersConfig->enter("richest", "This is an example top-player command that shows the richest players.");
        }

        $topQueries = [];

        foreach ($topPlayersConfig->getKeys() as $key) {
            $queryConfig = $topPlayersConfig->enter($key, null);
            $topQueries[] = self::parseTopPlayerQuery($queryConfig, $schema, $key);
        }

        return new self(
            singleQueries: $singleQueries,
            topQueries: $topQueries,
        );
    }

    /**
     * @return SingleQuery<Player>
     */
    private static function parseSingleQuery(Parser $infoConfig, Schema\Schema $schema) : SingleQuery {
        $type = $infoConfig->expectString("of", "account", <<<'EOT'
            The data source of this info.
            If set to "account", the info is calculated from statistics of some of the player's accounts.
            If set to "transaction", the info is calculated from statistics of the player's recent transactions.
            EOT);
        if ($type !== "account" && $type !== "transaction") {
            $type = $infoConfig->setValue("of", "account", "Expected \"account\" or \"transaction\"");
        }

        if ($type === "account") {
            $selectorConfig = $infoConfig->enter("selector", "Selects which accounts of the player to calculate.");
            $infoSchema = $schema->cloneWithCompleteConfig($selectorConfig);

            $metric = AccountQueryMetric::parseConfig($infoConfig, "metric");

            return new AccountSingleQuery($metric, fn(Player $player) => $infoSchema->getSelector($player));
        }

        if ($type === "transaction") {
            $selectorConfig = $infoConfig->enter("selector", "Filter transactions by labels");
            $labels = [];
            foreach ($selectorConfig->getKeys() as $labelKey) {
                $labels[$labelKey] = $selectorConfig->expectString($labelKey, "", null);
            }
            $labels = new ParameterizedLabelSelector($labels);

            $metric = TransactionQueryMetric::parseConfig($infoConfig, "metric");

            return new TransactionSingleQuery($metric, fn(Player $player) => $labels->transform(new PlayerInfo($player)));
        }

        throw new AssertionError("unreachable code");
    }

    private static function parseTopPlayerQuery(Parser $infoConfig, Schema\Schema $schema, string $cmdName) : ConfigTop {
        $queryArgs = TopQueryArgs::parse($infoConfig, $schema);

        $listLength = $infoConfig->expectInt("list-length", 5, "Number of top players to display");

        $cmdConfig = $infoConfig->enter("command", "The command that displays the information.");
        $command = DynamicCommand::parse($cmdConfig, "analytics", $cmdName, "Displays the richest player", false);

        $refreshConfig = $infoConfig->enter("refresh", <<<'EOT'
            Refresh settings for the top query.
            These settings depend on how many active accounts you have in the database
            as well as how powerful the CPU of your database server is.
            Try increasing the frequencies and reducing batch size if the database server is lagging.
            EOT);
        $refreshArgs = TopRefreshArgs::parse($refreshConfig);

        return new ConfigTop(
            command: $command,
            listLength: $listLength,
            queryArgs: $queryArgs,
            refreshArgs: $refreshArgs,
        );
    }
}
