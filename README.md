## CAPTURE THE FLAG - MINI-GAME

- [x] Altay (WORKING) 1.2-1.6
- [x] PMMP (WORKING) 1.2-1.6

<pre>
===============================================================================================

@TODO
- Optimize arena rrendering time in Windows
- Fix any bugs in v1.5 
- update language translations
- Prepare Design for Version 2.0 

* Always welcome suggestions in improve game features and code optimizations


Previous version fixes
===============================================================================================
- team member can not break own team flag
- when a team member carry a enermy flag dead, enemy flag return to original location
- game starts when only have members join
===============================================================================================
This is a new MCPE version of Capture the flag mini-game. 
Your goal is to capture the enemy's flag. 
The enemy's flag will be on a fence post in or near their base. 
Break into the enemy's base and steal their flag. 

Testing Status:  Verified 
-Minecraft 1.2 - 1.6
===============================================================================================

How To Play?
----------------------------------------------------------------------------------------------


SETUP
------------------------------
1. Administrator/Ops can continue use existing arena or reset "/ctf create" if needed     
   Please see note below:    

PLAY | JOINING
------------------------------

2. player go to game board then tap [new game] sign, if busy then please wait. 

3. player select a team to join RED or BLUE sign, tap to join
   note: default maximum 10 players per team, change in config file

4. On joining player 

   4.1 player automatically equip with equal armors, bows, arrows and food
      - Red Team armor is Chainmail 
      - Blue team armor is Iron 
        
        note: both armors have same capabilities

   4.2 player display name tag will show along the team join and player name 
     eg. 
       -  Blue Team | mcpad19
       -  Red Team | crafter99
 
   4.3 player will be transport to selected team flag base. 
       the empty white block, next to yoru team flag is reserved to place enermy flag   
   
   4.4 player scout the area, avoid lava holes and fence is up before game start
    
   4.5 two team border fence get remove when the game start   
 
Alternatives: 
players can also join/leave/start/stop the game using commands. 
recommend way is use signs / color blocks
 
GAME START 
------------------------------
5. When all players on each team join the game, then when agree to start. 
   one player goto border and tap [GREEN] block to start the game
   after game start and fire lift up, GREEN button is gone. 

       [GREEN] block  -- start the game 
       [YELLOW] block -- leave the game 
       [BLUE] block   -- stop the game   

OBJECTIVE
------------------------------
6. Your goal is capture the enermy flag, first break the flag then safely move back to your team base and place next to your team flag.   

7. Your team got one point for each win, there are total of 3 rounds for each game. this can be change in configuration.

8. On end of each round, team member of each team moved back to own base, fence is up and open again in 350 ticks for next round.

9. When all 3 rounds finished, the game stop automatically and players will be teleport out to the game board.

10. game inventory will also remove 

DEATH
------------------------------
When you die,during the play , you can join back to your team. equipments will be added automatically on joining.


INSTALLATION and SETUP OPTIONS
------------------------------
Option #1  (Recommend)
download the demo maps and drop this plugin in server folder. 
you are ready to go. 

Option #2
download this plugin, drop to server folder. 
use admin console issue command /ctf create
Customized, location of signin/exit

DOWNLOADS
--------------------------------------------------------------------------
CTF World 
http://www.mediafire.com/download/bwj0y4gkgfj2d9i/world_CTF.zip


KNOW ISSUES
=====================
- switch gamemode in-game crash minecraft pe
- player in different game mode can not see each other



</pre>

Thanks
many1337
