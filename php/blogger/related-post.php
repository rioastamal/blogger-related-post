<?php
/**
 * Quick and dirty blogger related post generator
 *
 * @author Rio Astamal <me@rioastamal.net>
 * @license GNU GPL v2
 */
define('BASE_PATH', realpath(dirname(__FILE__) . '/../../'));
define('MAX_RESULT_FEED', 6);
// without trailing slash
define('BLOG_DOMAIN', 'notes.rioastamal.net');

// We output as text/javascript MIME
header('Content-type: application/x-javascript');

include(BASE_PATH . '/libs/rayap.php');
include(BASE_PATH . '/libs/js_functions.php');

// Article that user is viewing will send us list of it's tag in query string 
// such as ?tags=foo,bar. So we need to parse those tags.
$tags = (isset($_GET['tags']) ? $_GET['tags'] : '');

if (strlen($tags) === 0) {
	echo ('var blogger_related_post_output = \'\'' . "\n");
	exit(1);
}

// base URL for querying the label
$blogger_feed_url = 'http://%s/feeds/posts/default/-/%s?alt=json&max-results=%d';
$id = @$_GET['id'];

// variabel for holding the result
$relateds = array();
// instance rayap's object
$rayap = new Rayap();

// loop throuh each tags to get it's related post
$tags = explode(',', $tags);
foreach ($tags as $tag) {
	$tag = trim($tag);
	$feed_url = sprintf($blogger_feed_url, BLOG_DOMAIN, $tag, MAX_RESULT_FEED);
	
	// get the json feed
	$posts = $rayap->get_page($feed_url);
	
	// parse and make it PHP Object
	$posts = json_decode($posts);
	
	// loop through each post since it may more than one post that had
	// the same tag/label
	if (isset($posts->feed->entry) === FALSE) {
		continue;	// skip
	}
	
	foreach ($posts->feed->entry as $post) {
		// why $t? see the blogger json source for details about the structure
		// that sent by blogger. Typically something like this:
		// -- snip --
		// [title] => stdClass Object
		//	(
		//		[type] => text
		//		[$t] => Some title here
		//	)
		// -- snip --
		$post_id = $post->id->{'$t'};
		
		// get the post id from string something like
		// tag:blogger.com,1999:blog-181262482510042921.post-7342230668238854740
		$_id = explode('post-', $post_id);
		
		// this is the same post? if so skip it...
		if ($_id[1] == $id) {
			continue;
		} 
		
		$post_title = $post->title->{'$t'};
		// why 4? again see the blogger json response
		$post_link = $post->link[4]->href;
		
		// fill the array $related vars with above values
		// we user associative array so PHP can overwrite the duplicate post
		// automatically since there's a big chance some post has the same label
		$relateds[$post_id] = array(
			'title' => $post_title,
			'link' => $post_link
		);
	}
}

// ok we got all the related post it's time to send it back to the user
$js = 'var blogger_related_post_output = \'\';' . "\n";
if (count($relateds) > 0) {
	$js .= 'blogger_related_post_output += \'<h3 class="related-post-title">Related Posts</h3>\';' . "\n";
	$js .= 'blogger_related_post_output += \'<ul class="related-post">\';' . "\n";
	foreach ($relateds as $related) {
		$js .= sprintf('blogger_related_post_output += \'<li><a href="%s">%s</a></li>\'' . "\n", $related['link'], htmlentities($related['title']));
	}
	$js .= 'blogger_related_post_output += \'</ul>\'' . "\n";
}
echo $js;
