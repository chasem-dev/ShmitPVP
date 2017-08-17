<?php

namespace com\shmozo\shmitpvp\utils;

use com\shmozo\shmitpvp\ShmitPVP;
use pocketmine\entity\Human;
use pocketmine\Player;

class SkinUtils {


    /** @var array */
    public $skinData = [];


    /**
     * Converts a human's skin to slim(32x64) if $slim is true, if $slim is false it will convert to non-slim(64x64)
     * @param Human $human
     * @param bool $slim
     */
    public static function setSlim(Human $human, $slim = true) {
        $human->setSkin($human->getSkinData(), $slim);
    }

    /**
     * Compresses skin data, for efficient storage
     * @param string $data
     * @param int $level
     * @return string
     */
    public static function compress($data, $level = 9) {
        return zlib_encode($data, ZLIB_ENCODING_DEFLATE, $level);
    }

    /**
     * Decompresses skin data, prepares it for usage in the plugin
     * @param string $data
     * @return string
     */
    public static function decompress($data) {
        return zlib_decode($data);
    }

    /**
     * Checks if the data/image file for a human/player exists
     * @param Human $human
     * @param bool $isData
     * @return bool
     */
    public static function isFileCreated(Human $human, $isData = true) {
        return file_exists(ShmitPVP::getInstance()->getDataFolder() . ($isData ? "data" : "images") . "/" . strtolower($human->getName()) . ($isData ? ".dat" : ".png"));
    }

    /**
     * Retrieves skin data from a file previously created
     * @param Human $human
     * @return string|bool
     */
    public static function fromFile(Human $human) {
        if (self::isFileCreated($human)) {
            return self::decompress(file_get_contents(ShmitPVP::getInstance()->getDataFolder() . "data/" . strtolower($human->getName()) . ".dat"));
        }
        return false;
    }

    /**
     * Creates a new file containing skin data
     * @param Human $human
     */
    public static function toFile(Human $human) {
        @mkdir(ShmitPVP::getInstance()->getDataFolder() . "data/");
        file_put_contents(ShmitPVP::getInstance()->getDataFolder() . "data/" . strtolower($human->getName()) . ".dat", self::compress($human->getSkinData()));
    }


    /**
     * @param Player $player
     */
    public function storeSkinData(Player $player) {
        $this->skinData[strtolower($player->getName())] = $this->compress($player->getSkinData());
        if (!self::isFileCreated($player)) {
            self::toFile($player);
            ShmitPVP::getInstance()->getLogger()->info("CREATING PLAYER DATA FILE");
        }
    }

    /**
     * @param Player $player
     * @return string
     */
    public function retrieveSkinData(Player $player) {
        return $this->decompress($this->skinData[strtolower($player->getName())]);
    }

    /**
     * @param Player $player
     */
    public function removeSkinData(Player $player) {
        if ($this->isSkinStored($player)) {
            unset($this->skinData[strtolower($player->getName())]);
        }
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function isSkinStored(Player $player) {
        return $this->skinData[strtolower($player->getName())] !== null;
    }


}