<?php

define('BLARG', 1);

function fixyoutube($m)
{
	$url = $m[1];
	if (substr($url,0,4) != 'http')
		$url = 'http://www.youtube.com/watch?v='.$url;
	
	return '<a href=\"'.htmlspecialchars($url).'\">(video)</a>';
}

require(__DIR__ . '/lib/common.php');

$fid = Settings::get('newsForum');
if(!HasPermission('forum.viewforum', $fid))
	die("You aren't allowed to access this forum.");

$rFora = Query("select * from {forums} where id = {0}",$fid);
if(NumRows($rFora))
	$forum = Fetch($rFora);
else
	die("Unknown forum ID.");


header('Content-type: application/rss+xml');

print '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
	<channel>
		<title><?php $title = Settings::get('rssTitle');
		            echo htmlspecialchars($title); ?></title>

        /*$ishttps è un booleano che in questo punto non è stato inizializzato, così come non è stata inizializzata
        la variabile $serverport*/
		<link><?php $url = "http".($ishttps?'s':'')."://{$_SERVER['SERVER_NAME']}{$serverport}";
                        echo htmlspecialchars($url); ?></link>
		<description><?php $desc = Settings::get('rssDesc');
		                        echo htmlspecialchars($desc); ?></description>

        //$ishttps è un booleano che in questo punto non è stato inizializzato
		<atom:link href="<?php $fullurl = getServerURLNoSlash($ishttps);
                               echo htmlspecialchars($fullurl); ?>/rss.php" rel="self" type="application/rss+xml" />

<?php

    $fid = Settings::get('newsForum');

	$entries = Query("	SELECT 
							t.id, t.title, 
							p.date,
							pt.text,
							su.name uname, su.displayname udname
						FROM 
							{threads} t
							LEFT JOIN {posts} p ON p.thread=t.id AND p.id=t.firstpostid
							LEFT JOIN {posts_text} pt ON pt.pid=p.id AND pt.revision=p.currentrevision
							LEFT JOIN {users} su ON su.id=t.user
						WHERE t.forum={0} AND p.deleted=0
						ORDER BY p.date DESC LIMIT 5", $fid);
	
	while($entry = Fetch($entries))
	{
		$tags = ParseThreadTags($entry['title']);
		
		$title = htmlspecialchars($entry['title']);
		$username = $entry['udname'] ? $entry['udname'] : $entry['uname'];
		$rfcdate = htmlspecialchars(gmdate(DATE_RFC1123, $entry['date']));
		$entryurl = htmlspecialchars($url.actionLink('thread', $entry['id'], '', $tags[0]));
		
		$text = $entry['text'];
		$text = preg_replace_callback('@\[youtube\](.*?)\[/youtube\]@si', 'fixyoutube', $text);
		$text = preg_replace('@\[spoiler.*?\].*?\[/spoiler\]@si', '(spoiler)', $text);
		$text = CleanUpPost($text, $username, true);
		
		$text = preg_replace('@<img[^>]+?src\s*=\s*(["\'])(.*?)\\1[^>]*?>@si', '<a href="$2">(image)</a>', $text);
		$text = preg_replace('@<img[^>]+?src\s*=\s*([^\s>]+?)(\s+[^>]*?)?>@si', '<a href="$1">(image)</a>', $text);
		
		$text = preg_replace('@([="\'])\?page=@si', '$1'.$fullurl.'/?page=', $text);

		if(strpos($text,']]>')!==FALSE)
		    $text = str_replace(']]>', ']]&gt;', $text);
		
		$username = htmlspecialchars($username);
		
		echo "
		<item>
			<title>{$title} -- posted by {$username}</title>
			<link>{$entryurl}</link>
			<pubDate>{$rfcdate}</pubDate>
			<description><![CDATA[{$text}]]></description>
			<guid>{$entryurl}</guid>
		</item>
";
	}
?>
	</channel>
</rss>
