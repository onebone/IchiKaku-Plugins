<?php

namespace IchiKaku\criminal;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;


class PocketClan extends PluginBase implements Listener {
    private $api, $messages;

    public $clanlist, $clandata;
    public $economy;

    public function onEnable() {
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
        $this->getLogger()->info(TextFormat::GOLD.$this->get("plugin-loaded"));
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
                        if($this->api->myMoney($p) < 30000) $sp->sendMessage($this->get("PocketClan-NotEnoughMoney"));
                        else {
                            $this->api->reduceMoney($p, 30000);
                            $this->clanlist[$args[2]] = $args[2];
                            $this->clandata[$args[2]][$p] = "admin";
                            $this->clandata[$args[2]]["list"] = array();
                            array_push($this->clandata[$args[2]]["list"] = array(), $p);
                            $sp->sendMessage($this->get("PocketClan-ClanMade") . " [" . $args[2] . "]");
                        }
                        return true;
                    case "join" :
                        foreach($this->clanlist as $cl) {
                            if ($cl == $args[2]) {
                                $this->clandata[$args[2]][$p] = "user";
                                array_push($this->clandata[$args[2]]["list"] = array(), $p);
                                $sp->sendMessage($this->get("PocketClan-ClanJoin") . "\"" . $args[2] . "\"");
                            }
                        }
                        return true;
                    case "list" :
                        if(isset($args[2]))
                            $p->sendMessage("[PocketClan]".$args[2]." people : ".sizeof($this->clandata[$args[2]]["list"])."list : ".$this->clandata[$args[2]]["list"]);
                        else
                            $p->sendMessage("[PocketClan]".$this->clanlist);
                        return true;
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
                }
                break;
            default :
                return false;
        }
        return true;
    }
    public function get($var) {
        return $this->messages [$this->messages ["default-language"] . "-" . $var];
    }
    public function loadData() {
        $this->clanlist = $this->initializeYML ( "clan_list.yml", [ ] );
        $this->clandata = $this->clanlist->getAll ();
    }
    public function saveData() {
        $this->clanlist->setAll ( $this->clandata);
        $this->clanlist->save ();
        $this->defaultTextData ();
    }
    public function initializeYML($path, $array) {
        return new Config ( $this->getDataFolder () . $path, Config::YAML, $array );
    }
}