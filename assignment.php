<?php
function getDataFromServer($url, $params)
{
	$queryStr = '';
	foreach($params as $name=>$value)
	{
		$queryStr .= $name . '=' . $value . '&'; 
	}
	$queryStr = trim($queryStr, '&');
	if (strlen($queryStr) > 0)
	{
		$url = $url . '?' . $queryStr;
	}
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_HTTPGET, true );
   	curl_setopt( $ch, CURLOPT_URL, $url );
 	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	
    $response = curl_exec( $ch );
	$results = json_decode($response, true);
    curl_close( $ch );
    return $results;
}
function postDataToServer($url, $postData)
{
	$ch = curl_init();
	curl_setopt( $ch, CURLOPT_POST, true );
   	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $postData);
 	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    $response = curl_exec( $ch );
	$results = json_decode($response, true);
    curl_close( $ch );
	
    return $results;
}

class UserPost
{
	private $id;
	private $from_name;
	private $from_id;
	private $message;
	private $type;
	private $created_time;
	function __construct($data)
	{
		$this->id = $data['id'];
		$this->from_name = $data['from_name'];
		$this->from_id = $data['from_id'];
		$this->message = $data['message'];
		$this->type = $data['type'];
		$this->created_time = strtotime($data['created_time']);
	}
	public function get_message_size()
	{
		return strlen($this->message);
	}

	public function get_poster()
	{
		return $this->from_id;
	}

	public function get_poster_name()
	{
		return $this->from_name;
	}

	public function get_creation_month()
	{
		return date('M', $this->created_time);
	}

	public function get_creation_week()
	{
		return date('W', $this->created_time);
	}

	public function get_value_array()
	{
		return array('id' => $this->id, 
					 'from_name' => $this->from_name,
					 'from_id' => $this->from_id,
					 'message' => $this->message,
					 'type' => $this->type,
					 'created_time' => date_format($this->created_time, DATE_ATOM));
	}
}
function get_token()
{
	$postUrl = 'https://api.supermetrics.com/assignment/register';
	$userInfo = array('client_id'=>'ju16a6m81mhid5ue1z3v2g0uh', 'email'=>'ethanlin072@gmail.com', 'name'=>'Ethan');
	$post_result = postDataToServer($postUrl, $userInfo);
	return $post_result['data']['sl_token'];
}

//total post size by month
$total_post_size = array();
//longest post by month
$longest_post = array();
//number of post by week
$weekly_post_number = array();
//user post by month
$monthly_post_user = array();
$token = get_token();

for ($i=1; $i<=10; $i++)
{
    $getUrl = 'https://api.supermetrics.com/assignment/posts';
	$params = array('page' => $i, 'sl_token'=>$token);
	$result = getDataFromServer($getUrl, $params);
	if (isset($result['error']))
	{
		if ($result['error']['message'] === 'Invalid SL Token')
		{
			$token = get_token();
			$params = array('page' => $i, 'sl_token'=>$token);
			$result = getDataFromServer($getUrl, $params);
		}
	}
		
	foreach ($result['data']['posts'] as $postInfo)
	{
		$post = new UserPost($postInfo);
		$month = $post->get_creation_month();
		$week = $post->get_creation_week();
		$size = $post->get_message_size();
		$user = $post->get_poster();
		if (array_key_exists($month, $total_post_size))
		{
			$total_post_size[$month]['size'] += $size;
			$total_post_size[$month]['number'] ++;

			if ($size > $longest_post[$month]->get_message_size())
			{
				$longest_post[$month] = $post;
			}
			if (!in_array($user, $monthly_post_user[$month]))
			{
				$monthly_post_user[$month][] = $user;
			}			
		}
		else
		{
			$total_post_size[$month] = array('size'=>$size, 'number'=>1);
			$longest_post[$month] = $post;
			$monthly_post_user[$month] = array($user);			
		}
		if (array_key_exists($week, $weekly_post_number))
		{
			$weekly_post_number[$week] ++;
		}
		else
		{
			$weekly_post_number[$week] = 1;
		}
	}
}
$monthly_result = array();
foreach ($total_post_size as $key=>$value)
{
	$monthly_result[$key] = array('average_post_size' => $value['size']/$value['number'],
								  'longest_post' => $longest_post[$key]->get_value_array(),
								  'average_post_number_per_user' => $value['number']/count($monthly_post_user[$key]));
}
$final_result = array('Monthly_result'=>$monthly_result, 
					  'Weekly_result' =>$weekly_post_number);

echo json_encode($final_result);					  
?>