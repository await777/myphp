<?php
$channel = $_GET['id'] ?? null;
$m3u8 = $_GET['m3u8'] ?? null;
$ts = $_GET['ts'] ?? null;

if ($channel)
    get_channel($channel);

if ($m3u8)
    get_m3u8($m3u8);

if ($ts)
    get_ts($ts);

function get_channel($channel) {
    $url = "https://www.youtube.com/watch?v={$channel}";
    $data = curl_get($url);

    $re = '/"hlsManifestUrl":"(.*?)"}/';
    preg_match($re, $data, $hlsUrl);
    $streamurl = $hlsUrl[1];

    $m3u8_list = curl_get($streamurl);
    $m3u8_arr = explode("\n", $m3u8_list);

    $sublist = array_slice($m3u8_arr, -3);

    $code =  "#EXTM3U". PHP_EOL . "#EXT-X-VERSION:3". PHP_EOL . implode("\n",$sublist);
    $baseUrl  = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
    $code = preg_replace_callback('/.*\.m3u8.*/', fn($m) => $baseUrl.'?m3u8='.base64_encode($m[0]), $code);
    
    if (strstr($code, "That’s an error.")) {
         get_channel($channel);
    } else {
        header("Content-Type: application/vnd.apple.mpegurl");
        header("Content-Disposition: attachment; filename=index.m3u8");
        echo $code;
    }
}

function get_m3u8($m3u8) {
    $m3u8 = base64_decode($m3u8);
    $code = curl_get($m3u8);
    $baseUrl  = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
    $code = preg_replace_callback('/.*\.ts.*/', fn($m) => $baseUrl.'?ts='.base64_encode($m[0]), $code);

    header("Content-Type: application/vnd.apple.mpegurl");
    header("Content-Disposition: inline; filename=index.m3u8");
    echo $code;
}



function get_ts($ts) {
    $ts = base64_decode($ts);

    header("Content-type: video/mp2t");
    curl_get($ts, 0);
}


function curl_get($url, $flag = 1) {
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
    // curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1'); //http代理服务器地址
    // curl_setopt($curl, CURLOPT_PROXYPORT, '10808'); //http代理服务器端口
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_ENCODING, '');
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}
