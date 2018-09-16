<?php
/**
 *
 *                                              $$\   $$$$$$\   $$$$$$\  $$$$$$$$\
 *                                            $$$$ | $$ ___$$\ $$ ___$$\ \____$$  |
 *$$$$$$\$$$$\   $$$$$$\  $$$$$$$\  $$\   $$\ \_$$ | \_/   $$ |\_/   $$ |    $$  /
 *$$  _$$  _$$\  \____$$\ $$  __$$\ $$ |  $$ |  $$ |   $$$$$ /   $$$$$ /    $$  /
 *$$ / $$ / $$ | $$$$$$$ |$$ |  $$ |$$ |  $$ |  $$ |   \___$$\   \___$$\   $$  /
 *$$ | $$ | $$ |$$  __$$ |$$ |  $$ |$$ |  $$ |  $$ | $$\   $$ |$$\   $$ | $$  /
 *$$ | $$ | $$ |\$$$$$$$ |$$ |  $$ |\$$$$$$$ |$$$$$$\\$$$$$$  |\$$$$$$  |$$  /
 *\__| \__| \__| \_______|\__|  \__| \____$$ |\______|\______/  \______/ \__/
 *                                  $$\   $$ |
 *                                  \$$$$$$  |
 *                                   \______/
 *
 */
namespace many1337\CTF;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\entity\Effect;
use pocketmine\tile\Chest;
use pocketmine\inventory\ChestInventory;
use onebone\economyapi\EconomyAPI;

class CTF extends PluginBase implements Listener {

        public $title = "§a§l[§6CTF§a]§e";
        public $reds = [ ];
        public $blues = [ ];
        public $arena = array();
        public $level = "";
        public $mode = 0;
        public $drops = array();
        
	public function onEnable()
	{
        $this->getLogger()->info("CTF by: many1337❤");
                $this->getServer()->getPluginManager()->registerEvents($this ,$this);
                $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                    if(!empty($this->economy))
                    {
                    $this->api = EconomyAPI::getInstance ();
                    }
                @mkdir($this->getDataFolder());
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		if($config->get("arenas")!=null)
		{
			$this->arena = $config->get("arenas");
		}
		foreach($this->arena as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
                $config->save();
                $this->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 10);
        }
        
        public function onDamage(EntityDamageEvent $event) {
            if ($event instanceof EntityDamageByEntityEvent) {
                if ($event->getEntity() instanceof Player && $event->getDamager() instanceof Player) {
                     if ( isset($this->reds[$event->getEntity()->getName()]) && isset($this->reds[$event->getDamager()->getName()]))
                         {
                         $event->setCancelled(true);
                         }
                     elseif( isset($this->blues[$event->getEntity()->getName()]) && isset($this->blues[$event->getDamager()->getName()])) {
                        $event->setCancelled(true);
                    }
                }
            }
        }
        
        public function onRespawn(PlayerRespawnEvent $event) {
        $player = $event->getPlayer();
        $mapa = $player->getLevel()->getFolderName();
        if(in_array($mapa,$this->arena))
            {
            if($player instanceof Player){
                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                $level = $this->getServer()->getLevelByName($mapa);
                if(isset($this->blues[$player->getName()]))
                {
                    $thespawn = $config->get($mapa . "SpawnBLUE");
                }
                else if(isset($this->reds[$player->getName()]))
                {
                    $thespawn = $config->get($mapa . "SpawnRED");
                }
                $event->setRespawnPosition(new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$level));
                if (isset($this->drops[$player->getName()])) {
			$player->getInventory()->setContents($this->drops[$player->getName()][0]);
			$player->getInventory()->setArmorContents($this->drops[$player->getName()][1]);
			unset($this->drops[$player->getName()]);
                }
		}
            }
        }
        
        public function onQuit(PlayerQuitEvent $event)
        {
            $player = $event->getPlayer();
            $mapa = $player->getLevel()->getFolderName();
            if(in_array($mapa,$this->arena))
            {
                if (isset($this->reds[$player->getName()]))
                    {
			$player->getServer()->broadcastMessage($player->getName()."§6 has left the game");
			unset ($this->reds[$player->getName()]);
                    }
                else if (isset($this->blues[$player->getName()]))
                    {
			$player->getServer()->broadcastMessage($player->getName()."§6 has left the game");
			unset ($this->blues[$player->getName()]);
                    }
            }
        }
        
        public function onDeath(PlayerDeathEvent $evento){
            $muerto = $evento->getEntity();
            $mapa = $muerto->getLevel()->getFolderName();
            $level = $muerto->getLevel();
            if(in_array($mapa,$this->arena))
            {
            $cause = $evento->getEntity()->getLastDamageCause();
            $muerto->getInventory()->remove(Item::get(35, 11, 1));
            $muerto->getInventory()->remove(Item::get(35, 14, 1));
            $this->drops[$muerto->getName()][1] = $muerto->getInventory()->getArmorContents();
            $this->drops[$muerto->getName()][0] = $muerto->getInventory()->getContents();
            $evento->setDrops(array());
            if($cause instanceof EntityDamageByEntityEvent)
            {
            $asassin = $cause->getDamager();
            if($asassin instanceof Player)
            {
            $evento->setDeathMessage("");
                foreach($level->getPlayers() as $pl)
                            {
                                    $killed = $muerto->getNameTag();
                                    $asesino = $asassin->getNameTag();
                                    $pl->sendMessage($killed . TextFormat::DARK_AQUA . " was killed by " . $asesino . TextFormat::WHITE . ".");
                            }
            }
            }
            }
        }
        
        public function onMove(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arena))
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$sofar = $config->get($level . "StartTime");
			if($sofar > 0)
			{
                            $lvl = $this->getServer()->getLevelByName($level);
                            if(isset($this->blues[$player->getName()]))
                            {
                                $thespawn = $config->get($level . "SpawnBLUE");
                                $spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$lvl);
                                $x = $player->getPosition()->x;
                                $z = $player->getPosition()->z;
                            }
                            else if(isset($this->reds[$player->getName()]))
                            {
                                $thespawn = $config->get($level . "SpawnRED");
                                $spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$lvl);
                                $x = $player->getPosition()->x;
                                $z = $player->getPosition()->z;
                            }
                            if (($x>$thespawn[0]+12) || ($x<$thespawn[0]-12) || ($z>$thespawn[2]+12) || ($z<$thespawn[2]-12))
                            {
                                $spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$lvl);
                                $lvl->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
				$player->teleport($spawn,0,0);
                                
                            }
			}
		}
	}
        
        public function onBlockBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arena))
		{
                    if(($event->getBlock()->getId() == 35) && ($event->getBlock()->getDamage() == 11))
                    {
                        if(isset($this->blues[$player->getName()]))
                            {
                                $event->setCancelled(true);
                            }
                            else if(isset($this->reds[$player->getName()]))
                            {
                                if(!$player->getInventory()->contains(Item::get(35, 11, 1)))
                                {
                                $player->getLevel()->dropItem($player,Item::get(35, 11, 1));
                                }
                            }
                    }
                    if(($event->getBlock()->getId() == 35) && ($event->getBlock()->getDamage() == 14))
                    {
                        if(isset($this->blues[$player->getName()]))
                            {
                                if(!$player->getInventory()->contains(Item::get(35, 14, 1)))
                                {
                                $player->getLevel()->dropItem($player,Item::get(35, 14, 1));
                                }
                            }
                            else if(isset($this->reds[$player->getName()]))
                            {
                                $event->setCancelled(true);
                            }
                    }
                    $event->setCancelled(true);
		}
	}
	
	public function onBlockPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		$namemap = $player->getLevel()->getFolderName();
		if(in_array($namemap,$this->arena))
		{
                    $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                    $level = $this->getServer()->getLevelByName($namemap);
                    $blueflag = $config->get($namemap . "BLUEFLAG");
                    $redflag = $config->get($namemap . "REDFLAG");
                    if(($event->getBlock()->getId() == 35) && ($event->getBlock()->getDamage() == 11))
                    {
                        if(($event->getBlock()->getX()<$redflag[0]+1) && ($event->getBlock()->getZ()<$redflag[2]+1) && ($event->getBlock()->getX()>$redflag[0]-1) && ($event->getBlock()->getZ()>$redflag[2]-1))
                        {
                            $win = "red";
                            $this->finishgame($namemap, $win);
                        }
                        else
                        {
                            $event->setCancelled(true);
                        }
                    }
                    if(($event->getBlock()->getId() == 35) && ($event->getBlock()->getDamage() == 14))
                    {
                        if(($event->getBlock()->getX()<$blueflag[0]+1) && ($event->getBlock()->getZ()<$blueflag[2]+1) && ($event->getBlock()->getX()>$blueflag[0]-1) && ($event->getBlock()->getZ()>$blueflag[2]-1))
                        {
                            $win = "blue";
                            $this->finishgame($namemap, $win);
                        }
                        else
                        {
                            $event->setCancelled(true);
                        }
                    }
			$event->setCancelled(true);
		}
	}
        
        public function onCommand(CommandSender $player, Command $cmd, $label, array $args) : bool {
        switch($cmd->getName()){
			case "ctf":
				if($player->isOp())
				{
					if(!empty($args[0]))
					{
						if($args[0]=="make")
						{
							if(!empty($args[1]))
							{
								if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
								{
									$this->getServer()->loadLevel($args[1]);
									$this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
									array_push($this->arena,$args[1]);
									$this->level = $args[1];
									$this->mode = 1;
									$player->sendMessage($this->title . "Touch BLUE spawn!");
									$player->setGamemode(1);
									$player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(),0,0);
                                                                        $name = $args[1];
                                                                        $this->zipper($player, $name);
								}
								else
								{
									$player->sendMessage($this->title . "ERROR missing world.");
								}
							}
							else
							{
								$player->sendMessage($this->title . "ERROR missing parameters.");
							}
						}
						else
						{
							$player->sendMessage($this->title . "ERROR invalid command.");
						}
					}
					else
					{
						$player->sendMessage($this->title . "ctf make <arena>: New CTF game.");
                                                $player->sendMessage($this->title . "Tap Red and Blue Spawn and register a sign.");
					}
				}
				else
				{
                                    $player->sendMessage($this->title . "Missing Command.");
				}
			return true;
                        
                        case "ctfstart":
                            if($player->isOp())
				{
                                $player->sendMessage($this->title . "Starting in 10 seconds...");
                                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                $config->set("arenas",$this->arena);
                                foreach($this->arena as $arena)
                                {
                                        $config->set($arena . "StartTime", 10);
                                }
                                $config->save();
                                }
                                return true;

		}
	}
        
        public function onChat(PlayerChatEvent $event)
	{
		$player = $event->getPlayer();
		$message = $event->getMessage();
		if(isset($this->blues[$player->getName()]))
                {
                    $event->setFormat("§9" . $player->getName() . " §a:§9 " . $message);
                }
                else if(isset($this->reds[$player->getName()]))
                {
                    $event->setFormat("§c" . $player->getName() . " §a:§c " . $message);
                }
		
	}
        
        public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		
		if($tile instanceof Sign) 
		{
			if($this->mode==5)
			{
				$tile->setText(TextFormat::DARK_AQUA . "[Join]",TextFormat::YELLOW  . "0 / 12","§f" . $this->level,$this->title);
				$this->level = "";
				$this->mode = 0;
				$player->sendMessage($this->title . "ARENA REGISTERED!");
                                $this->refreshArenas();
			}
			else
			{
				$text = $tile->getText();
				if($text[3] == $this->title)
				{
					if($text[0]==TextFormat::DARK_AQUA . "[Join]")
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                                $namemap = str_replace("§f", "", $text[2]);
						$level = $this->getServer()->getLevelByName($namemap);
                                                if($text[1]==TextFormat::YELLOW  . "0 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnBLUE");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§9" . $name);
                                                $this->blues [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §9BLUE TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "2 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnBLUE");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§9" . $name);
                                                $this->blues [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §9BLUE TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "4 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnBLUE");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§9" . $name);
                                                $this->blues [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §9BLUE TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "6 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnBLUE");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§9" . $name);
                                                $this->blues [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §9BLUE TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "8 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnBLUE");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§9" . $name);
                                                $this->blues [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §9BLUE TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "10 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnBLUE");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§9" . $name);
                                                $this->blues [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §9BLUE TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "1 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnRED");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§c" . $name);
                                                $this->reds [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §cRED TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "3 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnRED");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§c" . $name);
                                                $this->reds [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §cRED TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "5 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnRED");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§c" . $name);
                                                $this->reds [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §cRED TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "7 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnRED");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§c" . $name);
                                                $this->reds [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §cRED TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "9 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnRED");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§c" . $name);
                                                $this->reds [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §cRED TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
                                                else if($text[1]==TextFormat::YELLOW  . "11 / 12")
                                                {
                                                $thespawn = $config->get($namemap . "SpawnRED");
                                                $name = $player->getName();
                                                $player->setNameTag("§l§c" . $name);
                                                $this->reds [$player->getName()] = $player;
                                                $player->sendMessage($this->title . "You are in §cRED TEAM");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($name . " §bhas joined in CTF");
                                                        }
                                                }
						$spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$level);
						$level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
						$player->teleport($spawn,0,0);
						$player->getInventory()->clearAll();
                                                $player->removeAllEffects();
                                                $player->setHealth(20);
                                                $player->getInventory()->setContents(array(Item::get(0, 0, 0)));
                                                $player->getInventory()->setHelmet(Item::get(Item::IRON_HELMET));
                                                $player->getInventory()->setChestplate(Item::get(Item::IRON_CHESTPLATE));
                                                $player->getInventory()->setLeggings(Item::get(Item::IRON_LEGGINGS));
                                                $player->getInventory()->setBoots(Item::get(Item::IRON_BOOTS));
                                                $player->getInventory()->setItem(0, Item::get(Item::IRON_SWORD, 0, 1));
                                                $player->getInventory()->setHotbarSlotIndex(0, 0);
					}
					else
					{
						$player->sendMessage($this->title . "You can't join");
					}
				}
			}
		}
		else if($this->mode==1)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->level . "SpawnBLUE", array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->title . "Spawn BLUE has been registered!");
                        $player->sendMessage($this->title . "Now touch the RED spawn.");
			$this->mode++;
			$config->save();
		}
                else if($this->mode==2)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->level . "SpawnRED", array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->title . "Spawn RED has been registered!");
                        $player->sendMessage($this->title . "Now touch the BLUE FLAG.");
			$this->mode++;
			$config->save();
		}
                else if($this->mode==3)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->level . "BLUEFLAG", array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->title . "BLUE FLAG has been registered!");
                        $player->sendMessage($this->title . "Now touch the RED FLAG.");
			$this->mode++;
			$config->save();
		}
                else if($this->mode==4)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->level . "REDFLAG", array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->title . "RED FLAG has been registered!");
                        $player->sendMessage($this->title . "Now touch the SIGN.");
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
			$player->teleport($spawn,0,0);
                        $this->mode=5;
			$config->save();
		}
	}
        
        public function refreshArenas()
	{
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		$config->set("arenas",$this->arena);
		foreach($this->arena as $arena)
		{
			$config->set($arena . "StartTime", 90);
		}
		$config->save();
	}
        
        public function zipper($player, $name)
        {
                                $path = realpath($player->getServer()->getDataPath() . 'worlds/' . $name);
				$zip = new \ZipArchive;
				@mkdir($this->getDataFolder() . 'arenas/', 0755);
				$zip->open($this->getDataFolder() . 'arenas/' . $name . '.zip', $zip::CREATE | $zip::OVERWRITE);
				$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($path),
					\RecursiveIteratorIterator::LEAVES_ONLY
				);
                                foreach ($files as $datos) {
					if (!$datos->isDir()) {
						$relativePath = $name . '/' . substr($datos, strlen($path) + 1);
						$zip->addFile($datos, $relativePath);
					}
				}
				$zip->close();
				$player->getServer()->loadLevel($name);
				unset($zip, $path, $files);
        }
        
        public function finishgame($level, $win)
        {
            $mundo = $this->getServer()->getLevelByName($level);
            $players = $mundo->getPlayers();
            if($win=="blue")
            {
            foreach($players as $pl)
            {
                foreach($this->getServer()->getOnlinePlayers() as $plpl)
                {
                    $plpl->sendMessage($this->title . "§9BLUE Team has Won");
                }
            $pl->getInventory()->clearAll();
            $pl->removeAllEffects();
            $pl->setNameTag($pl->getName());
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
            $pl->teleport($spawn,0,0);
            $pl->setHealth(20);
            if (isset($this->reds[$pl->getName()]))
                    {
			unset ($this->reds[$pl->getName()]);
                    }
            else if (isset($this->blues[$pl->getName()]))
                    {
			unset ($this->blues[$pl->getName()]);
                    }
            }
            }
            if($win=="red")
            {
            foreach($players as $pl)
            {
               foreach($this->getServer()->getOnlinePlayers() as $plpl)
                {
                    $plpl->sendMessage($this->title . "§cRED Team has Won");
                }
            $pl->getInventory()->clearAll();
            $pl->removeAllEffects();
            $pl->setNameTag($pl->getName());
            $spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
            $this->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
            $pl->teleport($spawn,0,0);
            $pl->setHealth(20);
            if (isset($this->reds[$pl->getName()]))
                    {
			unset ($this->reds[$pl->getName()]);
                    }
            else if (isset($this->blues[$pl->getName()]))
                    {
			unset ($this->blues[$pl->getName()]);
                    }
            }
            }
            
            if ($this->getServer()->isLevelLoaded($level))
            {
                    $this->getServer()->unloadLevel($mundo);
            }
            $zip = new \ZipArchive;
            $zip->open($this->getDataFolder() . 'arenas/' . $level . '.zip');
            $zip->extractTo($this->getServer()->getDataPath() . 'worlds');
            $zip->close();
            unset($zip);
            $this->getServer()->loadLevel($level);
            return true;
        }
}

class RefreshSigns extends PluginTask {
    public $title = "§a§l[§6CTF§a]§e";
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$allplayers = $this->plugin->getServer()->getOnlinePlayers();
		$level = $this->plugin->getServer()->getDefaultLevel();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[3]==$this->title)
				{
                                        $aop = 0;
                                        $namemap = str_replace("§f", "", $text[2]);
					foreach($allplayers as $player)
                                            {
                                            if($player->getLevel()->getFolderName()==$namemap)
                                                {
                                                $aop=$aop+1;
                                                }
                                            }
					$ingame = TextFormat::DARK_AQUA . "[Join]";
					$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($namemap . "StartTime")<=0)
					{
						$ingame = TextFormat::DARK_PURPLE . "[Running]";
					}
					else if($aop>=12)
					{
						$ingame = TextFormat::GOLD . "[Full]";
					}
					$t->setText($ingame,TextFormat::YELLOW  . $aop . " / 12",$text[2],$this->title);
				}
			}
		}
	}
}

class GameSender extends PluginTask {
    public $title = "§a§l[§6CTF§a]§e";
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$arenas = $config->get("arenas");
		if(!empty($arenas))
		{
			foreach($arenas as $arena)
			{
				$timeToStart = $config->get($arena . "StartTime");
				$levelArena = $this->plugin->getServer()->getLevelByName($arena);
				if($levelArena instanceof Level)
				{
					$playersArena = $levelArena->getPlayers();
					if(count($playersArena)==0)
					{
						$config->set($arena . "StartTime", 90);
					}
					else
					{
						if(count($playersArena)>=2)
						{
							if($timeToStart>0)
							{
								$timeToStart--;
								foreach($playersArena as $pl)
								{
									$pl->sendTip(TextFormat::GREEN . $timeToStart . " seconds to start");
								}
                                                                if($timeToStart==89)
                                                                {
                                                                    $levelArena->setTime(7000);
                                                                    $levelArena->stopTime();
                                                                }
                                                                $config->set($arena . "StartTime", $timeToStart);
							}
						}
						else
						{
                                                    foreach($playersArena as $pl)
                                                    {
                                                            $pl->sendTip(TextFormat::GOLD . "Need more players");
                                                    }
                                                    $config->set($arena . "StartTime", 90);
						}
					}
				}
			}
		}
		$config->save();
	}
}
