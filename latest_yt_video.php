<?php
error_reporting(E_ALL);
define('YT_API_URL', 'http://gdata.youtube.com/feeds/api/videos?q=');
$id = NULL;
$username = 'pflanzenhunger';

// zuerst besorgen wir uns die Video ID des letzten Videos
$xml = simplexml_load_file(sprintf('http://gdata.youtube.com/feeds/base/users/%s/uploads?alt=rss&v=2&orderby=published', $username));

if ( ! empty($xml->channel->item[0]->link) )
{
  parse_str(parse_url($xml->channel->item[0]->link, PHP_URL_QUERY), $url_query);

  if ( ! empty($url_query['v']) )
    $id = $url_query['v'];
}

$video_id = $id;

// wir nutzen cURL um mit der YouTube API zu kommunizieren
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, YT_API_URL . $video_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
// in $feed befindet sich der xml feed, welcher von der YouTube API stammt
$feed = curl_exec($ch);
curl_close($ch);
 
// Nun nehmen wir SimpleXML um den YouTube Feed auszulesen
$xml = simplexml_load_string($feed);
 
$entry = $xml->entry[0];
// Error Abfrage. Sollte eigentlich nicht eintreffen...
if(!$entry) exit('Fehler: Kein Video mit ID "' . $video_id . '" gefunden. Schau bitte nach was mit der ID nicht stimmt...');
$media = $entry->children('media', true);
$group = $media->group;
 
$title = $group->title;//$title: Videotitel
$desc = $group->description;//$desc: Videobeschreibung
$vid_keywords = $group->keywords;//$vid_keywords: Keywords
$thumb = $group->thumbnail[0];//Es gibt 4 thumbnails, das erste (index 0) ist das größte.
list($thumb_url, $thumb_width, $thumb_height, $thumb_time) = $thumb->attributes();
$content_attributes = $group->content->attributes();
$vid_duration = $content_attributes['duration'];
$duration_formatted = str_pad(floor($vid_duration/60), 2, '0', STR_PAD_LEFT) . ':' . str_pad($vid_duration%60, 2, '0', STR_PAD_LEFT);
 
// wir besorgen uns den "Click Counter" & den "Like Counter des aktuellen Videos
$JSON = file_get_contents("https://gdata.youtube.com/feeds/api/videos/$video_id?v=2&alt=json");
$JSON_Data = json_decode($JSON);
// Gesamte Klicks:
$views = $JSON_Data->{'entry'}->{'yt$statistics'}->{'viewCount'};
// Gesamte "Likes":
$likes = $JSON_Data->{'entry'}->{'yt$rating'}->{'numLikes'};

// wir bauen uns eine HTML ausgabe im PHP Script, damit das ergebnis direkt auf Seiten dargestellt werden kann
echo "<center>Unser aktuellstes Video:";
echo "<br>";
echo  '<a href="http://www.youtube.com/watch?v=' . $video_id . '" TARGET="_blank" ><img src="' . $thumb_url . '"></a>';
echo  "<br>";
echo  "&raquo; Titel: <i>$title</i> || Klicks: $views || Likes: $likes || L&auml;nge: $duration_formatted &laquo;</center>";
?>
