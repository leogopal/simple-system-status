<div class="wrap">
	<h2 class="sss-title"><?php _e( 'Simple System Status', 'simple-system-status' ); ?></h2>
		<div id="templateside">
			<p class="instructions"><?php _e( 'Simple System Status displays information about your WordPress Configuration and Server Information that may be useful for debugging.', 'simple-system-status' ) ?></p>
		</div>
		<div id="template">
			<?php // Form used to download .txt file ?>
			<form action="<?php echo esc_url( self_admin_url( 'admin-ajax.php' ) ); ?>" method="post" enctype="multipart/form-data" >
				<input type="hidden" name="action" value="download_simple_system_status" />
				<div>
					<textarea readonly="readonly" onclick="this.focus();this.select()" id="sss-textarea" name="simple-system-status-textarea" title="<?php _e( 'To copy the System Status, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'simple-system-status' ); ?>">
<?php //Non standard indentation needed for plain-text display ?>
<?php echo esc_html( self::display() ) ?>
					</textarea>
				</div>
				<p class="submit">
					<input type="submit" class="button-secondary" value="<?php _e( 'Download Sytem Info as Text File', 'simple-system-status' ) ?>" />
				</p>
			</form>
			<h3 class="sss-remote-title"><?php _e( 'Remote Viewing', 'simple-system-status' ) ?></h3>
			<?php Simple_System_Status_Viewer::remote_viewing_section() ?>
		</div>
</div>
