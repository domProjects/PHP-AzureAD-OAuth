<?php
/**
 * 
 * Original project: Katy Nicholson - https://github.com/CoasterKaty
 *
 */

require_once dirname(__FILE__) . '/mysql.php';
require_once dirname(__FILE__) . '/oauth.php';

class modAuth
{
	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $modDB;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $Token;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $userData;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return string
	 */
	public $userName;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return string
	 */
	public $preferredUserName;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $oAuthVerifier;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $oAuthChallenge;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $oAuthChallengeMethod;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $userRoles;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $isLoggedIn = 0;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public $oAuth;

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	public function __construct($allowAnonymous = '0')
	{
		$this->modDB = new modDB();
		$this->oAuth = new modOAuth();

		if (! isset($_SESSION))
		{
			session_start();
		}

		// check session key against database. If it's expired or doesnt exist then forward to Azure AD
		if (isset($_SESSION['sessionkey']))
		{
			// see if it's still valid. Expiry date doesn't mean that we can't just use the refresh token, so don't test this here
			$res = $this->_getSessionDb($_SESSION['sessionkey']);

			$this->oAuthVerifier = $res['txtCodeVerifier'];
			$this->oAuthChallenge();

			if (! $res OR ! $res['txtIDToken'])
			{
				//not in DB or empty id token field
				unset($_SESSION['sessionkey']);
				session_destroy();
				header('Location: ' . $_SERVER['REQUEST_URI']);
				exit;
			}

			if (isset($_GET['action']) == 'logout')
			{
				$this->_sendLogout($res['intAuthID'], $_SESSION['sessionkey']);
			}

			if (strtotime($res['dtExpires']) < strtotime('+10 minutes'))
			{
				//attempt token refresh
				if ($res['txtRefreshToken'])
				{
					$oauthRequest = $this->oAuth->generateRequest('grant_type=refresh_token&refresh_token=' . $res['txtRefreshToken'] . '&client_id=' . _OAUTH_CLIENTID . '&scope=' . _OAUTH_SCOPE);
					$response = $this->oAuth->postRequest('token', $oauthRequest);
					$reply = json_decode($response);

					if (isset($reply->error))
					{
						if(substr($reply->error_description, 0, 12) == 'AADSTS70008:')
						{
							//refresh token expired
							$this->modDB->Update('tblAuthSessions', array('txtRedir' => _URL, 'txtRefreshToken' => '', 'dtExpires' => date('Y-m-d H:i:s', strtotime('+5 minutes'))),  array('intAuthID' => $res['intAuthID']));
							$oAuthURL = 'https://login.microsoftonline.com/' . _OAUTH_TENANTID . '/oauth2/v2.0/' . 'authorize?response_type=code&client_id=' . _OAUTH_CLIENTID . '&redirect_uri=' . urlencode(_URL . '/oauth.php') . '&scope=' . _OAUTH_SCOPE . '&code_challenge=' . $this->oAuthChallenge . '&code_challenge_method=' . $this->oAuthChallengeMethod;
							header('Location: ' . $oAuthURL);
							exit;
						}

						echo $this->oAuth->errorMessage($reply->error_description);
						exit;
					}

					$idToken = base64_decode(explode('.', $reply->id_token)[1]);

					$this->modDB->Update('tblAuthSessions', array(
						'txtToken' => $reply->access_token,
						'txtRefreshToken' => $reply->refresh_token,
						'txtIDToken' => $idToken,
						'txtRedir' => '',
						'dtExpires' => date('Y-m-d H:i:s', strtotime('+' . $reply->expires_in . ' seconds'))
					), array('intAuthID' => $res['intAuthID']));

					$res['txtToken'] = $reply->access_token;
				}
			}

			// Populate userData and userName from the JWT stored in the database.
			// ????
			$this->Token = $res['txtToken'];

			if ($res['txtIDToken'])
			{
				$idToken = json_decode($res['txtIDToken']);

				$this->preferredUserName = $this->getPreferredUserName($idToken);
				$this->userName = $this->getUserName($idToken);
				$this->userRoles = $this->getUserRoles($idToken);
			}

			$this->isLoggedIn = 1;
		}
		else
		{
			if (! $allowAnonymous OR $_GET['action'] == 'login')
			{
				$this->_sendLogin();
			}
		}

		// Clean up old entries
		// The refresh token is valid for 72 hours by default, but there doesn't seem to be a way to see when the specific one issued expires. So assume anything 72 hours past the expiry of the access token is gone and delete.
		$maxRefresh = strtotime('-72 hour');
		$this->modDB->Query('DELETE FROM tblAuthSessions WHERE dtExpires < \'' . date('Y-m-d H:i:s', $maxRefresh) . '\'');
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	protected function _getSessionDb($session)
	{
		return $this->modDB->QuerySingle('SELECT * FROM tblAuthSessions WHERE txtSessionKey = \'' . $this->modDB->Escape($session) . '\'');
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return xxxxx
	 */
	protected function _sendLogin()
	{
		// Generate the code verifier and challenge
		$this->oAuthChallenge();

		// Generate a session key and store in cookie, then populate database
		$sessionKey = $this->_uuid();
		$_SESSION['sessionkey'] = $sessionKey;

		$this->modDB->Insert('tblAuthSessions', [
			'txtSessionKey' => $sessionKey,
			'txtRedir' => _URL,
			'txtCodeVerifier' => $this->oAuthVerifier,
			'dtExpires' => date('Y-m-d H:i:s', strtotime('+5 minutes'))
		]);

		// Redirect to Azure AD login page
		$oAuthURL = 'https://login.microsoftonline.com/' . _OAUTH_TENANTID . '/oauth2/v2.0/' . 'authorize?response_type=code&client_id=' . _OAUTH_CLIENTID . '&redirect_uri=' . urlencode(_URL . '/oauth.php') . '&scope=' . _OAUTH_SCOPE . '&code_challenge=' . $this->oAuthChallenge . '&code_challenge_method=' . $this->oAuthChallengeMethod;
		header('Location: ' . $oAuthURL);
		exit;
	}

	/**
	 * Logout action selected, clear from database and browser cookie, redirect to logout URL.
	 *
	 * @return xxxxx
	 */
	protected function _sendLogout($authID, $sessionKey)
	{
		$this->modDB->Delete('tblAuthSessions', array('intAuthID' => $authID));
		unset($sessionKey);
		session_destroy();
		header('Location: ' . _OAUTH_LOGOUT);
		exit;
	}

	/**
	 * _uuid function is not my code, but unsure who the original author is. KN
	 * _uuid version 4
	 *
	 * @return xxxxx
	 */
	protected function _uuid()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			// 16 bits for "time_mid"
			mt_rand(0, 0xffff),
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand(0, 0x0fff) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand(0, 0x3fff) | 0x8000,
			// 48 bits for "node"
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);
	}

	/**
	 * Function to generate code verifier and code challenge for oAuth login.
	 * See RFC7636 for details.
	 *
	 * @return xxxxx
	 */
	protected function oAuthChallenge()
	{
		$verifier = $this->oAuthVerifier;

		if (! $this->oAuthVerifier)
		{
			$chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';
			$charLen = strlen($chars) - 1;
			$verifier = '';

			for ($i = 0; $i < 128; $i++)
			{
				$verifier .= $chars[mt_rand(0, $charLen)];
			}

			$this->oAuthVerifier = $verifier;
		}

		// Challenge = Base64 Url Encode ( SHA256 ( Verifier ) )
		// Pack (H) to convert 64 char hash into 32 byte hex
		// As there is no B64UrlEncode we use strtr to swap +/ for -_ and then strip off the =
		$this->oAuthChallenge = str_replace('=', '', strtr(base64_encode(pack('H*', hash('sha256', $verifier))), '+/', '-_'));
		$this->oAuthChallengeMethod = 'S256';
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return string
	 */
	public function getUserName($idToken): string
	{
		return $idToken->name;
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return string
	 */
	public function getPreferredUserName($idToken): string
	{
		return $idToken->preferred_username;
	}

	/**
	 * xxxxx xxxxx xxxxx
	 *
	 * @return array
	 */
	public function getUserRoles($idToken): array
	{
		return (! isset($idToken->roles)) ? ['Default Access'] : $idToken->roles;
	}

	/**
	 * Check that the requested role has been assigned to the user
	 *
	 * @return xxxxx
	 */
	public function checkUserRole($role)
	{
		if (in_array($role, $this->userRoles))
		{
			return 1;
		}
		return;
	}
}

?>