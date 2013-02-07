<?php
	$ci =& get_instance();
	$ci->load->model('vbx_incoming_numbers');
	
	try {
		$numbers = $ci->vbx_incoming_numbers->get_numbers();
	}
	catch (VBX_IncomingNumberException $e) {
		log_message('Incoming numbers exception: '.$e->getMessage.' :: '.$e->getCode());
		$numbers = array();
	}
	
	$callerId = AppletInstance::getValue('callerId', null);
	$version = AppletInstance::getValue('version', null);
?>
<div class="vbx-applet dial-applet">

	<h2>Dial Whom</h2>
	<div class="vbx-full-pane">
		<fieldset class="vbx-input-container">
  		<h4>Phone number</h4>
  		<div class="vbx-input-container input">
  			<input type="text" class="medium" name="number" value="<?php echo AppletInstance::getValue('number') ?>"/>
  		</div>
		</fieldset>
		<fieldset class="vbx-input-container">
  		<h4>Extension</h4>
  		<div class="vbx-input-container input">
  			<input type="text" class="medium" name="extension" value="<?php echo AppletInstance::getValue('extension') ?>"/>
  		</div>
		</fieldset>
	</div>

	<br />
	<h2>Delay before extension</h2>
	<div class="vbx-full-pane">
		<fieldset class="vbx-input-container">
  		<h4>Delay in seconds</h4>
  		<div class="vbx-input-container input">
  			<input type="text" class="medium" name="delay" value="<?php echo AppletInstance::getValue('delay', '1.0') ?>"/>
  		</div>
		</fieldset>
	</div>

	<br />
	<h2>Caller ID</h2>
	<div class="vbx-full-pane">
		<fieldset class="vbx-input-container">
			<select class="medium" name="callerId">
				<option value="">Caller's Number</option>
<?php if(count($numbers)) foreach($numbers as $number): $number->phone = normalize_phone_to_E164($number->phone); ?>
				<option value="<?php echo $number->phone; ?>"<?php echo $number->phone == $callerId ? ' selected="selected" ' : ''; ?>><?php echo $number->name; ?></option>
<?php endforeach; ?>
			</select>
		</fieldset>
	</div>

	<br />
	<h2>If nobody answers...</h2>
	<div class="vbx-full-pane nobody-answers-number">
		<?php echo AppletUI::DropZone('no-answer-redirect') ?>
	</div>

	<!-- Set the version of this applet -->
	<input type="hidden" name="version" value="1" />
</div><!-- .vbx-applet -->