<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace OpencastApi\Util;

/**
 * Opencast API Utility class.
 *
 * This class provides additional functionality to simplify the integration and consumption of this library's output within applications.
 */
class OcUtils
{
    /**
     * This function searches for a specific key in a given object or array, including nested structures.
     *
     * @param object|array $object The object or array to search in.
     * @param string $targetKey The key to search for.
     *
     * @return mixed|null The value of the found key, or null if the key is not found.
     */
    public static function findValueByKey(object|array $object, string $targetKey) {
        if (is_object($object)) {
            // Perform first-level type casting,
            // to preserve the data types of child elements.
            $object = (array) $object;
        }

        foreach ($object as $key => $value) {
            if ($key === $targetKey) {
                return $value;
            } else if (is_array($value) || is_object($value)) {
                // Recursively search in nested structures.
                $found = self::findValueByKey($value, $targetKey);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }
}
