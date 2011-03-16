<?php

require 'Autolink.php';
require 'Extractor.php';

menu_register(array(
  '' => array(
    'callback' => 'twitter_home_page',
    'accesskey' => '0',
  ),
  'status' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_status_page',
  ),
  'update' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_update',
  ),
  'twitter-retweet' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_retweet',
  ),
  'twitter-comment' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_comment',
  ),
  'mentions' => array(
    'security' => true,
    'callback' => 'twitter_replies_page',
    'accesskey' => '1',
  ),
  'cmts' => array(
    'security' => true,
    'callback' => 'twitter_cmts_page',
    'accesskey' => '9',
  ),
  'favourite' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_mark_favourite_page',
  ),
  'unfavourite' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_mark_favourite_page',
  ),
  'directs' => array(
    'security' => true,
    'callback' => 'twitter_directs_page',
    'accesskey' => '2',
  ),
  'search' => array(
    'security' => true,
    'callback' => 'twitter_search_page',
    'accesskey' => '3',
  ),
  'public' => array(
    'security' => true,
    'callback' => 'twitter_public_page',
    'accesskey' => '4',
  ),
  'user' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_user_page',
  ),
  'follow' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_follow_page',
  ),
  'unfollow' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_follow_page',
  ),
  'confirm' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_confirmation_page',
  ),
  'block' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_block_page',
  ),
  'unblock' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_block_page',
  ),
  'spam' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_spam_page',
  ),
  'favourites' => array(
    'security' => true,
    'callback' =>  'twitter_favourites_page',
  ),
  'followers' => array(
    'security' => true,
    'callback' => 'twitter_followers_page',
  ),
  'friends' => array(
    'security' => true,
    'security' => true,
    'callback' => 'twitter_friends_page',
  ),
  'delete' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_delete_page',
  ),
  'retweet' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_retweet_page',
  ),
  'comment' => array(
    'hidden' => true,
    'security' => true,
    'callback' => 'twitter_comment_page',
  ),
  'flickr' => array(
    'security' => true,
    'hidden' => true,
    'callback' => 'generate_thumbnail',
  ),
  'moblog' => array(
    'security' => true,
    'hidden' => true,
    'callback' => 'generate_thumbnail',
  ),
  'hash' => array(
    'security' => true,
    'hidden' => true,
    'callback' => 'twitter_hashtag_page',
  ),
  'upload' => array(
    'security' => true,
    'hidden' => false,
    'callback' => 'twitter_upload_page',
  ),
  'trends' => array(
    'security' => true,
    'hidden' => true,
    'callback' => 'twitter_trends_page',
  )
));

function long_url($shortURL)
{
	if (!defined('LONGURL_KEY'))
	{
		return $shortURL;
	}
	$url = "http://www.longurlplease.com/api/v1.1?q=" . $shortURL;
	$curl_handle=curl_init();
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($curl_handle,CURLOPT_URL,$url);
	$url_json = curl_exec($curl_handle);
	curl_close($curl_handle);

	$url_array = json_decode($url_json,true);
	
	$url_long = $url_array["$shortURL"];
	
	if ($url_long == null)
	{
		return $shortURL;
	}
	
	return $url_long;
}


function friendship_exists($user_a) {
  $request = 'http://twitter.com/friendships/show.json?target_screen_name=' . $user_a;
  $following = twitter_process($request);
  
  if ($following->relationship->target->following == 1) {
    return true;
  } else {
    return false;
  }
}

function twitter_block_exists($query) 
{
	//http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-blocks-blocking-ids
	//Get an array of all ids the authenticated user is blocking
	$request = 'http://twitter.com/blocks/blocking/ids.json';
	$blocked = (array) twitter_process($request);
	
	//bool in_array  ( mixed $needle  , array $haystack  [, bool $strict  ] )		
	//If the authenticate user has blocked $query it will appear in the array
	return in_array($query,$blocked);
}

function twitter_trends_page($query) 
{
  $trend_type = $query[1];
  if($trend_type == '') $trend_type = 'current';
  $request = 'http://search.twitter.com/trends/' . $trend_type . '.json';
  $trends = twitter_process($request);
  $search_url = 'search?query=';
  foreach($trends->trends as $temp) {
    foreach($temp as $trend) {
      $row = array('<strong><a href="' . $search_url . urlencode($trend->query) . '">' . $trend->name . '</a></strong>');
      $rows[] = $row;
    }
  }
  //$headers = array('<p><a href="trends">Current</a> | <a href="trends/daily">Daily</a> | <a href="trends/weekly">Weekly</a></p>'); //output for daily and weekly not great at the moment
  $headers = array();
  $content = theme('table', $headers, $rows, array('class' => 'timeline'));
  theme('page', 'Trends', $content);
}

function js_counter($name, $length='140')
{
	$script = '<script type="text/javascript">
function updateCount() {
var remaining = ' . $length . ' - document.getElementById("' . $name . '").value.length;
document.getElementById("remaining").innerHTML = remaining;
if(remaining < 0) {
 var colour = "#FF0000";
 var weight = "bold";
} else {
 var colour = "";
 var weight = "";
}
document.getElementById("remaining").style.color = colour;
document.getElementById("remaining").style.fontWeight = weight;
setTimeout(updateCount, 400);
}
updateCount();
</script>';
	return $script;
}

function twitter_upload_page($query) {
  if (user_type() == 'oauth') {
    return theme('page', 'Error', '<p>You can\'t use Twitpic uploads while accessing Dabr using an OAuth login.</p>');
  }
  if ($_POST['message']) {
    $response = twitter_process('http://api.t.sina.com.cn/statuses/upload.xml', array(
      'pic' => '@'.$_FILES['media']['tmp_name'],
      'status' => stripslashes($_POST['message']),
      //'username' => user_current_username(),
      //'password' => $GLOBALS['user']['password'],
    ));
    if (preg_match('#thumbnail_pic>(.*)</thumbnail_pic#', $response, $matches)) {
      $id = $matches[1];
      twitter_refresh("upload/confirm/$id");
    } else {
      twitter_refresh('upload/fail');
    }
  } elseif ($query[1] == 'confirm') {
    $content = "<p>Upload success.</p><p><img src='{$id}' alt='' /></p>";
  } elseif ($query[1] == 'fail') {
    $content = '<p>Twitpic upload failed. No idea why!</p>';
  } else {
    $content = '<form method="post" action="upload" enctype="multipart/form-data">Image <input type="file" name="media" /><br />Message: <textarea name="message" cols="80" rows="6"></textarea><br /><input type="submit" value="Upload" /></form>';
  }
  return theme('page', 'Twitpic Upload', $content);
}

function endsWith( $str, $sub ) {
  return ( substr( $str, strlen( $str ) - strlen( $sub ) ) == $sub );
}

function twitter_process($url, $post_data = false) {
    $url = str_replace("https://api.twitter.com/", "http://api.t.sina.com.cn/", $url);
    $url = str_replace("http://api.twitter.com/", "http://api.t.sina.com.cn/", $url);
    $url = str_replace("://twitter.com/", "://api.t.sina.com.cn/", $url);

	if ($post_data === true)
	{
		$post_data = array();
	}
	if (user_type() == 'oauth' && ( strpos($url, 'sina.com.cn') !== false ))
	{
		user_oauth_sign($url, $post_data);
    }
    elseif (strpos($url, 'api.t.sina.com.cn') !== false && is_array($post_data)) {
    // Passing $post_data as an array to twitter.com (non-oauth) causes an error :(
    $s = array();
    foreach ($post_data as $name => $value)
      $s[] = $name.'='.urlencode($value);
    $post_data = implode('&', $s);
  }
  #if (! isset($post_data['source'])) {
    if (endsWith($url, ".json") || endsWith($url, ".xml"))
        $url = $url . "?";
    else
        $url = $url . "&";
    $url = $url . "source=" . OAUTH_CONSUMER_KEY;
  #}
  $ch = curl_init($url);

  if($post_data !== false && !$_GET['page']) {
    curl_setopt ($ch, CURLOPT_POST, true);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data);
  }

  if (user_type() != 'oauth' && user_is_authenticated())
    curl_setopt($ch, CURLOPT_USERPWD, user_current_username().':'.$GLOBALS['user']['password']);

  curl_setopt($ch, CURLOPT_VERBOSE, 0);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_USERAGENT, 'dabr');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  
  #file_put_contents('/tmp/urls', $url." ".user_type(). " ".json_encode($post_data)."\n", FILE_APPEND);
  #file_put_contents('/tmp/bt', var_export(debug_backtrace(), true)."\n", FILE_APPEND);
  $response = curl_exec($ch);
  $response_info=curl_getinfo($ch);
  curl_close($ch);

  switch( intval( $response_info['http_code'] ) ) {
    case 200:
      $json = json_decode($response);
      if ($json) return $json;
      return $response;
    case 401:
      user_logout();
      theme('error', "<p>Error: Login credentials incorrect.</p><p>$url</p><pre>".var_export(debug_backtrace(), true).'</pre>');
    case 0:
      theme('error', '<h2>Twitter timed out</h3><p>Dabr gave up on waiting for Twitter to respond. They\'re probably overloaded right now, try again in a minute.</p>'."<p>$url</p><pre>".var_export(debug_backtrace(), true).'</pre>');
    default:
      $result = json_decode($response);
      $result = $result->error ? $result->error : $response;
      if (strlen($result) > 500) $result = 'Something broke on Twitter\'s end.';
      theme('error', "<h2>An error occured while calling the Twitter API</h2><p>{$response_info['http_code']}: {$result}</p><hr><p>$url</p>");
  }
}

function twitter_url_shorten($text) {
  return preg_replace_callback('#((\w+://|www)[\w\#$%&~/.\-;:=,?@\[\]+]{33,1950})(?<![.,])#is', 'twitter_url_shorten_callback', $text);
}

function twitter_url_shorten_callback($match) {
  if (preg_match('#http://www.flickr.com/photos/[^/]+/(\d+)/#', $match[0], $matches)) {
    return 'http://flic.kr/p/'.flickr_encode($matches[1]);
  }
  if (!defined('BITLY_API_KEY')) return $match[0];
  $request = 'http://api.bit.ly/shorten?version=2.0.1&longUrl='.urlencode($match[0]).'&login='.BITLY_LOGIN.'&apiKey='.BITLY_API_KEY;
  $json = json_decode(twitter_fetch($request));
  if ($json->errorCode == 0) {
    $results = (array) $json->results;
    $result = array_pop($results);
    return $result->shortUrl;
  } else {
    return $match[0];
  }
}

function twitter_fetch($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
}

class Dabr_Autolink extends Twitter_Autolink {
	function replacementURLs($matches) {
		$replacement  = $matches[2];
		$url = $matches[3];
		if (!preg_match("#^https{0,1}://#i", $url)) {
			$url = "http://{$url}";
		}
		if (setting_fetch('gwt') == 'on') {
			$encoded = urlencode($url);
			$replacement .= "<a href='http://google.com/gwt/n?u={$encoded}' target='_blank'>{$url}</a>";
		} else {
			$replacement .= theme('external_link', $url);
		}
		return $replacement;
	}
}

function twitter_parse_tags($input)
{

	$urls = Twitter_Extractor::extractURLS($input);

	$out = $input;

	foreach ($urls as $value)
	{
		$out = str_replace ($value, long_url($value) , $out) ;
	}

	$autolink = new Dabr_Autolink();
	$out = $autolink->autolink($out);

	//If this is worksafe mode - don't display any images
	if (!in_array(setting_fetch('browser'), array('text', 'worksafe')))
	{
		//Add in images
		$out = twitter_embed_thumbnails($out);
	}

	//Linebreaks.  Some clients insert \n for formatting.
	$out = nl2br($out);

	//Return the completed string
	return $out;
}

function flickr_decode($num) {
  $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
  $decoded = 0;
  $multi = 1;
  while (strlen($num) > 0) {
    $digit = $num[strlen($num)-1];
    $decoded += $multi * strpos($alphabet, $digit);
    $multi = $multi * strlen($alphabet);
    $num = substr($num, 0, -1);
  }
  return $decoded;
}

function flickr_encode($num) {
  $alphabet = '123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
  $base_count = strlen($alphabet);
  $encoded = '';
  while ($num >= $base_count) {
    $div = $num/$base_count;
    $mod = ($num-($base_count*intval($div)));
    $encoded = $alphabet[$mod] . $encoded;
    $num = intval($div);
  }
  if ($num) $encoded = $alphabet[$num] . $encoded;
  return $encoded;
}

function twitter_embed_thumbnails($text) 
{
	if (setting_fetch('hide_inline')) {
		return $text;
	}
	$images = array();
	$tmp = strip_tags($text);
	
	//Using oEmbed from http://api.embed.ly/
	$embedly_re = "/http:\/\/(.*youtube\.com\/watch.*|.*\.youtube\.com\/v\/.*|youtu\.be\/.*|.*\.youtube\.com\/user\/.*|.*\.youtube\.com\/.*#.*\/.*|m\.youtube\.com\/watch.*|m\.youtube\.com\/index.*|.*\.youtube\.com\/profile.*|.*justin\.tv\/.*|.*justin\.tv\/.*\/b\/.*|.*justin\.tv\/.*\/w\/.*|www\.ustream\.tv\/recorded\/.*|www\.ustream\.tv\/channel\/.*|www\.ustream\.tv\/.*|qik\.com\/video\/.*|qik\.com\/.*|qik\.ly\/.*|.*revision3\.com\/.*|.*\.dailymotion\.com\/video\/.*|.*\.dailymotion\.com\/.*\/video\/.*|www\.collegehumor\.com\/video:.*|.*twitvid\.com\/.*|www\.break\.com\/.*\/.*|vids\.myspace\.com\/index\.cfm\?fuseaction=vids\.individual&videoid.*|www\.myspace\.com\/index\.cfm\?fuseaction=.*&videoid.*|www\.metacafe\.com\/watch\/.*|www\.metacafe\.com\/w\/.*|blip\.tv\/file\/.*|.*\.blip\.tv\/file\/.*|video\.google\.com\/videoplay\?.*|.*revver\.com\/video\/.*|video\.yahoo\.com\/watch\/.*\/.*|video\.yahoo\.com\/network\/.*|.*viddler\.com\/explore\/.*\/videos\/.*|liveleak\.com\/view\?.*|www\.liveleak\.com\/view\?.*|animoto\.com\/play\/.*|dotsub\.com\/view\/.*|www\.overstream\.net\/view\.php\?oid=.*|www\.livestream\.com\/.*|www\.worldstarhiphop\.com\/videos\/video.*\.php\?v=.*|worldstarhiphop\.com\/videos\/video.*\.php\?v=.*|teachertube\.com\/viewVideo\.php.*|www\.teachertube\.com\/viewVideo\.php.*|www1\.teachertube\.com\/viewVideo\.php.*|www2\.teachertube\.com\/viewVideo\.php.*|bambuser\.com\/v\/.*|bambuser\.com\/channel\/.*|bambuser\.com\/channel\/.*\/broadcast\/.*|www\.schooltube\.com\/video\/.*\/.*|bigthink\.com\/ideas\/.*|bigthink\.com\/series\/.*|sendables\.jibjab\.com\/view\/.*|sendables\.jibjab\.com\/originals\/.*|www\.xtranormal\.com\/watch\/.*|dipdive\.com\/media\/.*|dipdive\.com\/member\/.*\/media\/.*|dipdive\.com\/v\/.*|.*\.dipdive\.com\/media\/.*|.*\.dipdive\.com\/v\/.*|.*yfrog\..*\/.*|tweetphoto\.com\/.*|www\.flickr\.com\/photos\/.*|flic\.kr\/.*|twitpic\.com\/.*|www\.twitpic\.com\/.*|twitpic\.com\/photos\/.*|www\.twitpic\.com\/photos\/.*|.*imgur\.com\/.*|.*\.posterous\.com\/.*|post\.ly\/.*|twitgoo\.com\/.*|i.*\.photobucket\.com\/albums\/.*|s.*\.photobucket\.com\/albums\/.*|phodroid\.com\/.*\/.*\/.*|www\.mobypicture\.com\/user\/.*\/view\/.*|moby\.to\/.*|xkcd\.com\/.*|www\.xkcd\.com\/.*|imgs\.xkcd\.com\/.*|www\.asofterworld\.com\/index\.php\?id=.*|www\.asofterworld\.com\/.*\.jpg|asofterworld\.com\/.*\.jpg|www\.qwantz\.com\/index\.php\?comic=.*|23hq\.com\/.*\/photo\/.*|www\.23hq\.com\/.*\/photo\/.*|.*dribbble\.com\/shots\/.*|drbl\.in\/.*|.*\.smugmug\.com\/.*|.*\.smugmug\.com\/.*#.*|emberapp\.com\/.*\/images\/.*|emberapp\.com\/.*\/images\/.*\/sizes\/.*|emberapp\.com\/.*\/collections\/.*\/.*|emberapp\.com\/.*\/categories\/.*\/.*\/.*|embr\.it\/.*|picasaweb\.google\.com.*\/.*\/.*#.*|picasaweb\.google\.com.*\/lh\/photo\/.*|picasaweb\.google\.com.*\/.*\/.*|dailybooth\.com\/.*\/.*|brizzly\.com\/pic\/.*|pics\.brizzly\.com\/.*\.jpg|img\.ly\/.*|www\.tinypic\.com\/view\.php.*|tinypic\.com\/view\.php.*|www\.tinypic\.com\/player\.php.*|tinypic\.com\/player\.php.*|www\.tinypic\.com\/r\/.*\/.*|tinypic\.com\/r\/.*\/.*|.*\.tinypic\.com\/.*\.jpg|.*\.tinypic\.com\/.*\.png|meadd\.com\/.*\/.*|meadd\.com\/.*|.*\.deviantart\.com\/art\/.*|.*\.deviantart\.com\/gallery\/.*|.*\.deviantart\.com\/#\/.*|fav\.me\/.*|.*\.deviantart\.com|.*\.deviantart\.com\/gallery|.*\.deviantart\.com\/.*\/.*\.jpg|.*\.deviantart\.com\/.*\/.*\.gif|.*\.deviantart\.net\/.*\/.*\.jpg|.*\.deviantart\.net\/.*\/.*\.gif|plixi\.com\/p\/.*|plixi\.com\/profile\/home\/.*|plixi\.com\/.*|www\.fotopedia\.com\/.*\/.*|fotopedia\.com\/.*\/.*|photozou\.jp\/photo\/show\/.*\/.*|photozou\.jp\/photo\/photo_only\/.*\/.*|instagr\.am\/p\/.*|skitch\.com\/.*\/.*\/.*|img\.skitch\.com\/.*|https:\/\/skitch\.com\/.*\/.*\/.*|https:\/\/img\.skitch\.com\/.*|share\.ovi\.com\/media\/.*\/.*|www\.questionablecontent\.net\/|questionablecontent\.net\/|www\.questionablecontent\.net\/view\.php.*|questionablecontent\.net\/view\.php.*|questionablecontent\.net\/comics\/.*\.png|www\.questionablecontent\.net\/comics\/.*\.png|picplz\.com\/user\/.*\/pic\/.*\/|twitrpix\.com\/.*|.*\.twitrpix\.com\/.*|www\.someecards\.com\/.
*\/.*|someecards\.com\/.*\/.*|some\.ly\/.*|www\.some\.ly\/.*|pikchur\.com\/.*|achewood\.com\/.*|www\.achewood\.com\/.*|achewood\.com\/index\.php.*|www\.achewood\.com\/index\.php.*|www\.whitehouse\.gov\/photos-and-video\/video\/.*|www\.whitehouse\.gov\/video\/.*|wh\.gov\/photos-and-video\/video\/.*|wh\.gov\/video\/.*|www\.hulu\.com\/watch.*|www\.hulu\.com\/w\/.*|hulu\.com\/watch.*|hulu\.com\/w\/.*|.*crackle\.com\/c\/.*|www\.fancast\.com\/.*\/videos|www\.funnyordie\.com\/videos\/.*|www\.funnyordie\.com\/m\/.*|funnyordie\.com\/videos\/.*|funnyordie\.com\/m\/.*|www\.vimeo\.com\/groups\/.*\/videos\/.*|www\.vimeo\.com\/.*|vimeo\.com\/m\/#\/featured\/.*|vimeo\.com\/groups\/.*\/videos\/.*|vimeo\.com\/.*|vimeo\.com\/m\/#\/featured\/.*|www\.ted\.com\/talks\/.*\.html.*|www\.ted\.com\/talks\/lang\/.*\/.*\.html.*|www\.ted\.com\/index\.php\/talks\/.*\.html.*|www\.ted\.com\/index\.php\/talks\/lang\/.*\/.*\.html.*|.*nfb\.ca\/film\/.*|www\.thedailyshow\.com\/watch\/.*|www\.thedailyshow\.com\/full-episodes\/.*|www\.thedailyshow\.com\/collection\/.*\/.*\/.*|movies\.yahoo\.com\/movie\/.*\/video\/.*|movies\.yahoo\.com\/movie\/.*\/trailer|movies\.yahoo\.com\/movie\/.*\/video|www\.colbertnation\.com\/the-colbert-report-collections\/.*|www\.colbertnation\.com\/full-episodes\/.*|www\.colbertnation\.com\/the-colbert-report-videos\/.*|www\.comedycentral\.com\/videos\/index\.jhtml\?.*|www\.theonion\.com\/video\/.*|theonion\.com\/video\/.*|wordpress\.tv\/.*\/.*\/.*\/.*\/|www\.traileraddict\.com\/trailer\/.*|www\.traileraddict\.com\/clip\/.*|www\.traileraddict\.com\/poster\/.*|www\.escapistmagazine\.com\/videos\/.*|www\.trailerspy\.com\/trailer\/.*\/.*|www\.trailerspy\.com\/trailer\/.*|www\.trailerspy\.com\/view_video\.php.*|www\.atom\.com\/.*\/.*\/|fora\.tv\/.*\/.*\/.*\/.*|www\.spike\.com\/video\/.*|www\.gametrailers\.com\/video\/.*|gametrailers\.com\/video\/.*|www\.koldcast\.tv\/video\/.*|www\.koldcast\.tv\/#video:.*|techcrunch\.tv\/watch.*|techcrunch\.tv\/.*\/watch.*|mixergy\.com\/.*|video\.pbs\.org\/video\/.*|www\.zapiks\.com\/.*|tv\.digg\.com\/diggnation\/.*|tv\.digg\.com\/diggreel\/.*|tv\.digg\.com\/diggdialogg\/.*|www\.trutv\.com\/video\/.*|www\.nzonscreen\.com\/title\/.*|nzonscreen\.com\/title\/.*|app\.wistia\.com\/embed\/medias\/.*|https:\/\/app\.wistia\.com\/embed\/medias\/.*|www\.godtube\.com\/featured\/video\/.*|godtube\.com\/featured\/video\/.*|www\.godtube\.com\/watch\/.*|godtube\.com\/watch\/.*|www\.tangle\.com\/view_video.*|mediamatters\.org\/mmtv\/.*|www\.clikthrough\.com\/theater\/video\/.*|soundcloud\.com\/.*|soundcloud\.com\/.*\/.*|soundcloud\.com\/.*\/sets\/.*|soundcloud\.com\/groups\/.*|snd\.sc\/.*|www\.last\.fm\/music\/.*|www\.last\.fm\/music\/+videos\/.*|www\.last\.fm\/music\/+images\/.*|www\.last\.fm\/music\/.*\/_\/.*|www\.last\.fm\/music\/.*\/.*|www\.mixcloud\.com\/.*\/.*\/|www\.radionomy\.com\/.*\/radio\/.*|radionomy\.com\/.*\/radio\/.*|www\.entertonement\.com\/clips\/.*|www\.rdio\.com\/#\/artist\/.*\/album\/.*|www\.rdio\.com\/artist\/.*\/album\/.*|www\.zero-inch\.com\/.*|.*\.bandcamp\.com\/|.*\.bandcamp\.com\/track\/.*|.*\.bandcamp\.com\/album\/.*|freemusicarchive\.org\/music\/.*|www\.freemusicarchive\.org\/music\/.*|freemusicarchive\.org\/curator\/.*|www\.freemusicarchive\.org\/curator\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/.*\/.*\/.*\/.*\/.*\/.*\/.*|www\.npr\.org\/templates\/story\/story\.php.*|huffduffer\.com\/.*\/.*|www\.audioboo\.fm\/boos\/.*|audioboo\.fm\/boos\/.*|boo\.fm\/b.*|www\.xiami\.com\/song\/.*|xiami\.com\/song\/.*|espn\.go\.com\/video\/clip.*|espn\.go\.com\/.*\/story.*|abcnews\.com\/.*\/video\/.*|abcnews\.com\/video\/playerIndex.*|washingtonpost\.com\/wp-dyn\/.*\/video\/.*\/.*\/.*\/.*|www\.washingtonpost\.com\/wp-dyn\/.*\/video\/.*\/.*\/.*\/.*|www\.boston\.com\/video.*|boston\.com\/video.*|www\.facebook\.com\/photo\.php.*|www\.facebook\.com\/video\/video\.php.*|www\.facebook\.com\/v\/.*|cnbc\.com\/id\/.*\?.*video.*|www\.cnbc\.com\/id\/.*\?.*video.*|cnbc\.com\/id\/.*\/play\/1\/video\/.*|www\.cnbc\.com\/id\/.*\/play\/1\/video\/.*|cbsnews\.com\/video\/watch\/.
*|www\.google\.com\/buzz\/.*\/.*\/.*|www\.google\.com\/buzz\/.*|www\.google\.com\/profiles\/.*|google\.com\/buzz\/.*\/.*\/.*|google\.com\/buzz\/.*|google\.com\/profiles\/.*|www\.cnn\.com\/video\/.*|edition\.cnn\.com\/video\/.*|money\.cnn\.com\/video\/.*|today\.msnbc\.msn\.com\/id\/.*\/vp\/.*|www\.msnbc\.msn\.com\/id\/.*\/vp\/.*|www\.msnbc\.msn\.com\/id\/.*\/ns\/.*|today\.msnbc\.msn\.com\/id\/.*\/ns\/.*|multimedia\.foxsports\.com\/m\/video\/.*\/.*|msn\.foxsports\.com\/video.*|www\.globalpost\.com\/video\/.*|www\.globalpost\.com\/dispatch\/.*|.*amazon\..*\/gp\/product\/.*|.*amazon\..*\/.*\/dp\/.*|.*amazon\..*\/dp\/.*|.*amazon\..*\/o\/ASIN\/.*|.*amazon\..*\/gp\/offer-listing\/.*|.*amazon\..*\/.*\/ASIN\/.*|.*amazon\..*\/gp\/product\/images\/.*|www\.amzn\.com\/.*|amzn\.com\/.*|www\.shopstyle\.com\/browse.*|www\.shopstyle\.com\/action\/apiVisitRetailer.*|www\.shopstyle\.com\/action\/viewLook.*|gist\.github\.com\/.*|twitter\.com\/.*\/status\/.*|twitter\.com\/.*\/statuses\/.*|mobile\.twitter\.com\/.*\/status\/.*|mobile\.twitter\.com\/.*\/statuses\/.*|www\.crunchbase\.com\/.*\/.*|crunchbase\.com\/.*\/.*|www\.slideshare\.net\/.*\/.*|www\.slideshare\.net\/mobile\/.*\/.*|.*\.scribd\.com\/doc\/.*|screenr\.com\/.*|polldaddy\.com\/community\/poll\/.*|polldaddy\.com\/poll\/.*|answers\.polldaddy\.com\/poll\/.*|www\.5min\.com\/Video\/.*|www\.howcast\.com\/videos\/.*|www\.screencast\.com\/.*\/media\/.*|screencast\.com\/.*\/media\/.*|www\.screencast\.com\/t\/.*|screencast\.com\/t\/.*|issuu\.com\/.*\/docs\/.*|www\.kickstarter\.com\/projects\/.*\/.*|www\.scrapblog\.com\/viewer\/viewer\.aspx.*|ping\.fm\/p\/.*|chart\.ly\/.*|maps\.google\.com\/maps\?.*|maps\.google\.com\/\?.*|maps\.google\.com\/maps\/ms\?.*|.*\.craigslist\.org\/.*\/.*|my\.opera\.com\/.*\/albums\/show\.dml\?id=.*|my\.opera\.com\/.*\/albums\/showpic\.dml\?album=.*&picture=.*|tumblr\.com\/.*|.*\.tumblr\.com\/post\/.*|www\.polleverywhere\.com\/polls\/.*|www\.polleverywhere\.com\/multiple_choice_polls\/.*|www\.polleverywhere\.com\/free_text_polls\/.*|www\.quantcast\.com\/wd:.*|www\.quantcast\.com\/.*|siteanalytics\.compete\.com\/.*|statsheet\.com\/statplot\/charts\/.*\/.*\/.*\/.*|statsheet\.com\/statplot\/charts\/e\/.*|statsheet\.com\/.*\/teams\/.*\/.*|statsheet\.com\/tools\/chartlets\?chart=.*|.*\.status\.net\/notice\/.*|identi\.ca\/notice\/.*|brainbird\.net\/notice\/.*|shitmydadsays\.com\/notice\/.*|www\.studivz\.net\/Profile\/.*|www\.studivz\.net\/l\/.*|www\.studivz\.net\/Groups\/Overview\/.*|www\.studivz\.net\/Gadgets\/Info\/.*|www\.studivz\.net\/Gadgets\/Install\/.*|www\.studivz\.net\/.*|www\.meinvz\.net\/Profile\/.*|www\.meinvz\.net\/l\/.*|www\.meinvz\.net\/Groups\/Overview\/.*|www\.meinvz\.net\/Gadgets\/Info\/.*|www\.meinvz\.net\/Gadgets\/Install\/.*|www\.meinvz\.net\/.*|www\.schuelervz\.net\/Profile\/.*|www\.schuelervz\.net\/l\/.*|www\.schuelervz\.net\/Groups\/Overview\/.*|www\.schuelervz\.net\/Gadgets\/Info\/.*|www\.schuelervz\.net\/Gadgets\/Install\/.*|www\.schuelervz\.net\/.*|myloc\.me\/.*|pastebin\.com\/.*|pastie\.org\/.*|www\.pastie\.org\/.*|redux\.com\/stream\/item\/.*\/.*|redux\.com\/f\/.*\/.*|www\.redux\.com\/stream\/item\/.*\/.*|www\.redux\.com\/f\/.*\/.*|cl\.ly\/.*|cl\.ly\/.*\/content|speakerdeck\.com\/u\/.*\/p\/.*|www\.kiva\.org\/lend\/.*|www\.timetoast\.com\/timelines\/.*|storify\.com\/.*\/.*|.*meetup\.com\/.*|meetu\.ps\/.*|www\.dailymile\.com\/people\/.*\/entries\/.*|.*\.kinomap\.com\/.*|www\.metacdn\.com\/api\/users\/.*\/content\/.*|www\.metacdn\.com\/api\/users\/.*\/media\/.*|prezi\.com\/.*\/.*|.*\.uservoice\.com\/.*\/suggestions\/.*)/i";

	//Tokenise the string (on whitespace) and search through it
	$tok = strtok($tmp, " \n\t");
	while ($tok !== false) 
	{
		if (preg_match_all($embedly_re, $tok, $matches, PREG_PATTERN_ORDER) > 0)
		{
			foreach ($matches[1] as $key => $match)
			{
				//Should use &maxwidth, but hard to know width of device - so using tinysrc to resize to 50%
				$url = "http://api.embed.ly/1/oembed?url=" . $match . "&format=json";
				
				$embedly_json = twitter_fetch($url);
				$embedly_data = json_decode($embedly_json);
				$thumb = $embedly_data->thumbnail_url;
				
				//We can use the height and width for better HTML, but some thumbnails are very large. Using tinysrc for now.
				$height = $embedly_data->thumbnail_height;
				$width = $embedly_data->thumbnail_width;
				
				if ($thumb) //Not all services have thumbnails
				{
					$images[] = theme('external_link', "http://$match", "<img src='http://i.tinysrc.mobi/x50/$thumb' />");
				}
			}
		}
		$tok = strtok(" \n\t");
	}
	
	if (empty($images)) return $text;
	return implode('<br />', $images).'<br />'.$text;
}

function generate_thumbnail($query) {
  $id = $query[1];
  if ($id) {
    header('HTTP/1.1 301 Moved Permanently');
    if ($query[0] == 'flickr') {
      if (!is_numeric($id)) $id = flickr_decode($id);
      $url = "http://api.flickr.com/services/rest/?method=flickr.photos.getSizes&photo_id=$id&api_key=".FLICKR_API_KEY;
      $flickr_xml = twitter_fetch($url);
      if (setting_fetch('browser') == 'mobile') {
        $pattern = '#"(http://.*_t\.jpg)"#';
      } else {
        $pattern = '#"(http://.*_m\.jpg)"#';
      }
      preg_match($pattern, $flickr_xml, $matches);
      header('Location: '. $matches[1]);
    }
    if ($query[0] == 'moblog') {
      $url = "http://moblog.net/view/{$id}/";
      $html = twitter_fetch($url);
      if (preg_match('#"(/media/[a-zA-Z0-9]/[^"]+)"#', $html, $matches)) {
        $thumb = 'http://moblog.net' . str_replace(array('.j', '.J'), array('_tn.j', '_tn.J'), $matches[1]);
        $pos = strrpos($thumb, '/');
        $thumb = substr($thumb, 0, $pos) . '/thumbs' . substr($thumb, $pos);
      }
      header('Location: '. $thumb);
    }
  }
  exit();
}

function format_interval($timestamp, $granularity = 2) {
  $units = array(
    'years' => 31536000,
    'days' => 86400,
    'hours' => 3600,
    'min' => 60,
    'sec' => 1
  );
  $output = '';
  foreach ($units as $key => $value) {
    if ($timestamp >= $value) {
      $output .= ($output ? ' ' : '').floor($timestamp / $value).' '.$key;
      $timestamp %= $value;
      $granularity--;
    }
    if ($granularity == 0) {
      break;
    }
  }
  return $output ? $output : '0 sec';
}

function twitter_status_page($query) {
  $id = (string) $query[1];
  if (is_numeric($id)) {
    $request = "http://twitter.com/statuses/show/{$id}.json";
    $status = twitter_process($request);
    $content = theme('status', $status);
    if (!$status->user->protected) {
      $thread = twitter_thread_timeline($id);
    }
    if ($thread) {
      $content .= '<p>And the experimental conversation view...</p>'.theme('timeline', $thread);
      $content .= "<p>Don't like the thread order? Go to <a href='settings'>settings</a> to reverse it. Either way - the dates/times are not always accurate.</p>";
    }
    theme('page', "Status $id", $content);
  }
}

function twitter_thread_timeline($thread_id) {
  $request = "http://search.twitter.com/search/thread/{$thread_id}";
  $tl = twitter_standard_timeline(twitter_fetch($request), 'thread');
  return $tl;
}

function twitter_retweet_page($query) {
  $id = (string) $query[1];
  if (is_numeric($id)) {
    $request = "http://twitter.com/statuses/show/{$id}.json";
    $tl = twitter_process($request);
    $content = theme('retweet', $tl);
    theme('page', 'Retweet', $content);
  }
}

function twitter_comment_page($query) {
  $id = (string) $query[1];
  if (is_numeric($id)) {
    $request = "http://twitter.com/statuses/show/{$id}.json";
    $tl = twitter_process($request);
    $content = theme('comment', $tl);
    theme('page', 'Comment', $content);
  }
}

/*
function twitter_replycomment_page($query) {
  $id = (string) $query[1];
  $cid = (string) $query[2];
  if (is_numeric($id)) {
    $request = "http://twitter.com/statuses/show/{$id}.json";
    $tl = twitter_process($request);
    $content = theme('comment', $tl);
    theme('page', 'Comment', $content);
  }
}*/

function twitter_refresh($page = NULL) {
  if (isset($page)) {
    $page = BASE_URL . $page;
  } else {
    $page = $_SERVER['HTTP_REFERER'];
  }
  header('Location: '. $page);
  exit();
}

function twitter_delete_page($query) {
  twitter_ensure_post_action();
  
  $id = (string) $query[1];
  if (is_numeric($id)) {
    $request = "http://twitter.com/statuses/destroy/{$id}.json?page=".intval($_GET['page']);
    $tl = twitter_process($request, true);
    twitter_refresh('user/'.user_current_username());
  }
}

function twitter_ensure_post_action() {
  // This function is used to make sure the user submitted their action as an HTTP POST request
  // It slightly increases security for actions such as Delete, Block and Spam
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Error: Invalid HTTP request method for this action.');
  }
}

function twitter_follow_page($query) {
  $user = $query[1];
  if ($user) {
    if($query[0] == 'follow'){
      $request = "http://twitter.com/friendships/create/{$user}.json";
    } else {
      $request = "http://twitter.com/friendships/destroy/{$user}.json";
    }
    twitter_process($request, true);
    twitter_refresh('friends');
  }
}

function twitter_block_page($query) {
  twitter_ensure_post_action();
  $user = $query[1];
  if ($user) {
    if($query[0] == 'block'){
      $request = "http://twitter.com/blocks/create/{$user}.json";
    } else {
      $request = "http://twitter.com/blocks/destroy/{$user}.json";
    }
    twitter_process($request, true);
    twitter_refresh("user/{$user}");
  }
}

function twitter_spam_page($query)
{
	//http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-report_spam
	//We need to post this data
	twitter_ensure_post_action();
	$user = $query[1];

	//The data we need to post
	$post_data = array("screen_name" => $user);

	$request = API_URL."report_spam.json";
	twitter_process($request, $post_data);

	//Where should we return the user to?  Back to the user
	twitter_refresh("user/{$user}");
}


function twitter_confirmation_page($query) 
{
	// the URL /confirm can be passed parameters like so /confirm/param1/param2/param3 etc.
	$action = $query[1];
	$target = $query[2];	//The name of the user we are doing this action on
	$target_id = $query[3];	//The targets's ID.  Needed to check if they are being blocked.

  switch ($action) {
    case 'block':
      if (twitter_block_exists($target_id)) //Is the target blocked by the user?
      {
        $action = 'unblock';
        $content  = "<p>Are you really sure you want to <strong>Unblock $target</strong>?</p>";
        $content .= '<ul><li>They will see your updates on their home page if they follow you again.</li><li>You <em>can</em> block them again if you want.</li></ul>';		
      }
      else
      {
        $content = "<p>Are you really sure you want to <strong>$action $target</strong>?</p>";
        $content .= "<ul><li>You won't show up in their list of friends</li><li>They won't see your updates on their home page</li><li>They won't be able to follow you</li><li>You <em>can</em> unblock them but you will need to follow them again afterwards</li></ul>";
      }
      break;
    
    case 'delete':
      $content = '<p>Are you really sure you want to delete your tweet?</p>';
      $content .= "<ul><li>Tweet ID: <strong>$target</strong></li><li>There is no way to undo this action.</li></ul>";
      break;

    case 'spam':
      $content  = "<p>Are you really sure you want to report <strong>$target</strong> as a spammer?</p>";
      $content .= "<p>They will also be blocked from following you.</p>";
      break;

  }    
  $content .= "<form action='$action/$target' method='post'>
						<input type='submit' value='Yes please' />
					</form>";
  theme('Page', 'Confirm', $content);
}

function twitter_friends_page($query) {
  $user = $query[1];
  if (!$user) {
    user_ensure_authenticated();
    $user = $GLOBALS['user']['screen_name'];
  }
  $request = "http://twitter.com/statuses/friends/{$user}.json?page=".intval($_GET['page']);
  $tl = twitter_process($request);
  $content = theme('followers', $tl);
  theme('page', 'Friends', $content);
}

function twitter_followers_page($query) {
  $user = $query[1];
  if (!$user) {
    user_ensure_authenticated();
    $user = $GLOBALS['user']['screen_name'];
  }
  $request = "http://twitter.com/statuses/followers/{$user}.json?page=".intval($_GET['page']);
  $tl = twitter_process($request);
  $content = theme('followers', $tl);
  theme('page', 'Followers', $content);
}

function twitter_update() {
  twitter_ensure_post_action();
  $status = twitter_url_shorten(stripslashes(trim($_POST['status'])));
  if ($status) {
    $request = API_URL.'statuses/update.json';
    $post_data = array('source' => OAUTH_CONSUMER_KEY, 'status' => $status);
    $in_reply_to_id = (string) $_POST['in_reply_to_id'];
    if (is_numeric($in_reply_to_id)) {
      $post_data['in_reply_to_status_id'] = $in_reply_to_id;
    }
		// Geolocation parameters
		list($lat, $long) = explode(',', $_POST['location']);
		$geo = 'N';
		if (is_numeric($lat) && is_numeric($long)) {
			$geo = 'Y';
			$post_data['lat'] = $lat;
			$post_data['long'] = $long;
	  // $post_data['display_coordinates'] = 'false';
		}
		setcookie_year('geo', $geo);
    $b = twitter_process($request, $post_data);
  }
  twitter_refresh($_POST['from'] ? $_POST['from'] : '');
}

function twitter_retweet($query) {
  twitter_ensure_post_action();
  $id = $query[1];
  if (is_numeric($id)) {
    $request = 'http://twitter.com/statuses/retweet/'.$id.'.xml';
    $status = twitter_url_shorten(stripslashes(trim($_POST['status'])));
    $post_data = array('source' => 'appkey', 'status' => $status);
    twitter_process($request, $post_data);
  }
  twitter_refresh($_POST['from'] ? $_POST['from'] : '');
}

function twitter_comment($query) {
  twitter_ensure_post_action();
  $comment = twitter_url_shorten(stripslashes(trim($_POST['comment'])));
  // $id = $query[1];
  $id = $_POST['id'];
  if (is_numeric($id)) {
    $request = 'http://twitter.com/statuses/comment.json';
    $post_data = array('source' => 'appkey', 'comment' => $comment, 'id' => $id);
    $b = twitter_process($request, $post_data);
  }
  twitter_refresh($_POST['from'] ? $_POST['from'] : '');
}

function twitter_public_page() {
  $request = 'http://twitter.com/statuses/public_timeline.json?page='.intval($_GET['page']);
  $content = theme('status_form');
  $tl = twitter_standard_timeline(twitter_process($request), 'public');
  $content .= theme('timeline', $tl);
  theme('page', 'Public Timeline', $content);
}

function twitter_replies_page() {
  $request = 'http://twitter.com/statuses/mentions.json?page='.intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'mentions');
  $content = theme('status_form');
  $content .= theme('timeline', $tl);
  theme('page', 'Replies', $content);
}

function twitter_cmts_page($query) {
  $action = strtolower(trim($query[1]));
  switch ($action) {
  case 'by_me':
  $request = 'http://twitter.com/statuses/comments_by_me.json?page='.intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'cmts');
  $content = theme_cmts_menu();
  $content .= theme('timeline', $tl);
  theme('page', 'Comments', $content);

  case '':
  case 'to_me':
  $request = 'http://twitter.com/statuses/comments_to_me.json?page='.intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'cmts');
  $content = theme_cmts_menu();
  $content .= theme('timeline', $tl);
  theme('page', 'Comments', $content);

 case 'reply': // reply comment
  $rid = strtolower(trim($query[2]));
  $request = 'http://twitter.com/statuses/comments_by_me.json?page='.intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'cmts');
  $content = theme_cmts_menu();
  $content .= theme('timeline', $tl);
  theme('page', 'Comments', $content);

  default:
  $request = "http://twitter.com/statuses/comments.json?id=$action&page=".intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'cmts');
  $content = theme_cmts_menu();
  $content .= theme('timeline', $tl);
  theme('page', 'Comments', $content);
  }
}

function twitter_directs_page($query) {
  $action = strtolower(trim($query[1]));
  switch ($action) {
    case 'delete':
      $id = $query[2];
      if (!is_numeric($id)) return;
      $request = "http://twitter.com/direct_messages/destroy/$id.json";
      twitter_process($request, true);
      twitter_refresh();
      
    case 'create':
      $to = $query[2];
      $content = theme('directs_form', $to);
      theme('page', 'Create DM', $content);
    
    case 'send':
      twitter_ensure_post_action();
      $to = trim(stripslashes($_POST['to']));
      $message = trim(stripslashes($_POST['message']));
      $request = 'http://twitter.com/direct_messages/new.json';
      twitter_process($request, array('screen_name' => $to, 'text' => $message));
      twitter_refresh('directs/sent');
    
    case 'sent':
      $request = 'http://twitter.com/direct_messages/sent.json?page='.intval($_GET['page']);
      $tl = twitter_standard_timeline(twitter_process($request), 'directs_sent');
      $content = theme_directs_menu();
      $content .= theme('timeline', $tl);
      theme('page', 'DM Sent', $content);

    case 'inbox':
    default:
      $request = 'http://twitter.com/direct_messages.json?page='.intval($_GET['page']);
      $tl = twitter_standard_timeline(twitter_process($request), 'directs_inbox');
      $content = theme_directs_menu();
      $content .= theme('timeline', $tl);
      theme('page', 'DM Inbox', $content);
  }
}

function theme_directs_menu() {
  return '<p><a href="directs/create">Create</a> | <a href="directs/inbox">Inbox</a> | <a href="directs/sent">Sent</a></p>';
}

function theme_cmts_menu() {
  return '<p><a href="cmts/to_me">To me</a> | <a href="cmts/by_me">by me</a></p>';
}

function theme_directs_form($to) {
  if ($to) {
/*	
	if (friendship_exists($to) != 1)
	{
		$html_to = "<em>Warning</em> <b>" . $to . "</b> is not following you. You cannot send them a Direct Message :-(<br/>";
	}
*/
    $html_to .= "Sending direct message to <b>$to</b><input name='to' value='$to' type='hidden'>";
  } else {
    $html_to .= "To: <input name='to'><br />Message:";
  }
   $content = "<form action='directs/send' method='post'>$html_to<br><textarea name='message' cols='50' rows='3' id='message'></textarea><br><input type='submit' value='Send'><span id='remaining'>140</span></form>";
   $content .= js_counter("message");
   return $content;
}

function twitter_search_page() {
  $search_query = $_GET['query'];
  $content = theme('search_form', $search_query);
  if (isset($_POST['query'])) {
    $duration = time() + (3600 * 24 * 365);
    setcookie('search_favourite', $_POST['query'], $duration, '/');
    twitter_refresh('search');
  }
  if (!isset($search_query) && array_key_exists('search_favourite', $_COOKIE)) {
    $search_query = $_COOKIE['search_favourite'];
  }
  if ($search_query) {
    $tl = twitter_search($search_query);
    if ($search_query !== $_COOKIE['search_favourite']) {
      $content .= '<form action="search/bookmark" method="post"><input type="hidden" name="query" value="'.$search_query.'" /><input type="submit" value="Save as default search" /></form>';
    }
    $content .= theme('timeline', $tl);
  }
  theme('page', 'Search', $content);
}

function twitter_search($search_query) {
  $page = (int) $_GET['page'];
  if ($page == 0) $page = 1;
  $request = 'http://api.t.sina.com.cn/search.json?q=' . urlencode($search_query).'&page='.$page;
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl->results, 'search');
  return $tl;
}

function twitter_user_page($query) {
  $screen_name = $query[1];
  if ($screen_name) {
    $content = '';
    if ($query[2] == 'reply') {
      $in_reply_to_id = (string) $query[3];
      if (is_numeric($in_reply_to_id)) {
        $content .= "<p>In reply to tweet ID $in_reply_to_id...</p>";
      }
    } else {
      $in_reply_to_id = 0;
    }
    $user = twitter_user_info($screen_name);
    if (!user_is_current_user($user->screen_name)) {
      $status = "@{$user->screen_name} ";
    } else {
      $status = '';
    }
    $content .= theme('status_form', $status, $in_reply_to_id);
    $content .= theme('user_header', $user);
    
    if (isset($user->status)) {
      $request = "http://twitter.com/statuses/user_timeline.json?screen_name={$screen_name}&page=".intval($_GET['page']);
      $tl = twitter_process($request);
      $tl = twitter_standard_timeline($tl, 'user');
      $content .= theme('timeline', $tl);
    }
    theme('page', "User {$screen_name}", $content);
  } else {
    // TODO: user search screen
  }
}

function twitter_favourites_page($query) {
  $screen_name = $query[1];
  if (!$screen_name) {
    user_ensure_authenticated();
    $screen_name = $GLOBALS['user']['screen_name'];
  }
  $request = "http://twitter.com/favorites/{$screen_name}.json?page=".intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'favourites');
  $content = theme('status_form');
  $content .= theme('timeline', $tl);
  theme('page', 'Favourites', $content);
}

function twitter_mark_favourite_page($query) {
  $id = (string) $query[1];
  if (!is_numeric($id)) return;
  if ($query[0] == 'unfavourite') {
    $request = "http://twitter.com/favorites/destroy/$id.json";
  } else {
    $request = "http://twitter.com/favorites/create/$id.json";
  }
  twitter_process($request, true);
  twitter_refresh();
}

function twitter_home_page() {
  user_ensure_authenticated();
  $request = 'http://twitter.com/statuses/home_timeline.json?count=30&page='.intval($_GET['page']);
  $tl = twitter_process($request);
  $tl = twitter_standard_timeline($tl, 'friends');
  $content = theme('status_form');
  $content .= theme('timeline', $tl);
  theme('page', 'Home', $content);
}

function twitter_hashtag_page($query) {
  if (isset($query[1])) {
    $hashtag = '#'.$query[1];
    $content = theme('status_form', $hashtag.' ');
    $tl = twitter_search($hashtag);
    $content .= theme('timeline', $tl);
    theme('page', $hashtag, $content);
  } else {
    theme('page', 'Hashtag', 'Hash hash!');
  }
}

function theme_status_form($text = '', $in_reply_to_id = NULL) {
  if (user_is_authenticated()) {
	  $request = 'http://twitter.com/statuses/user_timeline.json?user_id=10503&count=2&page='.intval($_GET['page']);
	  $tl = twitter_process($request);

    return "<form method='post' action='update'><input name='status' value='{$text}' maxlength='140' /> <input name='in_reply_to_id' value='{$in_reply_to_id}' type='hidden' /><input type='submit' value='Update' /></form>";
  }
}

function theme_status($status) {
  $time_since = theme('status_time_link', $status);
  $parsed = twitter_parse_tags($status->text);
  $avatar = theme('avatar', $status->user->profile_image_url, 1);

  $out = theme('status_form', "@{$status->user->screen_name} ");
  $out .= "<p>$parsed</p>
<table align='center'><tr><td>$avatar</td><td><a href='user/{$status->user->screen_name}'>{$status->user->screen_name}</a>
<br />$time_since</td></tr></table>";
  if (user_is_current_user($status->user->screen_name)) {
    $out .= "<form action='delete/{$status->id}' method='post'><input type='submit' value='Delete without confirmation' /></form>";
  }
  return $out;
}

function theme_retweet($status) {
  $text = "{$status->text}";
  $length = function_exists('mb_strlen') ? mb_strlen($text,'UTF-8') : strlen($text);
  $from = substr($_SERVER['HTTP_REFERER'], strlen(BASE_URL));
  $content = "<!--p>Old style \"organic\" retweet:</p><form action='update' method='post'><input type='hidden' name='from' value='$from' /><textarea name='status' cols='50' rows='3' id='status'>$text</textarea><br><input type='submit' value='Retweet'><span id='remaining'>" . (140 - $length) ."</span></form-->";
  $content .= js_counter("status");  
	if($status->user->protected == 0){
    $content.="<br />repost comment<br /><form action='twitter-retweet/{$status->id}' method='post'>
<textarea name='status' cols='50' rows='3' id='status'></textarea><br>$text
<input type='hidden' name='from' value='$from' /><input type='submit' value='repost'></form>";
  }
  return $content;
}

function theme_comment($status) {
  $text = "@{$status->user->screen_name}: ";
  $length = function_exists('mb_strlen') ? mb_strlen($text,'UTF-8') : strlen($text);
  $from = substr($_SERVER['HTTP_REFERER'], strlen(BASE_URL));
  $content = "<p>Sina style comment:</p><form action='twitter-comment/{$status->id}' method='post'><input type='hidden' name='id' value='$status->id' /><input type='hidden' name='from' value='$from' /><textarea name='comment' cols='50' rows='3' id='comment'>$text</textarea><br><input type='submit' value='Comment'><span id='remaining'>" . (140 - $length) ."</span></form>";
  $content .= js_counter("status");
        /*if($status->user->protected == 0){
    $content.="<br />Or Twitter's new style retweets<br /><form action='twitter-retweet/{$status->id}' method='post'><input type='hidden' name='from' value='$from' /><input type='submit' value='Twitter Retweet'></form>";
  }*/
  return $content;
}

function twitter_tweets_per_day($user, $rounding = 1) {
  // Helper function to calculate an average count of tweets per day
  $days_on_twitter = (time() - strtotime($user->created_at)) / 86400;
  return round($user->statuses_count / $days_on_twitter, $rounding);
}

function theme_user_header($user) {
  $name = theme('full_name', $user);
  $full_avatar = str_replace('_normal.', '.', $user->profile_image_url);
  $link = theme('external_link', $user->url);
  $raw_date_joined = strtotime($user->created_at);
  $date_joined = date('jS M Y', $raw_date_joined);
  $tweets_per_day = twitter_tweets_per_day($user, 1);
  $out = "<table><tr><td>".theme('external_link', $full_avatar, theme('avatar', $user->profile_image_url, 1))."</td>
<td><b>{$name}</b>
<small>";
  if ($user->verified == true) {
    $out .= '<br /><strong>Verified Account</strong>';
  }
  if ($user->protected == true) {
    $out .= '<br /><strong>Private/Protected Tweets</strong>';
  }
  $out .= "
<br />Bio: {$user->description}
<br />Link: {$link}
<br />Location: {$user->location}
<br />Joined: {$date_joined} (~$tweets_per_day tweets per day)
</small>
<br />
{$user->statuses_count} tweets |
<a href='followers/{$user->screen_name}'>{$user->followers_count} followers</a> ";

  if ($user->following !== true) {
    $out .= "| <a href='follow/{$user->screen_name}'>Follow</a>";
  } else {
    $out .= " | <a href='unfollow/{$user->screen_name}'>Unfollow</a>";
  }
  
	//We need to pass the User Name and the User ID.  The Name is presented in the UI, the ID is used in checking
	$out.= " | <a href='confirm/block/{$user->screen_name}/{$user->id}'>Block | Unblock</a>";
	$out .= " | <a href='confirm/spam/{$user->screen_name}/{$user->id}'>Report Spam</a>";
  $out.= " | <a href='friends/{$user->screen_name}'>{$user->friends_count} friends</a>
| <a href='favourites/{$user->screen_name}'>{$user->favourites_count} favourites</a>
| <a href='directs/create/{$user->screen_name}'>Direct Message</a>
| <a href='lists/{$user->screen_name}'>Lists</a>
</td></table>";
  return $out;
}

function theme_avatar($url, $force_large = false) {
  $size = $force_large ? 48 : 24;
  return "<img src='$url' height='$size' width='$size' />";
}

function theme_status_time_link($status, $is_link = true) {
  $time = strtotime($status->created_at);
  if ($time > 0) {
    /*if (twitter_date('dmy') == twitter_date('dmy', $time)) {
      $out = format_interval(time() - $time, 1). ' ago';
    } else {*/
      $out = twitter_date('M d H:i:s', ($time + 60 * 60 * 8) );
    //}
  } else {
    $out = $status->created_at;
  }
  if ($is_link)
    $out = "<a href='status/{$status->id}'>$out</a>";
  return "<small>$out</small>";
}

function twitter_date($format, $timestamp = null) {
  if (!isset($timestamp)) {
    $timestamp = time();
  }
  return gmdate($format, $timestamp);
}

function twitter_standard_timeline($feed, $source) {
  $output = array();
  if (!is_array($feed) && $source != 'thread') return $output;
  switch ($source) {
    case 'favourites':
    case 'friends':
    case 'public':
    case 'mentions':
    case 'user':
      foreach ($feed as $status) {
        $new = $status;
        $new->from = $new->user;
        unset($new->user);
        $output[(string) $new->id] = $new;
      }
      return $output;
   
    case 'cmts':
      foreach ($feed as $status) {
        $new = $status;
        $new->from = $new->user;
        unset($new->user);
        $output[(string) $new->id] = $new;
      }
      return $output;
 
    case 'search':
      foreach ($feed as $status) {
        $output[(string) $status->id] = (object) array(
          'id' => $status->id,
          'text' => $status->text,
          'source' => strpos($status->source, '&lt;') !== false ? html_entity_decode($status->source) : $status->source,
          'from' => (object) array(
            'id' => $status->from_user_id,
            'screen_name' => $status->from_user,
            'profile_image_url' => $status->profile_image_url,
          ),
          'to' => (object) array(
            'id' => $status->to_user_id,
            'screen_name' => $status->to_user,
          ),
          'created_at' => $status->created_at,
        );
      }
      return $output;
    
    case 'directs_sent':
    case 'directs_inbox':
      foreach ($feed as $status) {
        $new = $status;
        if ($source == 'directs_inbox') {
          $new->from = $new->sender;
          $new->to = $new->recipient;
        } else {
          $new->from = $new->recipient;
          $new->to = $new->sender;
        }
        unset($new->sender, $new->recipient);
        $new->is_direct = true;
        $output[] = $new;
      }
      return $output;
    
    case 'thread':
      // First pass: extract tweet info from the HTML
      $html_tweets = explode('</li>', $feed);
      foreach ($html_tweets as $tweet) {
        $id = preg_match_one('#msgtxt(\d*)#', $tweet);
        if (!$id) continue;
        $output[$id] = (object) array(
          'id' => $id,
          'text' => strip_tags(preg_match_one('#</a>: (.*)</span>#', $tweet)),
          'source' => preg_match_one('#>from (.*)</span>#', $tweet),
          'from' => (object) array(
            'id' => preg_match_one('#profile_images/(\d*)#', $tweet),
            'screen_name' => preg_match_one('#twitter.com/([^"]+)#', $tweet),
            'profile_image_url' => preg_match_one('#src="([^"]*)"#' , $tweet),
          ),
          'to' => (object) array(
            'screen_name' => preg_match_one('#@([^<]+)#', $tweet),
          ),
          'created_at' => str_replace('about', '', preg_match_one('#info">\s(.*)#', $tweet)),
        );
      }
      // Second pass: OPTIONALLY attempt to reverse the order of tweets
      if (setting_fetch('reverse') == 'yes') {
        $first = false;
        foreach ($output as $id => $tweet) {
          $date_string = str_replace('later', '', $tweet->created_at);
          if ($first) {
            $attempt = strtotime("+$date_string");
            if ($attempt == 0) $attempt = time();
            $previous = $current = $attempt - time() + $previous;
          } else {
            $previous = $current = $first = strtotime($date_string);
          }
          $output[$id]->created_at = date('r', $current);
        }
        $output = array_reverse($output);
      }
      return $output;

    default:
      echo "<h1>$source</h1><pre>";
      print_r($feed); die();
  }
}

function preg_match_one($pattern, $subject, $flags = NULL) {
  preg_match($pattern, $subject, $matches, $flags);
  return trim($matches[1]);
}

function twitter_user_info($username = null) {
  if (!$username) {
    //error_log("twitter_user_info");
//	 debug_print_backtrace  ();
return null;//    $username = user_current_username();
  }
 
//  $username_e = urlencode($username); 
  $request = "http://twitter.com/users/show.json?screen_name=$username";
  $user = twitter_process($request);
  return $user;
}

function theme_timeline($feed) {
  if (count($feed) == 0) return theme('no_tweets');
  $rows = array();
  $page = menu_current_page();
  $date_heading = false;
  foreach ($feed as $status) {
    $time = strtotime($status->created_at);
    if ($time > 0) {
      $date = twitter_date('l jS F Y', strtotime($status->created_at));
      if ($date_heading !== $date) {
        $date_heading = $date;
        $rows[] = array(array(
          'data' => "<small><b>$date</b></small>",
          'colspan' => 2
        ));
      }
    } else {
      $date = $status->created_at;
    }
    if ($status->in_reply_to_status_id) {
      $source .= " in reply to <a href='status/{$status->in_reply_to_status_id}'>{$status->in_reply_to_screen_name}</a>";
    }
    if($status->status) { // comment
      $text = twitter_parse_tags($status->text);
      $srctext = twitter_parse_tags($status->status->text);
      if ($status->status->thumbnail_pic)
        $srctext .= "<br/> <a href='{$status->status->original_pic}' target=_blank><img src='{$status->status->thumbnail_pic}' /></a> <br />";
      $link = theme('status_time_link', $status, !$status->is_direct);
      $actions = theme('action_icons', $status);
      $avatar = theme('avatar', $status->from->profile_image_url);
      $source2 = $status->status->source ? " from {$status->status->source}" : '';
      $row = array(
        "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link<br />{$text} <br /> 原文<br/> <b> <a href='user/{$status->status->user->screen_name}'>{$status->status->user->screen_name}</a></b> <br />{$srctext} <small>$source2</small>",
      );
    }
    elseif($status->retweeted_status){
      //$avatar = theme('avatar',$status->retweeted_status->user->profile_image_url);
      $avatar = theme('avatar', $status->from->profile_image_url);
      $link = theme('status_time_link', $status, !$status->is_direct);
      $actions = theme('action_icons', $status);

      $text = twitter_parse_tags($status->retweeted_status->text);
      if ($status->retweeted_status->thumbnail_pic)
        $text .= "<br/> <a href='{$status->retweeted_status->original_pic}' target=_blank><img src='{$status->retweeted_status->thumbnail_pic}' /></a> <br />";

      $source = $status->source ? " from {$status->source}" : '';
      $source2 = $status->retweeted_status->source ? " from {$status->retweeted_status->source}" : '';
      $row = array(
        "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link <br /> $status->text <small>$source</small> <br/>原文<br/> <b> <a href='user/{$status->retweeted_status->user->screen_name}'>{$status->retweeted_status->user->screen_name}</a></b> <br />{$text} <small>$source2</small>",
      );
    }
    else{
      $text = twitter_parse_tags($status->text);
      if ($status->thumbnail_pic)
        $text .= "<br/> <a href='$status->original_pic' target=_blank><img src='$status->thumbnail_pic' /></a> <br />";
      $link = theme('status_time_link', $status, !$status->is_direct);
      $actions = theme('action_icons', $status);
      $avatar = theme('avatar', $status->from->profile_image_url);
      $source = $status->source ? " from {$status->source}" : '';
      $row = array(
        "<b><a href='user/{$status->from->screen_name}'>{$status->from->screen_name}</a></b> $actions $link<br />{$text} <small>$source</small>",
      );
    }

    if ($page != 'user' && $avatar) {
      array_unshift($row, $avatar);
    }
    if ($page != 'mentions' && twitter_is_reply($status)) {
      $row = array('class' => 'reply', 'data' => $row);
    }
    $rows[] = $row;
  }
  $content = theme('table', array(), $rows, array('class' => 'timeline'));
  if (count($feed) >= 15) {
    $content .= theme('pagination');
  }
  return $content;
}

function twitter_is_reply($status) {
  if (!user_is_authenticated()) {
    return false;
  }
  $user = user_current_username();
  return preg_match("#@$user#i", $status->text);
}

function theme_followers($feed, $hide_pagination = false) {
  $rows = array();
  if (count($feed) == 0 || $feed == '[]') return '<p>No users to display.</p>';
  foreach ($feed as $user) {
    $test = "";
    /*
    foreach ($user as $usera) {
      foreach ($usera as $uk => $uv) {
        $test .= $uk;
	$test .= ",";
	$test .= $uv;
        $test .= ",";
      }
    }*/
    $name = theme('full_name', $user);
    $tweets_per_day = twitter_tweets_per_day($user);
    $rows[] = array(
      theme('avatar', $user->profile_image_url),
      "{$name} - {$user->location}<br />" .
      "<small>{$user->description}<br />" .
      "Info: {$test} {$user->statuses_count} tweets, {$user->friends_count} friends, {$user->followers_count} followers, ~{$tweets_per_day} tweets per day</small>"
    );
  }
  $content = theme('table', array(), $rows, array('class' => 'followers'));
  if (!$hide_pagination)
    $content .= theme('pagination');
  return $content;
}

function theme_full_name($user) {
  $name = "<a href='user/{$user->screen_name}'>{$user->screen_name}</a>";
  if ($user->name && $user->name != $user->screen_name) {
    $name .= " ({$user->name})";
  }
  return $name;
}

function theme_no_tweets() {
  return '<p>No tweets to display.</p>';
}

function theme_search_results($feed) {
  $rows = array();
  foreach ($feed->results as $status) {
    $text = twitter_parse_tags($status->text);
    $link = theme('status_time_link', $status);
    $actions = theme('action_icons', $status);

    $row = array(
      theme('avatar', $status->profile_image_url),
      "<a href='user/{$status->from_user}'>{$status->from_user}</a> $actions - {$link}<br />{$text}",
    );
    if (twitter_is_reply($status)) {
      $row = array('class' => 'reply', 'data' => $row);
    }
    $rows[] = $row;
  }
  $content = theme('table', array(), $rows, array('class' => 'timeline'));
  $content .= theme('pagination');
  return $content;
}

function theme_search_form($query) {
  $query = stripslashes(htmlentities($query,ENT_QUOTES,"UTF-8"));
  return "<form action='search' method='get'><input name='query' value=\"$query\" /><input type='submit' value='Search' /></form>";
}

function theme_external_link($url, $content = null) {
	//Long URL functionality.  Also uncomment function long_url($shortURL)
	if (!$content) 
	{	
		return "<a href='$url' target='_blank'>".long_url($url)."</a>";
	}
	else
	{
		return "<a href='$url' target='_blank'>$content</a>";
	}
	
}

function theme_pagination() {
  $page = intval($_GET['page']);
  if (preg_match('#&q(.*)#', $_SERVER['QUERY_STRING'], $matches)) {
    $query = $matches[0];
  }
  if ($page == 0) $page = 1;
  $links[] = "<a href='{$_GET['q']}?page=".($page+1)."$query' accesskey='9'>Older</a> 9";
  if ($page > 1) $links[] = "<a href='{$_GET['q']}?page=".($page-1)."$query' accesskey='8'>Newer</a> 8";
  return '<p>'.implode(' | ', $links).'</p>';
}


function theme_action_icons($status) {
  $from = $status->from->screen_name;
  $actions = array();
  
  if (!$status->is_direct) {
    $actions[] = theme('action_icon', "user/{$from}/reply/{$status->id}", 'images/reply.png', '@');
  }
  if (!user_is_current_user($from)) {
    $actions[] = theme('action_icon', "directs/create/{$from}", 'images/dm.png', 'DM');
  }
  if (!$status->is_direct) {
    if ($status->favorited == '1') {
      $actions[] = theme('action_icon', "unfavourite/{$status->id}", 'images/star.png', 'UNFAV');
    } else {
      $actions[] = theme('action_icon', "favourite/{$status->id}", 'images/star_grey.png', 'FAV');
    }
  if (!$status->status) {
    $actions[] = theme('action_icon', "retweet/{$status->id}", 'images/retweet.png', 'RT');
    $actions[] = theme('action_icon', "comment/{$status->id}", 'images/comments.gif', 'CMT');
    $actions[] = theme('action_icon', "cmts/{$status->id}", 'images/list.png', 'CMS');
  } else {
    $actions[] = theme('action_icon', "recomment/{$status->id}", 'images/comments.gif', 'CMS');
  }

    if (user_is_current_user($from)) {
      $actions[] = theme('action_icon', "confirm/delete/{$status->id}", 'images/trash.gif', 'DEL');
    }
  } else {
    $actions[] = theme('action_icon', "directs/delete/{$status->id}", 'images/trash.gif', 'DEL');
  }

  return implode(' ', $actions);
}

function theme_action_icon($url, $image_url, $text) {
  // alt attribute left off to reduce bandwidth by about 720 bytes per page
  return "<a href='$url'><img src='$image_url' /></a>";
}

?>
