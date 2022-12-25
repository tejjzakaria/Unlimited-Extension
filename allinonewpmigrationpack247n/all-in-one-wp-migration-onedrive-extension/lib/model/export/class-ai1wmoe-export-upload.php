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

class Ai1wmoe_Export_Upload {

	public static function execute( $params, Ai1wmoe_OneDrive_Client $onedrive = null ) {

		$params['completed'] = false;

		// Validate upload URL
		if ( ! isset( $params['upload_url'] ) ) {
			throw new Ai1wm_Import_Exception( __( 'OneDrive Upload URL is not specified.', AI1WMOE_PLUGIN_NAME ) );
		}

		// Set archive offset
		if ( ! isset( $params['archive_offset'] ) ) {
			$params['archive_offset'] = 0;
		}

		// Set archive size
		if ( ! isset( $params['archive_size'] ) ) {
			$params['archive_size'] = ai1wm_archive_bytes( $params );
		}

		// Set file chunk size for upload
		$file_chunk_size = get_option( 'ai1wmoe_onedrive_file_chunk_size', AI1WMOE_DEFAULT_FILE_CHUNK_SIZE );

		// Set file range start
		if ( ! isset( $params['file_range_start'] ) ) {
			$params['file_range_start'] = 0;
		}

		// Set file range end
		if ( ! isset( $params['file_range_end'] ) ) {
			$params['file_range_end'] = min( $file_chunk_size, $params['archive_size'] ) - 1;
		}

		// Set upload retries
		if ( ! isset( $params['upload_retries'] ) ) {
			$params['upload_retries'] = 0;
		}

		// Set OneDrive client
		if ( is_null( $onedrive ) ) {
			$onedrive = new Ai1wmoe_OneDrive_Client(
				get_option( 'ai1wmoe_onedrive_token', false ),
				get_option( 'ai1wmoe_onedrive_ssl', true )
			);
		}

		// Open the archive file for reading
		$archive = fopen( ai1wm_archive_path( $params ), 'rb' );

		// Read file chunk data
		if ( ( fseek( $archive, $params['archive_offset'] ) !== -1 )
				&& ( $file_chunk_data = fread( $archive, $file_chunk_size ) ) ) {

			$onedrive->load_upload_url( $params['upload_url'] );

			try {

				$params['upload_retries'] += 1;

				// Upload file chunk data
				if ( ( $response = $onedrive->upload_file_chunk( $file_chunk_data, $params['archive_size'], $params['file_range_start'], $params['file_range_end'] ) ) ) {
					if ( isset( $response['webUrl'] ) ) {
						$params['archive_url'] = $response['webUrl'];
					}

					// Set archive offset
					$params['archive_offset'] = ftell( $archive );

					// Get next expected ranges
					if ( isset( $response['nextExpectedRanges'][0] ) ) {
						if ( ( $file_ranges = explode( '-', $response['nextExpectedRanges'][0] ) ) ) {
							if ( isset( $file_ranges[0] ) ) {
								$params['file_range_start'] = $params['archive_offset'] = $file_ranges[0];
							}

							if ( isset( $file_ranges[0], $file_ranges[1] ) ) {
								$params['file_range_end'] = min( $file_ranges[0] + $file_chunk_size - 1, $file_ranges[1] );
							}
						}
					}
				}

				// Unset upload retries
				unset( $params['upload_retries'] );

			} catch ( Ai1wmoe_Connect_Exception $e ) {
				if ( $params['upload_retries'] <= 5 ) {
					return $params;
				}

				throw $e;
			} catch ( Ai1wmoe_Invalid_Range_Exception $e ) {
				if ( ( $file_ranges = $onedrive->get_next_expected_ranges() ) ) {
					if ( isset( $file_ranges[0] ) ) {
						$params['file_range_start'] = $params['archive_offset'] = $file_ranges[0];
					}

					if ( isset( $file_ranges[0], $file_ranges[1] ) ) {
						$params['file_range_end'] = min( $file_ranges[0] + $file_chunk_size - 1, $file_ranges[1] );
					}
				}

				if ( $params['upload_retries'] <= 5 ) {
					return $params;
				}

				throw $e;
			}

			// Set archive details
			$name = ai1wm_archive_name( $params );
			$size = ai1wm_archive_size( $params );

			// Get progress
			$progress = (int) ( ( $params['archive_offset'] / $params['archive_size'] ) * 100 );

			// Set progress
			if ( defined( 'WP_CLI' ) ) {
				WP_CLI::log(
					sprintf(
						__( 'Uploading %s (%s) [%d%% complete]', AI1WMOE_PLUGIN_NAME ),
						$name,
						$size,
						$progress
					)
				);
			} else {
				Ai1wm_Status::info(
					sprintf(
						__(
							'<i class="ai1wmoe-icon-onedrive"></i> ' .
							'Uploading <strong>%s</strong> (%s)<br />%d%% complete',
							AI1WMOE_PLUGIN_NAME
						),
						$name,
						$size,
						$progress
					)
				);
			}
		} else {

			// Set last backup date
			update_option( 'ai1wmoe_onedrive_timestamp', time() );

			// Unset upload URL
			unset( $params['upload_url'] );

			// Unset archive offset
			unset( $params['archive_offset'] );

			// Unset archive size
			unset( $params['archive_size'] );

			// Unset file range start
			unset( $params['file_range_start'] );

			// Unset file range end
			unset( $params['file_range_start'] );

			// Unset completed
			unset( $params['completed'] );
		}

		// Close the archive file
		fclose( $archive );

		return $params;
	}
}
