<?php
/**
 * Created by PhpStorm.
 * User: chase
 * Date: 8/11/2017
 * Time: 3:18 PM
 */

namespace com\shmozo\shmitpvp;


use pocketmine\item\Armor;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class Kit {

    /**
     * @var string
     */
    public $kitName;
    /**
     * @var int
     */
    public $cooldown;
    /**
     * @var array
     */
    public $items = array();
    /**
     * @var string
     */
    public $kitIdentifier;

    /**
     * Kit constructor.
     *
     * @param string $kitId
     */
    public function __construct($kitId) {

        $this->kitIdentifier = $kitId;
        $var = "kits." . $kitId . ".name";
        ShmitPVP::getInstance()->getLogger()->info($var);
        $this->kitName = ShmitPVP::getInstance()->cfg->getNested($var, $kitId);

        $this->kitName = str_replace("&", "§", $this->kitName);


        ShmitPVP::getInstance()->getLogger()->info("   LOADED KIT: " . $this->kitName);

        foreach (ShmitPVP::getInstance()->cfg->getNested("kits." . $kitId . ".items") as $itemLine) {
            ShmitPVP::getInstance()->getLogger()->info($itemLine);
            $metaData = -1;

            $elements = explode(" ", $itemLine);
            if (sizeof($elements) == 0) {
                return;
            }

            try {
                if (strpos($elements[0], ":") !== false) {
                    $itemID = intval(substr($elements[0], 0, strrpos($elements[0], ":")));
                    $metaData = intval(substr($elements[0], 0, strrpos($elements[0], ":") + 1));
                } else {
                    $itemID = intval($elements[0]);
                }
            } catch (\Exception $exception) {
                $itemID = Item::STICK;
            }
            $amount = intval($elements[1]);
            /** @var \pocketmine\item\Item $item */
            $item = Item::get($itemID);
            if ($metaData != -1) {
                $item = Item::get($itemID, $metaData);
            }
            $item->setCount($amount);
            for ($i = 2; $i < sizeof($elements); $i++) {
                $current = $elements[$i];
                ShmitPVP::getInstance()->getLogger()->info("CURRENT: " . $current);
                if (strpos($current, "name:") === 0) {
                    ShmitPVP::getInstance()->getLogger()->info("CONTAINS NAME:");
                    $name = substr($current, strpos($current, ":") + 1, strlen($current));
                    ShmitPVP::getInstance()->getLogger()->info("  NAME:" . $name);

                    $item->setCustomName($name);
                } else if (strpos($current, "lore:") === 0) {
                    $loreString = substr($current, strpos($current, ":") + 1, strlen($current));

                    $lore = array();
                    ShmitPVP::getInstance()->getLogger()->info("  LORE: ");
                    foreach (explode("\\|", $loreString) as $line) {
                        ShmitPVP::getInstance()->getLogger()->info($line);
                        $finalLine = str_replace("_", " ", $line);
                        array_push($lore, $finalLine);
                    }
                    $item->setLore($lore);
                } else if ($this->getEnchant($current) != null) {
                    $enchant = $this->getEnchant($current);
                    $item->addEnchantment($enchant);
                } else if (strpos($current, "color")) {
                    //TODO
                }
            }

            array_push($this->items, $item);

        }

    }

    /**
     * @param $enchantName
     * @return Enchantment
     */

    public function getEnchant($enchantName) {
        return Enchantment::getEnchantmentByName($enchantName);
    }

    public function applyTo(Player $player) {
        $player->getInventory()->clearAll();
        /**
         * @var Item $item
         */
        foreach ($this->items as $item) {

            if ($item instanceof Armor) {
                if (strpos($item->getName(), "Helmet") || strpos($item->getName(), "Cap")) {
                    $player->getInventory()->setHelmet($item);
                    continue;
                } else if (strpos($item->getName(), "Chestplate") || strpos($item->getName(), "Tunic")) {
                    $player->getInventory()->setChestplate($item);
                    continue;
                } else if (strpos($item->getName(), "Leggings") || strpos($item->getName(), "Pants")) {
                    $player->getInventory()->setLeggings($item);
                    continue;
                } else if (strpos($item->getName(), "Boots")) {
                    $player->getInventory()->setBoots($item);
                    continue;
                } else {
                    $player->sendMessage($item->getName());
                }
            }


            $player->getInventory()->addItem($item);
        }
        $player->sendMessage(TextFormat::GREEN . "Applied " . TextFormat::YELLOW . $this->kitName . TextFormat::GREEN . " kit!");
    }
}