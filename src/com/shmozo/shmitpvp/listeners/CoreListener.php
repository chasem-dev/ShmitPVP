<?php
/**
 * Created by PhpStorm.
 * User: chase
 * Date: 8/11/2017
 * Time: 3:08 PM
 */

namespace com\shmozo\shmitpvp\listeners;


use com\shmozo\shmitpvp\ShmitPVP;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CoreListener implements Listener {

    public function onPlayerJoin(PlayerJoinEvent $event) {
        $event->setJoinMessage(null);//TODO CHECK IF  "" instead of NULL?
        $event->getPlayer()->sendMessage(TextFormat::YELLOW . "Welcome to " . TextFormat::AQUA . "ShmitPVP!");
        $event->getPlayer()->sendMessage(TextFormat::YELLOW . "Type " . TextFormat::RED . " /kit" . TextFormat::YELLOW . " to prepare for battle!");
        foreach ($event->getPlayer()->getLevel()->getEntities() as $entity) {
            if ($entity->namedtag->offsetExists("NPC")) {
                $entity->spawnTo($event->getPlayer());
                $entity->setNameTagAlwaysVisible(true);
                $entity->setNameTag($entity->getNameTag());
            }
        }
        ShmitPVP::getInstance()->skinUtils->storeSkinData($event->getPlayer());
        $event->getPlayer()->teleport($event->getPlayer()->getLevel()->getSpawnLocation());
    }


    public function onInteractNPC(EntityDamageEvent $event) {
        if ($event instanceof EntityDamageByEntityEvent) {
            $player = $event->getDamager();
            if ($player instanceof Player) {
                if ($event->getEntity()->namedtag->offsetExists("NPC")) {
                    $event->setCancelled();
                    if ($player->isCreative() && $player->isSneaking()) {
                        /**
                         * @var Human $human
                         */
                        $human = $event->getEntity();
                        if ($human instanceof Human) {
                            $player->sendMessage(TextFormat::RED . "Removed NPC " . $human->getNameTag());
                            $human->getInventory()->clearAll();
                            $human->kill();

                        }
                    } else {
                        $kit = ShmitPVP::getInstance()->getKit($event->getEntity()->getNameTag());
                        if ($kit != null) {
                            $kit->applyTo($player);
                        }
                    }
                }
            }
        }
    }


    public function onNPCDamaged(EntityDamageEvent $event) {
        if ($event->getEntity()->namedtag->offsetExists("NPC")) {
            $event->setCancelled();
        }
    }


    /**
     * @param PlayerQuitEvent $event
     * @priority MONITOR
     */
    public function onPlayerQuit(PlayerQuitEvent $event) {
        if (ShmitPVP::getInstance()->skinUtils->isSkinStored($event->getPlayer())) {
            ShmitPVP::getInstance()->skinUtils->removeSkinData($event->getPlayer());
        }
    }

}