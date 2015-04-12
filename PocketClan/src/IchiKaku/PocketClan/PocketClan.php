<?php

namespace IchiKaku\PocketClan;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;


class PocketClan extends PluginBase implements Listener {
    /** @var EconomyAPI */
    private $api = null;

    private $messages;
    public $m_version = 1;
    public $clanlist, $clandata, $playerclan;
    public $clan_list, $clan_data, $player_clan;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
        if ($this->getServer ()->getPluginManager ()->getPlugin ( "EconomyAPI" ) != null)
            $this->api = EconomyAPI::getInstance ();
        else {
            $this->getLogger()->error("'EconomyAPI' 플러그인이 없습니다.");
            $this->getServer ()->getPluginManager ()->disablePlugin ( $this );
        }
        $this->saveResource ( "messages.yml", false );
        $this->messagesUpdate ( "messages.yml" );
        $this->messages = (new Config ( $this->getDataFolder () . "messages.yml", Config::YAML ))->getAll ();
        $this->loadData();
        $this->getLogger()->info(TextFormat::GOLD."[PocketClan]Plugin was activitied");
    }
    public function onDisable() {
        $this->saveData();
    }
    public function onChat(PlayerChatEvent $e) {
        $e->setCancelled(true);
        $e->getPlayer()->setRemoveFormat ( false );
        $this->getServer ()->broadcastMessage ("[".$this->getClan($e->getPlayer()->getName())."]" . $e->getPlayer()->getName() . " : " . $e->getMessage () );
    }
    /**
     * @param CommandSender $sp
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sp, Command $command, $label, array $args){
        $p = $sp->getName();
        switch($command) {
            case "clan":
                switch($args[0]) {
                    case "make":
                        if(!isset($args[1])) {
                            $sp->sendMessage("[PocketClan] Please Input ClanName");
                        }
                        if($this->api->myMoney($p) < 30000) $sp->sendMessage("[PocketClan] You don't have enough money");
                        else {
                            $this->api->reduceMoney($p, 30000);
                            $this->clanlist[$args[2]] = $args[2];
                            $this->clandata[$args[2]][$p] = "admin";
                            $this->clandata[$args[2]]["list"] = array();
                            array_push($this->clandata[$args[2]]["list"], $p);
                            $this->playerclan[$p] = $args[2];
                            $sp->sendMessage("[PocketClan] Clan was made " . " [" . $args[2] . "]");
                        }
                        return true;
                    case "join" :
                        if(!isset($args[1])) {
                            $sp->sendMessage("[PocketClan] Please Input ClanName");
                        }
                        foreach($this->clanlist as $cl) {
                            if ($cl == $args[2]) {
                                $this->clandata[$args[2]][$p] = "user";
                                array_push($this->clandata[$args[2]]["list"], $p);
                                $this->playerclan[$p] = $args[2];
                                $sp->sendMessage("[PocketClan] Succesfully joined in  " . "\"" . $args[2] . "\"");
                            } else $sp->sendMessage("[PocketClan] Can't find Clan");
                        }
                        return true;
                    case "list" :
                        if(isset($args[2])) {
                            $list = "";
                            foreach($this->clandata[$args[2]]["list"] as $cl) $list .= $cl.",";
                            $sp->sendMessage("[PocketClan]" . $args[2] . " people : " . sizeof($this->clandata[$args[2]]["list"]) . "list : " . $list);
                        } else {
                            $list = "";
                            foreach($this->clanlist as $cl) $list .= $cl.",";
                            $sp->sendMessage("[PocketClan]" . $list);
                        }
                        return true;
                    case "leave" :
                        if(!isset($args[1])) {
                            $sp->sendMessage("[PocketClan] Please Input ClanName");
                        }
                        if(isset($args[2])) {
                            if($this->getClan($p) == $args[2]) {
                                $this->clandata[$args[2]][$p]="NotInClan";
                                $this->playerclan[$p] = "none";
                                array_replace($this->clandata[$args[2]]["list"], $p);
                            } else {
                                $sp->sendMessage("[PocketClan] Clan not founded");
                            }
                        } else return false;
                    break;
                    default:
                        $sp->sendMessage("[PocketClan] Usage: /clan [make/join/leave/list");
                }
                break;
            case "clanManage" :
                switch($args[0]) {
                    case "delete" :
                        return true;
                    case "ban" :
                        return true;
                    case "admin" :
                        return true;
                    default: $sp->sendMessage("[PocketClan] Usage: /clanManage [delete/ban/admin]");
                }
                break;
            default :
                return false;
        }
        return true;
    }
    public function getClan($player) {
        return $this->playerclan[$player];
    }
    public function get($var) {
        //method used by hmmm
        return $this->messages [$this->messages ["default-language"] . "-" . $var];
    }
    public function loadData() {
        $this->clan_list = $this->initializeYML ( "clan_list.yml", [ ] );
        $this->clan_data = $this->initializeYML("clan_data.yml", []);
        $this->player_clan = $this->initializeYML("player_clan.yml", []);
        $this->clandata = $this->clan_data->getAll();
        $this->clanlist = $this->clan_list->getAll ();
        $this->playerclan = $this->player_clan->getAll();
    }
    public function saveData() {
        $this->clan_list->setAll ( $this->clanlist);
        $this->clan_data->setAll($this->clandata);
        $this->player_clan->setAll($this->playerclan);
        $this->clan_list->save ();
        $this->clan_data->save();
        $this->player_clan->save();
    }
    public function initializeYML($path, $array) {
        //method used by hmmm
        return new Config ( $this->getDataFolder () . $path, Config::YAML, $array );
    }
    public function messagesUpdate($targetYmlName) {
        //method used by hmmm
        $targetYml = (new Config ( $this->getDataFolder () . $targetYmlName, Config::YAML ))->getAll ();
        if (! isset ( $targetYml ["m_version"] )) {
            $this->saveResource ( $targetYmlName, true );
        } else if ($targetYml ["m_version"] < $this->m_version) {
            $this->saveResource ( $targetYmlName, true );
        }
    }
}