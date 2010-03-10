<?php

class TextWise_API
{
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
    	//Check for required configuration parameters
        isset($parameters['baseUrl']) or die("required parameter 'baseUrl' is not defined");
        $this->_baseUrl = $parameters['baseUrl'];

		isset($parameters['token']) or die("required parameter 'token' is not defined");
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

        $url .= $this->_token . '/';
        $url .= $service;


        if ( ($service == 'filter' || $service == 'concept') && isset($parameters['content']) ) {
        	$badchars = array('"', "'");
        	$parameters['content'] = str_replace($badchars, ' ', $parameters['content']);
        }

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
				die("unknown service");
		}

        $url .= isset($configId) ? '/' . $configId : '';
        $response = $this->do_post_request($url, $this->build_query($parameters));

        if (strlen($response) == 0) {
        	die("server did not return a response");
        }

        switch ($parameters['format']) {
        	case 'xml':
        	case '':
        		$badchars = array("\n", "\r");
        		$response = str_replace($badchars, ' ', $response);
        		$returnValue = $this->parse_response_xml($response);
        		break;
        	default:
        		$returnValue = $response;
        }
        return $returnValue;
    }

	//Parse the XML data
    function parse_response_xml($xmldata) {
        $p = xml_parser_create();
        xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($p, $xmldata, $parsed);
        xml_parser_free($p);
        $n = count($parsed);
        $i = 0;

        while ($i < $n)
        {
            $e0 = $parsed[$i];

            if (($e0['tag'] == 'response') && ($e0['level'] == 1))
            {
                // ignore
            }
            else if (($e0['tag'] == 'message') && ($e0['level'] == 2))
            {
                $returnValue['message'] = $e0['attributes'];
                return $returnValue;
            }
            else if (($e0['tag'] == 'about') && ($e0['level'] == 2) && ($e0['type'] == 'open'))
            {
                $aboutArray = array();
                ++$i;

                while ($i < $n)
                {
                    $e1 = $parsed[$i];

                    if (($e1['tag'] == 'about') && ($e1['level'] == 2) && ($e1['type'] == 'close'))
                    {
                        $returnValue['about'] = $aboutArray;
                        break;
                    }
                    else
                    {
                        $aboutArray[$e1['tag']] = $e1['value'];
                    }

                    ++$i;
                }
            }
            else if (($e0['tag'] == 'siggen') && ($e0['level'] == 2) && ($e0['type'] == 'open'))
            {
                ++$i;

                while ($i < $n)
                {
                    $e1 = $parsed[$i];

                    if (($e1['tag'] == 'siggen') && ($e1['level'] == 2) && ($e1['type'] == 'close'))
                    {
                        break;
                    }
                    else if (($e1['tag'] == 'siggenResponse') && ($e1['level'] == 3) && ($e1['type'] == 'open'))
                    {
                        ++$i;

                        while ($i < $n)
                        {
                            $e2 = $parsed[$i];

                            if (($e2['tag'] == 'siggenResponse') && ($e2['level'] == 3) && ($e2['type'] == 'close'))
                            {
                                break;
                            }
                            else if (($e2['tag'] == 'signature') && ($e2['level'] == 4) && ($e2['type'] == 'open'))
                            {
                                $dimensionArray = array();
                                ++$i;

                                while ($i < $n)
                                {
                                    $e3 = $parsed[$i];

                                    if (($e3['tag'] == 'signature') && ($e3['level'] == 4) && ($e3['type'] == 'close'))
                                    {
                                        break;
                                    }
                                    else if (($e3['tag'] == 'dimension') && ($e3['level'] == 5) && ($e3['type'] == 'complete'))
                                    {
                                        $dimensionArray[] = $e3['attributes'];
                                    }
                                    else
                                    {
                                        $this->xml_parse_error($parsed[$i]);
                                    }

                                    ++$i;
                                }

                                $returnValue['dimensions'] = $dimensionArray;
                            }
                            else
                            {
                                $this->xml_parse_error($parsed[$i]);
                            }

                            ++$i;
                        }
                    }
                    else
                    {
                        $this->xml_parse_error($parsed[$i]);
                    }

                    ++$i;
                }
            }
            else if (($e0['tag'] == 'conceptExtractor') && ($e0['level'] == 2) && ($e0['type'] == 'open'))
            {
                ++$i;

                while ($i < $n)
                {
                    $e1 = $parsed[$i];

                    if (($e1['tag'] == 'conceptExtractor') && ($e1['level'] == 2) && ($e1['type'] == 'close'))
                    {
                        break;
                    }
                    else if (($e1['tag'] == 'conceptExtractorResponse') && ($e1['level'] == 3) && ($e1['type'] == 'open'))
                    {
                        ++$i;

                        while ($i < $n)
                        {
                            $e2 = $parsed[$i];

                            if (($e2['tag'] == 'conceptExtractorResponse') && ($e2['level'] == 3) && ($e2['type'] == 'close'))
                            {
                                break;
                            }
                            else if (($e2['tag'] == 'concepts') && ($e2['level'] == 4) && ($e2['type'] == 'open'))
                            {
                                $conceptArray = array();
                                ++$i;

                                while ($i < $n)
                                {
                                    $e3 = $parsed[$i];

                                    if (($e3['tag'] == 'concepts') && ($e3['level'] == 4) && ($e3['type'] == 'close'))
                                    {
                                        break;
                                    }
                                    else if (($e3['tag'] == 'concept') && ($e3['level'] == 5) && ($e3['type'] == 'complete'))
                                    {
                                        $conceptArray[] = $e3['attributes'];
                                    }
                                    //Additions by Jeff Brand to accomodate 'includePositions' option
                                    else if (($e3['tag'] == 'concept') && ($e3['level'] == 5) && ($e3['type'] == 'open'))
                                    {
                                        $conceptArray[] = $e3['attributes'];
                                    	$positionArray = array();
                                    	++$i;
                                    	while ($i < $n)
                                    	{
                                    		$e4 = $parsed[$i];
                                    		if (($e4['tag'] == 'concept') && ($e4['level'] == 5) && ($e4['type'] == 'close'))
                                    		{
                                    			break;
                                    		}
                                    		else if (($e4['tag'] == 'position') && ($e4['level'] == 6) && ($e4['type'] == 'complete'))
                                    		{
                                    			$conceptArray[count($conceptArray)-1]['positions'][] = $e4['attributes'];
                                    		}
                                    		else
                                    		{
                                    			$this->xml_parse_error($parsed[$i]);
                                    		}

                                    		++$i;
                                    	}
                                    }
                                    //End of Jeff's additions
                                    else
                                    {
                                        $this->xml_parse_error($parsed[$i]);
                                    }

                                    ++$i;
                                }

                                $returnValue['concepts'] = $conceptArray;
                            }
                            else if (($e2['tag'] == 'concepts') && ($e2['level'] == 4) && ($e2['type'] == 'complete'))
                            {
								$returnValue['concepts'] = array();
                            }
                            else
                            {
                                $this->xml_parse_error($parsed[$i]);
                            }

                            ++$i;
                        }
                    }
                    else
                    {
                        $this->xml_parse_error($parsed[$i]);
                    }

                    ++$i;
                }
            }
            else if (($e0['tag'] == 'categorizer') && ($e0['level'] == 2) && ($e0['type'] == 'open'))
            {
                ++$i;

                while ($i < $n)
                {
                    $e1 = $parsed[$i];

                    if (($e1['tag'] == 'categorizer') && ($e1['level'] == 2) && ($e1['type'] == 'close'))
                    {
                        break;
                    }
                    else if (($e1['tag'] == 'categorizerResponse') && ($e1['level'] == 3) && ($e1['type'] == 'open'))
                    {
                        ++$i;

                        while ($i < $n)
                        {
                            $e2 = $parsed[$i];

                            if (($e2['tag'] == 'categorizerResponse') && ($e2['level'] == 3) && ($e2['type'] == 'close'))
                            {
                                break;
                            }
                            else if (($e2['tag'] == 'categories') && ($e2['level'] == 4) && ($e2['type'] == 'open'))
                            {
                                $categoryArray = array();
                                ++$i;

                                while ($i < $n)
                                {
                                    $e3 = $parsed[$i];

                                    if (($e3['tag'] == 'categories') && ($e3['level'] == 4) && ($e3['type'] == 'close'))
                                    {
                                        break;
                                    }
                                    else if (($e3['tag'] == 'category') && ($e3['level'] == 5) && ($e3['type'] == 'complete'))
                                    {
                                        $categoryArray[] = $e3['attributes'];
                                    }
                                    else
                                    {
                                        $this->xml_parse_error($parsed[$i]);
                                    }

                                    ++$i;
                                }

                                $returnValue['categories'] = $categoryArray;
                            }
                            else if (($e2['tag'] == 'categories') && ($e2['level'] == 4) && ($e2['type'] == 'complete'))
							{
								$returnValue['categories'] = array();
							}
                            else
                            {
                                $this->xml_parse_error($parsed[$i]);
                            }

                            ++$i;
                        }
                    }
                    else
                    {
                        $this->xml_parse_error($parsed[$i]);
                    }

                    ++$i;
                }
            }
            else if (($e0['tag'] == 'contentMatch') && ($e0['level'] == 2) && ($e0['type'] == 'open'))
            {
                ++$i;

                while ($i < $n)
                {
                    $e1 = $parsed[$i];

                    if (($e1['tag'] == 'contentMatch') && ($e1['level'] == 2) && ($e1['type'] == 'close'))
                    {
                        break;
                    }
                    else if (($e1['tag'] == 'contentMatchResponse') && ($e1['level'] == 3) && ($e1['type'] == 'open'))
                    {
                        ++$i;

                        while ($i < $n)
                        {
                            $e2 = $parsed[$i];

                            if (($e2['tag'] == 'contentMatchResponse') && ($e2['level'] == 3) && ($e2['type'] == 'close'))
                            {
                                break;
                            }
                            else if (($e2['tag'] == 'matches') && ($e2['level'] == 4) && ($e2['type'] == 'open'))
                            {
                                $matchesArray = array();
                                ++$i;

                                while ($i < $n)
                                {
                                    $e3 = $parsed[$i];

                                    if (($e3['tag'] == 'matches') && ($e3['level'] == 4) && ($e3['type'] == 'close'))
                                    {
                                        break;
                                    }
                                    else if (($e3['tag'] == 'match') && ($e3['level'] == 5) && ($e3['type'] == 'open'))
                                    {
                                        $matchArray = $e3['attributes'];
                                        ++$i;

                                        while ($i < $n)
                                        {
                                            $e4 = $parsed[$i];

                                            if (($e4['tag'] == 'match') && ($e4['level'] == 5) && ($e4['type'] == 'close'))
                                            {
                                                break;
                                            }
                                            else if (($e4['tag'] == 'attribute') && ($e4['level'] == 6) && ($e4['type'] == 'complete'))
                                            {
                                                $attributeName = $e4['attributes']['name'];
                                                $matchArray[$attributeName] = $e4['value'];
                                            }

                                            ++$i;
                                        }

                                        $matchesArray[] = $matchArray;
                                    }
                                    else
                                    {
                                        $this->xml_parse_error($parsed[$i]);
                                    }

                                    ++$i;
                                }

                                $returnValue['matches'] = $matchesArray;
                            }
                            else if (($e2['tag'] == 'matches') && ($e2['level'] == 4) && ($e2['type'] == 'complete'))
                            {	//Handle empty result set
                        		$returnValue['matches'] = array();
                            }
                            else
                            {
                                $this->xml_parse_error($parsed[$i]);
                            }

                            ++$i;
                        }
                    }
                    else
                    {
                        $this->xml_parse_error($parsed[$i]);
                    }

                    ++$i;
                }
            }
            else if (($e0['tag'] == 'filter') && ($e0['level'] == 2) && ($e0['type'] == 'open'))
            {
                ++$i;

                while ($i < $n)
                {
                    $e1 = $parsed[$i];

                    if (($e1['tag'] == 'filter') && ($e1['level'] == 2) && ($e1['type'] == 'close'))
                    {
                        break;
                    }
                    else if (($e1['tag'] == 'filterResponse') && ($e1['level'] == 3) && ($e1['type'] == 'open'))
                    {
                        ++$i;

                        while ($i < $n)
                        {
                            $e2 = $parsed[$i];

                            if (($e2['tag'] == 'filterResponse') && ($e2['level'] == 3) && ($e2['type'] == 'close'))
                            {
                                break;
                            }
                            else if (($e2['tag'] == 'filteredTextLength') && ($e2['level'] == 4) && ($e2['type'] == 'complete'))
                            {
                                $returnValue['filteredTextLength'] = $e2['value'];
                            }
                            else if (($e2['tag'] == 'filteredText') && ($e2['level'] == 4) && ($e2['type'] == 'complete'))
                            {
                                $returnValue['filteredText'] = $e2['value'];
                            }
                            else
                            {
                                $this->xml_parse_error($parsed[$i]);
                            }

                            ++$i;
                        }
                    }
                    else
                    {
                        $this->xml_parse_error($parsed[$i]);
                    }

                    ++$i;
                }
            }
            else
            {
                $this->xml_parse_error($parsed[$i]);
            }

            ++$i;
        }

        return $returnValue;
    }

	function xml_parse_error($xmlNode) {
		die("bad xml: " . $xmlNode['tag'] . ", level = " . $xmlNode['level']);
	}

	function build_query($parameters) {
		foreach ($parameters as $key => $val) {
			$result[] = urlencode($key).'='.urlencode($val);
		}
		return implode('&', $result);
	}

    function do_post_request($url, $data, $optional_headers = null)
    {
        $params = array('http' => array('method' => 'POST', 'content' => $data));
		$params['http']['header'] = "Content-type: application/x-www-form-urlencoded\r\n";
		$params['http']['header'] .= "Content-length: ".strlen($data)."\r\n";
        if ($optional_headers !== null)
        {
            $params['http']['header'] .= $optional_headers;
        }

        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx) or die('Error accessing TextWise API');

 		while (!feof($fp)) {
			$response .= fread($fp, 8192);
		}


        if ($response === false)
        {
            die("Problem reading data from $url, $php_errormsg");
        }

		fclose($fp);
        return $response;
    }


}
?>
