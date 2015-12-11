<?php
/*
    Filename: dataMigrate.php
    Description: Classes to migrate users between groups and the required surrounding config
    Usage:   
           $migrator = new Migrator(OCIP_USER, OCIP_PASS, OCIP_HOST);

           # Configuration
           $migrator->setOrigEnterprise('10001-EnterpriseOld');
           $migrator->setOrigGroup('10001-GroupOld');
           $migrator->setDestEnterprise('10002-EnterpriseNew');
           $migrator->setDestGroup('10002-GroupNew');

           $enterprise  = new Enterprise($migrator);
           $group       = new Group($migrator);
           $servicePack = new ServicePack($migrator);

           # Fetch domains and add to new ent/group
           $domains = $group->GroupGetDomain();
           $enterprise->ServiceProviderDomainAdd($domains);
           $group->GroupDomainAssign($domains);

           # As it says on the tin
           $servicePack->GroupAuthoriseServicePack();

           # Each user in the group
           foreach ($migrator->getUsersInGroup() as $user) {
               moveuser($user, $group, $enterprise, $migrator);
           }

           # Or a specific user
           moveuser("userone@one.com", $group, $enterprise, $migrator);

*/
require_once 'config.php';
require_once OCIP_BASEPATH . 'core/OCIClient.php';

ini_set("max_execution_time", 0);
ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL);
Factory::getOCISchemaSearchCriteria();
Factory::getOCISchemaDataTypes();
Factory::getOCISchemaGroup();
Factory::getOCISchemaServiceProvider();
Factory::getOCISchemaServiceCallForwardingAlways();
Factory::getOCISchemaServiceAlternateNumbers();
Factory::getOCISchemaServiceCallForwardingBusy();
Factory::getOCISchemaServiceCallForwardingNoAnswer();
Factory::getOCISchemaServiceCallForwardingNotReachable();
Factory::getOCISchemaServiceCallForwardingSelective();
Factory::getOCISchemaServiceDoNotDisturb();
Factory::getOCISchemaServiceVoiceMessaging();
Factory::getOCISchemaServiceCallForwardingAlways();
Factory::getOCISchemaServiceCallWaiting();
Factory::getOCISchemaServiceCallForwardingSelective();
Factory::getOCISchemaServiceCallingLineIDDeliveryBlocking();
Factory::getOCISchemaServiceHotelingGuest();

class Migrator extends OCIClient {

    public $orig_ent;
    public $orig_group;
    public $dest_ent;
    public $dest_group;
    public $logfile;

    public function __construct($user = null, $pass = null, $host = null) {
        parent::__construct($host, true);
        if (!$this->login($user, $pass)) die($this->errorControl->getErrors());
        $this->setTimeout(10);
        $this->logfile = fopen("migrate.log", 'a');
    }

    public function setOrigEnterprise($orig_ent) {
        $enterprise = new Enterprise($this);
        $this->orig_ent = $orig_ent;
    }

    public function setDestEnterprise($dest_ent) {
        $enterprise = new Enterprise($this);
        if ($enterprise->getServiceProvider($dest_ent)) {
            $this->dest_ent = $dest_ent;
        } else {
            die("Destination Enterprise not found : $dest_ent");
        }
    }

    public function getDestEnterprise() {
        return $this->dest_ent;
    }

    public function getDestGroup() {
        return $this->dest_group;
    }

    public function setDestGroup($dest_group) {
        $group = new Group($this);
        if ($group->getGroup($dest_group)) {
            $this->dest_group = $dest_group;
        } else {
            die("Destination Group not found : $dest_group");
        }
    }

    public function getUsersInGroup() {
        $users = [];
        $this->send(OCISchemaUser::UserGetListInGroupRequest($this->getOrigEnterprise(), $this->getOrigGroup()));
        if ($response = $this->getResponse()) {
            if (!array_key_exists('row', $response->userTable)) return $users;
            foreach ($response->userTable['row'] as $item) {
                $users[] = new User($item['col'][0], $this);
            }
        }
        return $users;
    }

    public function getOrigEnterprise() {
        return $this->orig_ent;
    }

    public function getOrigGroup() {
        return $this->orig_group;
    }

    public function setOrigGroup($orig_group) {
        $this->orig_group = $orig_group;
    }

    public function __destruct() {
        fclose($this->logfile);
    }
}

class User {

    public $userId = null;
    public $userDetail = null;
    public $migrator = null;
    public $dn = null;
    Public $servicePack = null;

    public function __construct($userId, $migrator) {
        if (is_object($migrator)) {
            $this->migrator = $migrator;
        } else {
            throw new LogicException("Migrator object invalid");
        }
        $this->userId = $userId;
        $this->migrator = $migrator;
        $this->config = new stdClass();
        $this->queryConfig(OCISchemaUser::UserGetRequest17sp4($this->userId));
        $this->queryConfig(OCISchemaServiceCallForwardingAlways::UserCallForwardingAlwaysGetRequest($this->userId));
        $this->queryConfig(OCISchemaServiceCallForwardingBusy::UserCallForwardingBusyGetRequest($this->userId));
        $this->queryConfig(OCISchemaServiceCallForwardingNoAnswer::UserCallForwardingNoAnswerGetRequest13mp16($this->userId));
         //       $this->queryConfig(OCISchemaServiceCallForwardingNotReachable::UserCallForwardingNotReachableGetRequest($this->userId));
        $this->queryConfig(OCISchemaUser::UserServiceGetAssignmentListRequest($this->userId));
        $this->queryConfig(OCISchemaServiceVoiceMessaging::UserVoiceMessagingUserGetVoiceManagementRequest17($this->userId));
        $this->queryConfig(OCISchemaServiceVoiceMessaging::UserVoiceMessagingUserGetAdvancedVoiceManagementRequest14sp3($this->userId));
        $this->queryConfig(OCISchemaServiceCallingLineIDDeliveryBlocking::UserCallingLineIDDeliveryBlockingGetRequest($this->userId));
        $this->queryConfig(OCISchemaServiceCallWaiting::UserCallWaitingGetRequest17sp4($this->userId));
        //$this->queryConfig(OCISchemaServiceCallForwardingSelective::UserCallForwardingSelectiveGetRequest16($this->userId));

    }

    public function queryConfig($cmd) {
        $this->migrator->send($cmd);
        if ($this->migrator->getResponse()) {
            $this->config->{$this->migrator->getResponse()->{'@attributes'}['xsi:type']} = $this->migrator->getResponse();
        }
    }

    public function deleteUser() {
        $this->migrator->send(OCISchemaUser::UserDeleteRequest($this->userId));
        return ($response = $this->migrator->getResponse());
    }

    public function createUser() {
        $password = uniqid();
        $request = $this->migrator->ociBuilder->map(
            [OCIDataTypes::OCI_PARAMS => $this->config->UserGetResponse17sp4],
            OCISchemaUser::UserAddRequest17sp4(null, null, $this->userId)
        );
        $request[OCIDataTypes::OCI_PARAMS]['serviceProviderId'] = $this->migrator->getDestEnterprise();
        $request[OCIDataTypes::OCI_PARAMS]['groupId'] = $this->migrator->getDestGroup();
        $request[OCIDataTypes::OCI_PARAMS]['password'] = $password;
        $this->migrator->send($request);
        if ($this->migrator->getResponse()) return $password;
    }

    public function getE164() {
        if ($this->config->UserGetResponse17sp4->phoneNumber) {
            return Numbering::dnE164(
                $this->config->UserGetResponse17sp4->phoneNumber,
                $this->config->UserGetResponse17sp4->countryCode,
                $this->config->UserGetResponse17sp4->nationalPrefix);
        }
    }

    public function removeDevice() {
        $request = OCISchemaUser::UserModifyRequest17sp4($this->userId);
        $request[OCIDataTypes::OCI_PARAMS]['endpoint'] = OCIDataTypes::XSI_NIL;
        $this->migrator->send($request);
        return $this->migrator->getResponse();
    }

    public function addDevice($device) {
        $request = OCISchemaUser::UserModifyRequest17sp4($this->userId);
        $request[OCIDataTypes::OCI_PARAMS]['endpoint']['accessDeviceEndpoint'] = $this->getConfig()->UserGetResponse17sp4->accessDeviceEndpoint;
        $request[OCIDataTypes::OCI_PARAMS]['endpoint']['accessDeviceEndpoint']['accessDevice']['deviceName'] = $device->getDevice()->macAddress;
        unset($request[OCIDataTypes::OCI_PARAMS]['endpoint']['accessDeviceEndpoint']['staticRegistrationCapable']);
        unset($request[OCIDataTypes::OCI_PARAMS]['endpoint']['accessDeviceEndpoint']['useDomain']);
        $this->migrator->send($request);
        return $this->migrator->getResponse();
    }

    public function getConfig() {
        return $this->config;
    }

    public function getDeviceName() {
        if (property_exists($this->getConfig()->UserGetResponse17sp4, 'accessDeviceEndpoint')) {
            return $this->getConfig()->UserGetResponse17sp4->accessDeviceEndpoint['accessDevice']['deviceName'];
        }
    }

    public function assignUserVoiceMail() {
        $this->migrator->send(OCISchemaUser::UserServiceAssignListRequest($this->userId, null, "Voicemail"));
        return $this->migrator->getResponse();
    }

    public function configureUserVoiceMail() {
        $request = $this->migrator->ociBuilder->map(
            [OCIDataTypes::OCI_PARAMS => $this->config->UserVoiceMessagingUserGetVoiceManagementResponse17],
            OCISchemaServiceVoiceMessaging::UserVoiceMessagingUserModifyVoiceManagementRequest($this->userId)
        );
        $this->migrator->send($request);

        $request = $this->migrator->ociBuilder->map(
            [OCIDataTypes::OCI_PARAMS => $this->config->UserVoiceMessagingUserGetAdvancedVoiceManagementResponse14sp3],
            OCISchemaServiceVoiceMessaging::UserVoiceMessagingUserModifyAdvancedVoiceManagementRequest($this->userId)
        );
        $this->migrator->send($request);
    }

    public function incomingCalls() {
        $request = $this->migrator->ociBuilder->map(
            [OCIDataTypes::OCI_PARAMS => $this->config->UserCallForwardingAlwaysGetResponse],
            OCISchemaServiceCallForwardingAlways::UserCallForwardingAlwaysModifyRequest($this->userId)
        );
        $this->migrator->send($request);

        $request = $this->migrator->ociBuilder->map(
            [OCIDataTypes::OCI_PARAMS => $this->config->UserCallForwardingBusyGetResponse],
            OCISchemaServiceCallForwardingBusy::UserCallForwardingBusyModifyRequest($this->userId)
        );
        $this->migrator->send($request);

        $request = $this->migrator->ociBuilder->map(
            [OCIDataTypes::OCI_PARAMS => $this->config->UserCallForwardingNoAnswerGetResponse13mp16],
            OCISchemaServiceCallForwardingNoAnswer::UserCallForwardingNoAnswerModifyRequest($this->userId)
        );
        $this->migrator->send($request);
    }

    public function outgoingCalls() {
        $request = $this->migrator->ociBuilder->map(
            [OCIDataTypes::OCI_PARAMS => $this->config->UserCallingLineIDDeliveryBlockingGetResponse],
            OCISchemaServiceCallingLineIDDeliveryBlocking::UserCallingLineIDDeliveryBlockingModifyRequest($this->userId)
        );
        $this->migrator->send($request);
    }

    public function callControl() {
        $request = $this->migrator->ociBuilder->map(
            [OCIDataTypes::OCI_PARAMS => $this->config->UserCallWaitingGetResponse17sp4],
            OCISchemaServiceCallWaiting::UserCallWaitingModifyRequest($this->userId)
        );
        $this->migrator->send($request);
        return $this->migrator->getResponse();
    }

    public function hoteling() {
        $this->migrator->send(OCISchemaServiceHotelingGuest::UserHotelingGuestModifyRequest($this->userId, "true"));
        return $this->migrator->getResponse();
     }
}

class Enterprise {

    public function __construct($migrator) {
        if (is_object($migrator)) {
            $this->migrator = $migrator;
        } else {
            throw new LogicException("Migrator object invalid");
        }
    }

    public function getServiceProvider($serviceProvider) {
        $this->migrator->send(OCISchemaServiceProvider::ServiceProviderGetRequest17sp1($serviceProvider));
        return $this->migrator->getResponse();
    }

    public function ServiceProviderDomainAdd($domains) {
        foreach ($domains->domain as $key => $value) {
            $this->migrator->send(OCISchemaServiceProvider::ServiceProviderDomainAssignListRequest(
                $this->migrator->getDestEnterprise(), $value
            ));
            $this->migrator->getResponse();
        }
    }

    public function ServiceProviderDnDelete($phoneNumber = null, $dnRange = null) {
        $base_request = OCISchemaServiceProvider::ServiceProviderDnDeleteListRequest(
            $this->migrator->getOrigEnterprise(), $phoneNumber, $dnRange
        );
        $this->migrator->send($base_request);
        return $this->migrator->getResponse();
    }

    public function ServiceProviderDnAdd($phoneNumber = null, $dnRange = null) {
        $base_request = OCISchemaServiceProvider::ServiceProviderDnAddListRequest(
            $this->migrator->getDestEnterprise(), $phoneNumber, $dnRange
        );
        $this->migrator->send($base_request);
        return $this->migrator->getResponse();
    }
}

class Group {

    public function __construct($migrator) {
        if (is_object($migrator)) {
            $this->migrator = $migrator;
        } else {
            throw new LogicException("Migrator object invalid");
        }
    }

    public function getGroup($group) {
        $this->migrator->send(OCISchemaGroup::GroupGetRequest14sp7($this->migrator->getDestEnterprise(), $group));
        return $this->migrator->getResponse();
    }

    public function GroupDnUnassign($phoneNumber = null, $dnRange = null) {
        $base_request = OCISchemaGroup::GroupDnUnassignListRequest(
            $this->migrator->getOrigEnterprise(), $this->migrator->getOrigGroup(), $phoneNumber, $dnRange
        );
        $this->migrator->send($base_request);
        return $this->migrator->getResponse();
    }

    public function GroupDnAssign($phoneNumber = null, $dnRange = null) {
        $base_request = OCISchemaGroup::GroupDnAssignListRequest(
            $this->migrator->getDestEnterprise(), $this->migrator->getDestGroup(), $phoneNumber, $dnRange
        );
        $this->migrator->send($base_request);
        return $this->migrator->getResponse();
    }

    public function GroupGetDomain() {
        $base_request = OCISchemaGroup::GroupDomainGetAssignedListRequest(
            $this->migrator->getOrigEnterprise(), $this->migrator->getOrigGroup());
        $this->migrator->send($base_request);
        $this->domains = $this->migrator->getResponse();
        return $this->domains;
    }

    public function GroupDomainAssign($domains) {
        foreach ($domains->domain as $key => $value) {
            $this->migrator->send(OCISchemaGroup::GroupDomainAssignListRequest(
                $this->migrator->getDestEnterprise(), $this->migrator->getDestGroup(), $value
            ));
            $this->migrator->getResponse();
        }
    }
}

Class Numbering {
    public static function dnE164($phoneNumber, $countryCode, $nationalPrefix) {
        return '+' . $countryCode . '-' . substr($phoneNumber, strlen($nationalPrefix));
    }
}

class ServicePack {

    public function __construct($migrator) {
        if (is_object($migrator)) {
            $this->migrator = $migrator;
        } else {
            throw new LogicException("Migrator object invalid");
        }
    }

    public function GroupAuthoriseServicePack() {
        $this->checkServicePack();
        foreach ($this->origSPs as $servicePack) {
            fwrite($this->migrator->logfile, "Authorizing $servicePack to {$this->migrator->getDestGroup()}\n");
            $this->migrator->send(OCISchemaGroup::GroupServiceModifyAuthorizationListRequest(
                $this->migrator->getDestEnterprise(), $this->migrator->getDestGroup(), ['servicePackName' => $servicePack, 'authorizedQuantity' => ['unlimited' => 'true']]));
            $this->migrator->getResponse();
        }

        foreach ($this->origGSs as $groupService) {
            fwrite($this->migrator->logfile, "Authorizing $groupService to {$this->migrator->getDestGroup()}\n");
            $this->migrator->send(OCISchemaGroup::GroupServiceModifyAuthorizationListRequest(
                $this->migrator->getDestEnterprise(), $this->migrator->getDestGroup(), null, ['serviceName' => $groupService, 'authorizedQuantity' => ['unlimited' => 'true']]));
            $this->migrator->getResponse();
        }

        foreach ($this->origGSs as $groupService) {
            fwrite($this->migrator->logfile, "Assigning $groupService to {$this->migrator->getDestGroup()}\n");
            $this->migrator->send(OCISchemaGroup::GroupServiceAssignListRequest(
                $this->migrator->getDestEnterprise(), $this->migrator->getDestGroup(), $groupService));
            $this->migrator->getResponse();
        }
    }

    private function checkServicePack() {
        $this->migrator->send(OCISchemaServiceProvider::ServiceProviderServicePackGetListRequest($this->migrator->getOrigEnterprise()));
        $this->origSPs = $this->migrator->getResponse()->servicePackName;
        $this->migrator->send(OCISchemaGroup::GroupServiceGetAuthorizedListRequest($this->migrator->getOrigEnterprise(), $this->migrator->getOrigGroup()));
        $this->origGSs = $this->migrator->getResponse()->groupServiceName;
        $this->migrator->send(OCISchemaServiceProvider::ServiceProviderServicePackGetListRequest($this->migrator->getDestEnterprise()));
        $destSP = $this->migrator->getResponse()->servicePackName;
        $newArray = (array_diff($this->origSPs, $destSP));
        $missing = false;
        foreach ($newArray as $key => $value) {
            $missing = true;
            echo "Destination Enterprise missing ServicePack: $value \n";
        }
        if ($missing) die("Please correct service packs");
    }

    public function AssignServicePacks($user) {
        foreach ($user->getConfig()->UserServiceGetAssignmentListResponse->servicePacksAssignmentTable['row'] as $value) {
            if ($value['col'][1] == "true") {
                fwrite($this->migrator->logfile, "Assigning {$value['col'][0]} to $user->userId\n");
                $this->migrator->send(OCISchemaUser::UserServiceAssignListRequest($user->userId, null, $value['col'][0]));
                $this->migrator->getResponse();
            }
        }
    }
}

class Device {

    var $device = null;

    public function __construct($deviceName, $migrator) {
        if (is_object($migrator)) {
            $this->migrator = $migrator;
            $this->deviceName = $deviceName;
            $this->device = $this->getDevice($deviceName);
        } else {
            throw new LogicException("Migrator object invalid");
        }
    }

    public function getDevice($deviceName = null) {
        if ($this->device) return $this->device;
        $request = OCISchemaGroup::GroupAccessDeviceGetRequest16(
            $this->migrator->getOrigEnterprise(),
            $this->migrator->getOrigGroup(),
            $deviceName
        );
        $this->migrator->send($request);
        return $this->migrator->getResponse();
    }

    public function createDevice() {
        $request = OCISchemaGroup::GroupAccessDeviceAddRequest14(
            $this->migrator->getDestEnterprise(),
            $this->migrator->getDestGroup(),
            $this->device->macAddress,
            $this->device->deviceType,
            $this->device->protocol
        );
        $request[OCIDataTypes::OCI_PARAMS]['macAddress'] = $this->device->macAddress;
        $this->migrator->send($request);
        return $this->migrator->getResponse();
    }

    public function deleteDevice() {
        $request = OCISchemaGroup::GroupAccessDeviceDeleteRequest(
            $this->migrator->getOrigEnterprise(), $this->migrator->getOrigGroup(), $this->deviceName);
        $this->migrator->send($request);
        return $this->migrator->getResponse();
    }
}


function moveuser($user, $group, $enterprise, $migrator) {
    // User
    $user->deleteUser();

    // DN
    $group->GroupDnUnassign($user->getE164());
    $enterprise->ServiceProviderDnDelete($user->getE164());
    $enterprise->ServiceProviderDnAdd($user->getE164());
    $group->GroupDnAssign($user->getE164());
    $user->createUser();

    // Device
    $device = new Device($user->getDeviceName(), $migrator);
    $user->removeDevice();
    $device->deleteDevice();
    $device->createDevice();
    $user->addDevice($device);
    $servicePack = new ServicePack($migrator);
    $servicePack->AssignServicePacks($user);
}
