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

class Ai1wmle_Export_Controller {

	public static function inactive_themes() {
		Ai1wm_Template::render(
			'export/inactive-themes',
			array(),
			AI1WMLE_TEMPLATES_PATH
		);
	}

	public static function inactive_plugins() {
		Ai1wm_Template::render(
			'export/inactive-plugins',
			array(),
			AI1WMLE_TEMPLATES_PATH
		);
	}

	public static function cache_files() {
		Ai1wm_Template::render(
			'export/cache-files',
			array(),
			AI1WMLE_TEMPLATES_PATH
		);
	}

	public static function exclude_files() {
		Ai1wm_Template::render(
			'export/exclude-files',
			array(),
			AI1WMLE_TEMPLATES_PATH
		);
	}

	public static function list_files( $params = array() ) {
		check_ajax_referer( 'ai1wmle_list', 'security' );
		ai1wm_setup_environment();

		// Set params
		if ( empty( $params ) ) {
			$params = stripslashes_deep( $_GET );
		}

		// Set folder path
		$folder_path = null;
		if ( isset( $params['folder_path'] ) ) {
			$folder_path = trim( $params['folder_path'] );
		}

		// Validate folder path
		if ( validate_file( $folder_path ) !== 0 ) {
			echo json_encode( array( 'success' => false ) );
			exit;
		}

		$files = array();

		// Get content directory files
		if ( is_dir( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $folder_path ) ) {

			// Iterate over content directory
			$iterator = new Ai1wm_Recursive_Directory_Iterator( WP_CONTENT_DIR . DIRECTORY_SEPARATOR . $folder_path );

			// Recursively iterate over content directory
			$iterator = new Ai1wm_Recursive_Iterator_Iterator( $iterator, RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );
			$iterator->setMaxDepth( 0 );

			// Loop over content directory
			foreach ( $iterator as $item ) {
				$files[] = array(
					'name'    => $iterator->getFilename(),
					'path'    => substr_replace( $iterator->getPathname(), '', 0, strlen( WP_CONTENT_DIR ) + 1 ),
					'toggled' => false,
					'checked' => false,
					'folder'  => $item->isDir(),
					'date'    => human_time_diff( $iterator->getMTime() ),
				);
			}

			$types = $names = array();
			foreach ( $files as $key => $value ) {
				$types[ $key ] = $value['folder'];
				$names[ $key ] = $value['name'];
			}

			array_multisort( $types, SORT_DESC, $names, SORT_ASC, $files );
		}

		echo json_encode( array( 'success' => true, 'files' => $files ) );
		exit;
	}
}
