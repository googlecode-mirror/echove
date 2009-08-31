<?php

/**
 * ECHOVE 1.0.0c (27 AUGUST 2009)
 * A Brightcove PHP SDK
 *
 * REFERENCES:
 *	 Official Website: http://echove.net/
 *	 Code Repository: http://code.google.com/p/echove/
 *
 * AUTHORS:
 *	 Matthew Congrove, Professional Services Engineer, Brightcove
 *	 Brian Franklin, Professional Services Engineer, Brightcove
 *
 * CONTRIBUTORS:
 *	 Luke Weber, Kristen McGregor, Jesse Streb
 *
 * CHANGE LOG:
 *	 1.0.0 - Added putData method. Added error exceptions. Unified request logic. Other
 *			 efficiency improvements. Brightcove API update adjustments.
 *	 0.4.0 - Provided better logic for createVideo method. Fixed error reporting in
 *			 createPlaylist, also included check to determine if video IDs are being
 *			 passed.
 *	 0.3.9 - Added share_video and get_upload_status methods. Corrected error for
 *			 find_modified_videos return. Updated error codes and reporting points.
 *	 0.3.8 - Improved debugging. Added new API find call, and get_item_count is now
 *			 assumed as TRUE.
 *	 0.3.7 - Fixed major error in Find method.
 *	 0.3.6 - Added debug information, video tag filtering, and a true Find All Videos
 *			 function.
 *	 0.3.5 - Added support for 32-bit servers. Error reporting can now be configured
 *			 during instantiation. Fixed URL encoding issue. Added support for tag
 *			 arrays.
 *	 0.3.4 - Improved error reporting. Added image upload.
 *	 0.3.3 - Fixed RTMP to HTTP URL function. Fixed video upload.
 *	 0.3.2 - Added RTMP to HTTP URL function, and function to easily parse video tags.
 *			 Improved SEF function. Added support for remote assets.
 *	 0.3.1 - Improved error reporting.
 *	 0.3.0 - Added Write API methods for creating, updating, and deleting both videos
 *			 and playlists.
 *	 0.2.2 - Fix to remove notices. Added embed method. Corrected video lengths.
 *	 0.2.1 - Setting default values to remove notices. Added inline code documentation.
 *			 Added utilities to convert video lengths from milliseconds to formatted
 *			 time or secondsfor ease of use and to convert video names to a search-
 *			 engine friendly format.
 *	 0.2.0 - Updated to include API return properties.
 *	 0.1.0 - Initial release.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this
 * software and associated documentation files (the "Software"), to deal in the Software
 * without restriction, including without limitation the rights to use, copy, modify,
 * merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following
 * conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR
 * THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

class Echove
{
	const ERROR_READ_TOKEN_NOT_PROVIDED = 1;
	const ERROR_WRITE_TOKEN_NOT_PROVIDED = 2;
	const ERROR_REQUESTED_METHOD_NOT_FOUND = 3;
	const ERROR_READ_API_TRANSACTION_FAILED = 4;
	const ERROR_WRITE_API_TRANSACTION_FAILED = 5;
	const ERROR_ID_NOT_PROVIDED = 8;
	const ERROR_TYPE_NOT_SPECIFIED = 9;
	const ERROR_INVALID_UPLOAD_OPTION = 10;
	const ERROR_UNKNOWN_API_ERROR = 11;
	const ERROR_TYPE_WARNING = 0;
	const ERROR_TYPE_NOTICE = 1;

	public $token_read;
	public $token_write;
	public $read_url;
	public $write_url;
	public $download_url;
	public $show_notices;
	public $secure;
	public $page_number = NULL;
	public $page_size = NULL;
	public $total_count = NULL;
	public $api_calls = 0;

	/**
	 * @access Public
	 * @since 0.1.0
	 * @param string [$token_read] The read API token for the Brightcove account
	 * @param string [$token_write] The write API token for the Brightcove account
	 * @param bool [$show_notices] Whether or not to show error notices
	 * @param bool [$secure] Whether or not to use HTTPS for API requests
	 */
	public function __construct($token_read, $token_write = NULL, $show_notices = FALSE, $secure = FALSE)
	{
		$this->token_read = $token_read;
		$this->token_write = $token_write;
		$this->download_url = 'http://brightcove.vo.llnwd.net/';
		$this->show_notices = $show_notices;
		$this->secure = $secure;
		$this->bit32 = ((string)'99999999999999' == (int)'99999999999999') ? FALSE : TRUE;
		
		if($this->secure)
		{
			$this->read_url = 'https://api.brightcove.com/services/library?';
			$this->write_url = 'https://api.brightcove.com/services/post';
		} else {
			$this->read_url = 'http://api.brightcove.com/services/library?';
			$this->write_url = 'http://api.brightcove.com/services/post';
		}

		if(!$token_read)
		{
			throw new EchoveTokenError($this, self::ERROR_READ_TOKEN_NOT_PROVIDED);
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
		$call = strtolower(preg_replace('/(?:find|_)+/i', '', $call));
		
		switch($call)
		{
			case 'allvideos':
				$method = 'find_all_videos';
				$get_item_count = TRUE;
				break;
			case 'videobyid':
				$method = 'find_video_by_id';
				$default = 'video_id';
				$get_item_count = FALSE;
				break;
			case 'relatedvideos':
				$method = 'find_related_videos';
				$default = 'video_id';
				$get_item_count = TRUE;
				break;
			case 'videosbyids':
				$method = 'find_videos_by_ids';
				$default = 'video_ids';
				$get_item_count = FALSE;
				break;
			case 'videobyreferenceid':
				$method = 'find_video_by_reference_id';
				$default = 'reference_id';
				$get_item_count = FALSE;
				break;
			case 'videosbyreferenceids':
				$method = 'find_videos_by_reference_ids';
				$default = 'reference_ids';
				$get_item_count = FALSE;
				break;
			case 'videosbyuserid':
				$method = 'find_videos_by_user_id';
				$default = 'user_id';
				$get_item_count = TRUE;
				break;
			case 'videosbycampaignid':
				$method = 'find_videos_by_campaign_id';
				$default = 'campaign_id';
				$get_item_count = TRUE;
				break;
			case 'videosbytext':
				$method = 'find_videos_by_text';
				$default = 'text';
				$get_item_count = TRUE;
				break;
			case 'videosbytags':
				$method = 'find_videos_by_tags';
				$default = 'or_tags';
				$get_item_count = TRUE;
				break;
			case 'modifiedvideos':
				$method = 'find_modified_videos';
				$default = 'from_date';
				$get_item_count = TRUE;
				break;
			case 'allplaylists':
				$method = 'find_all_playlists';
				$get_item_count = TRUE;
				break;
			case 'playlistbyid':
				$method = 'find_playlist_by_id';
				$default = 'playlist_id';
				$get_item_count = FALSE;
				break;
			case 'playlistsbyids':
				$method = 'find_playlists_by_id';
				$default = 'playlist_ids';
				$get_item_count = FALSE;
				break;
			case 'playlistbyreferenceid':
				$method = 'find_playlist_by_reference_id';
				$default = 'reference_id';
				$get_item_count = FALSE;
				break;
			case 'playlistsbyreferenceids':
				$method = 'find_playlists_by_reference_ids';
				$default = 'reference_ids';
				$get_item_count = FALSE;
				break;
			case 'playlistsforplayerid':
				$method = 'find_playlists_for_player_id';
				$default = 'player_id';
				$get_item_count = TRUE;
				break;
			default:
				throw new EchoveInvalidMethodCall($this, self::ERROR_REQUESTED_METHOD_NOT_FOUND);
				break;
		}

		if(isset($params['from_date']) || $default == 'from_date')
		{
			if($default == 'from_date' && !isset($params['from_date']))
			{
				$from_date = (string)$params;
			} else {
				$from_date = (string)$params['from_date'];
			}

			if(strlen($from_date) > 9)
			{
				$from_date = floor((int)$from_date / 60);
			}

			if(!is_array($params) && $default == 'from_date')
			{
				$params = $from_date;
			} else {
				$params['from_date'] = $from_date;
			}
		}

		if($get_item_count)
		{
			if(!isset($params['get_item_count']))
			{
				if(!is_array($params))
				{
					$params[$default] = $params;
				}

				$params['get_item_count'] = 'TRUE';
			}
		}

		if($default && !is_array($params))
		{
			$url = $this->appendParams($method, $params, $default);
		} else {
			$url = $this->appendParams($method, $params);
		}

		return $this->getData($url);
	}

	/**
	 * Finds all videos in account, ignoring pagination
	 * @access Public
	 * @since 0.3.6
	 * @param array [$params] A key-value array of API parameters and values
	 * @return object An object containing all API return data
	 */
	public function findAll($params = NULL)
	{
		$videos = array();
		$current_page = 0;
		$total_count = 0;
		$total_page = 1;

		$params['get_item_count'] = 'TRUE';
		$params['page_size'] = 100;
		$params['page_number'] = 0;

		while($current_page < $total_page)
		{
			$params['page_number'] = $current_page;

			$url = $this->appendParams('find_all_videos', $params);
			$result = $this->getData($url);

			if($total_count < 1)
			{
				$total_count = $this->total_count;
				$total_page = ceil($total_count / 100);
			}

			foreach($result as $video)
			{
				$videos[] = $video;
			}

			$current_page++;
		}

		return $videos;
	}

	/**
	 * Uploads a video file to Brightcove
	 * @access Public
	 * @since 0.3.0
	 * @param string [$file] The location of the temporary file
	 * @param array [$meta] The video information
	 * @param array [$options] Optional upload values
	 * @return mixed The video ID if successful, otherwise FALSE
	 */
	public function createVideo($file = NULL, $meta, $options = NULL)
	{
		$request = array();
		$post = array();
		$params = array();
		$video = array();

		foreach($meta as $key => $value)
		{
			$video[$key] = $value;
		}

		if(!$video['name'])
		{
			$video['name'] = time();
		}

		if(!$video['shortDescription'])
		{
			$video['shortDescription'] = time();
		}

		if(!$video['referenceId'])
		{
			$video['referenceId'] = time();
		}

		$params['token'] = $this->token_write;
		$params['video'] = $video;

		if($file)
		{
			preg_match('/(\.f4a|\.f4b|\.f4v|\.f4p|\.flv)*$/i', $file, $invalid_extensions);

			if($invalid_extensions[1])
			{
				if(isset($options['encode_to']))
				{
					unset($options['encode_to']);
					$this->triggerError(self::ERROR_INVALID_UPLOAD_OPTION);
				}
				
				if(isset($options['create_multiple_renditions']))
				{
					$options['create_multiple_renditions'] = 'FALSE';
					$this->triggerError(self::ERROR_INVALID_UPLOAD_OPTION);
				}
				
				if(isset($options['preserve_source_rendition']))
				{
					unset($options['preserve_source_rendition']);
					$this->triggerError(self::ERROR_INVALID_UPLOAD_OPTION);
				}
			}
			
			if($options['create_multiple_renditions'] === TRUE && $options['H264NoProcessing'] === TRUE)
			{
				unset($options['H264NoProcessing']);
				$this->triggerError(self::ERROR_INVALID_UPLOAD_OPTION);
			}
		}
		
		if($options)
		{
			foreach($options as $key => $value)
			{
				$params[$key] = $value;
			}
		}

		$post['method'] = 'create_video';
		$post['params'] = $params;

		$request['json'] = json_encode($post) . "\n";

		if($file)
		{
			$request['file'] = '@' . $file;
		}

		return $this->putData($request)->result;
	}

	/**
	 * Uploads an image file to Brightcove
	 * @access Public
	 * @since 0.3.4
	 * @param string [$file] The location of the temporary file
	 * @param array [$meta] The image information
	 * @param int [$video_id] The ID of the video asset to assign the image to
	 * @param string [$ref_id] The reference ID of the video asset to assign the image to
	 * @param bool [$resize] Whether or not to resize the image on upload
	 * @return mixed The image DTO
	 */
	public function createImage($file = NULL, $meta, $video_id = NULL, $ref_id = NULL, $resize = TRUE)
	{
		$request = array();
		$post = array();
		$image = array();
		$params = array();

		foreach($meta as $key => $value)
		{
			$image[$key] = $value;
		}

		$params['token'] = $this->token_write;
		$params['image'] = $image;

		if(!$meta['referenceId'])
		{
			$meta['referenceId'] = time();
		}

		if($video_id)
		{
			$params['video_id'] = $video_id;
		} elseif($ref_id) {
			$params['video_reference_id'] = $ref_id;
		} else {
			throw new EchoveIdNotProvided($this, self::ERROR_ID_NOT_PROVIDED);
		}

		if($resize)
		{
			$params['resize'] = 'TRUE';
		} else {
			$params['resize'] = 'FALSE';
		}

		$post['method'] = 'add_image';
		$post['params'] = $params;

		$request['json'] = json_encode($post) . "\n";

		if($file)
		{
			$request['file'] = '@' . $file;
		}

		return $this->putData($request)->result;
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

		if(isset($playlist['videoIds']))
		{
			if($playlist['playlistType'] != 'explicit')
			{
				foreach($playlist['videoIds'] as $key => $value)
				{
					$playlist['videoIds'][$key] = (int)$value;
				}
			}
		}

		$params['token'] = $this->token_write;
		$params['playlist'] = $playlist;

		$post['method'] = 'create_playlist';
		$post['params'] = $params;

		$request['json'] = json_encode($post);

		return $this->putData($request)->result;
	}

	/**
	 * Retrieves the status of a video upload
	 * @access Public
	 * @since 0.3.9
	 * @param int [$video_id] The ID of the video asset
	 * @param string [$ref_id] The reference ID of the video asset
	 * @return string The upload status
	 */
	public function getStatus($video_id = NULL, $ref_id = TRUE)
	{
		if(!$video_id && !$ref_id)
		{
			$this->triggerError(self::ERROR_ID_NOT_PROVIDED);
		}

		$request = array();
		$post = array();
		$params = array();

		$params['token'] = $this->token_write;

		if($video_id)
		{
			$params['video_id'] = $video_id;
		}

		if($ref_id)
		{
			$params['reference_id'] = $ref_id;
		}

		$post['method'] = 'get_upload_status';
		$post['params'] = $params;

		$request['json'] = json_encode($post) . "\n";

		return $this->putData($request)->result;
	}

	/**
	 * Updates a video or playlist
	 * @access Public
	 * @since 0.3.0
	 * @param string [$type] The item to update, either a video or playlist
	 * @param array [$meta] The information for the video or playlist
	 * @return object The new video DTO
	 */
	public function update($type, $meta)
	{
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
			throw new EchoveTypeNotSpecified($this, self::ERROR_TYPE_NOT_SPECIFIED);
		}

		$post['params'] = $params;

		$request['json'] = json_encode($post) . "\n";

		return $this->putData($request)->result;
	}

	/**
	 * Deletes a video or playlist
	 * @access Public
	 * @since 0.3.0
	 * @param string [$type] The item to delete, either a video or playlist
	 * @param int [$id] The ID of the video or playlist
	 * @param string [$ref_id] The reference ID of the video or playlist
	 * @param array [$options] Optional upload values
	 */
	public function delete($type, $id = NULL, $ref_id = NULL, $options = NULL)
	{
		$request = array();
		$post = array();
		$params = array();

		$params['token'] = $this->token_write;
		
		if($options)
		{
			foreach($options as $key => $value)
			{
				$params[$key] = $value;
			}
		}

		$type = strtolower($type);
		
		if($type == 'video')
		{
			if($id)
			{
				$params['video_id'] = $id;
			} elseif($ref_id) {
				$params['reference_id'] = $ref_id;
			} else {
				throw new EchoveIdNotProvided($this, self::ERROR_ID_NOT_PROVIDED);
			}

			$post['method'] = 'delete_video';
		} elseif($type == 'playlist') {
			if($id)
			{
				$params['playlist_id'] = $id;
			} elseif($ref_id) {
				$params['reference_id'] = $ref_id;
			} else {
				throw new EchoveIdNotProvided($this, self::ERROR_ID_NOT_PROVIDED);
			}

			$post['method'] = 'delete_playlist';
		} else {
			throw new EchoveTypeNotSpecified($this, self::ERROR_TYPE_NOT_SPECIFIED);
		}

		$post['params'] = $params;

		$request['json'] = json_encode($post) . "\n";

		return $this->putData($request, FALSE);
	}

	/**
	 * Shares a video with the selected accounts
	 * @access Public
	 * @since 0.3.9
	 * @param int [$video_id] The ID of the video asset
	 * @param array [$account_ids] An array of account IDs
	 * @param bool [$accept] Whether the share should be auto accepted
	 * @return array The new video IDs
	 */
	public function shareVideo($video_id, $account_ids, $accept = FALSE)
	{
		if(!$video_id)
		{
			throw new EchoveIdNotProvided($this, self::ERROR_ID_NOT_PROVIDED);
		}

		$request = array();
		$post = array();
		$params = array();

		$params['token'] = $this->token_write;
		$params['video_id'] = $video_id;
		$params['sharee_account_ids'] = $account_ids;

		if($accept)
		{
			$params['auto_accept'] = 'TRUE';
		} else {
			$params['auto_accept'] = 'FALSE';
		}

		$post['method'] = 'share_video';
		$post['params'] = $params;

		$request['json'] = json_encode($post) . "\n";

		return $this->putData($request)->result;
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
				$url .= '&' . $default . '=' . urlencode($params);
			} else {
				foreach($params as $option => $value)
				{
					$url .= '&' . $option . '=' . urlencode($value);
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
		$response = $this->curlRequest($url, TRUE);

		if($response && $response != 'NULL')
		{
			$response_object = json_decode($response);

			if($response_object->error)
			{
				throw new EchoveApiError($this, self::ERROR_UNKNOWN_API_ERROR, $response_object->error);
			} else {
				if(isset($response_object->items))
				{
					$data = $response_object->items;
				} else {
					$data = $response_object;
				}

				$this->page_number = $response_object->page_number;
				$this->page_size = $response_object->page_size;
				$this->total_count = $response_object->total_count;

				return $data;
			}
		} else {
			throw new EchoveApiError($this, self::ERROR_READ_API_TRANSACTION_FAILED);
		}
	}


	/**
	 * Sends data to the API
	 * @access Private
	 * @since 1.0.0
	 * @param array [$request] The data to send
	 * @return object An object containing all API return data
	 */
	private function putData($request, $return_json = TRUE)
	{
		if(!$this->token_write)
		{
			throw new EchoveTokenError($this, self::ERROR_WRITE_TOKEN_NOT_PROVIDED);
		}

		$response = $this->curlRequest($request, FALSE);

		if($return_json)
		{
			$response_object = json_decode($response);

			if(!$response_object->result)
			{
				throw new EchoveApiError($this, self::ERROR_UNKNOWN_API_ERROR);
			}
		}

		return $response_object;
	}

	/**
	 * Makes a cURL request
	 * @access Private
	 * @since 1.0.0
	 * @param mixed [$request] URL to fetch or the data to send via POST
	 * @param boolean [$get_request] If false, send POST params
	 * @return void
	 */
	private function curlRequest($request, $get_request = FALSE)
	{
		$curl = curl_init();

		if($get_request)
		{
			curl_setopt($curl, CURLOPT_URL, $request);
		} else {
			curl_setopt($curl, CURLOPT_URL, $this->write_url);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
		}

		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($curl);

		$this->api_calls++;

		$curl_error = NULL;
		
		if(curl_errno($curl))
		{
			$curl_error = curl_error($curl);
		}

		curl_close($curl);

		if($curl_error !== NULL)
		{
			if($get_request)
			{
				throw new EchoveApiError($this, self::ERROR_READ_API_TRANSACTION_FAILED, $curl_error);
			} else {
				throw new EchoveApiError($this, self::ERROR_WRITE_API_TRANSACTION_FAILED, $curl_error);
			}
		}

		return $this->bit32clean($response);
	}

	/**
	 * Cleans the response for 32-bit machine compliance.
	 * @access Private
	 * @since 1.0.0
	 * @param string [$response] The response from a cURL request
	 * @return string The cleansed string if using a 32-bit machine.
	 */
	private function bit32Clean($response)
	{
		if($this->bit32)
		{
			$response = preg_replace('/:\s*(\d{10,})/', ':"$1"', $response);
			$response = preg_replace('/(\d{10,})\]/', '"$1"]', $response);
			$response = preg_replace('/(\d{10,})\,/', '"$1",', $response);
		}

		return $response;
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

			if($total_seconds >= 3600)
			{
				$value['hours'] = floor($total_seconds/3600);
				$total_seconds = ($total_seconds%3600);

				$time .= $value['hours'] . ':';
			}

			if($total_seconds >= 60)
			{
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
	 * Parses video tags array into a key-value array
	 * @access Public
	 * @since 0.3.2
	 * @param array [$tags] The tags array from a video DTO
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
					$key = trim($group[0]);
					$value = trim($group[1]);

					if(!isset($return[$key]))
					{
						$return[$key] = $value;
					} else {
						if(is_array($return[$key]))
						{
							$return[$key][] = $value;
						} else {
							$return[$key] = array($return[$key], $value);
						}
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Removes videos that don't contain the appropriate tags
	 * @access Public
	 * @since 0.3.6
	 * @param array [$videos] All the videos you wish to filter
	 * @param string [$tag] A comma-separated list of tags to filter on
	 * @return array The filtered list of videos
	 */
	public function filter($videos, $tags)
	{
		$filtered = array();
		
		foreach($videos as $video)
		{
			foreach($video->tags as $k => $v)
			{
				$video->tags[$k] = strtolower($v);
			}
			
			if(count(array_intersect(explode(',', strtolower($tags)), $video->tags)) > 0)
			{
				$filtered[] = $video;
			}
		}

		return $filtered;
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
	 * @param string [$flvUrl] The RTMP FLV URL of an asset
	 * @return string The HTTP URL of an asset
	 */
	public function downloadUrl($flvUrl)
	{
		$return = '';
		$matches = array();

		$preg = preg_match('/((.*?)\/)*/', $flvUrl, $matches);
		$filename = preg_replace('/.*\//', '', $flvUrl);
		$filename = preg_replace('/&.*/', '', $filename);

		if(strpos($flvUrl, 'mp4') !== FALSE)
		{
			$filename .= '.mp4';
		} else {
			$filename .= '.flv';
		}

		if(strpos($flvUrl, '/d5/') !== FALSE)
		{
			$return = ($this->download_url . 'pd5/media/' . $matches[2] . '/' . $filename);
		} elseif(strpos($flvUrl, '/o2/') !== FALSE) {
			$return = ($this->download_url . 'pd2/media/' . $matches[2] . '/' . $filename);
		} elseif(strpos($flvUrl, '/d6/') !== FALSE) {
			$return = ($this->download_url . 'pd6/media/' . $matches[2] . '/' . $filename);
		} elseif(strpos($flvUrl, '/d7/') !== FALSE) {
			$return = ($this->download_url. 'pd7/media/' . $matches[2] . '/' . $filename);
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
		if(!$playerId)
		{
			throw new EchoveIdNotProvided($this, self::ERROR_ID_NOT_PROVIDED);
		}

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
		$text = $this->getErrorAsString($err_code);
		
		if($this->show_notices)
		{
			trigger_error($text, E_USER_NOTICE);
		}
	}

	/**
	 * Converts an error code into a textual representation
	 * @access public
	 * @since 1.0.0
	 * @param int [$err_code] The code number of an error
	 * @return string The error text
	 */
	public function getErrorAsString($err_code)
	{
		switch($err_code)
		{
			case self::ERROR_READ_TOKEN_NOT_PROVIDED:
				return 'Read token not provided';
				break;
			case self::ERROR_WRITE_TOKEN_NOT_PROVIDED:
				return 'Write token not provided';
				break;
			case self::ERROR_REQUESTED_METHOD_NOT_FOUND:
				return 'Requested method not found';
				break;
			case self::ERROR_READ_API_TRANSACTION_FAILED:
				return 'Read API transaction failed';
				break;
			case self::ERROR_WRITE_API_TRANSACTION_FAILED:
				return 'Write API transaction failed';
				break;
			case self::ERROR_ID_NOT_PROVIDED:
				return 'ID not provided';
				break;
			case self::ERROR_TYPE_NOT_SPECIFIED:
				return 'Type not specified';
				break;
			case self::ERROR_INVALID_UPLOAD_OPTION:
				return 'An invalid media upload parameter has been set';
				break;
			case self::ERROR_UNKNOWN_API_ERROR:
				return 'Unknown API error';
				break;
		}
	}
}

class EchoveException extends Exception
{
	public function __construct(Echove $obj, $err_code, $raw_error_string = NULL)
	{
		$error = $obj->getErrorAsString($err_code);
		
		if($raw_error_string !== NULL)
		{
			$error .= "\n" . 'Details: ' . "\n" . $raw_error_string;
		}
		
		parent::__construct($error, $err_code);
	}
}

class EchoveTokenError extends EchoveException{}
class EchoveApiError extends EchoveException{}
class EchoveInvalidMethodCall extends EchoveException{}
class EchoveTypeNotSpecified extends EchoveException{}
class EchoveIdNotProvided extends EchoveException{}