<?php
/**
 * Copyright (C) 2014-2020 ServMask Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ███████╗███████╗██████╗ ██╗   ██╗███╗   ███╗ █████╗ ███████╗██╗  ██╗
 * ██╔════╝██╔════╝██╔══██╗██║   ██║████╗ ████║██╔══██╗██╔════╝██║ ██╔╝
 * ███████╗█████╗  ██████╔╝██║   ██║██╔████╔██║███████║███████╗█████╔╝
 * ╚════██║██╔══╝  ██╔══██╗╚██╗ ██╔╝██║╚██╔╝██║██╔══██║╚════██║██╔═██╗
 * ███████║███████╗██║  ██║ ╚████╔╝ ██║ ╚═╝ ██║██║  ██║███████║██║  ██╗
 * ╚══════╝╚══════╝╚═╝  ╚═╝  ╚═══╝  ╚═╝     ╚═╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Kangaroos cannot jump here' );
}

class Ai1wmoe_Import_Database {

	public static function execute( $params ) {

		$model = new Ai1wmoe_Settings;

		// Set progress
		Ai1wm_Status::info( __( 'Updating OneDrive settings...', AI1WMOE_PLUGIN_NAME ) );

		// Read settings.json file
		$handle = ai1wm_open( ai1wm_settings_path( $params ), 'r' );

		// Parse settings.json file
		$settings = ai1wm_read( $handle, filesize( ai1wm_settings_path( $params ) ) );
		$settings = json_decode( $settings, true );

		// Close handle
		ai1wm_close( $handle );

		// Update OneDrive settings
		$model->set_cron_timestamp( $settings['ai1wmoe_onedrive_cron_timestamp'] );
		$model->set_cron( $settings['ai1wmoe_onedrive_cron'] );
		$model->set_token( $settings['ai1wmoe_onedrive_token'] );
		$model->set_ssl( $settings['ai1wmoe_onedrive_ssl'] );
		$model->set_folder_id( $settings['ai1wmoe_onedrive_folder_id'] );
		$model->set_backups( $settings['ai1wmoe_onedrive_backups'] );
		$model->set_total( $settings['ai1wmoe_onedrive_total'] );
		$model->set_days( $settings['ai1wmoe_onedrive_days'] );
		$model->set_file_chunk_size( $settings['ai1wmoe_onedrive_file_chunk_size'] );
		$model->set_notify_ok_toggle( $settings['ai1wmoe_onedrive_notify_toggle'] );
		$model->set_notify_error_toggle( $settings['ai1wmoe_onedrive_notify_error_toggle'] );
		$model->set_notify_error_subject( $settings['ai1wmoe_onedrive_notify_error_subject'] );
		$model->set_notify_email( $settings['ai1wmoe_onedrive_notify_email'] );
		$model->set_access_token( $settings['ai1wmoe_onedrive_access_token'] );
		$model->set_access_token_expires_in( $settings['ai1wmoe_onedrive_access_token_expires_in'] );
		$model->set_lock_mode( $settings['ai1wmoe_onedrive_lock_mode'] );

		// Set progress
		Ai1wm_Status::info( __( 'Done updating OneDrive settings.', AI1WMOE_PLUGIN_NAME ) );

		return $params;
	}
}
