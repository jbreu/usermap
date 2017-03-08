<?php

ini_set("allow_url_include", "1");
echo ini_get("allow_url_include");

$pdo = new PDO('mysql:host=localhost;dbname=******;charset=utf8', 'username', 'password');

$sql = "SELECT users.user_id as user_id, profile_fields_data.pf_wohnort as pf_wohnort, users.user_usermap_lat as user_usermap_lat FROM profile_fields_data LEFT JOIN users ON profile_fields_data.user_id=users.user_id";
foreach ($pdo->query($sql) as $row) {
	if ($row['pf_wohnort'] != '' && $row['user_usermap_lat'] == '') {
	 $coords = get_cords_form_zip($row['pf_wohnort']);

	 $updatesql = "UPDATE users SET user_usermap_lon=".$coords['lon'].", user_usermap_lat=".$coords['lat']." WHERE user_id=".$row['user_id'];

	 $pdo->query($updatesql);

	 echo $row['user_id']." ".$row['pf_wohnort']." -> ".$coords['lon'].",".$coords['lat']."\n";
	 usleep(25000);
 }
}

function _randomize_coordinate($coordinate)
{
	$rand = rand(11111, 99999);
	return number_format($coordinate, 2) . $rand;
}

function get_cords_form_zip($zip)
	{
		$zip = str_replace(' ', '%20', $zip);
		$info = get_web_page('https://maps.google.com/maps/api/geocode/json?address=' . myUrlEncode($zip) . '&key=*********API-Key**********');

		if ( $info['errno'] != 0 )
     print_r($info['errno']);

		 if ( $info['http_code'] != 200 )
    	print_r($info['http_code']);

		$info = json_decode($info['content'], true);
		if (isset($info['results']['0']['geometry']['location']))
		{
			return array(
				'lon'		=> substr(_randomize_coordinate($info['results']['0']['geometry']['location']['lng']), 0, 10),
				'lat'		=> substr(_randomize_coordinate($info['results']['0']['geometry']['location']['lat']), 0, 10),
			);
		}
		else
		{
			print_r($info);
		}
	}

	function myUrlEncode($string) {
	    $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
	    $replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
	    return str_replace($entities, $replacements, urlencode($string));
	}

	/**
   * Get a web file (HTML, XHTML, XML, image, etc.) from a URL.  Return an
   * array containing the HTTP server response header fields and content.
   */
  function get_web_page( $url )
  {
      $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

      $options = array(

          CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
          CURLOPT_POST           =>false,        //set to GET
          CURLOPT_USERAGENT      => $user_agent, //set user agent
          CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
          CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
          CURLOPT_RETURNTRANSFER => true,     // return web page
          CURLOPT_HEADER         => false,    // don't return headers
          CURLOPT_FOLLOWLOCATION => true,     // follow redirects
          CURLOPT_ENCODING       => "",       // handle all encodings
          CURLOPT_AUTOREFERER    => true,     // set referer on redirect
          CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
          CURLOPT_TIMEOUT        => 120,      // timeout on response
          CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
      );

      $ch      = curl_init( $url );
      curl_setopt_array( $ch, $options );
      $content = curl_exec( $ch );
      $err     = curl_errno( $ch );
      $errmsg  = curl_error( $ch );
      $header  = curl_getinfo( $ch );
      curl_close( $ch );

      $header['errno']   = $err;
      $header['errmsg']  = $errmsg;
      $header['content'] = $content;
      return $header;
  }

?>
