<?php
function call_lamp($ip, $channel, $action) {
    if (empty($ip) || empty($channel) || !in_array($action, ['on', 'off'], true)) {
        return false;
    }
    $url = "http://{$ip}/{$action}?ch={$channel}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    $response = curl_exec($ch);
    if ($response === false) {
        $response = curl_error($ch);
    }
    curl_close($ch);
    return $response;
}
?>
