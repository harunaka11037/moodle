<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * H5P autoloader management class.
 *
 * @package    core_h5p
 * @copyright  2019 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p\local\library;

defined('MOODLE_INTERNAL') || die();

/**
 * H5P autoloader management class.
 *
 * @package    core_h5p
 * @copyright  2019 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class autoloader {

    /**
     * Returns the list of plugins that can work as H5P library handlers (have class PLUGINNAME\local\library\handler)
     * @return array with the format: pluginname => class
     */
    public static function get_all_handlers(): array {
        $handlers = [];
        foreach (\core_component::get_plugin_types() as $ptype => $unused) {
            $plugins = \core_component::get_plugin_list_with_class($ptype, 'local\library\handler') +
                \core_component::get_plugin_list_with_class($ptype, 'local_library_handler');
            // Allow plugins to have the class either with namespace or without (useful for unittest).
            foreach ($plugins as $pname => $class) {
                $handlers[$pname] = $class;
            }
        }

        return $handlers;
    }

    /**
     * Returns the default H5P library handler.
     * @return string|null H5P library handler class
     */
    public static function get_default_handler(): ?string {
        $default = null;
        $handlers = self::get_all_handlers();
        if (!empty($handlers)) {
            // The default handler will be the first in the list.
            $keys = array_keys($handlers);
            $default = array_shift($keys);
        }

        return $default;
    }

    /**
     * Returns the current H5P library handler class.
     *
     * @return string H5P library handler class
     * @throws \moodle_exception
     */
    public static function get_handler_classname(): string {
        global $CFG;

        $handlers = self::get_all_handlers();
        if (!empty($CFG->h5plibraryhandler)) {
            if (isset($handlers[$CFG->h5plibraryhandler])) {
                return $handlers[$CFG->h5plibraryhandler];
            }
        }

        // If no handler has been defined or it doesn't exist, return the default one.
        $defaulthandler = self::get_default_handler();
        if (empty($defaulthandler)) {
            // If there is no default handler, throw an exception.
            throw new \moodle_exception('noh5plibhandlerdefined', 'core_h5p');
        }

        return $defaulthandler;
    }

    /**
     * Get the current version of the H5P core library.
     *
     * @return string
     */
    public static function get_h5p_version(): string {
        return component_class_callback(self::get_handler_classname(), 'get_h5p_version', []);
    }

    /**
     * Get a URL for the current H5P Core Library.
     *
     * @param string $filepath The path within the h5p root
     * @param array $params these params override current params or add new
     * @return null|moodle_url
     */
    public static function get_h5p_core_library_url(?string $filepath = null, ?array $params = null): ?\moodle_url {
        return component_class_callback(self::get_handler_classname(), 'get_h5p_core_library_url', [$filepath, $params]);
    }

    /**
     * Register the H5P autoloader.
     */
    public static function register(): void {
        component_class_callback(self::get_handler_classname(), 'register', []);
    }
}
