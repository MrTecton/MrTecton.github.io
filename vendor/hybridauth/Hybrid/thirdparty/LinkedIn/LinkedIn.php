<?php
class LinkedInException extends Exception {}

class LinkedIn {
  const _API_OAUTH_REALM             = 'http://api.linkedin.com';
  const _API_OAUTH_VERSION           = '1.0';
  
  const _DEFAULT_RESPONSE_FORMAT     = 'xml';
    
  const _GET_RESPONSE                = 'lResponse';
  const _GET_TYPE                    = 'lType';
  
  const _INV_SUBJECT                 = 'Invitation to connect';
  const _INV_BODY_LENGTH             = 200;
  
  const _METHOD_TOKENS               = 'POST';
  
  const _NETWORK_LENGTH              = 1000;
  const _NETWORK_HTML                = '<a>';
  
  const _RESPONSE_JSON               = 'JSON';
  const _RESPONSE_JSONP              = 'JSONP';
  const _RESPONSE_XML                = 'XML';
  
  const _SHARE_COMMENT_LENGTH        = 700;
  const _SHARE_CONTENT_TITLE_LENGTH  = 200;
  const _SHARE_CONTENT_DESC_LENGTH   = 400;
  
	const _URL_ACCESS                  = 'https://api.linkedin.com/uas/oauth/accessToken';
	const _URL_API                     = 'https://api.linkedin.com';
	const _URL_AUTH                    = 'https://www.linkedin.com/uas/oauth/authenticate?oauth_token=';
	const _URL_REQUEST                 = 'https://api.linkedin.com/uas/oauth/requestToken?scope=r_basicprofile+r_emailaddress+rw_nus'; 
	const _URL_REVOKE                  = 'https://api.linkedin.com/uas/oauth/invalidateToken';
	
	const _VERSION                     = '3.2.0';
  
  protected $callback;
  protected $token                   = NULL;
  
  protected $application_key, 
            $application_secret;
  
  protected $response_format         = self::_DEFAULT_RESPONSE_FORMAT;

  public $last_request_headers, 
         $last_request_url;

	public function __construct($config) {
    if(!is_array($config)) {
		  throw new LinkedInException('LinkedIn->__construct(): bad data passed, $config must be of type array.');
    }
    $this->setApplicationKey($config['appKey']);
	  $this->setApplicationSecret($config['appSecret']);
	  $this->setCallbackUrl($config['callbackUrl']);
	}
	
  public function __destruct() {
    unset($this);
	}
	
	public function bookmarkJob($jid) {
	  if(!is_string($jid)) {
		  throw new LinkedInException('LinkedIn->bookmarkJob(): bad data passed, $jid must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/people/~/job-bookmarks';
	  $response = $this->fetch('POST', $query, '<job-bookmark><job><id>' . trim($jid) . '</id></job></job-bookmark>');
	  
		return $this->checkResponse(201, $response);
	}
	
	public function bookmarkedJobs() {	
	  $query    = self::_URL_API . '/v1/people/~/job-bookmarks';
	  $response = $this->fetch('GET', $query);
	  
		return $this->checkResponse(200, $response);
	}
	
	private function intWalker($value, $key) {
        if(!is_int($value)) {
			throw new LinkedInException('LinkedIn->checkResponse(): $http_code_required must be an integer or an array of integer values');
		}
    }
	
	private function checkResponse($http_code_required, $response) {
    if(is_array($http_code_required)) {
		  array_walk($http_code_required, array($this, 'intWalker'));
		} else {
		  if(!is_int($http_code_required)) {
  			throw new LinkedInException('LinkedIn->checkResponse(): $http_code_required must be an integer or an array of integer values');
  		} else {
  		  $http_code_required = array($http_code_required);
  		}
		}
		if(!is_array($response)) {
			throw new LinkedInException('LinkedIn->checkResponse(): $response must be an array');
		}		
		
		if(in_array($response['info']['http_code'], $http_code_required)) {
		  $response['success'] = TRUE;
		} else {
			$response['success'] = FALSE;
			$response['error']   = 'HTTP response from LinkedIn end-point was not code ' . implode(', ', $http_code_required);
		}
		return $response;
	}
	
	public function closeJob($jid) {
	  if(!is_string($jid)) {
		  throw new LinkedInException('LinkedIn->closeJob(): bad data passed, $jid must be of string value.');
	  }
	  
	  $query    = self::_URL_API . '/v1/jobs/partner-job-id=' . trim($jid);
	  $response = $this->fetch('DELETE', $query);
	  
	  return $this->checkResponse(204, $response);
	}
	
	public function comment($uid, $comment) {
	  if(!is_string($uid)) {
		  throw new LinkedInException('LinkedIn->comment(): bad data passed, $uid must be of type string.');
	  }
    if(!is_string($comment)) {
		  throw new LinkedInException('LinkedIn->comment(): bad data passed, $comment must be a non-zero length string.');
    }
    
    $comment = substr(trim(htmlspecialchars(strip_tags($comment))), 0, self::_SHARE_COMMENT_LENGTH);
		$data    = '<?xml version="1.0" encoding="UTF-8"?>
                <update-comment>
  				        <comment>' . $comment . '</comment>
  				      </update-comment>';

    $query    = self::_URL_API . '/v1/people/~/network/updates/key=' . $uid . '/update-comments';
    $response = $this->fetch('POST', $query, $data);
    
    return $this->checkResponse(201, $response);
	}
	
	public function comments($uid) {
	  if(!is_string($uid)) {
		  throw new LinkedInException('LinkedIn->comments(): bad data passed, $uid must be of type string.');
	  }
		
    $query    = self::_URL_API . '/v1/people/~/network/updates/key=' . $uid . '/update-comments';
    $response = $this->fetch('GET', $query);
    
    return $this->checkResponse(200, $response);
	}
	
	public function company($options, $by_email = FALSE) {
	  if(!is_string($options)) {
		  throw new LinkedInException('LinkedIn->company(): bad data passed, $options must be of type string.');
	  }
	  if(!is_bool($by_email)) {
		  throw new LinkedInException('LinkedIn->company(): bad data passed, $by_email must be of type boolean.');
	  }
	  
	  $query    = self::_URL_API . '/v1/companies' . ($by_email ? '' : '/') . trim($options);
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function companyProducts($cid, $options = '') {
	  if(!is_string($cid)) {
		  throw new LinkedInException('LinkedIn->companyProducts(): bad data passed, $cid must be of type string.');
	  }
	  if(!is_string($options)) {
		  throw new LinkedInException('LinkedIn->companyProducts(): bad data passed, $options must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/companies/' . trim($cid) . '/products' . trim($options);
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
  	
	public function connections($options = '~/connections') {
	  if(!is_string($options)) {
		  throw new LinkedInException('LinkedIn->connections(): bad data passed, $options must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/people/' . trim($options);
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function createPost($gid, $title, $summary = '') {
		if(!is_string($gid)) {
			throw new LinkedInException('LinkedIn->createPost(): bad data passed, $gid must be of type string.');
		}
		if(!is_string($title) || empty($title)) {
			throw new LinkedInException('LinkedIn->createPost(): bad data passed, $title must be a non-empty string.');
		}
		if(!is_string($summary)) {
			throw new LinkedInException('LinkedIn->createPost(): bad data passed, $summary must be of type string.');
		}
		
		$data = '<?xml version="1.0" encoding="UTF-8"?>
    				 <post>
    					 <title>'. $title . '</title>
    					 <summary>' . $summary . '</summary>
    				 </post>';
		
		$query    = self::_URL_API . '/v1/groups/' . trim($gid) . '/posts';
		$response = $this->fetch('POST', $query, $data);
		
		return $this->checkResponse(201, $response);
	}
	
	public function deletePost($pid) {
		if(!is_string($pid)) {
			throw new LinkedInException('LinkedIn->deletePost(): bad data passed, $pid must be of type string');
		}
		
		$query    = self::_URL_API . '/v1/posts/' . trim($pid);
		$response = $this->fetch('DELETE', $query);
		
		return $this->checkResponse(204, $response);
	}
	
	public function editJob($jid, $xml) {
	  if(!is_string($jid)) {
		  throw new LinkedInException('LinkedIn->editJob(): bad data passed, $jid must be of string value.');
	  }
	  if(is_string($xml)) {
	    $xml = trim(stripslashes($xml));
	  } else {
		  throw new LinkedInException('LinkedIn->editJob(): bad data passed, $xml must be of string value.');
	  }
               
	  $query    = self::_URL_API . '/v1/jobs/partner-job-id=' . trim($jid);
	  $response = $this->fetch('PUT', $query, $xml);
	  
	  return $this->checkResponse(200, $response);
	}
	
	protected function fetch($method, $url, $data = NULL, $parameters = array()) {
	  if(!extension_loaded('curl')) {
      throw new LinkedInException('LinkedIn->fetch(): PHP cURL extension does not appear to be loaded/present.');
	  }
	  
    try {
	    $oauth_consumer  = new OAuthConsumer($this->getApplicationKey(), $this->getApplicationSecret(), $this->getCallbackUrl());
	    $oauth_token     = $this->getToken();
	    $oauth_token     = (!is_null($oauth_token)) ? new OAuthToken($oauth_token['oauth_token'], $oauth_token['oauth_token_secret']) : NULL;
      $defaults        = array(
        'oauth_version' => self::_API_OAUTH_VERSION
      );
	    $parameters    = array_merge($defaults, $parameters);
	    
  		$oauth_req = OAuthRequest::from_consumer_and_token($oauth_consumer, $oauth_token, $method, $url, $parameters);
      $oauth_req->sign_request(new OAuthSignatureMethod_HMAC_SHA1(), $oauth_consumer, $oauth_token);
      
      if(!$handle = curl_init()) {
        throw new LinkedInException('LinkedIn->fetch(): cURL did not initialize properly.');
      }
      
	    curl_setopt($handle, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($handle, CURLOPT_URL, $url);
      curl_setopt($handle, CURLOPT_VERBOSE, FALSE);
      
      $header = array($oauth_req->to_header(self::_API_OAUTH_REALM));
      if(is_null($data)) {
        $header[] = 'Content-Type: text/plain; charset=UTF-8';
        switch($this->getResponseFormat()) {
          case self::_RESPONSE_JSON:
            $header[] = 'x-li-format: json';
            break;
          case self::_RESPONSE_JSONP:
            $header[] = 'x-li-format: jsonp';
            break;
        }
      } else {
        $header[] = 'Content-Type: text/xml; charset=UTF-8';
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data);
      }
      curl_setopt($handle, CURLOPT_HTTPHEADER, $header);
    
      $this->last_request_url = $url;
      $this->last_request_headers = $header;
      
      $return_data['linkedin']        = curl_exec($handle);
      $return_data['info']            = curl_getinfo($handle);
      $return_data['oauth']['header'] = $oauth_req->to_header(self::_API_OAUTH_REALM);
      $return_data['oauth']['string'] = $oauth_req->base_string;
            
      if(self::isThrottled($return_data['linkedin'])) {
        throw new LinkedInException('LinkedIn->fetch(): throttling limit for this user/application has been reached for LinkedIn resource - ' . $url);
      }
      
      curl_close($handle);
      
      return $return_data;
    } catch(OAuthException $e) {
      throw new LinkedInException('OAuth exception caught: ' . $e->getMessage());
    }
	}
	
	public function flagPost($pid, $type) {
		if(!is_string($pid)) {
			throw new LinkedInException('LinkedIn->flagPost(): bad data passed, $pid must be of type string');
		}
		if(!is_string($type)) {
			throw new LinkedInException('LinkedIn->flagPost(): bad data passed, $like must be of type string');
		}
		$data = '<?xml version="1.0" encoding="UTF-8"?>';
		switch($type) {
			case 'promotion':
				$data .= '<code>promotion</code>';
				break;
			case 'job':
				$data .= '<code>job</code>';
				break;
			default: 
				throw new LinkedInException('LinkedIn->flagPost(): invalid value for $type, must be one of: "promotion", "job"');
				break;	
		}
		
		$query    = self::_URL_API . '/v1/posts/' . $pid . '/category/code';
		$response = $this->fetch('PUT', $query, $data);
		  
		return $this->checkResponse(204, $response);
	}
	
	public function followCompany($cid) {
	  if(!is_string($cid)) {
		  throw new LinkedInException('LinkedIn->followCompany(): bad data passed, $cid must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/people/~/following/companies';
	  $response = $this->fetch('POST', $query, '<company><id>' . trim($cid) . '</id></company>');
	  
	  return $this->checkResponse(201, $response);
	}
	
	public function followPost($pid, $follow) {
		if(!is_string($pid)) {
			throw new LinkedInException('LinkedIn->followPost(): bad data passed, $pid must be of type string');
		}
		if(!($follow === TRUE || $follow === FALSE)) {
			throw new LinkedInException('LinkedIn->followPost(): bad data passed, $follow must be of type boolean');
		}
		
		$data = '<?xml version="1.0" encoding="UTF-8"?>
				     <is-following>'. (($follow) ? 'true' : 'false'). '</is-following>';
		
		$query    = self::_URL_API . '/v1/posts/' . trim($pid) . '/relation-to-viewer/is-following';
		$response = $this->fetch('PUT', $query, $data);
		
		return $this->checkResponse(204, $response);
	}
	
	public function followedCompanies() {	  
    $query    = self::_URL_API . '/v1/people/~/following/companies';
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function getApplicationKey() {
	  return $this->application_key;
	}
	
	public function getApplicationSecret() {
	  return $this->application_secret;
	}
	
	public function getCallbackUrl() {
	  return $this->callback;
	}
  
	public function getResponseFormat() {
	  return $this->response_format;
	}
	
	public function getToken() {
	  return $this->token;
	}
	
	public function getTokenAccess() {
	  return $this->getToken();
	}
	
	public function group($gid, $options = '') {
		if(!is_string($gid)){
			throw new LinkedInException('LinkedIn->group(): bad data passed, $gid must be of type string.');
		}
		if(!is_string($options)) {
			throw new LinkedInException('LinkedIn->group(): bad data passed, $options must be of type string');
		}
	
		$query    = self::_URL_API . '/v1/groups/' . trim($gid) . trim($options); 
		$response = $this->fetch('GET', $query);
		
		return $this->checkResponse(200, $response);
	}
	
	public function groupMemberships($options = '') {
		if(!is_string($options)) {
			throw new LinkedInException('LinkedIn->groupMemberships(): bad data passed, $options must be of type string');
		}
		
		$query    = self::_URL_API . '/v1/people/~/group-memberships' . trim($options) . '?membership-state=member';
		$response = $this->fetch('GET', $query);
		
		return $this->checkResponse(200, $response);
	}
	
	public function groupPost($pid, $options = '') {
		if(!is_string($pid)) {
			throw new LinkedInException('LinkedIn->groupPost(): bad data passed, $pid must be of type string.');
		}
		if(!is_string($options)) {
			throw new LinkedInException('LinkedIn->groupPost(): bad data passed, $options must be of type string.');
		}
		
		$query    = self::_URL_API . '/v1/posts/' . trim($pid) . trim($options);
		$response = $this->fetch('GET', $query);
		
		return $this->checkResponse(200, $response);
	}
	
	public function groupPostComments($pid, $options = ''){ 
		if(!is_string($pid)){
			throw new LinkedInException('LinkedIn->groupPostComments(): bad data passed, $pid must be of type string.');
		}
		if(!is_string($options)) {
			throw new LinkedInException('LinkedIn->groupPostComments(): bad data passed, $options must be of type string.');
		}		
		
		$query    = self::_URL_API . '/v1/posts/' . trim($pid) . '/comments' . trim($options);
		$response = $this->fetch('GET', $query);

		return $this->checkResponse(200, $response);
	}
	
	public function groupPosts($gid, $options = '') {
		if(!is_string($gid)){
			throw new LinkedInException('LinkedIn->groupPosts(): bad data passed, $gid must be of type string');
		}
		if(!is_string($options)){
			throw new LinkedInException('LinkedIn->groupPosts(): bad data passed, $options must be of type string');
		}
		
		$query    = self::_URL_API . '/v1/groups/' . trim($gid)  .'/posts' . trim($options);
		$response = $this->fetch('GET', $query);
		
		return $this->checkResponse(200, $response);
	}
	
	public function groupSettings($gid, $options = '') {
		if(!is_string($gid)) {
			throw new LinkedInException('LinkedIn->groupSettings(): bad data passed, $gid must be of type string');
		}
		if(!is_string($options)) {
			throw new LinkedInException('LinkedIn->groupSettings(): bad data passed, $options must be of type string');
		}
		
		$query    = self::_URL_API . '/v1/people/~/group-memberships/' . trim($gid) . trim($options);
		$response = $this->fetch('GET', $query);
		
		return $this->checkResponse(200, $response);
	}
	
	public function invite($method, $recipient, $subject, $body, $type = 'friend') {
    if(empty($recipient)) {
   		throw new LinkedInException('LinkedIn->invite(): you must provide an invitation recipient.');
    }
    switch($method) {
      case 'email':
        if(is_array($recipient)) {
          $recipient = array_map('trim', $recipient);
        } else {
          throw new LinkedInException('LinkedIn->invite(): invitation recipient email/name array is malformed.');
        }
        break;
      case 'id':
        $recipient = trim($recipient);
        if(!self::isId($recipient)) {
          throw new LinkedInException('LinkedIn->invite(): invitation recipient ID does not match LinkedIn format.');
        }
        break;
      default:
        throw new LinkedInException('LinkedIn->invite(): bad invitation method, must be one of: email, id.');
        break;
    }
    if(!empty($subject)) {
      $subject = trim(htmlspecialchars(strip_tags(stripslashes($subject))));
    } else {
      throw new LinkedInException('LinkedIn->invite(): message subject is empty.');
    }
    if(!empty($body)) {
      $body = trim(htmlspecialchars(strip_tags(stripslashes($body))));
      if(strlen($body) > self::_INV_BODY_LENGTH) {
        throw new LinkedInException('LinkedIn->invite(): message body length is too long - max length is ' . self::_INV_BODY_LENGTH . ' characters.');
      }
    } else {
      throw new LinkedInException('LinkedIn->invite(): message body is empty.');
    }
    switch($type) {
      case 'friend':
        break;
      default:
        throw new LinkedInException('LinkedIn->invite(): bad invitation type, must be one of: friend.');
        break;
    }
    
		$data   = '<?xml version="1.0" encoding="UTF-8"?>
		           <mailbox-item>
		             <recipients>
                   <recipient>';
                     switch($method) {
                       case 'email':
                         $data .= '<person path="/people/email=' . $recipient['email'] . '">
                                     <first-name>' . htmlspecialchars($recipient['first-name']) . '</first-name>
                                     <last-name>' . htmlspecialchars($recipient['last-name']) . '</last-name>
                                   </person>';
                         break;
                       case 'id':
                         $data .= '<person path="/people/id=' . $recipient . '"/>';
                         break;
                     }
    $data  .= '    </recipient>
                 </recipients>
                 <subject>' . $subject . '</subject>
                 <body>' . $body . '</body>
                 <item-content>
                   <invitation-request>
                     <connect-type>';
                       switch($type) {
                         case 'friend':
                           $data .= 'friend';
                           break;
                       }
    $data  .= '      </connect-type>';
                     switch($method) {
                       case 'id':
                         $query                 = 'id=' . $recipient . ':(api-standard-profile-request)';
                         $response              = self::profile($query);
                         if($response['info']['http_code'] == 200) {
                           $response['linkedin'] = self::xmlToArray($response['linkedin']);
                           if($response['linkedin'] === FALSE) {
                             throw new LinkedInException('LinkedIn->invite(): LinkedIn returned bad XML data.');
                           }
                           $authentication = explode(':', $response['linkedin']['person']['children']['api-standard-profile-request']['children']['headers']['children']['http-header']['children']['value']['content']);
                                  
                           $data .= '<authorization>
                                       <name>' . $authentication[0] . '</name>
                                       <value>' . $authentication[1] . '</value>
                                     </authorization>';
                         } else {
                           throw new LinkedInException('LinkedIn->invite(): could not send invitation, LinkedIn says: ' . print_r($response['linkedin'], TRUE));
                         }
                         break;
                     }
    $data  .= '    </invitation-request>
                 </item-content>
               </mailbox-item>';
    
    $query    = self::_URL_API . '/v1/people/~/mailbox';
    $response = $this->fetch('POST', $query, $data);
		
    return $this->checkResponse(201, $response);
	}
	
	public static function isId($id) {
    if(!is_string($id)) {
	    throw new LinkedInException('LinkedIn->isId(): bad data passed, $id must be of type string.');
	  }
	  
	  $pattern = '/^[a-z0-9_\-]{10}$/i';
	  if($match = preg_match($pattern, $id)) {
	    $return_data = TRUE;
	  } else {
	    $return_data = FALSE;
	  }
	  return $return_data;
	}
	
	public static function isThrottled($response) {
	  $return_data = FALSE;
    
	  if(!empty($response) && is_string($response)) {
      $temp_response = self::xmlToArray($response);
  	  if($temp_response !== FALSE) {
    	  if(array_key_exists('error', $temp_response) && ($temp_response['error']['children']['status']['content'] == 403) && preg_match('/throttle/i', $temp_response['error']['children']['message']['content'])) {

  	      $return_data = TRUE;
    	  }
  	  }
  	}
  	return $return_data;
	}
	
	public function job($jid, $options = '') {
	  if(!is_string($jid)) {
		  throw new LinkedInException('LinkedIn->job(): bad data passed, $jid must be of type string.');
	  }
	  if(!is_string($options)) {
		  throw new LinkedInException('LinkedIn->job(): bad data passed, $options must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/jobs/' . trim($jid) . trim($options);
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function joinGroup($gid) {
		if(!is_string($gid)) {
			throw new LinkedInException('LinkedIn->joinGroup(): bad data passed, $gid must be of type string.');
		}
		
		$data = '<?xml version="1.0" encoding="UTF-8"?>
  				   <group-membership>
  				   	 <membership-state>
  				  	 	 <code>member</code>
  				  	 </membership-state>
  				   </group-membership>';
		
		$query    = self::_URL_API . '/v1/people/~/group-memberships/' . trim($gid);
		$response = $this->fetch('PUT', $query, $data);
		
		return $this->checkResponse(array(200, 201), $response);
	}
	        	
	public function lastRequestHeader() {
	   return $this->last_request_headers;
	}
	  	
	public function lastRequestUrl() {
	   return $this->last_request_url;
	}
	
	public function leaveGroup($gid){
		if(!is_string($gid)) {
			throw new LinkedInException('LinkedIn->leaveGroup(): bad data passed, $gid must be of type string');
		}
		
		$query    = self::_URL_API . '/v1/people/~/group-memberships/'  .trim($gid);
		$response = $this->fetch('DELETE', $query);
		
		return $this->checkResponse(204, $response);
	}
	
	public function like($uid) {
	  if(!is_string($uid)) {
		  throw new LinkedInException('LinkedIn->like(): bad data passed, $uid must be of type string.');
	  }
    
		$data = '<?xml version="1.0" encoding="UTF-8"?>
		         <is-liked>true</is-liked>';
		
    $query    = self::_URL_API . '/v1/people/~/network/updates/key=' . $uid . '/is-liked';
    $response = $this->fetch('PUT', $query, $data);
    
    return $this->checkResponse(201, $response);
	}
	
	public function likePost($pid, $like) {
		if(!is_string($pid)) {
			throw new LinkedInException ('LinkedIn->likePost(): bad data passed, $pid must be of type string');
		}
		if(!($like === TRUE || $like === FALSE)) {
			throw new LinkedInException('LinkedIn->likePost(): bad data passed, $like must be of type boolean');
		}
		
		$data = '<?xml version="1.0" encoding="UTF-8"?>
		         <is-liked>'.(($like) ? 'true': 'false').'</is-liked>';
		
		$query    = self::_URL_API . '/v1/posts/' . trim($pid) . '/relation-to-viewer/is-liked';
		$response = $this->fetch('PUT', $query, $data);
		
		return $this->checkResponse(204, $response);
	}
	
	public function likes($uid) {
	  if(!is_string($uid)) {
		  throw new LinkedInException('LinkedIn->likes(): bad data passed, $uid must be of type string.');
	  }
		
    $query    = self::_URL_API . '/v1/people/~/network/updates/key=' . $uid . '/likes';
    $response = $this->fetch('GET', $query);
    
    return $this->checkResponse(200, $response);
	}
	
	public function message($recipients, $subject, $body, $copy_self = FALSE) {
    if(!empty($subject) && is_string($subject)) {
      $subject = trim(strip_tags(stripslashes($subject)));
    } else {
      throw new LinkedInException('LinkedIn->message(): bad data passed, $subject must be of type string.');
    }
    if(!empty($body) && is_string($body)) {
      $body = trim(strip_tags(stripslashes($body)));
    } else {
      throw new LinkedInException('LinkedIn->message(): bad data passed, $body must be of type string.');
    }
    if(!is_array($recipients) || count($recipients) < 1) {
      throw new LinkedInException('LinkedIn->message(): at least one message recipient required.');
    }
    
		$data   = '<?xml version="1.0" encoding="UTF-8"?>
		           <mailbox-item>
		             <recipients>';
    $data  .=     ($copy_self) ? '<recipient><person path="/people/~"/></recipient>' : '';
                  for($i = 0; $i < count($recipients); $i++) {
                    if(is_string($recipients[$i])) {
                      $data .= '<recipient><person path="/people/' . trim($recipients[$i]) . '"/></recipient>';
                    } else {
                      throw new LinkedInException ('LinkedIn->message(): bad data passed, $recipients must be an array of type string.');
                    }
                  }
    $data  .= '  </recipients>
                 <subject>' . htmlspecialchars($subject) . '</subject>
                 <body>' . htmlspecialchars($body) . '</body>
               </mailbox-item>';
    
    $query    = self::_URL_API . '/v1/people/~/mailbox';
    $response = $this->fetch('POST', $query, $data);
		
    return $this->checkResponse(201, $response);
	}
	
	public function postJob($xml) {
    if(is_string($xml)) {
      $xml = trim(stripslashes($xml));
    } else {
      throw new LinkedInException('LinkedIn->postJob(): bad data passed, $xml must be of type string.');
    }
   
    $query    = self::_URL_API . '/v1/jobs';
    $response = $this->fetch('POST', $query, $xml);
		
    return $this->checkResponse(201, $response);
	}
	
	public function profile($options = '~') {
	  if(!is_string($options)) {
		  throw new LinkedInException('LinkedIn->profile(): bad data passed, $options must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/people/' . trim($options);
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function raw($method, $url, $body = NULL) {
	  if(!is_string($method)) {
		  throw new LinkedInException('LinkedIn->raw(): bad data passed, $method must be of string value.');
	  }
	  if(!is_string($url)) {
		  throw new LinkedInException('LinkedIn->raw(): bad data passed, $url must be of string value.');
	  }
	  if(!is_null($body) && !is_string($url)) {
		  throw new LinkedInException('LinkedIn->raw(): bad data passed, $body must be of string value.');
	  }
    
	  $query = self::_URL_API . '/v1' . trim($url);
	  return $this->fetch($method, $query, $body);
	}
	
	public function removeSuggestedGroup($gid) {
		if(!is_string($gid)) {
			throw new LinkedInException('LinkedIn->removeSuggestedGroup(): bad data passed, $gid must be of type string');
		} 
		
		$query    = self::_URL_API . '/v1/people/~/suggestions/groups/'  .trim($gid);
		$response = $this->fetch('DELETE', $query);
		
		return $this->checkResponse(204, $response);
	}
	
	public function renewJob($jid, $cid) {
	  if(!is_string($jid)) {
		  throw new LinkedInException('LinkedIn->renewJob(): bad data passed, $jid must be of string value.');
	  }
	  if(!is_string($cid)) {
		  throw new LinkedInException('LinkedIn->renewJob(): bad data passed, $cid must be of string value.');
	  }
	  
		$data   = '<?xml version="1.0" encoding="UTF-8"?>
		           <job>
		             <contract-id>' . trim($cid) . '</contract-id>
                 <renewal/>
               </job>';
               
	  $query    = self::_URL_API . '/v1/jobs/partner-job-id=' . trim($jid);
	  $response = $this->fetch('PUT', $query, $data);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function retrieveTokenAccess($token, $secret, $verifier) {
    if(!is_string($token) || !is_string($secret) || !is_string($verifier)) {
		  throw new LinkedInException('LinkedIn->retrieveTokenAccess(): bad data passed, string type is required for $token, $secret and $verifier.');
    }
    
	  $this->setToken(array('oauth_token' => $token, 'oauth_token_secret' => $secret));
    $parameters = array(
      'oauth_verifier' => $verifier
    );
    $response = $this->fetch(self::_METHOD_TOKENS, self::_URL_ACCESS, NULL, $parameters);
    parse_str($response['linkedin'], $response['linkedin']);
    
    if($response['info']['http_code'] == 200) {
      $this->setToken($response['linkedin']);
      
      $return_data            = $response;
      $return_data['success'] = TRUE;
    } else {
       $this->setToken(NULL);
       
      $return_data            = $response;
      $return_data['error']   = 'HTTP response from LinkedIn end-point was not code 200';
      $return_data['success'] = FALSE;
    }
    return $return_data;
	}
	
	public function retrieveTokenRequest() {
    $parameters = array(
      'oauth_callback' => $this->getCallbackUrl()
    );
    $response = $this->fetch(self::_METHOD_TOKENS, self::_URL_REQUEST, NULL, $parameters);
    parse_str($response['linkedin'], $response['linkedin']);
    
    if(($response['info']['http_code'] == 200) && (array_key_exists('oauth_callback_confirmed', $response['linkedin'])) && ($response['linkedin']['oauth_callback_confirmed'] == 'true')) {
      $this->setToken($response['linkedin']);
      
      $return_data            = $response;
      $return_data['success'] = TRUE;        
    } else {
      $this->setToken(NULL);
      
      $return_data = $response;
      if((array_key_exists('oauth_callback_confirmed', $response['linkedin'])) && ($response['linkedin']['oauth_callback_confirmed'] == 'true')) {
        $return_data['error'] = 'HTTP response from LinkedIn end-point was not code 200';
      } else {
        $return_data['error'] = 'OAuth callback URL was not confirmed by the LinkedIn end-point';
      }
      $return_data['success'] = FALSE;
    }
    return $return_data;
	}
	
	public function revoke() {
	  $response = $this->fetch('GET', self::_URL_REVOKE);
  
    return $this->checkResponse(200, $response);
	}
	
	public function search($options = NULL) {
		return searchPeople($options);
	}
	
	public function searchCompanies($options = '') {
	  if(!is_string($options)) {
		  throw new LinkedInException('LinkedIn->searchCompanies(): bad data passed, $options must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/company-search' . trim($options);
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function searchJobs($options = '') {
	  if(!is_string($options)) {
		  throw new LinkedInException('LinkedIn->jobsSearch(): bad data passed, $options must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/job-search' . trim($options);
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function searchPeople($options = NULL) {
    if(!is_null($options) && !is_string($options)) {
		  throw new LinkedInException('LinkedIn->search(): bad data passed, $options must be of type string.');
	  }
	  
    $query    = self::_URL_API . '/v1/people-search' . trim($options);
		$response = $this->fetch('GET', $query);
		
		return $this->checkResponse(200, $response);
	}
	
	public function setApplicationKey($key) {
	  $this->application_key = $key;
	}
	
	public function setApplicationSecret($secret) {
	  $this->application_secret = $secret;
	}
	
	public function setCallbackUrl($url) {
	  $this->callback = $url;
	}
	
	public function setGroupSettings($gid, $xml) {
		if(!is_string ($gid)) {
      throw new LinkedInException('LinkedIn->setGroupSettings(): bad data passed, $token_access should be in array format.');
		}
		if(!is_string ($xml)) {
      throw new LinkedInException('LinkedIn->setGroupSettings(): bad data passed, $token_access should be in array format.');
		}
		
		$query    = self::_URL_API . '/v1/people/~/group-memberships/' . trim($gid);
		$response = $this->fetch('PUT', $query, $xml);
		
		return $this->checkResponse(200, $response);
	}
	
	public function setResponseFormat($format = self::_DEFAULT_RESPONSE_FORMAT) {
	  $this->response_format = $format;
	}
	
	public function setToken($token) {
    if(!is_null($token) && !is_array($token)) {
      throw new LinkedInException('LinkedIn->setToken(): bad data passed, $token_access should be in array format.');
    }
    
    $this->token = $token;
	}
	
	public function setTokenAccess($token_access) {
    $this->setToken($token_access);
	}
	
	public function share($action, $content, $private = TRUE, $twitter = FALSE) {
    if(!empty($action) && !empty($content)) {
      $share_flag   = FALSE;
      $content_xml  = NULL;
      switch($action) {
        case 'new':
          if(array_key_exists('title', $content) && array_key_exists('submitted-url', $content)) {
            $content_title = trim(htmlspecialchars(strip_tags(stripslashes($content['title']))));
            if(strlen($content_title) > self::_SHARE_CONTENT_TITLE_LENGTH) {
              throw new LinkedInException('LinkedIn->share(): title length is too long - max length is ' . self::_SHARE_CONTENT_TITLE_LENGTH . ' characters.');
            }
            $content_xml .= '<content>
                               <title>' . $content_title . '</title>
                               <submitted-url>' . trim(htmlspecialchars($content['submitted-url'])) . '</submitted-url>';
            if(array_key_exists('submitted-image-url', $content)) {
              $content_xml .= '<submitted-image-url>' . trim(htmlspecialchars($content['submitted-image-url'])) . '</submitted-image-url>';
            }
            if(array_key_exists('description', $content)) {
              $content_desc = trim(htmlspecialchars(strip_tags(stripslashes($content['description']))));
              if(strlen($content_desc) > self::_SHARE_CONTENT_DESC_LENGTH) {
                throw new LinkedInException('LinkedIn->share(): description length is too long - max length is ' . self::_SHARE_CONTENT_DESC_LENGTH . ' characters.');
              }
              $content_xml .= '<description>' . $content_desc . '</description>';
            }
            $content_xml .= '</content>';
            
            $share_flag = TRUE;
          }
          
          if(array_key_exists('comment', $content)) {
          	$comment = htmlspecialchars(trim(strip_tags(stripslashes($content['comment']))));
          	if(strlen($comment) > self::_SHARE_COMMENT_LENGTH) {
              throw new LinkedInException('LinkedIn->share(): comment length is too long - max length is ' . self::_SHARE_COMMENT_LENGTH . ' characters.');
            }
            $content_xml .= '<comment>' . $comment . '</comment>';
          	
          	$share_flag = TRUE; 
      	  }
          break;
        case 'reshare':
          if(array_key_exists('id', $content)) {
            $content_xml .= '<attribution>
                               <share>
                                 <id>' . trim($content['id']) . '</id>
                               </share>
                             </attribution>';
            
            if(array_key_exists('comment', $content)) {
            	$comment = htmlspecialchars(trim(strip_tags(stripslashes($content['comment']))));
            	if(strlen($comment) > self::_SHARE_COMMENT_LENGTH) {
                throw new LinkedInException('LinkedIn->share(): comment length is too long - max length is ' . self::_SHARE_COMMENT_LENGTH . ' characters.');
              }
              $content_xml .= '<comment>' . $comment . '</comment>';
        	  }
        	  
        	  $share_flag = TRUE;
          }
          break;
        default:
          throw new LinkedInException('LinkedIn->share(): share action is an invalid value, must be one of: share, reshare.');
          break;
      }
      
      if($share_flag) {
        $visibility = ($private) ? 'connections-only' : 'anyone';
        $data       = '<?xml version="1.0" encoding="UTF-8"?>
                       <share>
                         ' . $content_xml . '
                         <visibility>
                           <code>' . $visibility . '</code>
                         </visibility>
                       </share>';
        
        $share_url = self::_URL_API . '/v1/people/~/shares';
  		  if($twitter) {
          $share_url .= '?twitter-post=true';
  			}
        
        $response = $this->fetch('POST', $share_url, $data);
  		} else {
		    throw new LinkedInException('LinkedIn->share(): sharing data constraints not met; check that you have supplied valid content and combinations of content to share.');
  		}
    } else {
		  throw new LinkedInException('LinkedIn->share(): sharing action or shared content is missing.');
    }
    
    return $this->checkResponse(201, $response);
	}
	
	public function statistics() {
    $query    = self::_URL_API . '/v1/people/~/network/network-stats';
		$response = $this->fetch('GET', $query);
		
		return $this->checkResponse(200, $response);
	}
	
	public function suggestedCompanies() {
    $query    = self::_URL_API . '/v1/people/~/suggestions/to-follow/companies';
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function suggestedGroups() {
		$query    = self::_URL_API . '/v1/people/~/suggestions/groups:(id,name,is-open-to-non-members)';
		$response = $this->fetch('GET', $query);
		
		return $this->checkResponse (200, $response);
	}

	public function suggestedJobs($options = ':(jobs)') {
	  if(!is_string($options)) {
		  throw new LinkedInException('LinkedIn->suggestedJobs(): bad data passed, $options must be of type string.');
	  }
	
	  $query    = self::_URL_API . '/v1/people/~/suggestions/job-suggestions' . trim($options);
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public function unbookmarkJob($jid) {
	  if(!is_string($jid)) {
		  throw new LinkedInException('LinkedIn->unbookmarkJob(): bad data passed, $jid must be of type string.');
	  }
	  
	  $query    = self::_URL_API . '/v1/people/~/job-bookmarks/' . trim($jid);
	  $response = $this->fetch('DELETE', $query);
	  
	  return $this->checkResponse(204, $response);
	}
	
	public function unfollowCompany($cid) {
	  if(!is_string($cid)) {
		  throw new LinkedInException('LinkedIn->unfollowCompany(): bad data passed, $cid must be of string value.');
	  }
	  
	  $query    = self::_URL_API . '/v1/people/~/following/companies/id=' . trim($cid);
	  $response = $this->fetch('DELETE', $query);
	  
	  return $this->checkResponse(204, $response);
	}
	
	public function unlike($uid) {
	  if(!is_string($uid)) {
		  throw new LinkedInException('LinkedIn->unlike(): bad data passed, $uid must be of type string.');
	  }
    
		$data = '<?xml version="1.0" encoding="UTF-8"?>
		         <is-liked>false</is-liked>';
		
    $query    = self::_URL_API . '/v1/people/~/network/updates/key=' . $uid . '/is-liked';
    $response = $this->fetch('PUT', $query, $data);
    
    return $this->checkResponse(201, $response);
	}
	
	public function updateNetwork($update) {
    if(!is_string($update)) {
		  throw new LinkedInException('LinkedIn->updateNetwork(): bad data passed, $update must be a non-zero length string.');
    }
	
    $response = self::profile('~:(first-name,last-name,site-standard-profile-request)');
    if($response['success'] === TRUE) {
      $person = self::xmlToArray($response['linkedin']);
      if($person === FALSE) {
        throw new LinkedInException('LinkedIn->updateNetwork(): LinkedIn returned bad XML data.');
      }
  		$fields = $person['person']['children'];
  
  		$first_name   = trim($fields['first-name']['content']);
  		$last_name    = trim($fields['last-name']['content']);
  		$profile_url  = trim($fields['site-standard-profile-request']['children']['url']['content']);
  
      $update = trim(htmlspecialchars(strip_tags($update, self::_NETWORK_HTML)));
      if(strlen($update) > self::_NETWORK_LENGTH) {
        throw new LinkedInException('LinkedIn->share(): update length is too long - max length is ' . self::_NETWORK_LENGTH . ' characters.');
      }
      $user   = htmlspecialchars('<a href="' . $profile_url . '">' . $first_name . ' ' . $last_name . '</a>');
  		$data   = '<activity locale="en_US">
    				       <content-type>linkedin-html</content-type>
    				       <body>' . $user . ' ' . $update . '</body>
    				     </activity>';
  
      $query    = self::_URL_API . '/v1/people/~/person-activities';
      $response = $this->fetch('POST', $query, $data);
      
      return $this->checkResponse(201, $response);
    } else {
      throw new LinkedInException('LinkedIn->updateNetwork(): profile data could not be retrieved.');
    }
	}
	
	public function updates($options = NULL, $id = NULL) {
    if(!is_null($options) && !is_string($options)) {
		  throw new LinkedInException('LinkedIn->updates(): bad data passed, $options must be of type string.');
	  }
	  if(!is_null($id) && !is_string($id)) {
		  throw new LinkedInException('LinkedIn->updates(): bad data passed, $id must be of type string.');
	  }
	  
	  if(!is_null($id) && self::isId($id)) {
	    $query = self::_URL_API . '/v1/people/' . $id . '/network/updates' . trim($options);
	  } else {
      $query = self::_URL_API . '/v1/people/~/network/updates' . trim($options);
    }
	  $response = $this->fetch('GET', $query);
	  
	  return $this->checkResponse(200, $response);
	}
	
	public static function xmlToArray($xml) {
    if(!is_string($xml)) {
      throw new LinkedInException('LinkedIn->xmlToArray(): bad data passed, $xml must be a non-zero length string.');
	  }
	  
	  $parser = xml_parser_create();
	  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    if(xml_parse_into_struct($parser, $xml, $tags)) {
	    $elements = array();
      $stack    = array();
      foreach($tags as $tag) {
        $index = count($elements);
        if($tag['type'] == 'complete' || $tag['type'] == 'open') {
          $elements[$tag['tag']]               = array();
          $elements[$tag['tag']]['attributes'] = (array_key_exists('attributes', $tag)) ? $tag['attributes'] : NULL;
          $elements[$tag['tag']]['content']    = (array_key_exists('value', $tag)) ? $tag['value'] : NULL;
          if($tag['type'] == 'open') {
            $elements[$tag['tag']]['children'] = array();
            $stack[count($stack)] = &$elements;
            $elements = &$elements[$tag['tag']]['children'];
          }
        }
        if($tag['type'] == 'close') {
          $elements = &$stack[count($stack) - 1];
          unset($stack[count($stack) - 1]);
        }
      }
      $return_data = $elements;
	  } else {
	    $return_data = FALSE;
	  }
	  xml_parser_free($parser);
    return $return_data;
  }
}
