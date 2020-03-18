<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Api\V2010\Account\Message;

use Twilio\ListResource;
use Twilio\Options;
use Twilio\Serialize;
use Twilio\Stream;
use Twilio\Values;
use Twilio\Version;

class MediaList extends ListResource {
    /**
     * Construct the MediaList
     *
     * @param Version $version Version that contains the resource
     * @param string $accountSid The SID of the Account that created this resource
     * @param string $messageSid The unique string that identifies the resource
     */
    public function __construct(Version $version, string $accountSid, string $messageSid) {
        parent::__construct($version);

        // Path Solution
        $this->solution = ['accountSid' => $accountSid, 'messageSid' => $messageSid, ];

        $this->uri = '/Accounts/' . \rawurlencode($accountSid) . '/Messages/' . \rawurlencode($messageSid) . '/Media.json';
    }

    /**
     * Streams MediaInstance records from the API as a generator stream.
     * This operation lazily loads records as efficiently as possible until the
     * limit
     * is reached.
     * The results are returned as a generator, so this operation is memory
     * efficient.
     *
     * @param array|Options $options Optional Arguments
     * @param int $limit Upper limit for the number of records to return. stream()
     *                   guarantees to never return more than limit.  Default is no
     *                   limit
     * @param mixed $pageSize Number of records to fetch per request, when not set
     *                        will use the default value of 50 records.  If no
     *                        page_size is defined but a limit is defined, stream()
     *                        will attempt to read the limit with the most
     *                        efficient page size, i.e. min(limit, 1000)
     * @return Stream stream of results
     */
    public function stream(array $options = [], int $limit = null, $pageSize = null): Stream {
        $limits = $this->version->readLimits($limit, $pageSize);

        $page = $this->page($options, $limits['pageSize']);

        return $this->version->stream($page, $limits['limit'], $limits['pageLimit']);
    }

    /**
     * Reads MediaInstance records from the API as a list.
     * Unlike stream(), this operation is eager and will load `limit` records into
     * memory before returning.
     *
     * @param array|Options $options Optional Arguments
     * @param int $limit Upper limit for the number of records to return. read()
     *                   guarantees to never return more than limit.  Default is no
     *                   limit
     * @param mixed $pageSize Number of records to fetch per request, when not set
     *                        will use the default value of 50 records.  If no
     *                        page_size is defined but a limit is defined, read()
     *                        will attempt to read the limit with the most
     *                        efficient page size, i.e. min(limit, 1000)
     * @return MediaInstance[] Array of results
     */
    public function read(array $options = [], int $limit = null, $pageSize = null): array {
        return \iterator_to_array($this->stream($options, $limit, $pageSize), false);
    }

    /**
     * Retrieve a single page of MediaInstance records from the API.
     * Request is executed immediately
     *
     * @param array|Options $options Optional Arguments
     * @param mixed $pageSize Number of records to return, defaults to 50
     * @param string $pageToken PageToken provided by the API
     * @param mixed $pageNumber Page Number, this value is simply for client state
     * @return MediaPage Page of MediaInstance
     */
    public function page(array $options = [], $pageSize = Values::NONE, string $pageToken = Values::NONE, $pageNumber = Values::NONE): MediaPage {
        $options = new Values($options);
        $params = Values::of([
            'DateCreated<' => Serialize::iso8601DateTime($options['dateCreatedBefore']),
            'DateCreated' => Serialize::iso8601DateTime($options['dateCreated']),
            'DateCreated>' => Serialize::iso8601DateTime($options['dateCreatedAfter']),
            'PageToken' => $pageToken,
            'Page' => $pageNumber,
            'PageSize' => $pageSize,
        ]);

        $response = $this->version->page(
            'GET',
            $this->uri,
            $params
        );

        return new MediaPage($this->version, $response, $this->solution);
    }

    /**
     * Retrieve a specific page of MediaInstance records from the API.
     * Request is executed immediately
     *
     * @param string $targetUrl API-generated URL for the requested results page
     * @return MediaPage Page of MediaInstance
     */
    public function getPage(string $targetUrl): MediaPage {
        $response = $this->version->getDomain()->getClient()->request(
            'GET',
            $targetUrl
        );

        return new MediaPage($this->version, $response, $this->solution);
    }

    /**
     * Constructs a MediaContext
     *
     * @param string $sid The unique string that identifies this resource
     */
    public function getContext(string $sid): MediaContext {
        return new MediaContext(
            $this->version,
            $this->solution['accountSid'],
            $this->solution['messageSid'],
            $sid
        );
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string {
        return '[Twilio.Api.V2010.MediaList]';
    }
}