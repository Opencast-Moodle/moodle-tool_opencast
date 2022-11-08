<?php
namespace OpencastApi\Rest;

class OcRecordings extends OcRest
{
    const URI = '/recordings';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Retrieves the last modified hash for specified agent
     *
     * @param string $agentId ID of capture agent for which the last modified hash will be retrieved
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK', 'body' => {The last modified hash of agent is in the body of response}]
     */
    public function getAgentLastModified($agentId)
    {
        $uri = self::URI . "/{$agentId}/lastmodified";
        return $this->restClient->performGet($uri);
    }

    /**
     * Searches for conflicting recordings based on parameters and returns result as XML or JSON. (JSON by default | XML on demand)
     *
     * @param string $agent Device identifier for which conflicts will be searched
     * @param int $start Start time of conflicting period, in milliseconds
     * @param int $end End time of conflicting period, in milliseconds 
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     * @param string $rrule (optional)  Rule for recurrent conflicting, specified as: "FREQ=WEEKLY;BYDAY=day(s);BYHOUR=hour;BYMINUTE=minute". FREQ is required. BYDAY may include one or more (separated by commas) of the following: SU,MO,TU,WE,TH,FR,SA.
     * @param int $duration (optional) If recurrence rule is specified duration of each conflicting period, in milliseconds . (Default value=0)
     * @param string $timezone (optional) The timezone of the capture device 
     *
     * @return array the response result (NO CONTENT if no recordings are in conflict within specified period or list of conflicting recordings in XML or JSON)
     *  ['code' => 200, 'body' => '{Found conflicting events, returned in body of response},'reason' => 'OK']
     *  ['code' => 204, 'body' => '', 'reason' => 'No Content']
     */
    public function getConflicts($agent, $start, $end, $format = '', $rrule = '', $duration = 0, $timezone = '')
    {
        $uri = self::URI . "/conflicts.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

        $query = [];
        if (!empty($rrule)) {
            $query['rrule'] = $rrule;
        }
        if (!empty($duration)) {
            $query['duration'] = intval($duration);
        }
        if (!empty($timezone)) {
            $query['timezone'] = $timezone;
        }
        $queryOpt = $this->restClient->getQueryParams($query);

        $formData = [
            'agent' => $agent,
            'start' => $start,
            'end' => $end,
        ];
        $formOpt = $this->restClient->getFormParams($formData);

        $options = array_merge($queryOpt, $formOpt);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Get the current capture event catalog as JSON
     *
     * @param string $agentId The agent identifier 
     *
     * @return array the response result ['code' => 200, 'body' => '{JSON (Object) DublinCore of current capture event is in the body of response}','reason' => 'OK']
     */
    public function getCurrentCapute($agentId)
    {
        $uri = self::URI . "/capture/{$agentId}";
        return $this->restClient->performGet($uri);
    }

    /**
     * Get the current capture event as XML
     *
     * @param string $agentId The agent identifier 
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) current event is in the body of response}','reason' => 'OK']
     */
    public function getCurrentRecording($agentId)
    {
        $uri = self::URI . "/currentRecording/{$agentId}";
        return $this->restClient->performGet($uri);
    }

    /**
     * Get the number of scheduled events
     *
     * @return array the response result ['code' => 200, 'body' => '{ The event count }','reason' => 'OK']
     */
    public function getEventCount()
    {
        $uri = self::URI . "/eventCount";
        return $this->restClient->performGet($uri);
    }

    /**
     * Return all registered recordings and their state
     *
     * @return array the response result ['code' => 200, 'body' => '{ (Array) all known recordings.}','reason' => 'OK']
     */
    public function getAllRecordings()
    {
        $uri = self::URI . "/recordingStatus";
        return $this->restClient->performGet($uri);
    }

    /**
     * Returns iCalendar for specified set of events
     * 
     * @param string $agentId (optional) Filter events by capture agent 
     * @param string $seriesId (optional) Filter events by series
     * @param int $cutOff (optional) A cutoff date in UNIX milliseconds to limit the number of events returned in the calendar. (Default value=0)
     * 
     * @return array the response result ['code' => 200, 'body' => '{ (Object) Events were modified, new calendar is in the body.}','reason' => 'OK']
     */
    public function getCalenders($agentId = '', $seriesId = '', $cutOff = 0)
    {
        $uri = self::URI . "/calendars";

        $query = [];
        if (!empty($agentId)) {
            $query['agentid'] = $agentId;
        }
        if (!empty($seriesId)) {
            $query['seriesid'] = $seriesId;
        }
        if (!empty($cutOff)) {
            $query['cutoff'] = intval($cutOff);
        }
        
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Returns a calendar in JSON format for specified events. This endpoint is not yet stable and might change in the future with no priot notice
     * 
     * @param string $agentId (optional) Filter events by capture agent 
     * @param int $cutOff (optional)  A cutoff date in UNIX milliseconds to limit the number of events returned in the calendar. (Default value=0)
     * 
     * @return array the response result ['code' => 200, 'body' => '{ (Object) Calendar for events in JSON format.}']
     */
    public function getCalendarJSON($agentId = '', $cutOff = 0)
    {
        $uri = self::URI . "/calendar.json";

        $query = [];
        if (!empty($agentId)) {
            $query['agentid'] = $agentId;
        }
        if (!empty($cutOff)) {
            $query['cutoff'] = intval($cutOff);
        }
        
        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Retrieves media package in XML (text) for specified event
     * 
     * @param string $eventId ID of event for which media package will be retrieved 
     * 
     * @return array the response result ['code' => 200, 'body' => '{ XML (text) DublinCore of event is in the body of response.}','reason' => 'OK']
     */
    public function getEventMediaPackageXML($eventId)
    {
        $uri = self::URI . "/{$eventId}/mediapackage.xml";
        return $this->restClient->performGet($uri);
    }

    /**
     * Return the state of a given recording
     * 
     * @param string $recordingId The ID of a given recording
     * 
     * @return array the response result ['code' => 200, 'body' => '{ Returns the state of the recording with the correct id.}','reason' => 'OK']
     */
    public function getRecordingState($recordingId)
    {
        $uri = self::URI . "/{$recordingId}/recordingStatus";
        return $this->restClient->performGet($uri);
    }

    /**
     * Retrieves the technical metadata for specified event as JSON (Object)
     * 
     * @param string $eventId  ID of event for which the technical metadata will be retrieved 
     * 
     * @return array the response result ['code' => 200, 'body' => '{technical metadata as JSON (Object) of event is in the body of response}','reason' => 'OK']
     */
    public function getTechnicalMetadataJSON($eventId)
    {
        $uri = self::URI . "/{$eventId}/technical.json";
        return $this->restClient->performGet($uri);
    }

    /**
     * Retrieves workflow configuration for specified event
     * 
     * @param string $eventId  ID of event for which workflow configuration will be retrieved 
     * 
     * @return array the response result ['code' => 200, 'body' => '{workflow configuration of event is in the body of response}','reason' => 'OK']
     */
    public function getEventWorkflowProps($eventId)
    {
        $uri = self::URI . "/{$eventId}/workflow.properties";
        return $this->restClient->performGet($uri);
    }

    /**
     * Retrieves Capture Agent properties for specified event
     * 
     * @param string $eventId ID of event for which agent properties will be retrieved 
     * 
     * @return array the response result ['code' => 200, 'body' => '{Capture Agent properties of event is in the body of response}','reason' => 'OK']
     */
    public function getEventCaptureAgentProps($eventId)
    {
        $uri = self::URI . "/{$eventId}/agent.properties";
        return $this->restClient->performGet($uri);
    }

    /**
     * Retrieves DublinCore for specified event as XML or JSON. (JSON by default | XML on demand)
     * 
     * @param string $eventId ID of event for which DublinCore will be retrieved
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     * 
     * @return array the response result ['code' => 200, 'body' => '{JSON (Object) ot XML (text) DublinCore of event is in the body of response}','reason' => 'OK']
     */
    public function getEventDublinCore($eventId, $format = '')
    {
        $uri = self::URI . "/{$eventId}/dublincore.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

        return $this->restClient->performGet($uri);
    }

     /**
     * Searches recordings and returns result as XML or JSON. (JSON by default | XML on demand)
     *
     * @param array $searchParams (optional) Search params containing:
     * [
     *      'agent' => '',      // (optional) Search by device 
     *      'startsfrom' => 0,  // (optional)  Search by when does event start (in miliseconds) (Default value=0)
     *      'startsto' => 0,    // (optional)  Search by when does event start (in miliseconds) (Default value=0)
     *      'endsfrom' => 0,    // (optional) Search by when does event finish (in miliseconds) (Default value=0)
     *      'endsto' => 0,      // (optional) Search by when does event finish (in miliseconds) (Default value=0)
     * ] 
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => '{XML (text) or JSON (Object) formated results}']
     */
    public function getRecordings($searchParams = [], $format = '')
    {
        $uri = self::URI . "/recordings.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }

        $query = [];
        if (array_key_exists('agent', $searchParams) && !empty($searchParams['agent'])) {
            $query['agent'] = $searchParams['agent'];
        }
        if (array_key_exists('startsfrom', $searchParams) && !empty($searchParams['startsfrom'])) {
            $query['startsfrom'] = intval($searchParams['startsfrom']);
        }
        if (array_key_exists('startsto', $searchParams) && !empty($searchParams['startsto'])) {
            $query['startsto'] = intval($searchParams['startsto']);
        }
        if (array_key_exists('endsfrom', $searchParams) && !empty($searchParams['endsfrom'])) {
            $query['endsfrom'] = intval($searchParams['endsfrom']);
        }
        if (array_key_exists('endsto', $searchParams) && !empty($searchParams['endsto'])) {
            $query['endsto'] = intval($searchParams['endsto']);
        }

        $options = $this->restClient->getQueryParams($query);
        return $this->restClient->performGet($uri, $options);
    }

    /**
     * Get the upcoming capture event catalog as JSON (Object)
     *
     * @param string $agentId The agent identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{DublinCore of the upcomfing capture event is in the body of response}']
     */
    public function getUpcomingCaptureCatalog($agentId)
    {
        $uri = self::URI . "/capture/{$agentId}/upcoming";
        return $this->restClient->performGet($uri);
    }

    /**
     * Get the upcoming capture event as XML (text)
     *
     * @param string $agentId The agent identifier
     *
     * @return array the response result ['code' => 200, 'body' => '{The upcoming capture event as XML (text)}']
     */
    public function getUpcomingCapture($agentId)
    {
        $uri = self::URI . "/upcomingRecording/{$agentId}";
        return $this->restClient->performGet($uri);
    }

    /**
     * Removes scheduled event with specified ID.
     *
     * @param string $eventId Event ID
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (Event was successfully removed)
     */
    public function deleteRecording($eventId)
    {
        $uri = self::URI . "/{$eventId}";
        return $this->restClient->performDelete($uri);
    }

    /**
     * Creates new event with specified parameters
     *
     * @param int $start The start date of the event in milliseconds from 1970-01-01T00:00:00Z 
     * @param int $end The end date of the event in milliseconds from 1970-01-01T00:00:00Z
     * @param string $agent The agent of the event
     * @param string $mediaPackage The media package of the event
     * @param string|array $users (optional) Comma separated string or array list of user ids (speakers/lecturers) for the event
     * @param string|array $wfproperties (optional) Workflow configuration keys for the event. Each key will be prefixed by 'org.opencastproject.workflow.config.' and added to the capture agent parameters. 
     * @param string|array $agentparameters (optional) The capture agent properties for the event.
     * @param string $source (optional) The scheduling source of the event
     * 
     *
     * @return array the response result ['code' => 201, 'reason' => 'Created'] (Event is successfully created)
     */
    public function createRecording($start, $end, $agent, $mediaPackage, $users = '', $wfproperties = '', $agentparameters = '', $source = '')
    {
        $uri = self::URI;

        $formData = [
            'start' => intval($start),
            'end' => intval($end),
            'agent' => $agent,
            'mediaPackage' => $mediaPackage,
        ];
        if (!empty($users)) {
            $users = (is_array($users)) ? implode(',', $users) : $users;
            $formData['users'] = $users;
        }
        if (!empty($wfproperties)) {
            $formData['wfproperties'] = $wfproperties;
        }
        if (!empty($agentparameters)) {
            $formData['agentparameters'] = $agentparameters;
        }
        if (!empty($source)) {
            $formData['source'] = $source;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Creates new event with specified parameters
     *
     * @param string $rrule The recurrence rule for the events
     * @param int $start The start date of the event in milliseconds from 1970-01-01T00:00:00Z
     * @param int $end The end date of the event in milliseconds from 1970-01-01T00:00:00Z
     * @param int $duration The duration of the events in milliseconds
     * @param string $tz The timezone of the events
     * @param string $agent The agent of the event
     * @param string $templateMp The template mediapackage for the events
     * @param string|array $users (optional) Comma separated string or array list of user ids (speakers/lecturers) for the event
     * @param string|array $wfproperties (optional) Workflow configuration keys for the event. Each key will be prefixed by 'org.opencastproject.workflow.config.' and added to the capture agent parameters. 
     * @param string|array $agentparameters (optional) The capture agent properties for the event.
     * @param string $source (optional) The scheduling source of the event
     * 
     *
     * @return array the response result ['code' => 201, 'reason' => 'Created'] (Event is successfully created)
     */
    public function createRecordingsMulti($rrule, $start, $end, $duration, $tz, $agent, $templateMp, $users = '', $wfproperties = '', $agentparameters = '', $source = '')
    {
        $uri = self::URI . "/multiple";

        $formData = [
            'rrule' => $rrule,
            'start' => intval($start),
            'end' => intval($end),
            'duration' => intval($duration),
            'tz' => $tz,
            'agent' => $agent,
            'templateMp' => $templateMp,
        ];
        if (!empty($users)) {
            $users = (is_array($users)) ? implode(',', $users) : $users;
            $formData['users'] = $users;
        }
        if (!empty($wfproperties)) {
            $formData['wfproperties'] = $wfproperties;
        }
        if (!empty($agentparameters)) {
            $formData['agentparameters'] = $agentparameters;
        }
        if (!empty($source)) {
            $formData['source'] = $source;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Prolong an immediate capture.
     *
     * @param string $agentId The agent identifier 
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (OK if event were successfully prolonged)
     */
    public function prolongCaptureAgent($agentId)
    {
        $uri = self::URI . "/capture/{$agentId}/prolong";
        return $this->restClient->performPut($uri);
    }

    /**
     * This will find and remove any scheduled events before the buffer time to keep performance in the scheduler optimum.
     *
     * @param int $buffer The amount of seconds before now that a capture has to have stopped capturing. It must be 0 or greater.
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (Removed old scheduled recordings)
     */
    public function removeOldScheduledRecordings($buffer)
    {
        $uri = self::URI . "/removeOldScheduledRecordings";
        
        $formData = [
            'buffer' => intval($buffer)
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Remove record of a given recording
     *
     * @param string $recordingId The ID of a given recording
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] ({recordingId} removed)
     */
    public function removeRecording($recordingId)
    {
        $uri = self::URI . "/{$recordingId}/recordingStatus";
        return $this->restClient->performDelete($uri);
    }

    /**
     * Create an immediate event
     *
     * @param string $agentId The agent identifier
     * @param string $workflowDefinitionId (optional) The workflow definition id to use
     *
     * @return array the response result ['code' => 201, 'reason' => 'CREATED'] (If events were successfully generated, status CREATED is returned)
     */
    public function startCapture($agentId, $workflowDefinitionId = '')
    {
        $uri = self::URI . "/capture/{$agentId}";
        
        $formData = [
            'workflowDefinitionId' => $workflowDefinitionId
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Stops an immediate capture.
     *
     * @param string $agentId The agent identifier
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (OK if event were successfully stopped)
     */
    public function stopCapture($agentId)
    {
        $uri = self::URI . "/capture/{$agentId}";
        return $this->restClient->performDelete($uri);
    }

    /**
     * Updates specified event
     *
     * @param string $eventId ID of event to be updated
     * @param int $start (optional) Updated start date for event (Default value=0)
     * @param int $end (optional) Updated end date for event (Default value=0)
     * @param string $agent (optional) Updated agent for event 
     * @param string|array $users (optional) Updated comma separated or an array list of user ids (speakers/lecturers) for the event 
     * @param string $mediaPackage (optional) Updated media package for event
     * @param string|array $wfproperties (optional) Workflow configuration properties
     * @param string|array $agentparameters (optional) Updated Capture Agent properties 
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (Status OK is returned if event was successfully updated, NOT FOUND if specified event does not exist or BAD REQUEST if data is missing or invalid)
     */
    public function updateRecording($eventId, $start = 0, $end = 0, $agent = '', $users = '', $mediaPackage = '', $wfproperties = '', $agentparameters = '')
    {
        $uri = self::URI . "/{$eventId}";

        $formData = [];
        if (!empty($start)) {
            $formData['start'] = intval($start);
        }
        if (!empty($end)) {
            $formData['end'] = intval($end);
        }
        if (!empty($agent)) {
            $formData['agent'] = $agent;
        }
        if (!empty($mediaPackage)) {
            $formData['mediaPackage'] = $mediaPackage;
        }
        if (!empty($users)) {
            $users = (is_array($users)) ? implode(',', $users) : $users;
            $formData['users'] = $users;
        }
        if (!empty($wfproperties)) {
            $formData['wfproperties'] = $wfproperties;
        }
        if (!empty($agentparameters)) {
            $formData['agentparameters'] = $agentparameters;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }

    /**
     * Set the status of a given recording, registering it if it is new
     *
     * @param string $recordingId The ID of a given recording
     * @param string $state The state of the recording. Possible values: unknown, capturing, capture_finished, capture_error, manifest, manifest_error, manifest_finished, compressing, compressing_error, uploading, upload_finished, upload_error.
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] ({recordingId} set to {state})
     */
    public function updateRecordingState($recordingId, $state)
    {
        $uri = self::URI . "/{$recordingId}/recordingStatus";

        $formData = [
            'state' => $state
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPut($uri, $options);
    }
}
?>