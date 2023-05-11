<?php
namespace OpencastApi\Rest;

class OcCaptureAdmin extends OcRest
{
    const URI = '/capture-admin';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Returns the state of a given capture agent. (JSON by default | XML on demand)
     *
     * @param string $name Name of the capture agent
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => ' {agentState}]
     */
    public function getAgentState($name, $format = '')
    {
        $uri = self::URI . "/agents/{$name}.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }
        return $this->restClient->performGet($uri);
    }

    /**
     * Returns the capabilities of a given capture agent. (JSON by default | XML on demand)
     *
     * @param string $name Name of the capture agent
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => ' {An XML (text) or JSON (Object) representation of the agent capabilities }]
     */
    public function getAgentCapabilities($name, $format = '')
    {
        $uri = self::URI . "/agents/{$name}/capabilities.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }
        return $this->restClient->performGet($uri);
    }

    /**
     * Return the configuration of a given capture agent. (JSON by default | XML on demand)
     *
     * @param string $name Name of the capture agent
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => ' {An XML (text) or JSON (Object) representation of the agent configuration}]
     */
    public function getAgentConfiguration($name, $format = '')
    {
        $uri = self::URI . "/agents/{$name}/configuration.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }
        return $this->restClient->performGet($uri);
    }

    /**
     * Return all registered recordings and their state
     *
     * @return array the response result ['code' => 200, 'body' => '{an array of all known recordings}']
     */
    public function recordings()
    {
        $uri = self::URI . "/recordings";
        return $this->restClient->performGet($uri);
    }

    /**
     * Return all of the known capture agents on the system. (JSON by default | XML on demand)
     *
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => ' {An XML (text) or JSON (Object) representation of all of the known capture agents}]
     */
    public function getAgents($format = '')
    {
        $uri = self::URI . "/agents.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }
        return $this->restClient->performGet($uri);
    }

    /**
     * Return the state of a given recording. (JSON by default | XML on demand)
     *
     * @param string $recordingId The ID of a given recording
     * @param string $format (optional) The output format (json or xml) of the response body. (Default value = 'json')
     *
     * @return array the response result ['code' => 200, 'body' => ' {An XML (text) or JSON (Object) representation of the state of the recording with the correct id}]
     */
    public function getRecording($recordingId, $format = '')
    {
        $uri = self::URI . "/recordings/{$recordingId}.json";
        if (!empty($format) && strtolower($format) == 'xml') {
            $uri = str_replace('.json', '.xml', $uri);
        }
        return $this->restClient->performGet($uri);
    }

    /**
     * Remove record of a given capture agent
     *
     * @param string $agentName Name of the capture agent
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] ({agentName} removed)
     */
    public function deleteAgent($agentName)
    {
        $uri = self::URI . "/agents/{$agentName}";
        return $this->restClient->performDelete($uri);
    }

    /**
     * Remove record of a given recording
     *
     * @param string $recordingId The ID of a given recording
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] ( {id} removed )
     */
    public function deleteRecording($recordingId)
    {
        $uri = self::URI . "/recordings/{$recordingId}";
        return $this->restClient->performDelete($uri);
    }

    /**
     *  Set the status of a given capture agent
     *
     * @param string $agentName Name of the capture agent
     * @param string $state The state of the capture agent. Known states are: idle, shutting_down, capturing, uploading, unknown, offline, error
     * @param string $address (optional) Address of the agent 
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] ({agentName} set to {state})
     */
    public function setAgentState($agentName, $state, $address = '')
    {
        $uri = self::URI . "/agents/{$agentName}";

        $formData = [
            'state' => $state
        ];
        if (!empty($address)) {
            $formData['address'] = $address;
        }

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Set the configuration of a given capture agent, registering it if it does not exist
     *
     * @param string $agentName Name of the capture agent
     * @param string $configuration An XML or JSON representation of the capabilities. XML as specified in http://java.sun.com/dtd/properties.dtd (friendly names as keys, device locations as corresponding values) 
     *
     * @return array the response result ['code' => 200, 'body' => '{ An XML or JSON representation of the agent configuration }']
     */
    public function setAgentStateConfiguration($agentName, $configuration)
    {
        $uri = self::URI . "/agents/{$agentName}/configuration";

        $formData = [
            'configuration' => $configuration
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }

    /**
     * Set the status of a given recording, registering it if it is new
     *
     * @param string $recordingId The ID of a given recording
     * @param string $state The state of the recording. Known states: unknown, capturing, capture_finished, capture_error, manifest, manifest_error, manifest_finished, compressing, compressing_error, uploading, upload_finished, upload_error. 
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] ({recordingId} set to {state})
     */
    public function setRecordingStatus($recordingId, $state)
    {
        $uri = self::URI . "/recordings/{$recordingId}";

        $formData = [
            'state' => $state
        ];

        $options = $this->restClient->getFormParams($formData);
        return $this->restClient->performPost($uri, $options);
    }
}
?>