<hr>

<h3>
	CleverReach settings
</h3>

<form method="post" class="thrive-third-party-form">
	<div>
		<label for="thrive-client-id"> Client ID </label>
		<input id="thrive-client-id" type="text" name="client_id" value="<?php echo $credentials['client_id']; ?>">
	</div>
	<div style="margin-top:10px">
		<label for="thrive-client-secret"> Client Secret </label>
		<input id="thrive-client-secret" type="text" name="client_secret" value="<?php echo $credentials['client_secret']; ?>">
	</div>

	<br>

	<input type="submit" class="thrive-api-connect" name="action" value="connect">
	<input type="submit" class="thrive-api-connect" name="action" value="disconnect">

	<br><br>

	<input type="submit" class="thrive-api-connect" name="action" value="test connection">
</form>

<br>
<hr>

<?php $is_connected = ! empty( $credentials['access_token'] ); ?>

<span style="color:<?php echo $is_connected ? 'green' : 'red'; ?>">
	Status: <?php echo $is_connected ? 'Connected' : 'Disconnected'; ?>
</span>

<hr>
