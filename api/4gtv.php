<?php
$channel = $_GET['id'] ?? null;
$ts = $_GET['ts'] ?? null;

if ($channel)
    get_m3u8($channel);
if ($ts)
    get_ts($ts);

function get_m3u8($channel) {
    $url = "https://app.4gtv.tv/Data/HiNet/GetURL.ashx?Type=LIVE&Content={$channel}";
    $code = curl_get($url);
    $code = findString($code, "{", "}");
    $json = json_decode($code, true);
    $data = $json['VideoURL'];
    $key = "VxzAfiseH0AbLShkQOPwdsssw5KyLeuv";
    $iv = substr($data, 0, 16);
    $streamurl = openssl_decrypt(base64_decode(substr($data, 16)), "AES-256-CBC", $key, 1, $iv);
    $m3u8_list = curl_get($streamurl);
    $m3u8_arr = explode("\n", $m3u8_list);
    $count = count($m3u8_arr);
    $streamurl = $m3u8_arr[$count-2];
    
    $domain = get_domain($channel);
    $code = curl_get($domain.$streamurl);
    
    $baseUrl  = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
    $code = preg_replace_callback('/.*\.ts.*/', fn($m) => $baseUrl.'?ts='.base64_encode($m[0].'&'.$channel), $code);
    
    header("Content-Type: application/vnd.apple.mpegurl");
    header("Content-Disposition: inline; filename=index.m3u8");
    echo $code;
}

function get_ts($ts) {
    $ts = base64_decode($ts);
    $pos = strrpos($ts, "&");
    $channel = substr($ts, $pos + 1);
    $ts = substr($ts, 0, $pos);
    
    $domain = get_domain($channel);
    $ts_url = $domain.$ts;
    
    header("Content-type: video/mp2t");
    curl_get($ts_url, 0);
}

function get_domain($channel){
    $channel_ids = ['4gtv-live007', '4gtv-live025', '4gtv-live206', '4gtv-live208', '4gtv-live026', '4gtv-live027', '4gtv-live130'];
    if (in_array($channel, $channel_ids))
        return "https://4gtvfree-cds.cdn.hinet.net/live/pool/{$channel}/4gtv-live-mid/";
    else
        return "https://4gtvfreepc-cds.cdn.hinet.net/live/pool/{$channel}/4gtv-live-mid/";
}

function curl_get($url, $flag=1) {
    $header = [
        "User-Agent: okhttp/3.12.11"
    ];
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, $flag);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_ENCODING, '');
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}

function findString($str, $start, $end) {
    $from_pos = strpos($str, $start);
    $end_pos = strpos($str, $end);
    return substr($str, $from_pos, ($end_pos - $from_pos + 1));
}
