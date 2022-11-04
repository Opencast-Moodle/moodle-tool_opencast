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

namespace tool_opencast\local;

/**
 * A class, to represent Opencast instances for Moodle.
 *
 * An instance of this class represents an Opencast instance for Moodle and has the properties,
 * that are given by or are definable with the admin settings of tool_opencast for an Opencast instance.
 *
 * @package    tool_opencast
 * @copyright  2022 Matthias Kollenbroich, University of Münster
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class opencast_instance {

    /**
     * The id of the Opencast instance.
     *
     * This id identifies the configuration of the Opencast instance for Moodle
     * and is not explicitly associated to Opencast.
     *
     * Note, that a valid id of an Opencast instance is greater than zero.
     *
     * @var int
     */
    public int $id;

    /**
     * The name of the Opencast instance.
     *
     * @var string
     */
    public string $name;

    /**
     * The visibility state of the Opencast instance.
     *
     * @var bool
     */
    public bool $isvisible;

    /**
     * The default state of the Opencast instance.
     *
     * Exactly one of the for Moodle configured Opencast instances
     * is the default Opencast instance.
     * For this instance, this property is true.
     * For all other instances, this property is false.
     *
     * @var bool
     */
    public bool $isdefault;

    /**
     * Constructs an instance with the properties of the passed \stdClass instance,
     * which are copied.
     *
     * All properties, that are required for an instance of the class
     * opencast_instance, must be defined for the passed \stdClass instance.
     *
     * @param \stdClass $dynamicobject
     */
    public function __construct(\stdClass $dynamicobject) {
        $this->id = $dynamicobject->id;
        $this->name = $dynamicobject->name;
        $this->isvisible = $dynamicobject->isvisible;
        $this->isdefault = $dynamicobject->isdefault;
    }

}
