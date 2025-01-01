<?php

/**
 * This code was generated by
 * ___ _ _ _ _ _    _ ____    ____ ____ _    ____ ____ _  _ ____ ____ ____ ___ __   __
 *  |  | | | | |    | |  | __ |  | |__| | __ | __ |___ |\ | |___ |__/ |__|  | |  | |__/
 *  |  |_|_| | |___ | |__|    |__| |  | |    |__] |___ | \| |___ |  \ |  |  | |__| |  \
 *
 * Twilio - Numbers
 * This is the public Twilio REST API.
 *
 * NOTE: This class is auto generated by OpenAPI Generator.
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */


namespace Twilio\Rest\Numbers\V2\RegulatoryCompliance;

use Twilio\Exceptions\TwilioException;
use Twilio\Options;
use Twilio\Values;
use Twilio\Version;
use Twilio\InstanceContext;
use Twilio\Serialize;


class RegulationContext extends InstanceContext
    {
    /**
     * Initialize the RegulationContext
     *
     * @param Version $version Version that contains the resource
     * @param string $sid The unique string that identifies the Regulation resource.
     */
    public function __construct(
        Version $version,
        $sid
    ) {
        parent::__construct($version);

        // Path Solution
        $this->solution = [
        'sid' =>
            $sid,
        ];

        $this->uri = '/RegulatoryCompliance/Regulations/' . \rawurlencode($sid)
        .'';
    }

    /**
     * Fetch the RegulationInstance
     *
     * @param array|Options $options Optional Arguments
     * @return RegulationInstance Fetched RegulationInstance
     * @throws TwilioException When an HTTP error occurs.
     */
    public function fetch(array $options = []): RegulationInstance
    {

        $options = new Values($options);

        $params = Values::of([
            'IncludeConstraints' =>
                Serialize::booleanToString($options['includeConstraints']),
        ]);

        $headers = Values::of(['Content-Type' => 'application/x-www-form-urlencoded' ]);
        $payload = $this->version->fetch('GET', $this->uri, $params, [], $headers);

        return new RegulationInstance(
            $this->version,
            $payload,
            $this->solution['sid']
        );
    }


    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString(): string
    {
        $context = [];
        foreach ($this->solution as $key => $value) {
            $context[] = "$key=$value";
        }
        return '[Twilio.Numbers.V2.RegulationContext ' . \implode(' ', $context) . ']';
    }
}
