<?php

namespace ojy\fame;

use ojy\fame\cmd\FameCommand;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;

class Fame extends PluginBase implements Listener
{

    /** @var Config */
    public static $data;

    /** @var array */
    public static $db = [];

    public function onEnable()
    {
        self::$data = new Config($this->getDataFolder() . "Data.yml     ", Config::YAML, [
            "fame" => [],
            "fc" => [],
            "fc-max" => 3,
            "day" => (int)date("d")
        ]);
        self::$db = self::$data->getAll();

        foreach (array_keys(self::$db["fc"]) as $name) {
            if (self::$db["fc"][$name] === 0)
                self::$db["fc"][$name] = [];
        }

        if (self::$db["day"] !== (int)date("d")) {
            self::$db["day"] = (int)date("d");
            foreach (array_keys(self::$db["fc"]) as $name) {
                self::$db["fc"][$name] = [];
            }
        }

        foreach ([FameCommand::class] as $c)
            Server::getInstance()->getCommandMap()->register("Fame", new $c);

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        if (!isset(self::$db["fame"][strtolower($player->getName())])) {
            $name = strtolower($player->getName());
            self::$db["fame"][$name] = 0;
            self::$db["fc"][$name] = [];
        }
    }

    public static function canGiveFame(Player $player): bool
    {
        return (count(self::$db["fc"][strtolower($player->getName())]) < self::$db["fc-max"]);
    }

    public static function getRank(string $playerName): ?int
    {
        if (isset(self::$db["fame"][strtolower($playerName)])) {
            arsort(self::$db["fame"]);
            $fameData = self::$db["fame"];
            $c = 1;
            foreach ($fameData as $name => $fame) {
                if (strtolower($playerName) === $name) {
                    return $c;
                }
                ++$c;
            }
        }
        return null;
    }

    public static function isGived(Player $sender, string $playerName): bool
    {
        if (in_array(strtolower($playerName), self::$db["fc"][strtolower($sender->getName())])) {
            return true;
        }
        return false;
    }

    public static function canGiveTo(Player $sender, string $playerName): bool
    {
        return self::canGiveFame($sender) && !self::isGived($sender, $playerName);
    }

    public static function getFame(string $playerName): ?int
    {
        if (isset(self::$db["fame"][strtolower($playerName)])) {
            return self::$db["fame"][strtolower($playerName)];
        }
        return null;
    }

    public static function addFame(Player $sender, string $playerName): bool
    {
        if (isset(self::$db["fame"][strtolower($playerName)])) {
            ++self::$db["fame"][strtolower($playerName)];
            self::$db["fc"][strtolower($sender->getName())][] = strtolower($playerName);
            return true;
        }
        return false;
    }

    public function onDisable()
    {
        self::$data->setAll(self::$db);
        self::$data->save();
    }
}