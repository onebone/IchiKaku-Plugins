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
use pocketmine\Player;


class PocketClan extends PluginBase implements Listener {
    /** @var EconomyAPI */
    private $api = null;
    public $clanlist = [], $clandata = [], $playerclan;
    public $clan_list, $clan_data, $player_clan;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->getServer ()->getPluginManager ()->registerEvents ( $this, $this );
        if ($this->getServer ()->getPluginManager ()->getPlugin ( "EconomyAPI" ) != null)
            $this->api = EconomyAPI::getInstance ();
        else {
            $this->getLogger()->error("'EconomyAPI' plugin was not activitied!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
        $this->loadData();
        $this->getLogger()->info(TextFormat::GOLD."[PocketClan] Plugin was activitied");
    }
    public function onDisable() {
        $this->saveData();
    }
    public function onChat(PlayerChatEvent $e) {
        $e->setCancelled(true);
        $e->getPlayer()->setRemoveFormat ( false );
        $this->getServer ()->broadcastMessage ("[".$this->getClan($e->getPlayer()->getName())."] " . $e->getPlayer()->getName() . " : " . $e->getMessage () );
    }
    public function onCommand(CommandSender $sp, Command $command, $label, array $args){
        $p = $sp->getName();
        if($sp instanceof Player)
            switch($command) {
             case "clan":
                  if(!isset($args[0])) {
                      $sp->sendMessage("[PocketClan] Usage: /clan [make/join/leave/list");
                      break;
                  }
                 switch($args[0]) {
                     case "make":
                         if(!isset($args[1])) {
                             $sp->sendMessage("[PocketClan] Please Input ClanName");
                             break;
                         }
                         if($this->api->myMoney($p) < 30000) $sp->sendMessage("[PocketClan] You don't have enough money");
                         else if(isset($args[1])) {
                             $this->api->reduceMoney($p, 30000);
                             $this->clanlist[$args[1]] = $args[1];
                             $this->clandata[$args[1]][$p] = "admin";
                             $this->clandata[$args[1]]["list"] = array();
                             array_push($this->clandata[$args[1]]["list"], $p);
                             $this->playerclan[$p] = $args[1];
                             $sp->sendMessage("[PocketClan] Clan was made " . " [" . $args[1] . "]");
                         }
                         return true;
                     case "join" :
                         if(!isset($args[1])) {
                             $sp->sendMessage("[PocketClan] Please Input ClanName");
                             break;
                         }
                         if($this->getClan($p) == $args[1]) {
                             $sp->sendMessage("You are already in Clan [".$args[1]."]");
                             break;
                         }
                         if($this->getClan($p) != "none") {
                             $sp->sendMessage("You are already in Clan [".$this->getClan($p)."]");
                             break;
                         }
                         foreach($this->clanlist as $cl) {
                             if ($cl == $args[1]) {
                                 $this->clandata[$args[1]][$p] = "user";
                                 array_push($this->clandata[$args[1]]["list"], $p);
                                 $this->playerclan[$p] = $args[1];
                                 $sp->sendMessage("[PocketClan] Succesfully joined in  " . "\"" . $args[1] . "\"");
                                 break;
                             } else $sp->sendMessage("[PocketClan] Can't find Clan");
                         }
                         return true;
                     case "list" :
                         if(isset($args[1])) {
                             $list = "";
                             foreach($this->clandata[$args[1]]["list"] as $cl) $list .= $cl.",";
                             $sp->sendMessage("[PocketClan] " . $args[1] . " people : " . sizeof($this->clandata[$args[1]]["list"]) . " list : " . $list);
                         } else {
                             $list = "";
                             foreach($this->clanlist as $cl) $list .= $cl.",";
                             $sp->sendMessage("[PocketClan] " . $list);
                         }
                         return true;
                     case "leave" :
                         if(isset($args[1])) {
                             if($this->getClan($p) == $args[1]) {
                                 $this->clandata[$args[1]][$p]="NotInClan";
                                 $this->playerclan[$p] = "none";
                                 unset($this->clandata[$args[1]]["list"][array_search($p,$this->clandata[$args[1]]["list"])]);
                                 $sp->sendMessage("Succesfully leaved Clan [".$args[1]."]");
                             } else {
                                 $sp->sendMessage("[PocketClan] Clan not founded");
                             }
                         } else $sp->sendMessage("[PocketClan] Please Input ClanName");
                     break;
                     default:
                         $sp->sendMessage("[PocketClan] Usage: /clan [make/join/leave/list");
                 }
                 break;
             case "clanManage" :
                 switch($args[0]) {
                     case "delete" :
                         //TODO: 오피가 클랜 삭제시 클랜이 없을 경우 예외처리
                         if($this->clandata[$this->getClan($p)][$p] == "admin") {
                             foreach($this->clandata[$this->getClan($p)]["list"] as $pl)
                                 $this->playerclan[$pl] = "none";
                             unset($this->clanlist[array_search($this->getClan($p),$this->clanlist)]);
                             unset($this->clandata[array_search($this->getClan($p),$this->clandata)]);
                         }
                         else if(!isset($args[1])) $sp->sendMessage("[PocketClan] Usage: /clan delete <name>");
                         else if($sp->isOP()) {
                             foreach($this->clandata[$args[1]]["list"] as $pl)
                                 $this->playerclan[$pl] = "none";
                             unset($this->clanlist[array_search($args[1],$this->clanlist)]);
                             unset($this->clandata[array_search($args[1],$this->clandata)]);
                         }
                         return true;
                     case "ban" :
                         //TODO: 플레이어가 없을 경우에 예외처리
                         if(!isset($args[1])) $sp->sendMessage("[PocketClan] Usage: /clan ban <name>");
                         if($this->clandata[$this->getClan($p)][$p] == ("admin"||"op")) {
                             $this->playerclan[$args[1]] = "none";
                             unset($this->clandata[$this->getClan($p)]["list"][array_search($p, $this->clandata[$this->getClan($p)]["list"])]);
                         }
                         return true;
                     case "admin" :
                         //TODO: 플레이어가 없을 경우에 예외처리
                         if(!isset($args[1])) $sp->sendMessage("[PocketClan] Usage: /clan admin <name>");
                         if($this->clandata[$this->getClan($p)][$p] == ("admin"||"op")) {
                             $this->clandata[$this->getClan($p)]["list"][array_search($p, $this->clandata[$this->getClan($p)]["list"])] = "op";
                         }
                         return true;
                     default: $sp->sendMessage("[PocketClan] Usage: /clanManage [delete/ban/admin]");
                 }
                 break;
             default :
                 $sp->sendMessage("[PocketClan] Usage: /clanManage [delete/ban/admin]");
        }
        return true;
    }
    public function getClan($player) {
        return isset($this->playerclan[$player]) ? $this->playerclan[$player] : "none";
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
}