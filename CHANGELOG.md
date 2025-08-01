CHANGELOG
=========

5.0.0 (2025-05-26)
------------------
* [FEATURE] The Opencast Course Overview is now accessible via the course navigation bar
* [CHANGE] Most features from the Opencast Block plugin have been moved to
the Opencast Tool plugin. The Opencast Block plugin is now optional.
* [CHANGE] The course backup functionality has changed. The option to select
individual events has been removed.\
Two site-wide admin settings (`importvideosonbackup`) and (`importreducedduplication`) have been added.\
If (`importvideosonbackup`) is enabled, videos will be backed up during course backups.\
If (`importreducedduplication`) is enabled, only the events and series embedded via LTI or the Opencast
activity module will be backed up.\
If disabled (default), all events from
the course will be included in the backup.
* [CHANGE] Introducing workflows config panel json compatibility
* [CHANGE] Refactor and upgrade transcription feature

4.5.3 (2025-01-16)
------------------
* [FIX] #74 Refactor settings handling when propagating
* [FIX] #73 Delete settings of all plugins when deleting an instance
* [FEATURE] #72 Add support for Opencast maintenance periods
* [FEATURE] #70 Improved Opencast API exceptions and error handling


4.5.2 aka 4.5.1 (2024-12-03)
------------------
* [FIX] #69 Lang file (en) sorted alphabetically
* [FEATURE] #63 Upgrade Opencast PHP Library to 1.8.0/Api Version setting (OC 16 Support)/Moodle 4.4 Required Changes

 
4.5.0 (2024-11-12)
------------------
Moodle 4.5 compatible version

