<?php
$username = ""; // getpocket username NOT your email-address
$password = ""; // getpocket password
$handle = curl_init('http://getpocket.com/users/'.$username.'/feed/all');

curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($handle, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($handle, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($handle, CURLOPT_ENCODING, 'identity');
curl_setopt($handle, CURLOPT_USERPWD,$username.':'.$password);

$response = curl_exec($handle);
curl_close($handle);
$xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
$json = json_encode($xml);
$data = json_decode($json, true);

header('Content-Type: text/xml; charset=utf-8', true);

$rss = new SimpleXMLElement('<rss xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom"></rss>');
$rss->addAttribute('version', '2.0');

$channel = $rss->addChild('channel');

$atom = $channel->addChild('atom:atom:link');
$atom->addAttribute('href', 'https://niklas-schmitt.de');
$atom->addAttribute('rel', 'self');
$atom->addAttribute('type', 'application/rss+xml');

$title = $channel->addChild('title','RSS-Feed for getPocket');
$description = $channel->addChild('description','my own pocket rss-feed');
$link = $channel->addChild('link','http://www.niklas-schmitt.de');
$language = $channel->addChild('language','de');

//Create RFC822 Date format to comply with RFC822
$date_f = date("D, d M Y H:i:s T", time());
$build_date = gmdate(DATE_RFC2822, strtotime($date_f));
$lastBuildDate = $channel->addChild('lastBuildDate',$date_f);
$generator = $channel->addChild('generator','PHP Simple XML');

$watchlater = array(
	'youtube.com',
	'zdf.de',
	'ardmediathek.de',
	'media.ccc.de',
);


if(isset($data['channel']['item'])) {
	foreach($data['channel']['item'] as $pocketItem) {
		$item = $channel->addChild('item');
		$title = $item->addChild('title', $pocketItem['title']);
		$title = $item->addChild('author', 'Niklas');
		$link = $item->addChild('link', htmlspecialchars($pocketItem['link']));

		$description = $item->addChild('description', '<![CDATA[<div><h2>'.$pocketItem['title'].'</h2><p>'.htmlspecialchars($pocketItem['link']).'</p></div>]]>');
		$guid = $item->addChild('guid', htmlspecialchars($pocketItem['guid']));
		foreach($watchlater as $wl) {
			if (stristr(htmlspecialchars($pocketItem['link']), $wl) !== false) {
				$category = $item->addChild('category', 'Später ansehen');
			}
		}

		$content = $item->addChild('content','<![CDATA[<div><h2>'.$pocketItem['title'].'</h2><p>'.htmlspecialchars($pocketItem['link']).'</p></div>]]>');
		$item = $item->addChild('pubDate', $pocketItem['pubDate']);
	}
}
echo $rss->asXML();
?>
