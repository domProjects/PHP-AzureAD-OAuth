<?php
/**
 * 
 * Original project: Katy Nicholson - https://github.com/CoasterKaty
 *
 */

require_once dirname(__FILE__) . '/auth.php';

class modGraph
{
	var $modAuth;

	function __construct()
	{
		$this->modAuth = new modAuth();
	}

	function getProfile()
	{
		return json_decode($this->sendGetRequest('https://graph.microsoft.com/v1.0/me'));
	}

	function getPhoto()
	{
		//Photo is a bit different, we need to request the image data which will include content type, size etc, then request the image
		$photoType = json_decode($this->sendGetRequest('https://graph.microsoft.com/v1.0/me/photo/'));
		$photo = $this->sendGetRequest('https://graph.microsoft.com/v1.0/me/photo/%24value');
		return '<img src="data:' . $photoType->{'@odata.mediaContentType'} . ';base64,' . base64_encode($photo) . '" alt="User Photo">';
	}

	function sendGetRequest($url)
	{
		$opt = [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			// if not https
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_SSL_VERIFYPEER => false,
			// end
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
			CURLOPT_HTTPHEADER => [
				'Authorization: Bearer ' . $this->modAuth->Token
			]
		];

		$ch = curl_init();

		curl_setopt_array($ch, $opt);

		$response = curl_exec($ch);

		curl_close($ch);
		return $response;
	}
}

?>