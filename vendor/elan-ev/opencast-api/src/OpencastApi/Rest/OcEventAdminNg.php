<?php
namespace OpencastApi\Rest;

class OcEventAdminNg extends OcRest
{
    const URI = '/admin-ng/event';

    public function __construct($restClient)
    {
        $restClient->registerHeaderException('Accept', self::URI);
        parent::__construct($restClient);
    }

    /**
     * Delete a single event.
     *
     * @param string $eventId The id of the event to delete.
     *
     * @return array the response result ['code' => 200, 'reason' => 'OK'] (OK if the event has been deleted.)
     */
    public function delete($eventId)
    {
        $uri = self::URI . "/{$eventId}";
        return $this->restClient->performDelete($uri);
    }
}
?>