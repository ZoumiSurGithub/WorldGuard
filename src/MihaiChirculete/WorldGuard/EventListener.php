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

use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

class EventListener implements Listener
{

    //The reason why item IDs are being used directly, rather than ItemIds::CONSTANTs is for the cross-compatibility amongst forks.

    //These are the items that can be activated with the "use" flag enabled.
    const USABLES = [23, 25, 54, 58, 61, 62, 63, 64, 68, 69, 70, 71, 72, 77, 84, 92, 93, 94, 96, 107, 116, 117, 118, 125, 130, 131, 132, 137, 138, 143, 145, 146, 147, 148, 149, 150, 154, 167, 183, 184, 185, 186, 187, 188, 189, 193, 194, 195, 196, 197];

    const POTIONS = [373, 374, 437, 438, 444];

    const OTHER = [256, 259, 269, 273, 277, 284, 290, 291, 292, 293, 294];

    private $plugin;

    public function __construct(WorldGuard $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @priority MONITOR
     */
    public function onJoin(PlayerJoinEvent $event)
    {
        $this->plugin->sessionizePlayer($event->getPlayer());
    }

    public function onLeave(PlayerQuitEvent $event)
    {
        $this->plugin->onPlayerLogoutRegion($event->getPlayer());
    }

    public function onInteract(PlayerInteractEvent $event)
    {
        if ($event->getItem()->getID() == 325) {
            $player = $event->getPlayer();
            if (($reg = $this->plugin->getRegionFromPosition($event->getBlock()->getPosition())) !== "") {
                if ($reg->getFlag("block-place") === "false") {
                    if ($event->getPlayer()->hasPermission("worldguard.place." . $reg->getName()) || $event->getPlayer()->hasPermission("worldguard.block-place." . $reg->getName())) {
                        return true;
                    } else {
                        $event->cancel();
                        if ($reg->getFlag("deny-msg") === "true") {
                            $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-block-place"]);
                        }
                        return false;
                    }
                }
            }
        }
        if (isset($this->plugin->creating[$id = ($player = $event->getPlayer())->getUniqueId()->toString()])) {
            if ($event->getAction() === $event::RIGHT_CLICK_BLOCK) {
                $block = $event->getBlock();
                $x = $block->getPosition()->getX();
                $z = $block->getPosition()->getZ();
                if ($x < 0) {
                    $x = ($x + 1);
                }
                if ($z < 0) {
                    $z = ($z + 1);
                }
                $player->sendMessage(TextFormat::YELLOW . 'Selected position: X' . $x . ', Y: ' . $block->getPosition()->getY() . ', Z: ' . $z . ', Level: ' . $block->getPosition()->getWorld()->getFolderName());
                if (!isset($this->plugin->extended[$id = ($player = $event->getPlayer())->getUniqueId()->toString()])) {
                    $this->plugin->creating[$id][] = [$x, $block->getPosition()->getY(), $z, $block->getPosition()->getWorld()->getFolderName()];
                } else {
                    if (count($this->plugin->creating[$id]) == 0) {
                        $this->plugin->creating[$id][] = [$x, 0, $z, $block->getPosition()->getWorld()->getFolderName()];
                    } elseif (count($this->plugin->creating[$id]) >= 1) {
                        $this->plugin->creating[$id][] = [$x, 255, $z, $block->getPosition()->getWorld()->getFolderName()];
                    }
                }
                if (count($this->plugin->creating[$id]) >= 2) {
                    if (($reg = $this->plugin->processCreation($player)) !== false) {
                        $player->sendMessage(TextFormat::GREEN . 'Successfully created region ' . $reg);
                    } else {
                        $player->sendMessage(TextFormat::RED . 'An error occurred while creating the region.');
                    }
                }
                $event->cancel();
                return;
            }
        }
        if (($reg = $this->plugin->getRegionByPlayer($player)) !== "") {
            if ($reg->getFlag("pluginbypass") === "false") {
                $block = $event->getBlock()->getId();
                if ($reg->getFlag("use") === "false") {
                    if ($player->hasPermission("worldguard.usechest." . $reg->getName()) && $block === BlockLegacyIds::CHEST)
                        return;
                    if ($player->hasPermission("worldguard.usechestender." . $reg->getName()) && $block === BlockLegacyIds::ENDER_CHEST)
                        return;
                    if ($player->hasPermission("worldguard.usetrappedchest." . $reg->getName()) && $block === BlockLegacyIds::TRAPPED_CHEST)
                        return;
                    if ($player->hasPermission("worldguard.enchantingtable." . $reg->getName()) && $block === BlockLegacyIds::ENCHANTING_TABLE)
                        return;
                    if ($player->hasPermission("worldguard.usefurnaces." . $reg->getName()) && $block === BlockLegacyIds::BURNING_FURNACE || $block === BlockLegacyIds::FURNACE)
                        return;
                    if ($player->hasPermission("worldguard.usedoors." . $reg->getName()) && ($block === BlockLegacyIds::ACACIA_DOOR_BLOCK || $block === BlockLegacyIds::BIRCH_DOOR_BLOCK || $block === BlockLegacyIds::DARK_OAK_DOOR_BLOCK || $block === BlockLegacyIds::IRON_DOOR_BLOCK || $block === BlockLegacyIds::JUNGLE_DOOR_BLOCK || $block === BlockLegacyIds::OAK_DOOR_BLOCK || $block === BlockLegacyIds::SPRUCE_DOOR_BLOCK || $block === BlockLegacyIds::WOODEN_DOOR_BLOCK))
                        return;
                    if ($player->hasPermission("worldguard.usetrapdoors." . $reg->getName()) && ($block === BlockLegacyIds::IRON_TRAPDOOR || $block === BlockLegacyIds::TRAPDOOR || $block === BlockLegacyIds::WOODEN_TRAPDOOR))
                        return;
                    if ($player->hasPermission("worldguard.usegates." . $reg->getName()) && ($block === BlockLegacyIds::ACACIA_FENCE_GATE || $block === BlockLegacyIds::BIRCH_FENCE_GATE || $block === BlockLegacyIds::DARK_OAK_FENCE_GATE || $block === BlockLegacyIds::FENCE_GATE || $block === BlockLegacyIds::JUNGLE_FENCE_GATE || $block === BlockLegacyIds::OAK_FENCE_GATE || $block === BlockLegacyIds::SPRUCE_FENCE_GATE))
                        return;
                    if ($player->hasPermission("worldguard.useanvil." . $reg->getName()) && ($block === BlockLegacyIds::ANVIL))
                        return;
                    if ($player->hasPermission("worldguard.usecauldron." . $reg->getName()) && ($block === BlockLegacyIds::CAULDRON_BLOCK))
                        return;
                    if ($player->hasPermission("worldguard.usebrewingstand." . $reg->getName()) && ($block === BlockLegacyIds::BREWING_STAND_BLOCK))
                        return;
                    if ($player->hasPermission("worldguard.usebeacon." . $reg->getName()) && ($block === BlockLegacyIds::BEACON))
                        return;
                    if ($player->hasPermission("worldguard.usecraftingtable." . $reg->getName()) && ($block === BlockLegacyIds::BEACON))
                        return;
                    if ($player->hasPermission("worldguard.usenoteblock." . $reg->getName()) && ($block === BlockLegacyIds::NOTE_BLOCK))
                        return;
                    if ($player->hasPermission("worldguard.usepressureplate." . $reg->getName()) && ($block === BlockLegacyIds::WOODEN_PRESSURE_PLATE || $block === BlockLegacyIds::LIGHT_WEIGHTED_PRESSURE_PLATE || $block === BlockLegacyIds::HEAVY_WEIGHTED_PRESSURE_PLATE || $block === BlockLegacyIds::STONE_PRESSURE_PLATE))
                        return;
                    if ($player->hasPermission("worldguard.usebutton." . $reg->getName()) && ($block === BlockLegacyIds::STONE_BUTTON || $block === BlockLegacyIds::WOODEN_BUTTON))
                        return;
                    if (in_array($block, self::USABLES)) {
                        if ($reg->getFlag("deny-msg") === "true") {
                            $player->sendMessage(TextFormat::RED . 'You cannot interact with ' . $event->getBlock()->getName() . 's.');
                        }
                        $event->cancel();
                        return;
                    }
                } else $event->uncancel();

                if ($reg->getFlag("potions") === "false") {
                    if (in_array($event->getItem()->getId(), self::POTIONS)) {
                        $player->sendMessage(TextFormat::RED . 'You cannot use ' . $event->getItem()->getName() . ' in this area.');
                        $event->cancel();
                        return;
                    }
                } else $event->uncancel();
                if (!$player->hasPermission("worldguard.edit." . $reg->getName())) {
                    if (in_array($event->getItem()->getId(), self::OTHER)) {
                        $player->sendMessage(TextFormat::RED . 'You cannot use ' . $event->getItem()->getName() . '.');
                        $event->cancel();
                        return;
                    }
                } else $event->uncancel();
                return;
            }
        }
    }

    public function onBlockUpdate(BlockUpdateEvent $event)
    {
        $block = $event->getBlock();
        $position = new Position($block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ(), $block->getPosition()->getWorld());
        $region = $this->plugin->getRegionFromPosition($position);
        if ($region !== "") {
            if ($region->getFlag("pluginbypass") === "false") {
                if ($block->getName() === "Lava" || $block->getName() === "Water") {
                    if ($region->getFlag("flow") === "false") {
                        $event->cancel();
                    }
                }
            }
        }
    }

    public function onItemUse(PlayerItemUseEvent $event){
        if ($event->getItem()->getID() == 368) {
            $player = $event->getPlayer();
            if (($region = $this->plugin->getRegionByPlayer($event->getPlayer())) !== "") {
                if ($region->getFlag("enderpearl") === "false") {
                    $event->cancel();
                    if ($region->getFlag("deny-msg") === "true") {
                        $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-ender-pearls"]);
                    }
                    return false;
                }
            }
        }
    }

    /**
     * @param BlockPlaceEvent $event
     * @return bool|void
     * @ignoreCancelled true
     */
    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $x = $block->getPosition()->getX();
        $z = $block->getPosition()->getZ();
        if ($x < 0) {
            $x = ($x + 1);
        }
        if ($z < 0) {
            $z = ($z + 1);
        }
        $position = new Position($x, $block->getPosition()->getY(), $z, $block->getPosition()->getWorld());
        if (($region = $this->plugin->getRegionFromPosition($position)) !== "") {
            if ($region->getFlag("pluginbypass") === "false") {
                if ($region->getFlag("block-place") === "false") {
                    if ($event->getPlayer()->hasPermission("worldguard.place." . $region->getName()) || $event->getPlayer()->hasPermission("worldguard.block-place." . $region->getName())) {
                        return true;
                    } else if ($event->getPlayer()->hasPermission("worldguard.build-bypass")) {
                        return true;
                    } else {
                        if ($region->getFlag("deny-msg") === "true") {
                            $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-block-place"]);
                        }
                        $event->cancel();
                    }
                }
            }
        }
    }

    /**
     * @param BlockBreakEvent $event
     * @return bool|void
     */
    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $x = $block->getPosition()->getX();
        $z = $block->getPosition()->getZ();
        if ($x < 0) {
            $x = ($x + 1);
        }
        if ($z < 0) {
            $z = ($z + 1);
        }
        $position = new Position($x, $block->getPosition()->getY(), $z, $block->getPosition()->getWorld());
        if (($region = $this->plugin->getRegionFromPosition($position)) !== "") {
            if ($region->getFlag("pluginbypass") === "false") {
                if ($region->getFlag("block-break") === "false") {
                    if ($event->getPlayer()->hasPermission("worldguard.break." . $region->getName()) || $event->getPlayer()->hasPermission("worldguard.block-break." . $region->getName())) {
                        return true;
                    } else if ($event->getPlayer()->hasPermission("worldguard.break-bypass")) {
                        return true;
                    } else {
                        if ($region->getFlag("deny-msg") === "true") {
                            $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-block-break"]);
                        }
                        $event->cancel();
                    }
                }
            }
            if ($region->getFlag("exp-drops") === "false") {
                $event->setXpDropAmount(0);
            }

        }
    }

    public function onDeathItemDrop(PlayerDeathEvent $event)
    {
        if (($reg = $this->plugin->getRegionByPlayer($player = $event->getPlayer())) !== "") {
            if ($reg->getFlag("item-by-death") === "false" && !$player->hasPermission("worldguard.deathdrop." . $reg->getName())) {
                if ($reg->getFlag("deny-msg") === "true") {
                    $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-item-death-drop"]);
                }
                $event->setDrops([]);
                return;
            }
        }
    }

    public function onBurn(BlockBurnEvent $event)
    {
        if (($region = $this->plugin->getRegionFromPosition($event->getBlock()->getPosition())) !== "") {
            if ($region->getFlag("allow-block-burn") === "false")
                $event->cancel();
        }
    }

    /**
     * @priority MONITOR
     */
    public function onMove(PlayerMoveEvent $event)
    {
        if (!$event->getFrom()->equals($event->getTo())) {
            if ($this->plugin->updateRegion($player = $event->getPlayer()) !== true) {
                $player->setMotion($event->getFrom()->subtract($player->getPosition()->getX(), $player->getPosition()->getY(), $player->getPosition()->getZ())->normalize()->multiply($this->plugin->getKnockback()));
            }
        }
    }

    public function onHurtByEntity(EntityDamageByEntityEvent $event)
    {
        $victim = $event->getEntity();
        $damager = $event->getDamager();
        if (($victim) instanceof Player && $damager instanceof Player) {
            if (($reg = $this->plugin->getRegionByPlayer($victim)) !== "") {
                if ($reg->getFlag("pvp") === "false") {
                    if (($damager) instanceof Player) {
                        if ($reg->getFlag("deny-msg") === "true") {
                            $damager->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-pvp"]);
                        }
                        $event->cancel();
                        return true;
                    }
                }
            }
            if (($damager) instanceof Player) {
                if (($reg = $this->plugin->getRegionByPlayer($damager)) !== "") {
                    if ($reg->getFlag("pvp") === "false") {
                        if (($victim) instanceof Player) {
                            if ($reg->getFlag("deny-msg") === "true") {
                                $damager->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-pvp"]);
                            }
                            $event->cancel();
                            return true;
                        }
                    }
                }
            }
        }

        // $this->plugin->getLogger()->notice(get_class($event->getEntity()));

        if (Utils::isAnimal($event->getEntity())) {
            if (($player = $event->getDamager()) instanceof Player) {
                if (($region = $this->plugin->getRegionFromPosition($event->getEntity()->getPosition())) !== "") {
                    if ($region->getFlag("allow-damage-animals") === "false") {
                        if ($region->getFlag("deny-msg") === "true") {
                            $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-hurt-animal"]);
                        }
                        $event->cancel();
                        return;
                    }
                }
            }
        }

        if (Utils::isMonster($event->getEntity())) {
            if (($player = $event->getDamager()) instanceof Player)
                if (($region = $this->plugin->getRegionFromPosition($event->getEntity()->getPosition())) !== "") {
                    if ($region->getFlag("allow-damage-animals") === "false") {
                        $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-hurt-monster"]);
                        $event->cancel();
                        return;
                    }
                }
        }

        if (strpos(get_class($event->getEntity()), "monster") !== false) {
            if (($player = $event->getDamager()) instanceof Player)
                if (($region = $this->plugin->getRegionFromPosition($event->getEntity()->getPosition())) !== "") {
                    if ($region->getFlag("allow-damage-monsters") === "false") {
                        $player->sendMessage(TextFormat::RED . 'You cannot hurt monsters of this region.');
                        $event->cancel();
                        return;
                    }
                }
        }
    }

    public function onHurt(EntityDamageEvent $event)
    {
        if (($this->plugin->getRegionFromPosition($event->getEntity()->getPosition())) !== "") {
            if ($this->plugin->getRegionFromPosition($event->getEntity()->getPosition())->getFlag("invincible") === "true") {
                if ($event->getEntity() instanceof Player) {
                    $event->cancel();
                }
            }
        }
        return;
    }

    public function onFallDamage(EntityDamageEvent $event)
    {
        if (($this->plugin->getRegionFromPosition($event->getEntity()->getPosition())) !== "") {
            $cause = $event->getCause();
            if ($this->plugin->getRegionFromPosition($event->getEntity()->getPosition())->getFlag("fall-dmg") === "false") {
                if ($cause == EntityDamageEvent::CAUSE_FALL) {
                    $event->cancel();
                }
            }
        }
        return;
    }

    /**
     * @param PlayerCommandPreprocessEvent $event
     * @ignoreCancelled true
     */
    public function onCommand(PlayerCommandPreprocessEvent $event)
    {
        if ($this->plugin->getRegionByPlayer($event->getPlayer()) !== "")
            if (strpos(strtolower($event->getMessage()), '/f claim') === 0) {
                $event->getPlayer()->sendMessage(TextFormat::RED . 'You cannot claim plots in this area.');
                $event->cancel();
            }


        $cmd = explode(" ", $event->getMessage())[0];
        if (substr($cmd, 0, 1) === '/') {
            if (($region = $this->plugin->getRegionByPlayer($player = $event->getPlayer())) !== "" && !$region->isCommandAllowed($cmd)) {
                if (!$player->hasPermission("worldguard.bypass-cmd." . $region->getName())) {
                    $player->sendMessage(TextFormat::RED . 'You cannot use ' . $cmd . ' in this area.');
                    $event->cancel();
                }
            }
        }
    }

    /**
     * @param PlayerDropItemEvent $event
     * @ignoreCancelled true
     */
    public function onDrop(PlayerDropItemEvent $event)
    {
        if (($reg = $this->plugin->getRegionByPlayer($player = $event->getPlayer())) !== "") {
            if ($reg->getFlag("item-drop") === "false" && !$player->hasPermission("worldguard.drop." . $reg->getName())) {
                if ($reg->getFlag("deny-msg") === "true") {
                    $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-item-drop"]);
                }
                $event->cancel();
                return;
            }
        }
    }

    /**
     * @param EntityExplodeEvent $event
     * @ignoreCancelled true
     */
    public function onExplode(EntityExplodeEvent $event)
    {
        foreach ($event->getBlockList() as $block) {
            if (($region = $this->plugin->getRegionFromPosition($block->getPosition())) !== "") {
                if ($region->getFlag("explosion") === "false") {
                    $event->cancel();
                    return;
                }
            }
        }
    }

    /**
     * @param PlayerBedEnterEvent $event
     * @ignoreCancelled true
     */
    public function onSleep(PlayerBedEnterEvent $event)
    {
        if (($region = $this->plugin->getRegionFromPosition($event->getBed()->getPosition())) !== "") {
            if ($region->getFlag("sleep") === "false") {
                $event->cancel();
            }
        }
    }

    /**
     * @param PlayerChatEvent $event
     * @ignoreCancelled true
     */
    public function onChat(PlayerChatEvent $event)
    {
        if (($reg = $this->plugin->getRegionByPlayer($player = $event->getPlayer())) !== "") {
            if ($reg->getFlag("send-chat") === "false") {
                if ($reg->getFlag("deny-msg") === "true") {
                    $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-chat"]);
                }
                $event->cancel();
                return;
            }
        }
        if (!empty($this->plugin->muted)) {
            $diff = array_diff($this->plugin->getServer()->getOnlinePlayers(), $this->plugin->muted);
            if (!in_array($player, $diff)) {
                $diff[] = $player;
            }
            $event->setRecipients($diff);
        }
    }

    public function onItemConsume(PlayerItemConsumeEvent $event)
    {
        $player = $event->getPlayer();
        if ($player instanceof Player) {
            if (($region = $this->plugin->getRegionByPlayer($event->getPlayer())) !== "") {
                if ($region->getFlag("eat") === "false" && !$player->hasPermission("worldguard.eat." . $region->getName())) {
                    $event->cancel();
                    if ($region->getFlag("deny-msg") === "true") {
                        $player->sendMessage(TextFormat::RED . $this->plugin->resourceManager->getMessages()["denied-eat"]);
                    }
                }
            }
        }
    }

    public function noHunger(PlayerExhaustEvent $exhaustEvent)
    {
        if ($exhaustEvent->getPlayer() instanceof Player) {
            if (($region = $this->plugin->getRegionByPlayer($exhaustEvent->getPlayer())) !== "") {
                if ($region->getFlag("hunger") === "false") {
                    $exhaustEvent->cancel();
                }
            }
        }
    }

    public function onLeafDecay(LeavesDecayEvent $event)
    {
        if (($region = $this->plugin->getRegionFromPosition($event->getBlock()->getPosition())) !== "")
            if ($region->getFlag("allow-leaves-decay") === "false")
                $event->cancel();
    }

    public function onPlantGrowth(BlockGrowEvent $event)
    {
        if (($region = $this->plugin->getRegionFromPosition($event->getBlock()->getPosition())) !== "")
            if ($region->getFlag("allow-plant-growth") === "false")
                $event->cancel();
    }

    public function onBlockSpread(BlockSpreadEvent $event)
    {
        if (($region = $this->plugin->getRegionFromPosition($event->getBlock()->getPosition())) !== "")
            if ($region->getFlag("allow-spreading") === "false")
                $event->cancel();
    }
}
