<?php
// 钉钉群机器人webhook地址
// 文档：https://open-doc.dingtalk.com/docs/doc.htm?spm=a219a.7629140.0.0.ytx9m4&treeId=257&articleId=105735&docType=1
const WEBHOOK = 'https://oapi.dingtalk.com/robot/send?access_token=xxxxxxx'; // TODO: 请自行获取
require __DIR__.'/Curl.php';

// 获取农历日期
require __DIR__.'/Lunar.php';
$lunar = new Lunar();
$lunar = $lunar->convertSolarToLunar(date('Y'), date('m'), date('d'));

// 将会发送到钉钉webhook的数据
$data = [
    'msgtype' => 'markdown',
    'markdown' => [
        'title' => '炉石早报',
        'text' => ''
    ]
];
$message = [ // 消息详情
    '各位炉石科技的伙伴们早上好！'
];

/**
 * 日期及农历
 */
$message[] = '今天是' . date('m月d日') . " 农历{$lunar[1]}{$lunar[2]}";

/**
 * 天气
 */
// 深圳天气详情页
const WEATHER_URL = 'http://www.weather.com.cn/weather/101280601.shtml';
$api = require(__DIR__.'/seniverse.php');
$c = new Curl($api);
$json = json_decode($c->get(), TRUE);

if(empty($json) || isset($json['status_code']) || !isset($json['results'][0]['daily'][0])){
    throw new Exception($json['status'], 500);
}

$json = $json['results'][0]['daily'][0]; // 只要今天天气
$message[] = "[天气：{$json['low']}-{$json['high']}° {$json['text_day']}](" . WEATHER_URL . ')';
$message[] = '【今日新闻】'; // 空一行

/**
 * 新闻
 */
const NEWS_URL = 'http://news.qq.com/newsgn/rss_newsgn.xml'; // 腾讯国内新闻详情页
$c = new Curl(NEWS_URL);
$xml = iconv('gb2312', 'utf-8//IGNORE', $c->get());

preg_match_all('|<title>(.*?)</title>\s*<link>(.*?)</link>|', $xml, $results);
$markdown = '';
for ($i=1; $i<6; $i++){ // 取前5条新闻
    $markdown .= "{$i}. [{$results[1][$i]}]({$results[2][$i]})" . '\r\n';
}
$message[] = $markdown;
$message[] = '【每日名言】';

/**
 * 每日一言
 * 文档：http://avatardata.cn/Docs/Api/5bc6f2a4-927c-415f-80ae-8772b76c8c73
 */
const MINGYAN = 'http://api.avatardata.cn/MingRenMingYan/Random?key=xxxxxx'; // TODO: 请自行获取
$c = new Curl(MINGYAN);
$json = json_decode($c->get(), TRUE);
if($json === NULL || $json['error_code'] != 0){
    throw new Exception($json['reason'], 500);
}
$message[] = $json['result']['famous_saying'] . ' ——' . $json['result']['famous_name'];

/**
 * 推送到钉钉
 */
// 原生markdown中两个换行才是真的换行，别听钉钉文档瞎吹，一个\n不行的
// 我在这儿被坑了超久
$data['markdown']['text'] = implode('\r\n\r\n', $message);
$data = json_encode($data);
// json编码后，上面的\r\n\r\n会变成\\r\\n\\r\\n，所以要替换成正常的状态
$data = str_replace('\\\\r\\\\n\\\\r\\\\n', '\r\n\r\n', $data);
$data = str_replace('\\\\r\\\\n', '\r\n', $data);

$c = new Curl(WEBHOOK);
$json = json_decode($c->post($data), TRUE);

if($json === NULL || $json['errcode'] != 0){
    throw new Exception($json['errmsg'], 500);
}