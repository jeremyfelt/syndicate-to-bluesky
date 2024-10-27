<?php
/**
 * Plugin Name:  Syndicate to Bluesky
 * Description:  Syndicate posts to Bluesky.
 * Version:      0.0.1
 * Plugin URI:   https://github.com/jeremyfelt/syndicate-to-bluesky/
 * Author:       Jeremy Felt
 * Author URI:   https://jeremyfelt.com
 * Text Domain:  syndicate-to-bluesky
 * Domain Path:  /languages
 * Requires PHP: 7.4
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * Quite a bit of inspiration and much of the initial code was forked
 * from the Share on Bluesky plugin, which is licensed as GPLv2. A big
 * thank you to Matthias Pfefferle, you should probably use that plugin
 * and ignore my experimentations. :)
 *
 * https://github.com/pfefferle/wordpress-share-on-bluesky
 *
 * @package syndicate-to-bluesky
 */

namespace SyndicateToBluesky;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PLUGIN_FILE = __FILE__;

require_once __DIR__ . '/vendor/autoload.php';

// Initialize the plugin.
Init::init();
