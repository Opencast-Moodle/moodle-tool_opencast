CHANGELOG
=========

5.0.2 (2025-09-22)
------------------
* [FIXED] upgrade.php: fix wrong savepoint statement


5.0.1 (2025-09-01)
------------------
* [CHANGE] upgrade.php: make sure to run upgrade job for version 5.0 without errors
* [CHANGE] #95 Updates the composer-managed dependencies and bumps the CI PHP version to 8.4
* [CHANGE] #96 Introduces a new language string servicename for end-user-facing pages
* [CHANGE] #98 Refactor settings helper: Moved user placeholder validation logic to a separate function to
  decouple it from ACL owner validation
* [FIXED] #97 The declared namespace and the location of the class files did occasionally not match


5.0.0 (2025-08-01)
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
* Moodle 5.0 compatible version


4.5.4 (2025-08-01)
------------------
* [FIX] #86, #87 Fix for behat test in maintenance mode
* [CHANGES] #82 Mark old cURL methods as deprecated
* [FEATURE] #84 Upgrade to oc-php-lib v1.9.0
* [FEATURE] #92 Enhance maintenance handling


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
* Moodle 4.5 compatible version

