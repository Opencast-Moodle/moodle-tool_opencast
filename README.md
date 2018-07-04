# Opencast API #

This tool provides API functions as well as general settings for the different opencast tools:
* [block_opencast](https://github.com/unirz-tu-ilmenau/moodle-block_opencast)
* [filter_opencast](https://github.com/unirz-tu-ilmenau/moodle-filter_opencast)
* [repository_opencast](https://github.com/unirz-tu-ilmenau/moodle-repository_opencast)

The tool stores the relation between courses and series ids and 
offers webservice endpoints for the opencast role provider. 

## Settings ##

Here the general settings for the connection to your opencast server can be set.
Required are the server and the API user.

Make sure that the API user you define here has the necessary access rights in opencast to actually access the API endpoints for *events*, *groups* and *series*.

Additionally, you can define a timeout for the connection.

## License ##

This plugin is developed in cooperation with the TU Ilmenau and the WWU Münster.

It is based on 2017 Andreas Wagner, SYNERGY LEARNING

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
