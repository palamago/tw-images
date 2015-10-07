<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

$handle = fopen('logger.txt', 'w');

ini_set('display_errors', 1);

$url = 'https://api.twitter.com/1.1/followers/ids.json';
$requestMethod = 'GET';

$cursor = -1;

$twitter = new TwitterAPIExchange($settings);	

$i=0;
$total=0;
while ($cursor != 0) {


	 $i++;	

	 //Get the first 5,000 followers, use the cursor to step through	

	 $getfield = '?screen_name='.$twUser.'&cursor=' . $cursor;	

	 $followers_json = $twitter->setGetfield($getfield)	

					 ->buildOauth($url, $requestMethod)	

					 ->performRequest();	

	 $followers_decoded = json_decode($followers_json);	

	 fwrite($handle, "FETCHING MORE LOOP NUMBER :  " . $i . ' | ' . $cursor . "\n");	

	 //the lookup.json method can only handle a max of 100 at a time	

	 $to_look_up = array_chunk($followers_decoded->ids, 100);

	 foreach ($to_look_up as &$ids) {

			//nothing dupes, skip to the next 100	
			if (count($ids) == 0)
				 continue;	

			//hydrate the remaining ids	
			$apiResp = $twitter->setGetField('?user_id=' . implode(',', $ids))	
											->buildOauth('https://api.twitter.com/1.1/users/lookup.json', $requestMethod)	
											->performRequest();

			$users_list = json_decode($apiResp);	

			set_time_limit(30);	

			foreach ($users_list as $key => $user) {
				if (filter_var($user->profile_image_url, FILTER_VALIDATE_URL) !== false) {	

					 //download the image
					 $filename = explode('/', $user->profile_image_url);
					 $filename = end($filename);	

					 $filename = str_replace('_normal.', '_bigger.', $filename);	

					 $ext = explode('.', $filename);
					 $ext = end($ext);	

					 $success = file_put_contents('images/' . $user->screen_name . '.' . $user->id . '.' . $ext, file_get_contents(str_replace('_normal.', '_bigger.', $user->profile_image_url)));	

					 $write = $user->id . " :: " . $user->screen_name . " :: " . $success . "\n";	

					 fwrite($handle, $write);

				} else {	

					 $write = $user->id . " :: " . $user->screen_name . " :: " . $user->profile_image_url . " :: " . 'NO URL FOR IMAGE' . "\n";	

					 fwrite($handle, $write);	

				}
			}

		$total += count($ids);
		fwrite($handle, "--TOTAL:  " . $total . "\n");
	 }

	$cursor = $followers_decoded->next_cursor;	

}

$write = "REACHED THE END";	

fwrite($handle, $write);

?>