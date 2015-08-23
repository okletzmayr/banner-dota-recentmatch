<?php
/* Examples
toSteamID(25490879)                 // STEAM_0:1:12745439 
toSteamID(76561197985756607)        // STEAM_0:1:12745439 
toSteamID("STEAM_0:1:12745439")     // STEAM_0:1:12745439 

toUserID(25490879)                  // 25490879 
toUserID(76561197985756607)         // 25490879 
toUserID("STEAM_0:1:12745439")      // 25490879 

toCommunityID(25490879)             // 76561197985756607 
toCommunityID(76561197985756607)    // 76561197985756607 
toCommunityID("STEAM_0:1:12745439") // 76561197985756607 
*/

function toCommunityID($id) {
    if (preg_match('/^STEAM_/', $id)) {
        $parts = explode(':', $id);
        return bcadd(bcadd(bcmul($parts[2], '2'), '76561197960265728'), $parts[1]);
    } elseif (is_numeric($id) && strlen($id) < 16) {
        return bcadd($id, '76561197960265728');
    } else {
        return $id; // We have no idea what this is, so just return it.
    }
}
/* Don't have BC math?  Here's an example for ya' with bit-shifting instead
function toCommunityID($id, $type = 1, $instance = 1) {
    if (preg_match('/^STEAM_/', $id)) {
        $parts = explode(':', str_replace('STEAM_', '', $id));
        $universe = (int)$parts[0];
        if ($universe == 0)
            $universe = 1;
        $steamID = ($universe << 56) | ($type << 52) | ($instance << 32) | ((int)$parts[2] << 1) | (int)$parts[1];
        return $steamID;
    } elseif (is_numeric($id) && strlen($id) < 16) {
        return (1 << 56) | ($type << 52) | ($instance << 32) | $id;
    } else {
        return $id; // We have no idea what this is, so just return it.
    }
} */
function toSteamID($id) {
    if (is_numeric($id) && strlen($id) >= 16) {
        $z = bcdiv(bcsub($id, '76561197960265728'), '2');
    } elseif (is_numeric($id)) {
        $z = bcdiv($id, '2'); // Actually new User ID format
    } else {
        return $id; // We have no idea what this is, so just return it.
    }
    $y = bcmod($id, '2');
    return 'STEAM_0:' . $y . ':' . floor($z);
}
// UserID formatting wrappers not included. Ex: RESULT => [U:1:RESULT]
function toUserID($id) {
    if (preg_match('/^STEAM_/', $id)) {
        $split = explode(':', $id);
        return $split[2] * 2 + $split[1];
    } elseif (preg_match('/^765/', $id) && strlen($id) > 15) {
        return bcsub($id, '76561197960265728');
    } else {
        return $id; // We have no idea what this is, so just return it.
    }
}
?>