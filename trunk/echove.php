<?php

/*********************************************************************

	ECHOVE, Brightcove PHP SDK

	Official Website - http://echove.net
	Code Repository - http://code.google.com/p/echove
	
	Authors:
		Matthew Congrove, Services Engineer, Brightcove, Inc
		Brian Franklin, Services Engineer, Brightcove, Inc

	Copyright 2009 Matthew Congrove, Brian Franklin
	
	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at
	
		http://www.apache.org/licenses/LICENSE-2.0
	
	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.
	
*********************************************************************/

class Echove
{

	function __construct($token)
	{
		if(!$token)
		{
			trigger_error(' [ECHOVE-001] Token not provided ', E_USER_WARNING);
			return FALSE;
		}
		
		$this->token = $token;
	}
	
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
		} else {
			$url = $this->appendParams($method, $params, $default);
		}

		return $this->getData($url);
	}
	
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
	
	private function getData($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($ch);
		curl_close($ch);
		
		if($response)
		{
			if($response != 'null')
			{
				$json = json_decode($response);

				if(isset($json->items))
				{
					return $json->items;
				} else {
					return $json;
				}
			} else {
				return FALSE;
			}
		} else {
			trigger_error(' [ECHOVE-003] API call failed ', E_USER_NOTICE);
			return FALSE;
		}
		
	}
	
}

?>