<?php

namespace AppBundle\Service;

class AeosService
{
    private $configuration;

    // Debugging.
    private $lastRequest;
    private $lastResponse;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    private function invoke($method)
    {
        $configuration = $this->configuration['client'];
        $options = isset($configuration['context']) ? $configuration['context'] : [];
        $context = stream_context_create($options);

        $location = $configuration['location'];
        $username = $configuration['username'];
        $password = $configuration['password'];
        $debug = isset($configuration['debug']) ? $configuration['debug'] : false;

        $client = new \SoapClient($location . '?wsdl', [
            'location' => $location,
            'login' => $username,
            'password' => $password,
            'stream_context' => $context,
            'trace' => $debug,
        ]);

        $result = null;
        if (func_num_args() > 1) {
            $arguments = array_slice(func_get_args(), 1);
            $result = call_user_func_array([$client, $method], $arguments);
        } else {
            $result = call_user_func([$client, $method]);
        }

        if ($debug) {
            $this->lastRequest = $client->__getLastRequest();
            $this->lastResponse = $client->__getLastResponse();
        }

        return $result;
    }

    const ACTIVATE_VERIFICATION = 'ActivateVerification';

    public function setVerificationState($carrier, bool $activated)
    {
        return $this->setCarrierState($carrier, self::ACTIVATE_VERIFICATION, $activated);
    }

    public function setCarrierState($carrier, string $state, bool $activated)
    {
        return $this->invoke('changeCarrierAttribute', (object)[
            'CarrierId' => $carrier->Id,
            'State' => $state,
            'Activated' => $activated,
        ]);
    }

    public function getCarrierStates($carrier)
    {
        return $this->invoke('findCarrierStates', $carrier->Id);
    }

    public function getIdentifiers(array $query = [])
    {
        list($query, $searchRange) = $this->splitQuery($query);
        // findToken requires at least one search value.
        $query += [
            'IdentifierType' => $this->configuration['aeos']['identifier_type'],
        ];

        $result = $this->invoke('findToken', (object)['IdentifierSearch' => $query, 'SearchRange' => $searchRange]);

        return !isset($result->IdentifierAndCarrierId) ? null : (is_array($result->IdentifierAndCarrierId) ? $result->IdentifierAndCarrierId : [$result->IdentifierAndCarrierId]);
    }

    public function getIdentifierByBadgeNumber($badgeNumber)
    {
        $result = $this->getIdentifiers(['BadgeNumber' => $badgeNumber]);

        return ($result && count($result) === 1) ? $result[0] : null;
    }

    public function deleteIdentifier($identifier)
    {
        $reason = $this->configuration['aeos']['block_reason'];
        $result = $this->invoke('blockToken', (object)[
            'IdentifierType' => $identifier->Identifier->IdentifierType,
            'BadgeNumber' => $identifier->Identifier->BadgeNumber,
            'Reason' => $reason,
        ]);

        return $result;
    }

    public function isDeleted($identifier)
    {
        return $identifier && isset($identifier->Identifier->Blocked) && $identifier->Identifier->Blocked === true;
    }

    public function getVisitors(array $query = [])
    {
        list($query, $searchRange) = $this->splitQuery($query);
        $result = $this->invoke('findVisitor', (object)['VisitorInfo' => $query, 'SearchRange' => $searchRange]);

        if (!isset($result->Visitor)) {
            return null;
        }

        return array_map(function ($item) {
            return $item->VisitorInfo;
        }, is_array($result->Visitor) ? $result->Visitor : [$result->Visitor]);
    }

    public function getVisitor($id)
    {
        $result = $this->getVisitors(['Id' => $id]);

        return ($result && count($result) === 1) ? $result[0] : null;
    }

    public function getVisitorByIdentifier($identifier)
    {
        return isset($identifier->CarrierId) ? $this->getVisitor($identifier->CarrierId) : null;
    }

    public function deleteVisitor($visitor)
    {
        $result = $this->invoke('removeVisitor', $visitor->Id);

        return $result;
    }

    public function getVisits(array $query = [])
    {
        list($query, $searchRange) = $this->splitQuery($query);
        $query['SearchRange'] = $searchRange;
        $result = $this->invoke('findVisit', (object)$query);

        return !isset($result->Visit) ? null : (is_array($result->Visit) ? $result->Visit : [$result->Visit]);
    }

    public function getVisit($id)
    {
        $result = $this->getVisits(['Id' => $id]);

        return ($result && count($result) === 1) ? $result[0] : null;
    }

    public function getVisitByVisitor($visitor)
    {
        $result = $this->getVisits(['VisitorId' => $visitor->Id]);

        return ($result && count($result) === 1) ? $result[0] : null;
    }

    public function deleteVisit($visit)
    {
        return $this->invoke('removeVisit', $visit->Id);
    }

    public function createVisitor(array $data)
    {
        return (object)$this->invoke('addVisitor', $data);
    }

    public function createIdentifier($visitor, $contactPerson)
    {
        $code = $this->generateCode();
        $data = [
            'IdentifierType' => $this->configuration['aeos']['identifier_type'],
            'BadgeNumber' => $code,
            'UnitId' => $contactPerson->UnitId,
            'CarrierId' => $visitor->Id,
        ];

        return (object)$this->invoke('assignToken', $data);
    }

    private function generateCode()
    {
        $codeLength = 8;
        $code = random_int(1, 9) . str_pad(random_int(1, pow(10, $codeLength - 1) - 1), $codeLength - 1, '0', STR_PAD_LEFT);

        return $code;
    }

    public function updateVisitor($id, array $data)
    {
        $data['Id'] = $id;

        return $this->invoke('changeVisitor', $data);
    }

    public function createVisit($visitor, $contactPerson, \DateTime $beginVisit, \DateTime $endVisit, $template)
    {
        $startTime = $this->formatDateTime($beginVisit);
        $endTime = $this->formatDateTime($endVisit);
        $data = [
            'VisitorId' => $visitor->Id,
            'ContactPersonId' => $contactPerson->Id,
            'beginVisit' => $startTime,
            'endVisit' => $endTime,
            'Authorization' => [
                'TemplateAuthorisation' => [
                    'Enabled' => true,
                    'TemplateId' => $template->Id,
                    'DateFrom' => $startTime,
                    'DateUntil' => $endTime,
                ],
            ],
        ];

        return $this->invoke('addVisit', (object)$data);
    }

    /**
     * Format date and time for AEOS service.
     *
     * @param \DateTime $date
     * @return string
     */
    private function formatDateTime(\DateTime $date)
    {
        $dateFormat = 'Y-m-d\TH:i:s';
        $date = clone $date;
        $timeZone = new \DateTimeZone($this->configuration['aeos']['timezone']);
        $date->setTimezone($timeZone);

        return $date->format($dateFormat);
    }

    public function getUnits(array $query = [])
    {
        list($query, $searchRange) = $this->splitQuery($query);
        $result = $this->invoke('findUnit', (object)['UnitSearchInfo' => $query, 'SearchRange' => $searchRange]);

        return !isset($result->Unit) ? null : (is_array($result->Unit) ? $result->Unit : [$result->Unit]);
    }

    public function getPersons(array $query = [])
    {
        list($query, $searchRange) = $this->splitQuery($query);
        $result = $this->invoke('findPerson', (object)['PersonInfo' => $query, 'SearchRange' => $searchRange]);

        return !isset($result->Person) ? null : (is_array($result->Person) ? $result->Person : [$result->Person]);
    }

    public function getPerson($id)
    {
        $result = $this->getPersons(['Id' => $id]);

        return ($result && count($result) === 1) ? $result[0] : null;
    }

    public function getTemplates(array $query = [])
    {
        list($query, $searchRange) = $this->splitQuery($query);
        $query += [
            'UnitOfAuthType' => 'OnLine',
        ];
        $result = $this->invoke('findTemplate', (object)['TemplateInfo' => $query, 'SearchRange' => $searchRange]);

        return !isset($result->Template) ? null : (is_array($result->Template) ? $result->Template : [$result->Template]);
    }

    public function getTemplate($id)
    {
        $result = $this->getTemplates(['Id' => $id]);

        return ($result && count($result) === 1) ? $result[0] : null;
    }

    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Split a query into a query and a search range.
     */
    private function splitQuery(array $query)
    {
        $searchRange = [
            'nrOfRecords' => 25,
            'startRecordNo' => 0,
        ];

        if (isset($query['amount'])) {
            $searchRange['nrOfRecords'] = $query['amount'];
            unset($query['amount']);
        }
        if (isset($query['offset'])) {
            $searchRange['startRecordNo'] = $query['offset'];
            unset($query['offset']);
        }

        return [$query, $searchRange];
    }
}
