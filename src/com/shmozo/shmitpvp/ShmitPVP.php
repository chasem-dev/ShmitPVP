<?php
/**
 * Created by PhpStorm.
 * User: chase
 * Date: 8/11/2017
 * Time: 3:00 PM
 */

namespace com\shmozo\shmitpvp;


use com\shmozo\shmitpvp\listeners\CoreListener;
use com\shmozo\shmitpvp\utils\SkinUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\item\Item;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;


class ShmitPVP extends PluginBase {


    /**
     * @var Config cfg;
     */
    public $cfg;

    /**
     * @var Location spawn;
     */
    public $spawn;

    /**
     * @var array
     */
    public $kits = array();


    /**
     * @var ShmitPVP
     */
    private static $instance;

    /**
     * @var SkinUtils
     */
    public $skinUtils;


    /**
     * @return ShmitPVP
     */
    public static function getInstance() {
        return self::$instance;
    }


    public function onLoad() {
        ShmitPVP::$instance = $this;
        $this->skinUtils = new SkinUtils();
    }

    public function onEnable() {
        $this->getLogger()->info("onEnable()");
        $this->getServer()->getPluginManager()->registerEvents(new CoreListener(), $this);
        @mkdir($this->getDataFolder());
        $this->cfg = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->loadKits();

        $x = $this->cfg->getNested("spawn.x", 0);
        $y = $this->cfg->getNested("spawn.y", 0);
        $z = $this->cfg->getNested("spawn.z", 0);
        $yaw = $this->cfg->getNested("spawn.yaw", 0);
        $pitch = $this->cfg->getNested("spawn.pitch", 0);
        $this->spawn = new Location($x, $y, $z, $yaw, $pitch);

        $this->getLogger()->info("SPAWN SET TO: " . strval($this->spawn));

    }

    /**
     * @return Location
     */
    public function getSpawn() {
        return $this->spawn;
    }


    /**
     * @param string $name
     * @param string $fileLocation
     * @param Player $player
     */
    public function spawnNPC($name = "NPC", $fileLocation = "char", $player) {

        $name = str_replace("&", "§", $name);

        $loc = $player->getLocation();
        $this->getLogger()->info("NAME: " . $name . " FILE: " . $fileLocation);

        $nbt = new CompoundTag();

        $motion = new Vector3(0, 0, 0);

        $nbt->Pos = new ListTag("Pos", [

            new DoubleTag("", $loc->getX()),
            new DoubleTag("", $loc->getY()),
            new DoubleTag("", $loc->getZ())

        ]);

        $nbt->Motion = new ListTag("Motion", [

            new DoubleTag("", $motion->x),
            new DoubleTag("", $motion->y),
            new DoubleTag("", $motion->z)

        ]);

        $nbt->Health = new ShortTag("Health", 1);
        $nbt->NameTag = new StringTag("name", $name);
        $nbt->Invulnerable = true;
        $nbt->offsetSet("NPC", true);
        $nbt->NPC = new StringTag("NPC", "true");

        $npc = Entity::createEntity("Human", $loc->getLevel(), $nbt);
        if ($npc instanceof Human) {
            $npc->canCollide = false;
            $npc->setSkin($this->skinUtils->retrieveSkinData($player), "char");
            //http://samueljh1.net/samcraft/img.php?url=http://i.imgur.com/PjTd2cj.png
            $inv = $npc->getInventory();
            if ($player->getInventory()->getHelmet() != Item::get(Item::AIR))
                $inv->setHelmet($player->getInventory()->getHelmet());
            if ($player->getInventory()->getChestplate() != Item::get(Item::AIR))
                $inv->setChestplate($player->getInventory()->getChestplate());
            if ($player->getInventory()->getLeggings() != Item::get(Item::AIR))
                $inv->setLeggings($player->getInventory()->getLeggings());
            if ($player->getInventory()->getBoots() != Item::get(Item::AIR))
                $inv->setBoots($player->getInventory()->getBoots());
            if ($player->getInventory()->getItemInHand() != Item::get(Item::AIR))
                $inv->setItemInHand($player->getInventory()->getItemInHand());
            $loc->getLevel()->addEntity($npc);
            $npc->teleport($player->getPosition(), $player->getYaw(), $player->getPitch());
//            $npc->spawnToAll();
        }
    }


    public function loadKits() {
        foreach ($this->cfg->get("kits") as $key => $w) {
            array_push($this->kits, new Kit($key));
        }
    }


    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        switch (strtolower($command->getName())) {
            case "debug":
                switch ($args[0]) {
                    case "npc":
                        $skin = $args[1];
                        $nameTag = $args[2];
                        if ($sender instanceof Player) {
                            $this->spawnNPC($nameTag, $skin, $sender);
                            $sender->sendMessage("NPC SPAWNED");
                        }
                        return true;
                        break;
                    case "spawn":
                        if ($sender instanceof Player) {
                            $level = $sender->getLevel();
                            if (!is_null($level)) {
                                $this->cfg->setNested("spawn.x", $sender->getLocation()->getX());
                                $this->cfg->setNested("spawn.y", $sender->getLocation()->getY());
                                $this->cfg->setNested("spawn.z", $sender->getLocation()->getZ());
                                $this->cfg->setNested("spawn.yaw", $sender->getLocation()->getYaw());
                                $this->cfg->setNested("spawn.pitch", $sender->getLocation()->getPitch());
                                $this->cfg->save();
                                $sender->sendMessage("Spawn set!");
                                $this->spawn = $sender->getLocation();
                                return true;

                            } else {
                                $sender->sendMessage("Level Null");
                            }
                        }
                        return true;
                        break;
                }
                break;
            case "kit":
                if (sizeof($args) == 0) {

                    /**
                     * @var Kit $kit
                     */

                    $sender->sendMessage(TextFormat::GREEN . "Kits: ");

                    foreach ($this->kits as $kit) {
                        $sender->sendMessage(TextFormat::YELLOW . "  " . $kit->kitName);
                    }

                    return true;
                }
                if ($sender instanceof Player) {
                    $kit = $this->getKit($args[0]);
                    if ($kit != null) {
                        $kit->applyTo($sender);
                    } else {
                        $sender->sendMessage("There is no " . $args[0] . " Kit!");
                    }
                    return true;
                }
                break;
        }
        return parent::onCommand($sender, $command, $label, $args);
    }

    /**
     * @param $kitName
     * @return Kit
     */
    private function getKit($kitName) {
        /**
         * @var Kit $kit
         */
        foreach ($this->kits as $kit) {
            if (strtolower($kit->kitName) == strtolower($kitName) || strtolower($kit->kitIdentifier) == strtolower($kitName)) {
                return $kit;
            }
        }

        return null;
    }


}