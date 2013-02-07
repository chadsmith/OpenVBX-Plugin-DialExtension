<?php

class TwimlDialException extends Exception {};

class TwimlDialExtension {
	/**
	 * Use the CodeIgniter session class to set the cookie
	 * Not using this has caused issues on some systems, but
	 * until we know that this squashes our bugs we'll leave
	 * the toggle to allow the legacy method of tracking
	 *
	 * @var bool
	 */
	private $use_ci_session = true;
	
	static $hangup_stati = array('completed', 'answered');
	static $voicemail_stati = array('no-answer', 'failed');
	
	protected $cookie_name;

	public $state;
	public $response;
	
	public $dial;
	
	protected $timeout = false;
	protected $transcribe = true;
	protected $voice = 'man';
	protected $language = 'en';

	/**
	 * Default timeout is the same as the Twilio default timeout
	 *
	 * @var int
	 */
	public $default_timeout = 20;
	
	public function __construct($settings = array())
	{
		$this->response = new TwimlResponse;
		
		$this->cookie_name = 'state-'.AppletInstance::getInstanceId();
		$this->version = AppletInstance::getValue('version', null);
		
		$this->callerId = AppletInstance::getValue('callerId', null);
		if (empty($this->callerId) && !empty($_REQUEST['From'])) 
		{
			$this->callerId = $_REQUEST['From'];
		}

		/* Get current instance	 */
		$this->number = AppletInstance::getValue('number');
    $this->extension = AppletInstance::getValue('extension');
    $this->delay = AppletInstance::getValue('delay', '1');

		$this->no_answer_redirect = AppletInstance::getDropZoneUrl('no-answer-redirect');
		
		if (count($settings)) {
			foreach ($settings as $setting => $value) 
			{
				if (isset($this->$setting)) 
				{
					$this->$setting = $value;
				}
			}
		}
	}
	
// Helpers

	public function getDial() 
	{
		if (empty($this->dial)) 
		{
			$this->dial = $this->response->dial(NULL, array(
					'action' => current_url(),
					'callerId' => $this->callerId,
					'timeout' => (!empty($this->timeout)) ? $this->timeout : $this->default_timeout
				));
		}
		return $this->dial;
	}
	
// Actions

	
	public function dial($device_or_user) 
	{
		$dialed = false;
		
		$dialed = $this->dialNumber($device_or_user);
		
		return $dialed;
	}
	
	/**
	 * Dial a number directly, no special sauce here
	 *
	 * @param string $number 
	 * @return bool
	 */
	public function dialNumber($number) 
	{
		$dial = $this->getDial();
		$number = normalize_phone_to_E164($number);
		$digits = array_merge(array_fill(0, round(floatval($this->delay) / 0.5), 'w'), (array) $this->extension);
		$dial->number($number, array(
		  'sendDigits' => implode('', $digits)
		));
		$this->state = 'calling';
		return true;
	}
	
	/**
	 * Handle nobody picking up the dail
	 *
	 * @return void
	 */
	public function noanswer() 
	{
		$_status = null;
		if(empty($this->no_answer_redirect)) 
		{
			$this->response->hangup();
		}
		$this->response->redirect($this->no_answer_redirect);
	}
	
	/**
	 * Add a hangup to the response
	 *
	 * @return void
	 */
	public function hangup() 
	{
		$this->response->hangup();
	}
	
	/**
	 * Send the response
	 *
	 * @return void
	 */
	public function respond() 
	{
		$this->response->respond();
	}

// State

	/**
	 * Figure out our state
	 * 
	 * - First check the DialCallStatus & CallStatus, they'll tell us if we're done or not
	 * - then check our state from the cookie to see if its empty, if so, we're new
	 * - then use the cookie value
	 *
	 * @return void
	 */
	public function set_state() 
	{
		$call_status = isset($_REQUEST['CallStatus']) ? $_REQUEST['CallStatus'] : null;
		$dial_call_status = isset($_REQUEST['DialCallStatus']) ? $_REQUEST['DialCallStatus'] : null;
		
		$this->state = $this->_get_state();

		if (in_array($dial_call_status, self::$hangup_stati) 
			|| in_array($call_status, self::$hangup_stati))
		{
			$this->state = 'hangup';
		}
		elseif(in_array($dial_call_status, self::$voicemail_stati))
		{
			$this->state = 'voicemail';
		}
		elseif (!$this->state) 
		{
			$this->state = 'new';
		}
	}
	
	/**
	 * Get the state from the cookie
	 *
	 * @return string json or std
	 */
	private function _get_state() 
	{
		$state = null;
		if ($this->use_ci_session) 
		{
			$CI =& get_instance();
			$state = $CI->session->userdata($this->cookie_name);
		}
		else 
		{
			if (!empty($_COOKIE[$this->cookie_name])) 
			{
				$state = $_COOKIE[$this->cookie_name];
			}
		}

		return $state;
	}
	
	/**
	 * Store the state for use on the next go-around
	 *
	 * @return void
	 */
	public function save_state() 
	{
		$state = $this->state;
		if ($this->use_ci_session) 
		{
			$CI =& get_instance();
			$CI->session->set_userdata($this->cookie_name, $state);
		}
		else 
		{
			set_cookie($this->cookie_name, $state, time() + (5 * 60));
		}
	}
}