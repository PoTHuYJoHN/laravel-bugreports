<?php

namespace Webkid\BugReporter;

use Exception;
use Jenssegers\Agent\Agent;

/**
 * Class Reporter
 *
 * Environment options are required: UKIE_REPORTS_ENABLE, UKIE_REPORTS_URL, UKIE_REPORTS_TOKEN
 * Composer packages are required: jenssegers/agent
 *
 * @package App\Services
 */
class Reporter
{
	CONST TYPE_PHP = 'php';
	CONST TYPE_JS = 'js';

	/**
	 * @var Exception
	 */
	private $e;

	/**
	 * Reporter constructor.
	 *
	 * @param Exception $e
	 */
	public function __construct()
	{
		$this->agent = new Agent();
	}

	/**
	 * Send curl request with data to server
	 *
	 * @param string $type
	 * @return bool
	 */
	public function sendReport(Exception $e, $type = self::TYPE_PHP)
	{
		$this->e = $e;
		//Check if enabled

		if(!config('bugreports.reports_enable', false) || $this->agent->isRobot()) return true;

		$request = app()->request;

		//generate array of data to post to server
		$params = $this->getDataFromRequest($type, $request);

		// Init curl & send data
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, config('bugreports.reports_url')); // Set ukie-reports url
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000);

		if(count($params)) {
			//DATA
			$postData = '';

			//create name value pairs seperated by &
			foreach($params as $k => $v)
			{
				$postData .= $k . '='.$v.'&';
			}
			rtrim($postData, '&');

			curl_setopt($ch, CURLOPT_POST, count($postData));
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		}

		curl_setopt($ch, CURLOPT_USERAGENT, $request->header('User-Agent'));

		/* Execute cURL, Return Data */
		$data = curl_exec($ch);
		/* Check HTTP Code */
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		/* Close cURL Resource */
		curl_close($ch);

		/* 200 Response! */
		if ($status == 200) {
			return $data;
		}
		//todo add some return
	}

	/**
	 * Generates array of data to post to server
	 *
	 * @param $type
	 * @param $request
	 * @return array
	 */
	private function getDataFromRequest($type, $request) : array
	{
		$e = $this->e;
		$report_default_message = app()->request->isJson() &&  $request->method() !== 'GET' ? 'form_validation_error' : 'no_message_title_from_exception';
		$token = config('bugreports.reports_token');

		if($type === self::TYPE_JS && is_array($e)) {
			// Build data array
			$params = array(
				'type' => 'frontend',
				'language' => 'js',
				'host' => isset($e['host']) ? $e['host'] : $request->getHost(),
				'ip' => $request->getClientIp(),
				'user_agent' => $request->server('HTTP_USER_AGENT'),
				'method' => $request->method(),
//				'params' => isset($e['params']) ? $e['params'] : null,
				'referrer' => $e['referrer'] ? $e['referrer'] : null,
				'protocol' => $request->server('SERVER_PROTOCOL'),
				'app_controller' => isset($e['app_controller']) ? $e['app_controller'] : null,
				'app_action' => isset($e['app_action']) ? $e['app_action'] : null,
				'app_user_id' => (\Auth::check() ? \Auth::id() . ', ' . \Auth::user()->role . ', ' . \Auth::user()->email : ''),
				'error_type' => isset( $e['error_type']) ?  $e['error_type'] : null,
				'message' => (string) isset($e['message']) ? $e['message'] : '',
				'file' => (string) isset($e['file']) ? $e['file'] : null,
				'trace' => (string) isset($e['trace']) ? json_encode($e['trace']) : null,
				'request' => isset($e['request']) ? json_encode($e['request']) : null,
				'request_time' => isset($e['request_time']) ? $e['request_time'] : null,
				'token' => $token, //Auth,
				'user_os' => $this->agent->device()
			);
		} else {
			// Build data array
			$params = array(
				'type' => 'backend',
				'language' => 'php',
				'host' => $request->getHost(),
				'ip' => $request->getClientIp(),
				'user_agent' => (string) $request->server('HTTP_USER_AGENT'),
				'method' => (string) $request->method(),
				'params' => (string) json_encode($request->query()),
				'referrer' => (string) $request->server('HTTP_REFERER'),
				'protocol' => (string) $request->server('SERVER_PROTOCOL'),
				//'app_controller' => Request::$controller,
				'app_action' => (string) ($request->route() ? $request->route()->getActionName() : 'null'),
				'app_user_id' => (string) (\Auth::check() ? \Auth::id() . ', ' . \Auth::user()->role . ', ' . \Auth::user()->email : ''),
				'error_type' => get_class($e),
				'message' => $e->getMessage() ? (string) $e->getMessage() : $report_default_message,
				'file' => (string) $e->getFile().':'.$e->getLine(),
				'trace' => (string) json_encode($e->getTrace()), // $e->getTraceAsString() // TODO try this
				'request' => (string) json_encode($request->all()),
				'request_time' => $request->server('REQUEST_TIME'),
				'token' => $token, //Auth
				'user_os' => $this->agent->device()
			);
		}

		return $params;
	}
}
