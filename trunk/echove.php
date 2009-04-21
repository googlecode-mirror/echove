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
 *     Matthew Congrove, Professional Services Engineer, Brightcove
 *     Brian Franklin, Professional Services Engineer, Brightcove
 * Copyright:
 *     Copyright (c) 2009, Matthew Congrove, Brian Franklin
 * Version:
 *     Echove 0.3.2 (19 APR 2009)
 * Change Log:
 *     0.3.2 - Added RTMP to HTTP URL function, and function to
 *             easily parse video tags. Improved SEF function.
 *             Added support for remote assets.
 *     0.3.1 - Improved error reporting.
 *     0.3.0 - Added Write API methods for creating, updating, and
 *             deleting both videos and playlists.
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
    * @param string [$token_read] The read API token for the Brightcove account
    * @param string [$token_write] The write API token for the Brightcove account
    */
	public function __construct($token_read, $token_write = NULL)
	{	
		$this->token_read = $token_read;
		$this->token_write = $token_write;
		$this->read_url = 'http://api.brightcove.com/services/library?';
		$this->write_url = 'http://api.brightcove.com/services/post';
		$this->show_errors = TRUE;
		$this->page_number = NULL;
		$this->page_size = NULL;
		$this->total_count = NULL;

		if(!$token_read)
		{
			$this->triggerError('001');
			return FALSE;
		}
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
				$this->triggerError('005');
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
		$url = $this->read_url . 'token=' . $this->token_read . '&command=' . $method;
		
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
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($curl);

		if(curl_errno($curl))
		{
			$this->triggerError('003');
			echo curl_error($curl);
			return FALSE;
		}

		curl_close($curl);
		
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
			$this->triggerError('003');
			return FALSE;
		}
	}

   /**
    * Uploads a video file to Brightcove
    * @access Public
    * @since 0.3.0
    * @param resource [$file] The pointer to the temporary file
    * @param array [$meta] The video information
    * @param bool [$multiple] Whether or not to create multiple renditions
    * @return mixed The video ID if successful, otherwise FALSE
    */
	public function createVideo($file = NULL, $meta, $multiple = FALSE)
	{
		if(!$this->token_write)
		{
			$this->triggerError('002');
			return FALSE;
		}

		$request = array();
		$post = array();
		$params = array();
		$video = array();
		
		foreach($meta as $key => $value)
		{
			$video[$key] = $value;
		}
		
		if(!$video['referenceId'])
		{
			$video['referenceId'] = time();
		}
		
		$params['token'] = $this->token_write;
		$params['video'] = $video;
		$params['create_multiple_renditions'] = $multiple;
		
		$post['method'] = 'create_video';
		$post['params'] = $params;
		
		$request['json'] = json_encode($post) . "\n";
		
		if($file)
		{
			$request['file'] = '@' . $file;
		}
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->write_url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		
		if(curl_errno($curl))
		{
			$this->triggerError('010');
			echo curl_error($curl);
			return FALSE;
		}
		
		curl_close($curl);
		
		$result = json_decode($result);

		if($return->result)
		{
			return $result->result;
		} else {
			$this->triggerError('11');
			return FALSE;
		}
	}

   /**
    * Creates a playlist
    * @access Public
    * @since 0.3.0
    * @param array [$meta] The playlist information
    * @return mixed The playlist ID if successful, otherwise FALSE
    */
	public function createPlaylist($meta)
	{
		if(!$this->token_write)
		{
			$this->triggerError('002');
			return FALSE;
		}

		$request = array();
		$post = array();
		$params = array();
		$playlist = array();
		
		foreach($meta as $key => $value)
		{
			$playlist[$key] = $value;
		}
		
		if(!$playlist['referenceId'])
		{
			$playlist['referenceId'] = time();
		}
		
		foreach($playlist['videoIds'] as $key => $value)
		{
			$playlist['videoIds'][$key] = (int)$value;
		}
		
		$params['token'] = $this->token_write;
		$params['playlist'] = $playlist;
		
		$post['method'] = 'create_playlist';
		$post['params'] = $params;
		
		$request['json'] = json_encode($post);
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->write_url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		
		if(curl_errno($curl))
		{
			$this->triggerError('004');
			echo curl_error($curl);
			return FALSE;
		}
		
		curl_close($curl);
		
		$result = json_decode($result);

		if($result->result)
		{
			return $result->result;
		} else {
			$this->error('011');
			return FALSE;
		}
	}
	
   /**
    * Updates a video or playlist
    * @access Public
    * @since 0.3.0
    * @param string [$type] The item to delete, either a video or playlist
    * @param array [$meta] The information for the video or playlist
    */
	public function update($type, $meta)
	{
		if(!$this->token_write)
		{
			$this->triggerError('002');
			return FALSE;
		}
		
		$request = array();
		$post = array();
		$params = array();
		$metaData = array();

		$params['token'] = $this->token_write;
		
		if(strtolower($type) == 'video')
		{		
			foreach($meta as $key => $value)
			{
				$metaData[$key] = $value;
			}
		
			$params['video'] = $metaData;
			$post['method'] = 'update_video';
		} elseif(strtolower($type) == 'playlist') {	
			foreach($meta as $key => $value)
			{
				$metaData[$key] = $value;
			}
				
			$params['playlist'] = $metaData;
			$post['method'] = 'update_playlist';
		} else {
			$this->triggerError('006');
			return FALSE;
		}
		
		$post['params'] = $params;
		
		$request['json'] = json_encode($post) . "\n";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->write_url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		
		if(curl_errno($curl))
		{
			$this->triggerError('004');
			echo curl_error($curl);
			return FALSE;
		}
		
		curl_close($curl);
	}
	
   /**
    * Deletes a video or playlist
    * @access Public
    * @since 0.3.0
    * @param string [$type] The item to delete, either a video or playlist
    * @param int [$id] The ID of the video or playlist
    * @param string [$ref_id] The reference ID of the video or playlist
    * @param bool [$cascade] Whether or not to cascade the deletion
    */
	public function delete($type, $id = NULL, $ref_id = NULL, $cascade = TRUE)
	{
		if(!$this->token_write)
		{
			$this->triggerError('002');
			return FALSE;
		}
		
		$request = array();
		$post = array();
		$params = array();

		$params['token'] = $this->token_write;
		$params['cascade'] = $cascade;
		
		if(strtolower($type) == 'video')
		{
			if($id)
			{
				$params['video_id'] = $id;
			} elseif($ref_id) {
				$params['reference_id'] = $ref_id;
			} else {
				$this->triggerError('008');
				return FALSE;
			}
			
			$post['method'] = 'delete_video';
		} elseif(strtolower($type) == 'playlist') {
			if($id)
			{
				$params['playlist_id'] = $id;
			} elseif($ref_id) {
				$params['reference_id'] = $ref_id;
			} else {
				$this->triggerError('008');
				return FALSE;
			}
			
			$post['method'] = 'delete_playlist';
		} else {
			$this->triggerError('007');
			return FALSE;
		}
		
		$post['params'] = $params;
		
		$request['json'] = json_encode($post) . "\n";
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->write_url);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		
		if(curl_errno($curl))
		{
			$this->triggerError('004');
			echo curl_error($curl);
			return FALSE;
		}
		
		curl_close($curl);
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
    * Parses video tags string into a key-value array
    * @access Public
    * @since 0.3.2
    * @param string [$tags] The tags string from a video DTO
    * @return array A key-value array of tags
    */
    public function tags($tags)
    {
		$return = array();
		
		if(count($tags) > 0)
		{
	    	foreach($tags as $tag)
	    	{
	    		if(strpos($tag, '=') === FALSE)
	    		{
	    			$return[] = $tag;
	    		} else {
	    			$group = explode('=', $tag);
	    			
	    			$return[trim($group[0])] = trim($group[1]);
	    		}
	    	}
	    }
		
		return $return;
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
		$name = preg_replace('/[^a-zA-Z0-9\s]+/', '', $name);
		$name = preg_replace('/\s/', '-', $name);
		
		return $name;
	}
	
   /**
    * Retrieves the HTTP URL from a streaming asset (RTMP).
    * @access Public
    * @since 0.3.2
    * @param string [$flvurl] The RTMP FLV URL of an asset
    * @return string The HTTP URL of an asset
    */
	public function downloadUrl($flvurl)
	{
		$return = '';
		$url = 'http://brightcove.vo.llnwd.net/';
		$matches = array();
		
		$preg = preg_match('/((.*?)\/)*/', $flvurl, $matches);
		$filename = preg_replace('/.*\//', '', $flvurl);
		$filename = preg_replace('/&.*/', '', $filename);

		if(strpos($flvurl, 'mp4') !== false)
		{
			$filename .= '.mp4';
		} else {
			$filename .= '.flv';
		}
		
		if(strpos($flvurl, 'd5') !== false)
		{
			$return = ($url . 'pd5/media/' . $matches[2] . '/' . $filename);
		} elseif(strpos($flvurl, 'o2') !== false) {
			$return = ($url . 'pd2/media/' . $matches[2] . '/' . $filename);
		} elseif(strpos($flvurl, 'd6') !== false) {
			$return = ($url . 'pd6/media/' . $matches[2] . '/' . $filename);
		} elseif(strpos($flvurl, 'd7') !== false) {
			$return = ($url . 'pd7/media/' . $matches[2] . '/' . $filename);
		}
		
		return $return;
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
				. $videoCode
				. $additionalCode . '
				<param name="isVid" value="true" />
				<param name="isUI" value="true" />
			</object>
		';
		
		return $code;
	}

   /**
    * Triggers an error if errors are enabled
    * @access Private
    * @since 0.3.1
    * @param string [$err_code] The code number of an error
    */	
	private function triggerError($err_code)
	{
		if($this->show_errors)
		{
			switch($err_code)
			{
				case '001':
					$text = 'Read token not provided';
					$type = 'WARNING';
					break;
				case '002':
					$text = 'Write token not provided';
					$type = 'WARNING';
					break;
				case '003':
					$text = 'Read API transaction failed';
					$type = 'NOTICE';
					break;
				case '004':
					$text = 'Write API transaction failed';
					$type = 'WARNING';
					break;
				case '005':
					$text = 'Requested method not found';
					$type = 'WARNING';
					break;
				case '006':
					$text = 'Update type not specified';
					$type = 'WARNING';
					break;
				case '007':
					$text = 'Deletion type not specified';
					$type = 'WARNING';
					break;
				case '008':
					$text = 'ID not provided';
					$type = 'WARNING';
					break;
				case '009':
					$text = 'Video file not provided';
					$type = 'WARNING';
					break;
				case '010':
					$text = 'Video file transfer failed';
					$type = 'WARNING';
					break;
				case '011':
					$text = 'Unknown API error';
					$type = 'WARNING';
					break;
			}
			
			if($type == 'NOTICE')
			{
				trigger_error(' [ECHOVE-' . $err_code . '] ' . $text . ' ', E_USER_NOTICE);
			} elseif($type == 'WARNING') {
				trigger_error(' [ECHOVE-' . $err_code . '] ' . $text . ' ', E_USER_WARNING);
			}
		}
	}

}

?>