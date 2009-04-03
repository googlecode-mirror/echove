<?php

/**
 * ECHOVE
 * A Brightcove PHP SDK
 * 
 * Official Website:
 *     http://echove.net/
 * Code Repository:
 *     http://code.google.com/p/echove/
 * Authors:
 *     Matthew Congrove, Prof. Services Engineer, Brightcove, Inc.
 *     Brian Franklin, Prof. Services Engineer, Brightcove, Inc.
 * Copyright:
 *     Copyright (c) 2009, Matthew Congrove, Brian Franklin
 * Version:
 *     Echove 0.2.2 (03 APR 2009)
 * Change Log:
 *     0.2.2 - Fix to remove notices. Added embed method. Corrected
 *             video lengths.
 *     0.2.1 - Setting default values to remove notices. Added inline
 *             code documentation. Added utilities to convert video
 *             lengths from milliseconds to formatted time or seconds
 *             for ease of use and to convert video names to a
 *             search-engine friendly format.
 *     0.2.0 - Updated to include API return properties.
 *     0.1.0 - Initial release.
 * 
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

class Echove
{

   /**
    * @access Public
    * @since 0.1.0
    * @param string [$token] The API token for the Brightcove account
    */
	public function __construct($token)
	{
		if(!$token)
		{
			trigger_error(' [ECHOVE-001] Token not provided ', E_USER_WARNING);
			return FALSE;
		}
		
		$this->token = $token;
		$this->page_number = NULL;
		$this->page_size = NULL;
		$this->total_count = NULL;
	}
	
   /**
    * Formats the API request URL, retrieves the data, and returns it.
    * @access Public
    * @since 0.1.0
    * @param string [$call] The requested API method
    * @param array [$params] A key-value array of API parameters and values
    * @return object An object containing all API return data
    */
	public function find($call, $params = NULL)
	{
		$call = strtolower(str_replace('find', '', str_replace('_', '', $call)));
		
		switch($call)
		{
			case 'allvideos':
				$method = 'find_all_videos';
				break;
			case 'videobyid':
				$method = 'find_video_by_id';
				$default = 'video_id';
				break;
			case 'relatedvideos':
				$method = 'find_related_videos';
				$default = 'video_id';
				break;
			case 'videosbyids':
				$method = 'find_videos_by_ids';
				$default = 'video_ids';
				break;
			case 'videobyreferenceid':
				$method = 'find_video_by_reference_id';
				$default = 'reference_id';
				break;
			case 'videosbyreferenceids':
				$method = 'find_videos_by_reference_ids';
				$default = 'reference_ids';
				break;
			case 'videosbyuserid':
				$method = 'find_videos_by_user_id';
				$default = 'user_id';
				break;
			case 'videosbycampaignid':
				$method = 'find_videos_by_campaign_id';
				$default = 'campaign_id';
				break;
			case 'videosbytext':
				$method = 'find_videos_by_text';
				$default = 'text';
				break;
			case 'videosbytags':
				$method = 'find_videos_by_tags';
				$default = 'or_tags';
				break;
			case 'allplaylists':
				$method = 'find_all_playlists';
				break;
			case 'playlistbyid':
				$method = 'find_playlist_by_id';
				$default = 'playlist_id';
				break;
			case 'playlistsbyids':
				$method = 'find_playlists_by_id';
				$default = 'playlist_ids';
				break;
			case 'playlistbyreferenceid':
				$method = 'find_playlist_by_reference_id';
				$default = 'reference_id';
				break;
			case 'playlistsbyreferenceids':
				$method = 'find_playlists_by_reference_ids';
				$default = 'reference_ids';
				break;
			case 'playlistsforplayerid':
				$method = 'find_playlists_for_player_id';
				$default = 'player_id';
				break;
			default:
				trigger_error(' [ECHOVE-002] Command <strong>' . $call . '</strong> not found ', E_USER_WARNING);
				return FALSE;
				break;
		}
		
		if(is_array($params))
		{
			$url = $this->appendParams($method, $params);
		} else if($default) {
			$url = $this->appendParams($method, $params, $default);
		} else {
			$url = $this->appendParams($method, $params);
		}

		return $this->getData($url);
	}
	
   /**
    * Appends API parameters onto API request URL
    * @access Private
    * @since 0.1.0
    * @param string [$method] The requested API method
    * @param array [$params] A key-value array of API parameters and values
    * @param string [$default] The default API parameter if only 1 provided
    * @return string The complete API request URL
    */
	private function appendParams($method, $params = NULL, $default = NULL)
	{
		$url = 'http://api.brightcove.com/services/library?token=' . $this->token . '&command=' . $method;
		
		if($params)
		{
			if($default)
			{
				$url .= '&' . $default . '=' . $params;
			} else {
				foreach($params as $option => $value)
				{
					$url .= '&' . $option . '=' . $value;
				}
			}
		}
		
		return $url;
	}

   /**
    * Retrieves API data from provided URL
    * @access Private
    * @since 0.1.0
    * @param string [$url] The complete API request URL
    * @return object An object containing all API return data
    */
	private function getData($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		curl_close($ch);
		
		if($response)
		{
			if($response != 'NULL')
			{
				$json = json_decode($response);

				if(isset($json->items))
				{
					$data = $json->items;
				} else {
					$data = $json;
				}
				
				$this->page_number = $json->page_number;
				$this->page_size = $json->page_size;
				$this->total_count = $json->total_count;
				
				return $data;
			} else {
				return FALSE;
			}
		} else {
			trigger_error(' [ECHOVE-003] API call failed ', E_USER_NOTICE);
			return FALSE;
		}
	}
	
   /**
    * Converts milliseconds to formatted time or seconds
    * @access Public
    * @since 0.2.1
    * @param int [$ms] The length of the video in milliseconds
    * @param bool [$seconds] Whether to return only seconds
    * @return mixed The formatted length or total seconds of the video
    */
	public function time($ms, $seconds = FALSE)
	{
		$total_seconds = ($ms / 1000);
			
		if($seconds)
		{
			return $total_seconds;
		} else {
			$time = '';
			$value = array(
				'hours' => 0,
				'minutes' => 0,
				'seconds' => 0
			);
			
			if($total_seconds >= 3600) {
				$value['hours'] = floor($total_seconds/3600);
				$total_seconds = ($total_seconds%3600);

				$time .= $value['hours'] . ':';
			}
			
			if($total_seconds >= 60) {
				$value['minutes'] = floor($total_seconds/60);
				$total_seconds = ($total_seconds%60);
				
				$time .= $value['minutes'] . ':';
			} else {
				$time .= '0:';
			}
			
			$value['seconds'] = floor($total_seconds);
			
			if($value['seconds'] < 10)
			{
				$value['seconds'] = '0' . $value['seconds'];
			}
			
			$time .= $value['seconds'];
			
			return $time;
		}
	}
	
   /**
    * Formats a video name to be search-engine friendly
    * @access Public
    * @since 0.2.1
    * @param string [$name] The video name
    * @return string The SEF video name
    */
	public function sef($name)
	{
		$name = preg_replace('/[^a-zA-Z0-9]+/', '-', $name);
		
		return $name;
	}
	
   /**
    * Returns the JavaScript version of the player embed code
    * @access Public
    * @since 0.2.2
    * @param int [$playerId] The ID of the player to embed
    * @param mixed [$videoIds] The ID of the default video, or an array of video IDs
    * @param array [$params] A key-value array of embed parameters
    * @param array [$additional] A key-value array of additional embed parameters
    * @return string The embed code
    */
	public function embed($playerId, $videoIds = NULL, $params = NULL, $additional = NULL)
	{
		$values = array('id' => 'myExperience', 'bgcolor' => 'FFFFFF', 'width' => 486, 'height' => 412);

		foreach($values as $key => $value)
		{
			if(isset($params[$key]))
			{
				$values[$key] = $params[$key];
			}
		}
		
		$additionalCode = '';
		
		if(is_array($additional))
		{
			foreach($additional as $key => $value)
			{
				$additionalCode .= '<param name="' . $key . '" value="' . $value . '" />';
			}
		}
		
		$videoCode = '';
		
		if($videoIds)
		{
			if(is_array($videoIds))
			{
				$videoCode = '<param name="@videoPlayer" value="';
				
				foreach($videoIds as $videoId)
				{
					$videoCode .= $videoId . ',';
				}
				
				$videoCode = substr($videoCode, 0, -1);
				$videoCode .= '" />';
			} else {
				$videoCode = '<param name="@videoPlayer" value="' . $videoIds . '" />';
			}
		}
		
		$code = '
			<object id="' . $values['id'] . '" class="BrightcoveExperience">
				<param name="bgcolor" value="#' . $values['bgcolor'] . '" />
				<param name="width" value="' . $values['width'] . '" />
				<param name="height" value="' . $values['height'] . '" />
				<param name="playerID" value="' . $playerId . '" />'
				. $videoCode .
				. $additionalCode . '
				<param name="isVid" value="true" />
				<param name="isUI" value="true" />
			</object>
		';
		
		return $code;
	}

}

?>