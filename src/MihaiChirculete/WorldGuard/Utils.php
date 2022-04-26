<?php

/**
*
*  _     _  _______  ______    ___      ______   _______  __   __  _______  ______    ______  
* | | _ | ||       ||    _ |  |   |    |      | |       ||  | |  ||   _   ||    _ |  |      | 
* | || || ||   _   ||   | ||  |   |    |  _    ||    ___||  | |  ||  |_|  ||   | ||  |  _    |
* |       ||  | |  ||   |_||_ |   |    | | |   ||   | __ |  |_|  ||       ||   |_||_ | | |   |
* |       ||  |_|  ||    __  ||   |___ | |_|   ||   ||  ||       ||       ||    __  || |_|   |
* |   _   ||       ||   |  | ||       ||       ||   |_| ||       ||   _   ||   |  | ||       |
* |__| |__||_______||___|  |_||_______||______| |_______||_______||__| |__||___|  |_||______| 
*
* By MihaiChirculete.
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Lesser General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* GitHub: https://github.com/MihaiChirculete
*/

namespace MihaiChirculete\WorldGuard;

use pocketmine\data\bedrock\BiomeIds;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\network\mcpe\protocol\SetPlayerGameTypePacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\biome\Biome;

class Utils {

    const GAMEMODES = [
        "0" => "survival",
        "s" => "survival",
        "1","c" => "creative",
        //"c" => "creative",
        "2" => "adventure",
        "a" => "adventure",
        "3" => "spectator",
        "sp" => "spectator",
    ];

    public static function getRomanNumber(int $integer, $upcase = true) : string
    {
        $table = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
        $return = '';
        while($integer > 0) {
            foreach($table as $rom=>$arb) {
                if($integer >= $arb) {
                    $integer -= $arb;
                    $return .= $rom;
                    break;
                }
            }
        }
        return $return;
    }

    public static function disableFlight(Player $player)
    {
        $player->setAllowFlight(false);
        if ($player->getGamemode() !== GameMode::CREATIVE()) {
            $player->setFlying(false);
        }
    }

    public static function gm2string(int $gm) : string
    {
        return self::GAMEMODES[$gm] ?? "survival";
    }

    /**
     * @param Player $player
     * @param string $msg
     * @return mixed
     *
     * Use this to parse aliases in a string
     */
    public static function aliasParse(Player $player, string $msg)
    {
        $parsedMsg = str_replace("{player_name}", $player->getName() ,$msg);
        $parsedMsg = str_replace("&", "§", $parsedMsg);

        return $parsedMsg;
    }


    /**
     * @param Entity $ent
     * @return bool
     *
     * Pass an entity to this function and it checks if its an animal or not.
     */
    public static function isAnimal(Entity $ent): bool
    {
        if($ent instanceof Living)
            return true;

        $classname = strtolower(get_class($ent));

        if(strpos($classname, "bat") !== false ||
            strpos($classname, "chicken") !== false ||
            strpos($classname, "cow") !== false ||
            strpos($classname, "donkey") !== false ||
            strpos($classname, "horse") !== false ||
            strpos($classname, "llama") !== false ||
            strpos($classname, "mooshroom") !== false ||
            strpos($classname, "mule") !== false ||
            strpos($classname, "ocelot") !== false ||
            strpos($classname, "parrot") !== false ||
            strpos($classname, "pig") !== false ||
            strpos($classname, "polarbear") !== false ||
            strpos($classname, "rabbit") !== false ||
            strpos($classname, "sheep") !== false ||
            strpos($classname, "wolf") !== false ||
            strpos($classname, "animal")
        )
            return true;

        return false;
    }

    /**
     * @param string $classname
     * @return bool
     *
     * Pass an entity to this function and it checks if its a monster or not.
     */
    public static function isMonster(Entity $ent): bool
    {
        if($ent instanceof Living)
            return true;

        $classname = strtolower(get_class($ent));

        if(strpos($classname, "blaze") !== false ||
            strpos($classname, "cavespider") !== false ||
            strpos($classname, "elderguardian") !== false ||
            strpos($classname, "enderdragon") !== false ||
            strpos($classname, "enderman") !== false ||
            strpos($classname, "endermite") !== false ||
            strpos($classname, "evoker") !== false ||
            strpos($classname, "ghast") !== false ||
            strpos($classname, "guardian") !== false ||
            strpos($classname, "husk") !== false ||
            strpos($classname, "magmacube") !== false ||
            strpos($classname, "pigzombie") !== false ||
            strpos($classname, "shulker") !== false ||
            strpos($classname, "silverfish") !== false ||
            strpos($classname, "skeleton") !== false ||
            strpos($classname, "slime") !== false ||
            strpos($classname, "spider") !== false ||
            strpos($classname, "stray") !== false ||
            strpos($classname, "undead") !== false ||
            strpos($classname, "vex") !== false ||
            strpos($classname, "vindicator") !== false ||
            strpos($classname, "witch") !== false ||
            strpos($classname, "wither") !== false ||
            strpos($classname, "witherskeleton") !== false ||
            strpos($classname, "zombievillager") !== false ||
            strpos($classname, "monster")
        )
            return true;

        return false;
    }

    private static function biomeParse($biomeName) : int
    {
        $biomeName = strtolower($biomeName);
        switch ($biomeName)
        {
            case "birch_forest":
                return BiomeIds::BIRCH_FOREST;
                break;

            case "desert":
                return BiomeIds::DESERT;
                break;

            case "forest":
                return BiomeIds::FOREST;
                break;

            case "hell":
                return BiomeIds::BIRCH_FOREST;
                break;

            case "ice_plains":
                return BiomeIds::ICE_PLAINS;
                break;

            case "mountains":
                return BiomeIds::ICE_MOUNTAINS;
                break;

            case "ocean":
                return BiomeIds::OCEAN;
                break;

            case "plains":
                return BiomeIds::PLAINS;
                break;

            case "river":
                return BiomeIds::RIVER;
                break;

            case "small_mountains":
                return BiomeIds::ICE_MOUNTAINS;
                break;

            case "swamp":
                return BiomeIds::SWAMPLAND;
                break;

            case "taiga":
                return BiomeIds::TAIGA;
                break;
        }
    }

    public static function setBiome($plugin, Region $reg, $biomeName)
    {
        $pos1 = $reg->getPos1();
        $pos2 = $reg->getPos2();

        $x1 = $pos1[0];
        $z1 = $pos1[2];

        $x2 = $pos2[0];
        $z2 = $pos2[2];

        if($x1>$x2)
        {
            $tmp = $x1;
            $x1 = $x2;
            $x2 = $tmp;
        }

        if($z1>$z2)
        {
            $tmp = $z1;
            $z1 = $z2;
            $z2 = $tmp;
        }

        for ($i=$x1; $i<=$x2; $i++)
            for($j=$z1; $j<=$z2; $j++)
                $plugin->getServer()->getLevelByName($reg->getLevelName())->setBiomeId($i, $j, self::biomeParse($biomeName));
    }

    // given an issuer object, returns the plugin object
    // useful for static functions when you need the plugin refference
    public static function getPluginFromIssuer(Player $issuer): ?\pocketmine\plugin\Plugin
    {
        return $issuer->getServer()->getPluginManager()->getPlugin("WorldGuard");
    }
}
