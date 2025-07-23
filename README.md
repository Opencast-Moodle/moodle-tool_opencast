moodle-tool_opencast
====================

[![Moodle Plugin CI](https://github.com/Opencast-Moodle/moodle-tool_opencast/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/Opencast-Moodle/moodle-tool_opencast/actions/workflows/moodle-ci.yml)

Moodle plugin which provides API functions as well as general settings for all Opencast plugins for Moodle and which is required by them.


The Opencast project
--------------------

[Opencast](https://opencast.org/) is a flexible, reliable, and scalable open-source video-capture, -management, and -distribution system for academic institutions, built by a community of developers from leading universities and organizations worldwide.

Integrating Opencast into Moodle is realized with a [set of flexible plugins](https://moodle.org/plugins/browse.php?list=set&id=109) and this plugin is a mandatory member of this plugin set.


Moodle requirements
-------------------

This plugin is maintained in one Git branch per Moodle core release.

This particular branch is developed and tested on Moodle 4.5.


Opencast support
----------------

Due to the nature of Opencast, the Moodle plugin releases have to match the Opencast releases.

We try our best to maintain backwards compatibility, but we cannot fully avoid breaking changes.\
Please see the overview table to understand this particular Moodle plugin release's support for the particular Opencast major releases:

|    Opencast release    | Support status |           Feature support            | Automated CI testing | Manual acceptance testing |
| ---------------------- | -------------- | ------------------------------------ | -------------------- | ------------------------- |
| Opencast 18            | Planned        | Full feature support                 | Not yet              | Not yet                   |
| Opencast 17            | Active         | Full feature support                 | Yes                  | Yes                       |
| Opencast 16            | Best effort    | Full feature support                 | Yes                  | No                        |
| Opencast 15            | Best effort    | Full feature support                 | Yes                  | No                        |
| Opencast 14 and before | Discontinued   | Expect breaking changes and failures | No                   | No                        |

In addition to that, please note that development and testing of the Moodle plugins is normally carried out against the latest Opencast minor releases. Please try to keep your own Opencast installations up-to-date as well.


Installation
------------

Install the plugin like any other plugin to folder
/admin/tool/opencast

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


External documentation
----------------------

There is an [extensive documentation page](https://moodle.docs.opencast.org/) which documents the set of Opencast plugins for Moodle. Please consider this README file here as basic documentation and consult that documentation as full reference how to use the plugins.

Theme support
-------------

This plugin is developed and tested on Moodle Core's Boost theme.
It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.


Plugin repositories
-------------------

This plugin is published and regularly updated in the Moodle plugins repository:
http://moodle.org/plugins/view/tool_opencast

The latest development version can be found on Github:
https://github.com/Opencast-Moodle/moodle-tool_opencast


Bug and problem reports / Support requests
------------------------------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.

Please report bugs and problems on Github:
https://github.com/Opencast-Moodle/moodle-tool_opencast/issues

We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.


Feature proposals
-----------------

The functionality of this plugin is primarily implemented for the needs of the active members of the Opencast-Moodle community and is published as-is. Nevertheless, we are always interested to read about your feature proposals or even get a pull request from you.

Please issue feature proposals on Github:
https://github.com/Opencast-Moodle/moodle-tool_opencast/issues

Please create pull requests on Github:
https://github.com/Opencast-Moodle/moodle-tool_opencast/pulls


Moodle release support
----------------------

Due to limited resources, this plugin is only maintained for the most recent major release of Moodle as well as the most recent LTS release of Moodle. Bugfixes are backported to the LTS release. However, new features and improvements are not necessarily backported to the LTS release.

Apart from these maintained releases, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that this plugin still works with a new major release - please let us know on Github.

If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is an undeniable risk for erratic behavior.


Translating this plugin
-----------------------

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

As the plugin creator, we manage the translation into german for our own local needs on AMOS. Please contribute your translation into all other languages in AMOS where they will be reviewed by the official language pack maintainers for Moodle.


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send us a pull request on Github with modifications.


Maintainers
-----------

The plugin is maintained by\
Thomas Niedermaier\
University of Münster

together with

Farbod Zamani\
elan e.V.


Copyright
---------

The copyright of this plugin is held by\
University of Münster

Individual copyrights of individual developers are tracked in PHPDoc comments and Git commits.


Initial copyright
-----------------

This plugin was initially built by\
Andreas Wagner\
Synergy Learning

on behalf of\
TU Ilmenau

It was contributed to the Opencast project and is since then maintained by University of Münster


License
-------

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
