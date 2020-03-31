<?php

namespace ojy\fame\cmd;

use ojy\fame\Fame;
use ojy\prefix\PrefixManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use ssss\utils\SSSSUtils;

class FameCommand extends Command
{

    public function __construct()
    {
        parent::__construct("인기도", "인기도 명령어입니다.", "/인기도");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player) {
            switch ($args[0] ?? "x") {
                case "주기":
                case "올리기":
                    // fc-max check
                    if (Fame::canGiveFame($sender)) {
                        // 오늘 줬는지 체크
                        //본인 닉네임 체크
                        if (isset($args[1])) {
                            unset($args[0]);
                            $name = implode(" ", $args);
                            $player = Server::getInstance()->getPlayer($name);
                            if ($player !== null)
                                $name = $player->getName();
                            if (strtolower($name) !== strtolower($sender->getName())) {
                                if (!Fame::isGived($sender, $name)) {
                                    if (Fame::addFame($sender, $name)) {
                                        SSSSUtils::message($sender, "{$name}님의 인기도를 올렸습니다!");
                                        if ($player !== null) {
                                            $player->sendMessage("§l§b[§f인기도§b] §r§6{$sender->getName()}§f님이 당신의 인기도를 올렸습니다.");
                                            $nameTag = PrefixManager::$instance->getNameTagFormat($player);
                                            $player->setNameTag($nameTag);
                                        }
                                    } else {
                                        SSSSUtils::message($sender, "{$name}님은 서버에 접속한 적이 없습니다.");
                                    }
                                } else {
                                    SSSSUtils::message($sender, "이미 오늘 {$name}님의 인기도를 올렸습니다.");
                                }
                            } else {
                                SSSSUtils::message($sender, "자기 자신에게 인기도를 줄 수 없습니다.");
                            }
                        } else {
                            SSSSUtils::message($sender, "/인기도 주기 [닉네임]");
                        }
                    } else {
                        SSSSUtils::message($sender, "오늘 줄 수 있는 인기도를 모두 사용했습니다.");
                    }
                    break;

                case "보기":
                    $name = $args[1] ?? $sender->getName();
                    $player = Server::getInstance()->getPlayer($name);
                    if ($player !== null)
                        $name = $player->getName();
                    $fame = Fame::getFame($name);
                    if ($fame !== null) {
                        $rank = Fame::getRank($name);
                        SSSSUtils::message($sender, "{$name}님의 인기도: {$fame}, 순위: {$rank}");
                    } else {
                        SSSSUtils::message($sender, "{$name}님의 기록을 찾을 수 없습니다.");
                    }
                    break;

                case "순위":
                    $yourRank = Fame::getRank($sender->getName());
                    $yourFame = Fame::getFame($sender->getName());
                    SSSSUtils::message($sender, "{$sender->getName()}님의 인기도: {$yourFame}, 순위: {$yourRank}");
                    $fameData = Fame::$db["fame"];
                    $page = 1;
                    if (isset($args[1]))
                        $page = intval($args[1]);
                    if ($page < 1)
                        $page = 1;
                    $maxPage = ceil(count($fameData) / 5);
                    if ($page > $maxPage)
                        $page = $maxPage;
                    SSSSUtils::message($sender, "인기도 순위를 표시합니다. ({$page}/{$maxPage})");
                    $i1 = $page * 5 - 5;
                    $i2 = $page * 5 - 1;
                    $c = 0;
                    foreach ($fameData as $pName => $fame) {
                        if ($c >= $i1 && $c <= $i2) {
                            $rank = $c + 1;
                            $sender->sendMessage("§l§a[{$rank}위] §r§7{$pName}, 인기도: {$fame}");
                        }
                        if ($c > $i2)
                            break;
                        ++$c;
                    }
                    break;

                default:
                    SSSSUtils::message($sender, "/인기도 주기 [닉네임]");
                    SSSSUtils::message($sender, "/인기도 보기 [닉네임]");
                    SSSSUtils::message($sender, "/인기도 순위 [페이지]");
                    break;
            }
        }
    }
}