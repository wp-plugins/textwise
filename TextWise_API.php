<?php

class TextWise_API
{
	//Set default whether to use fopen or curl for API call.
	var $use_curl = false;

    // parameter names that will be passed for the various service Ids
    var $_signatureId = 'ConfigurationID';
    var $_conceptId = 'ConfigurationID';
    var $_categoryId = 'ConfigurationID';
    var $_matchId = 'indexId';
    var $_extractorId = 'ExtractorID';

    var $_baseUrl;
    var $_token;

	//Class Constructor, PHP4 compatible format
    function TextWise_API($parameters)
    {
		//Check for http call functionality

		//Force CURL if config has "allow_url_fopen = Off"
		if ( !ini_get('allow_url_fopen') ) {
			$this->use_curl = true;
		}

		if ( $this->use_curl && !function_exists('curl_init') ) {
			return array('error' => 'Server cannot support outgoing HTTP requests');
		}

    	//Check for required configuration parameters
        if ( !isset($parameters['baseUrl']) ) {
        	return array('error' => "required parameter 'baseUrl' is not defined");
        }
        $this->_baseUrl = $parameters['baseUrl'];
		if ( !isset($parameters['token']) ) {
			return array('error' => "required parameter 'token' is not defined");
		}
        $this->_token = $parameters['token'];
    }

	//Calls to services
    function signature($parameters)
    {
        return $this->_send($parameters, 'signature');
    }

    function concept($parameters)
    {
        return $this->_send($parameters, 'concept');
    }

    function category($parameters)
    {
        return $this->_send($parameters, 'category');
    }

    function match($parameters)
    {
        return $this->_send($parameters, 'match');
    }

    function filter($parameters)
    {
        return $this->_send($parameters, 'filter');
    }

    function _send($parameters, $service)
    {
        $url = $this->_baseUrl;

        if (substr($url, -1) != '/')
        {
            $url .= '/';
        }

        $url .= urlencode($this->_token) . '/';
        $url .= urlencode($service);


		//Append additional settings (Configuration/Index Id) depending on service
		switch ($service) {
			case 'signature':
			case 'concept':
			case 'category':
			case 'filter':
				if (isset($parameters['ConfigurationId'])) {
					$configId = $parameters['ConfigurationId'];
					unset($parameters['ConfigurationId']);
				}
				break;
			case 'match':
				if (isset($parameters[$this->_matchId])) {
					$configId = $parameters[$this->_matchId];
					unset($parameters[$this->_matchId]);
				}
			break;
			default:
				return array('error' =>"unknown service");
		}

		$url .= isset($configId) ? '/' . $configId : '';
		$response = $this->do_post_request($url, $this->build_query($parameters));

		if (strlen($response) == 0) {
			return array('error' => "API did not return a response");
		} else if ( is_array($response) ) {
			return $response;
		}

		switch ($parameters['format']) {
			case 'xml':
			case '':
				$badchars = array("\n", "\r");
				$response = str_replace($badchars, ' ', $response);
				$returnValue = $this->parse_response_xml($response);
				$returnValue = $this->parse_response_array($returnValue);
				break;
			default:
				$returnValue = $response;
		}
		return $returnValue;
	}

	function parse_response_array($arr) {
		$about = array();

		//About section
		if ( isset($arr['response'][0]['about'][0]) ) {
			foreach ($arr['response'][0]['about'][0] as $key => $val) {
				$about[$key] = $val[0]['value'];
			}
			$result['about'] = $about;
		}

		if ( isset($arr['response'][0]['message'][0]) ) {
			foreach ($arr['response'][0]['message'][0] as $key => $val) {
				$message[$key] = $val;
			}

			$result['message'] = $message;
		}

		switch ($about['systemType']) {
			case 'signature':
				$dimensions = array();
				if ( isset($arr['response'][0]['siggen'][0]['siggenResponse'][0]['signature'][0]['dimension']) ) {
			    	foreach ($arr['response'][0]['siggen'][0]['siggenResponse'][0]['signature'][0]['dimension'] as $key => $val) {
			    		$dimensions[$key]['weight']				= $val['weight'];
			    		$dimensions[$key]['index']				= $val['index'];
			    		$dimensions[$key]['label']				= $val['label'];
		    		}
				}
	    		$result['dimensions'] = $dimensions;
				break;
			case 'concept':
				$concepts = array();
				if ( isset($arr['response'][0]['conceptExtractor'][0]['conceptExtractorResponse'][0]['concepts'][0]['concept']) ) {
			    	foreach ($arr['response'][0]['conceptExtractor'][0]['conceptExtractorResponse'][0]['concepts'][0]['concept'] as $key => $val) {
			    		$concepts[$key]['weight']				= $val['weight'];
			    		$concepts[$key]['label']				= $val['label'];
			    		if ( isset($val['position']) ) {
				    		$concepts[$key]['positions']		= $val['position'];
			    		}
		    		}
				}
				$result['concepts'] = $concepts;
				break;
			case 'category':
				$categories = array();
				if ( isset($arr['response'][0]['categorizer'][0]['categorizerResponse'][0]['categories'][0]['category']) ) {
			    	foreach ($arr['response'][0]['categorizer'][0]['categorizerResponse'][0]['categories'][0]['category'] as $key => $val) {
			    		$categories[$key]['id']					= $val['id'];
			    		$categories[$key]['weight']				= $val['weight'];
			    		$categories[$key]['label']				= $val['label'];
		    		}
				}
				$result['categories'] = $categories;
				break;
			case 'match':
				$matches = array();
				if ( isset($arr['response'][0]['contentMatch'][0]['contentMatchResponse'][0]['matches'][0]['match']) ) {
			    	foreach ($arr['response'][0]['contentMatch'][0]['contentMatchResponse'][0]['matches'][0]['match'] as $key => $val) {
			    		$matches[$key]['id']				= $val['id'];
			    		$matches[$key]['score']				= $val['score'];
						if ( isset($val['attribute']) ) {
				    		foreach ($val['attribute'] as $k => $v) {
					    		$matches[$key][$v['name']]	= $v['value'];
				    		}
						}
		    		}
				}
	    		$result['matches'] = $matches;
	    		break;
			case 'filter':
				$result['filteredTextLength'] = $arr['response'][0]['filter'][0]['filterResponse'][0]['filteredTextLength'][0]['value'];
				$result['filteredText'] = $arr['response'][0]['filter'][0]['filterResponse'][0]['filteredText'][0]['value'];
				break;
		}

		return $result;
	}

	//Parse the XML data
	function parse_response_xml($xmldata) {
		$p = xml_parser_create();
		xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 0);
		xml_parse_into_struct($p, $xmldata, $parsed);
		xml_parser_free($p);


		$result = array();
		$current = &$result;
		$lasttag = &$result;
		foreach ($parsed as $e) {
			switch ($e['type']) {
				case 'open':
					$lasttag = &$current;
					$current = &$current[$e['tag']][];
					$current['_parent'] = &$lasttag;

					$current = array_merge($current, $this->process_values($e));
					break;
				case 'complete':
					$current[$e['tag']][] = $this->process_values($e);;
					break;

				case 'close':
					$parent = &$current['_parent'];
					unset($current['_parent']);
					$current = &$parent;
					break;
				case 'cdata':
					if (trim($e['value']) != '') {
						$current['cdata'] = $e['value'];
					}
					break;
			}
		}

		return $result;
	}

	function process_values($e) {
		$data = array();
		if ( isset($e['attributes']) ) {
			foreach ($e['attributes'] as $key => $val) {
				$data[$key] = $val;
			}
		}
		if ( trim($e['value']) != '' ) { $data['value'] = $e['value']; }
		return $data;
	}

	function build_query($parameters) {
		foreach ($parameters as $key => $val) {
			$result[] = urlencode($key).'='.urlencode($val);
		}
		return implode('&', $result);
	}

    function do_post_request($url, $data, $optional_headers = null) {
		//$optional_header use format $optional_headers['Header'] = 'Value';
		$headers['Content-type']		= 'Content-type: application/x-www-form-urlencoded';
		$headers['Content-Length']		= 'Content-Length: '.strlen($data);
		$headers['User-Agent']			= 'User-Agent: PHP/TextWise API';
		if ( $optional_headers != null ) {
			$headers = array_merge($headers, $optional_headers);
		}

		if ( $this->use_curl ) {
			$result = $this->do_post_request_curl($url, $data, $headers);
		} else {
			$result = $this->do_post_request_fopen($url, $data, $headers);
		}
		return $result;
    }

    function do_post_request_fopen($url, $data, $headers) {
		$params = array('http' => array('method' => 'POST', 'content' => $data, 'header' => ''));
		foreach ($headers as $key => $value) {
			$params['http']['header'] .= "{$value}\r\n";
		}

		$httpreq = parse_url($url);
		$http_host = $httpreq['host'];
		$http_path = $httpreq['path'];
		$http_query = $httpreq['query'];
		$crlf = "\r\n";

//		$ctx = stream_context_create($params);
//		$fp = @fopen($url, 'rb', false, $ctx);
		$fp = @fsockopen($http_host, 80, $ferrnum, $ferrstr, 30);
		if ($fp) {
			//??
		} else {
			return array('error' => 'Your web server cannot connect to the TextWise API'.print_r($params, true));
		}

		$request = "POST {$http_path}";
		if ( $http_query ) $request .= "?{$http_query}";
		$request .= " HTTP/1.0$crlf";
		$request .= implode($crlf, $headers). $crlf;
		$request .= 'Host: '. $http_host . $crlf;
		$request .= $crlf;
		$request .= $data;
		$request .= $crlf.$crlf;

		fwrite( $fp, $request );

		$response = '';
		while (!feof($fp)) {
			$response .= fread($fp, 8192);
		}

		if ($response === false)
		{
		    return array('error' => "Your web server cannot read data from $url, $php_errormsg");
		}

		list( $response_header, $response_body ) = explode( "\r\n\r\n", $response, 2 );

		fclose($fp);
		return $response_body;
	}

	function do_post_request_curl($url, $data, $headers) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FAILONERROR, false);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

		$response = curl_exec($curl);

		if ( curl_errno($curl) ) {
			switch ( curl_errno($curl) ) {
				case CURLE_COULDNT_CONNECT:			$error = 'Your web server cannot connect to the TextWise API (curl)'; break;
				default:
					$error = 'CURL error: '.curl_error($curl);
			}
			$response = array('error' => $error );
		}
		curl_close($curl);
		return $response;
	}


}
?>
