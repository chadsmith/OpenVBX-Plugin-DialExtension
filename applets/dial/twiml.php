<?php
include_once('TwimlDialExtension.php');
define('DIAL_COOKIE', 'state-'.AppletInstance::getInstanceId());

$CI =& get_instance();

$transcribe = (bool) $CI->vbx_settings->get('transcriptions', $CI->tenant->id);
$voice = $CI->vbx_settings->get('voice', $CI->tenant->id);
$language = $CI->vbx_settings->get('voice_language', $CI->tenant->id);
$timeout = $CI->vbx_settings->get('dial_timeout', $CI->tenant->id);

$dialer = new TwimlDialExtension(array(
	'transcribe' => $transcribe,
	'voice' => $voice,
	'language' => $language,
	'timeout' => $timeout
));
$dialer->set_state();

/**
 * Respond based on state
 * 
 * **NOTE** dialing is done purely on a sequential basis for now.
 * Due to a limitation in Twilio Client we cannot do simulring.
 * If ANY device picks up a call Client stops ringing.
 * 
 * The flow is as follows:
 * - Single User: Sequentially dial devices. If user is online
 *   then the first device will be Client.
 * - Group: Sequentially dial each user's 1st device. If user
 *   is online Client will be the first device.
 * - Number: The number will be dialed.
 */ 
try {
	switch ($dialer->state) {
		case 'voicemail':
			$dialer->noanswer();
			break;
		case 'hangup':
			$dialer->hangup();
			break;
		default:
		  $dialer->dial($dialer->number);
			break;
	}
}
catch (Exception $e) {
	error_log('Dial Applet exception: '.$e->getMessage());
	$dialer->response->say("We're sorry, an error occurred while dialing. Goodbye.");
	$dialer->hangup();
}

$dialer->save_state();
$dialer->respond();