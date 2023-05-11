# 1.1.0
 - Provide progress upon event uploads [#6](https://github.com/elan-ev/opencast-php-library/issues/6)
 - Update guzzle to 7.4.4 [#8](https://github.com/elan-ev/opencast-php-library/issues/8)
 - Handle Exceptions [#5](https://github.com/elan-ev/opencast-php-library/issues/5)
 - Perform a call without any headers or request options [#4](https://github.com/elan-ev/opencast-php-library/issues/4)
 - Error Handling (Version control) [#3](https://github.com/elan-ev/opencast-php-library/issues/3)
 - Add dynamic role headers with "X-RUN-WITH-ROLES" to requests [#2](https://github.com/elan-ev/opencast-php-library/issues/2)
 - A single filter can occur multiple times [#1](https://github.com/elan-ev/opencast-php-library/issues/1)
 
# 1.1.1
- Sysinfo Endpoint [#9](https://github.com/elan-ev/opencast-php-library/issues/9)
- Optional serviceType in getServiceJSON [#10](https://github.com/elan-ev/opencast-php-library/issues/10)

# 1.2.0
- typo in method name "addSinlgeAcl" in OcEventsApi [#13](https://github.com/elan-ev/opencast-php-library/issues/13)
- Dynamic timeouts per each call to the methods [#12](https://github.com/elan-ev/opencast-php-library/issues/12)
- Rename the OpenCast class to Opencast [#14](https://github.com/elan-ev/opencast-php-library/issues/14)

# 1.3.0
- Introducing the Mock handling mechanism for testing
- A new API Events Endpoint to add track to an event, which also can be used to removed/overwrite the existing tracks of a flavor.
- Depricated Methods OcWorkflowsApi->getAll() since it is has been removed from Opencast 12.
- Depricated Methods OcWorkflow->getStatistics() since it is has been removed from Opencast 12.
- Depricated Methods OcWorkflow->getInstances() since it is has been removed from Opencast 12.
- Depricated Methods OcSeries->getTitles() or (/series/allSeriesIdTitle.json Endpoint) since it is has been removed from Opencast 12.
- Depricated Methods OcSeries->getAll() or (/series/series.json|xml Endpoints) since it is has been removed from Opencast 12.
- Add the series fulltext search query into Series API in: OcSeriesApi->getAllFullTextSearch()
- The ingest API now allows setting tags when ingesting attachments or catalogs via URL, therefore OcIngest methods including addCatalog, addCatalogUrl, addAttachment and addAttachmentUrl now accept an array parameter containing the tags.
- Dynamic ingest endpoint loading into Opencast class.
- Upgrade guzzlehttp/guzzle to 7.5.1