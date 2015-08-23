<?php
include("res/php/Box.php");
include("res/php/Color.php");
include("res/php/SteamUserFunctions.php");

use GDText\Box;
use GDText\Color;

function banner_recentmatch() {
	header("Content-type: image/png");

	// account to be scanned and API key.
	$scan_acc = "your_steam3id";
	$apikey = "your_api_key";
	
	// names and amount of heroes from the static heroes.json file
	$heroes = json_decode(file_get_contents("res/heroes.json"), true);
	$maxval = count($heroes["heroes"]);
	
	// the 1st req gets recent matches from the acc $scan_acc, $match_id is the most recent match
	$req1 = "https://api.steampowered.com/IDOTA2Match_570/GetMatchHistory/V001/?account_id=" . $scan_acc . "&matches_requested=1&key=" . $apikey;
	@$data1 = json_decode(file_get_contents($req1), true);
	$match_id = $data1["result"]["matches"][0]["match_id"];

	// if no cache exists, create an image
	if(!file_exists("cache/recentmatch/$match_id.png")) {
		
		// the 2nd req gets details from the match $match_id
		$req2 = "https://api.steampowered.com/IDOTA2Match_570/GetMatchDetails/V001/?match_id=" . $match_id . "&key=" . $apikey;
		@$data2 = json_decode(file_get_contents($req2), true);

		// checks which side won, echoes game result
		if($data2["result"]["radiant_win"] == true) $radiant_win = true; else $radiant_win = false;

		// setting gpm and xpm values, to be incremented later in loop; sets $acc_req to be empty for now.
		$gpm = 0;
		$xpm = 0;
		$acc_req = "";

		// loop to get each players gpm/xpm, account id (CommunityID), echoes hero images, and resets gpm/xpm after one sides average has been calculated.
		for($x=0; $x < 10; $x++) {
			$gpm += $data2["result"]["players"][$x]["gold_per_min"];
			$xpm += $data2["result"]["players"][$x]["xp_per_min"];
			$hero_id = $data2["result"]["players"][$x]["hero_id"];

			$acc_id = toCommunityID($data2["result"]["players"][$x]["account_id"]);
			$players[$x]["id"] = $acc_id;

			$acc_req .= $acc_id . ",";

			for($i=0; $i < $maxval; $i++) {
				if($hero_id == $heroes["heroes"][$i]["id"]) {
					$hero_name = $heroes["heroes"][$i]["name"];
					$players[$x]["hero"] = $hero_name;
				}
			}

			if($x == 4){
				$radiant_gpm = round($gpm / 5);
				$radiant_xpm = round($xpm / 5);
				$gpm = 0;
				$xpm = 0;
			}
		}

		// the 3rd request gets personanames from Steam API
		$req3 = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=" . $apikey . "&steamids=" . $acc_req;
		@$data3 = json_decode(file_get_contents($req3), true);

		// counts public profiles, as private ones are hidden
		$maxval = count($data3["response"]["players"]);

		// writes name entry for $players when the profile is public
		// array_column is a PHP 5.5-only function, returns column of array_search
		for($i=0; $i < $maxval; $i++) { 
			$steamid = $data3["response"]["players"][$i]["steamid"];
			$column = array_search($steamid, array_column($players, "id"));
			$players[$column]["name"] = $data3["response"]["players"][$i]["personaname"];
		}

		// the loop stored the Radiant gpm/xpm, after the loop Dire is stored
		$dire_gpm = round($gpm / 5);
		$dire_xpm = round($xpm / 5);

		// converts durations to human readable form
		$start_time = gmdate("d-M, H:i:s",$data2["result"]["start_time"]);
		$duration = $data2["result"]["duration"];
		if($duration >= 3600) {
			$duration = gmdate("H:i:s", $duration);
		} else {
			$duration = gmdate("i:s", $duration);
		}

		// first blood can be a negative value. if $fbseconds value is negative a minus is added.
		$fbseconds = $data2["result"]["first_blood_time"];
		$firstblood = gmdate("i:s", abs($fbseconds));

		if($fbseconds < 0) {
			$firstblood = "-" . $firstblood;
		}

		// sets the victory message
		if($radiant_win == true) $wintext = "Radiant Victory!"; else $wintext = "Dire Victory!";

		// GD stuff: header declaration & static background
		$bg = imagecreatefrompng("images/recentmatch.png");

		// creating hero image array, filling it, copying & resampling the pictures correctly, flushing the memory
		for($i=0; $i < 10; $i++) { 
			$hero_images[$i] = imagecreatefrompng("images/dota_heroes/" . $players[$i]["hero"] . "_lg.png");
		}

		imagecopyresampled($bg, $hero_images[0], 20,  25,  0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[1], 160, 25,  0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[2], 300, 25,  0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[3], 90,  120, 0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[4], 230, 120, 0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[5], 655, 25,  0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[6], 795, 25,  0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[7], 935, 25,  0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[8], 725, 120, 0, 0, 125, 70, 205, 115);
		imagecopyresampled($bg, $hero_images[9], 865, 120, 0, 0, 125, 70, 205, 115);

		for($i=0; $i < 10; $i++) { 
			imagedestroy($hero_images[$i]);
		}

		// now comes the text stuff
		// main text settings
		$font1 = "fonts/GoudyTrajan.otf";
		$font2 = "fonts/Ubuntu-C.ttf";
		$col0 = imagecolorallocate($bg, 0, 128, 0);
		$col1 = imagecolorallocate($bg, 128, 0, 0);
		$col2 = imagecolorallocate($bg, 255, 255, 255);

		if($radiant_win == true) $wincolor = $col0; else $wincolor = $col1;

		// textbox 1: victory text, bottom center
		$tb1 = imagettfbbox(26, 0, $font1, $wintext);
		$x1 = ceil((1080 - $tb1[2]) / 2);
		imagettftext($bg, 26, 0, $x1, 248, $wincolor, $font1, $wintext);

		// textbox 2: radiant stats, bottom left
		$stats1 = $radiant_gpm . " GPM " . $radiant_xpm . " XPM";
		imagettftext($bg, 22, 0, 20, 245, $col2, $font1, $stats1);

		// textbox 3: dire stats, bottom right
		$stats2 = $dire_gpm . " GPM " . $dire_xpm . " XPM";
		$tb2 = imagettfbbox(22, 0, $font1, $stats2);
		$x2 = ceil(1060 - $tb2[2]);
		imagettftext($bg, 22, 0, $x2, 245, $col2, $font1, $stats2);

		// name textboxes: utilizing gd-text
		for($i=0; $i < 10; $i++) {
			if(isset($players[$i]["name"])) {
				if(strlen($players[$i]["name"]) <= 16) {
					$names[$i] = $players[$i]["name"];
				} else {
					$names[$i] = rtrim(substr($players[$i]["name"], 0, 16)) . "...";
				}
			} else {
				$names[$i] = "Anonymous";
			}
		}

		for($i=0; $i < 10; $i++) {
			$persona[$i] = new Box($bg);
			$persona[$i]->setFontFace($font2);
			$persona[$i]->setFontSize(16);
			$persona[$i]->setTextAlign('center', 'top');
			$persona[$i]->setFontColor(new Color(255, 255, 255));
		}

		$persona[0]->setBox(20, 97, 125, 20);
		$persona[1]->setBox(160, 97, 125, 20);
		$persona[2]->setBox(300, 97, 125, 20);
		$persona[3]->setBox(90, 192, 125, 20);
		$persona[4]->setBox(230, 192, 125, 20);
		$persona[5]->setBox(655, 97, 125, 20);
		$persona[6]->setBox(795, 97, 125, 20);
		$persona[7]->setBox(935, 97, 125, 20);
		$persona[8]->setBox(725, 192, 125, 20);
		$persona[9]->setBox(865, 192, 125, 20);

		for($i=0; $i < 10; $i++) {
			$persona[$i]->draw($names[$i]);
		}

		imagepng($bg, "cache/recentmatch/$match_id.png", 1);
	} else {

		// file list from cache
		$cache = scandir("cache/recentmatch/");

		// if there are > 5 files in cache, delete oldest
		if(count($cache) >= 5) {
			unlink("cache/recentmatch/" . $cache[2]);
		}

		// a file exists, get it from cache
		$bg = imagecreatefrompng("cache/recentmatch/$match_id.png");
	}

	// GD stuff is done. create image and flush memory
	imagepng($bg, NULL, 1);
	imagedestroy($bg);
}
?>