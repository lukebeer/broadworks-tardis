<?php
/**
 * Author: Luke B
 * Date: 14/06/13
 * Description: Migrate old-skool Polycom device to new-skool profile-server config ('DMS')
 */

require_once '../Broadworks_OCI-P/common.php';
ini_set("max_execution_time", 0);
ini_set("display_errors", 0);
ini_set("error_reporting", E_ALL);
Factory::getOCISchemaDataTypes();
Factory::getOCISchemaSearchCriteria();
Factory::getOCISchemaSystem();
Factory::getOCISchemaServiceProvider();
Factory::getOCISchemaGroup();
Factory::getOCISchemaServiceSharedCallAppearance();


class DeviceMigration {

    public $messages = [];
    public $device_map = [];
    private $debug = false;
    private $devices = [];
    private $flipped_map = false;

    // Set up device map and try init framework.
    public function __construct($user = null, $pass = null, $host = 'http://bsews1.ipt.intechnology.co.uk/webservice/services/ProvisioningService', $device_map = []) {
        $this->errorControl = CoreFactory::getErrorControl();
        $this->client = CoreFactory::getOCIClient($host, false);
        $this->client->setTimeout(5);
        if (!$this->client->login($user, $pass)) throw new Exception("Unable to login");
        if (empty($device_map)) {
            $this->device_map['Polycom Soundpoint IP 321'] = 'DMS-Polycom-321';
            $this->device_map['Polycom Soundpoint IP 331'] = 'DMS-Polycom-331';
            $this->device_map['Polycom Soundpoint IP 430'] = 'DMS-Polycom-430';
            $this->device_map['Polycom Soundpoint IP 450'] = 'DMS-Polycom-450';
            $this->device_map['Polycom Soundpoint IP 550'] = 'DMS-Polycom-550';
            $this->device_map['Polycom Soundpoint IP 650'] = 'DMS-Polycom-650';
            $this->device_map['Polycom Soundpoint IP 670'] = 'DMS-Polycom-670';
            $this->device_map['Polycom Soundpoint IP 5000'] = 'DMS-Polycom-5000';
            $this->device_map['Polycom Soundpoint IP 6000'] = 'DMS-Polycom-6000';
            $this->device_map['Polycom Soundpoint IP 7000'] = 'DMS-Polycom-7000';
            $this->device_map['Polycom SoundStation IP 5000'] = 'DMS-Polycom-5000';
            $this->device_map['Polycom SoundStation IP 5000'] = 'DMS-Polycom-5000';
        } else {
            $this->device_map = $device_map;
        }
    }

    // Flips the device map and re-adds the devices,
    // Useful for creating 'backups' of access device config in case of failed migration
    public function flipMap() {
        $this->device_map = array_flip($this->device_map);
        $this->flipped_map = ($this->flipped_map) ? false : true;
        if ($devices = $this->getDevices()) {
            $this->clearDevices();
            foreach ($devices as $device => $detail) {
                $this->addDevice($device);
            }
        }
        return $this->device_map;
    }

    // Add device into device array with current device configuration and build migration command
    public function addDevice($mac) {
        $mac = strtoupper($mac);
        if (!@array_key_exists($mac, $this->getDevices())) {
            if ($device = $this->getDevice($mac)) {
                $this->devices[$mac]['device'] = $device;
                $this->devices[$mac]['deviceDetail'] = $this->getDeviceDetail($device);
                $this->devices[$mac]['userList'] = $this->getDeviceUserList($device);
                $deviceUserTable = $this->devices[$mac]['userList']->deviceUserTable;
                if (array_key_exists('row', $deviceUserTable)) {
                    if (array_key_exists('col', $deviceUserTable['row'])) {
                        $this->devices[$mac]['users'][$deviceUserTable['row']['col'][4]] = $this->getUser($deviceUserTable['row']['col'][4]);
                    } else {
                        foreach ($deviceUserTable['row'] as $row) {
                            $this->devices[$mac]['users'][$row['col'][4]] = $this->getUser($row['col'][4]);
                        }
                    }
                }
    #            $this->devices[$mac]['migrationPath'] = ($this->flipped_map)
    #                ? $this->devices[$mac]['deviceDetail']->deviceType
    #                : @$this->device_map[$this->devices[$mac]['deviceDetail']->deviceType];
    #            if ($this->setMigration($mac)) {
                    return $this->devices[$mac];
    #            }
            }
        } else {
            $this->errorControl->addError("$mac ({$this->devices[$mac]['deviceDetail']->deviceType}): Duplicate mac address");
        }
        return false;
    }

    // Return devices
    public function getDevices() {
        if (!empty($this->devices)) {
            return $this->devices;
        } else {
            return false;
        }
    }

    public function device($mac) {
        $result = (array_key_exists($mac, $this->devices)) ? $this->devices[$mac] : false;
        return $result;
    }

    // Flush added devices. Useful for flipMap()
    public function clearDevices() {
        $this->devices = [];
    }

    // Get group device mac addresses
    public function addGroupDevices($ent, $grp) {
        $this->client->send(OCISchemaGroup::GroupAccessDeviceGetListRequest($ent, $grp));
        if (isset($this->client->getResponse()->accessDeviceTable['row'])) {
            foreach ($this->client->getResponse()->accessDeviceTable['row'] as $row) {
                if (!empty($row['col'][4])) {
                    $this->addDevice($row['col'][4]);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    // Get basic device info
    private function getDevice($mac) {
        $search = OCIBuilder::buildSearch(
            OCISchemaSearchCriteria::SearchCriteriaDeviceMACAddress(OCISearchModes::EQUAL, $mac, true));
        $this->client->send(OCISchemaSystem::SystemAccessDeviceGetAllRequest(1, null, $search));
        $response = $this->client->getResponse();
        if (isset($response->accessDeviceTable['row'])) {
            return $response;
        } else {
            $this->errorControl->addError("$mac: Device not found");
            return false;
        }
    }

    // Build our command to create the new device
    private function setMigration($mac) {
        if (!$this->validate($mac)) return false;
        $this->devices[$mac]['migrationCommand'] = OCISchemaGroup::GroupAccessDeviceAddRequest14(
            $this->devices[$mac]['device']->accessDeviceTable['row']['col'][0],
            $this->devices[$mac]['device']->accessDeviceTable['row']['col'][2],
            $this->devices[$mac]['deviceDetail']->macAddress,
            $this->devices[$mac]['migrationPath'],
            $this->devices[$mac]['deviceDetail']->protocol,
            null, null, null, null,
            $this->devices[$mac]['deviceDetail']->macAddress
        );
        return true;
    }


    // Get SCA
    private function getSharedCallAppearance($userId) {
        $this->client->send(OCISchemaServiceSharedCallAppearance::UserSharedCallAppearanceGetRequest16sp2($userId));
        return $this->client->getResponse();
    }

    // UserGetRequest17sp4
    private function getUser($userId) {
        $this->client->send(OCISchemaUser::UserGetRequest17sp4($userId));
        return $this->client->getResponse();
    }

    // Validate if there is a valid migration path
    private function validate($mac) {
        // Check if the device matches one of our keys in device_map
        if (array_key_exists($this->devices[$mac]['deviceDetail']->deviceType, $this->device_map)) {
            $this->messages[] = "$mac ({$this->devices[$mac]['deviceDetail']->deviceType}): Can migrate to a {$this->devices[$mac]['migrationPath']}";
            return true;
        }
        if (($this->flipped_map) && (in_array($this->devices[$mac]['deviceDetail']->deviceType, $this->device_map))) {
            $this->messages[] = "$mac ({$this->devices[$mac]['deviceDetail']->deviceType}): Backed up as {$this->devices[$mac]['migrationPath']}";
            return true;
        }
        if ((in_array($this->devices[$mac]['deviceDetail']->deviceType, $this->device_map)) && (!$this->flipped_map)) {
            $this->errorControl->addError(
                "$mac ({$this->devices[$mac]['deviceDetail']->deviceType}): Already matches target device type"
            );
        } else {
            $this->errorControl->addError(
                "$mac ({$this->devices[$mac]['deviceDetail']->deviceType}): No migration path found"
            );
        }
        unset($this->devices[$mac]);
        return false;
    }

    // Get users associated to device
    private function getDeviceUserList($deviceResponse) {
        if (array_key_exists('row', $deviceResponse->accessDeviceTable)) {
            $device = $deviceResponse->accessDeviceTable['row']['col'];
            $this->client->send(OCISchemaGroup::GroupAccessDeviceGetUserListRequest($device[0], $device[2], $device[3]));
            return $this->client->getResponse();
        }
        return null;
    }

    // Get device specific detail
    private function getDeviceDetail($deviceResponse) {
        if (array_key_exists('row', $deviceResponse->accessDeviceTable)) {
            $device = $deviceResponse->accessDeviceTable['row']['col'];
            $this->client->send(OCISchemaGroup::GroupAccessDeviceGetRequest16($device[0], $device[2], $device[3]));
            return $this->client->getResponse();
        }
        return null;
    }

    // Reboot device by registration
    private function rebootDevice($device) {
        $sp_id = $device['device']->accessDeviceTable['row']['col'][0];
        $group_id = $device['device']->accessDeviceTable['row']['col'][2];
        $device_name = ($this->flipped_map)
            ? $device['device']->accessDeviceTable['row']['col'][6]
            : $device['device']->accessDeviceTable['row']['col'][3];
        $request = OCISchemaGroup::GroupAccessDeviceResetRequest($sp_id, $group_id, $device_name);
        return ['schema' => 'OCISchemaGroup', 'command' => $request];
    }

    // Reboot device via UDP packet
    private function rebootDeviceUDP($ip = '127.0.0.1') {
        $message = "NOTIFY sip:test@test.com SIP/2.0\r\n";
        $message .= "To: sip:test@test.com\r\n";
        $message .= "From: sip:reboot@test.com\r\n";
        $message .= "CSeq: 10 NOTIFY\r\n";
        $message .= "Call-ID: 1234@test.com\r\n";
        $message .= "Event: check-sync;reboot=false\r\n";
        if ($socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) {
            socket_sendto($socket, $message, strlen($message), 0, $ip, '5060');
            $this->messages[] = "Reboot packet sent to $ip";
        } else {
            $this->errorControl->addError("Can't create socket for reboot on $ip");
        }
    }

    // Maps OCITable into an array of requests, example below. OCI-P cheat code.
    //  $device_parmas = $this->mapTable($device['device'], OCISchemaGroup::GroupAccessDeviceModifyUserRequest())[0];
    //  $user_command  = $this->mapTable($device['userList'], $device_parmas)[0];
    //  $output       .= $this->createConfigCSV(['schema' => 'OCISchemaGroup', 'command' => $user_command])."\r\n";
    private function mapTable($response, $request) {
        $response = (array) $response;
        $headings = $requests = [];
        foreach ($response as $k => $v) {
            if ((substr($k, -5) == 'Table') && (array_key_exists('row', $response[$k]))) {
                foreach ($response[$k]['colHeading'] as $heading) {
                    // line/port to linePort
                    if (preg_match('/\//', $heading)) {
                        $heading = str_replace('/', ' ', $heading);
                    }
                    // Primary Line Port to is Primary Line Port
                    if ($heading == 'Primary Line Port') {
                        $heading = "is $heading";
                    }
                    // 'First Name' becomes 'firstName' etc...
                    $camelCased = lcfirst(str_replace(' ', '', ucwords(strtolower($heading))));
                    $headings[$camelCased] = null;
                }
                foreach ($response[$k]['row'] as $item) {
                    $map['Params'] = array_combine(array_keys($headings), array_values($item));
                    $requests[] = $this->map($map, $request);
                }
            }
        }
        return $requests;
    }

    // Maps response into a new request
    private function map($response, $request) {
        $response = (array) $response;
        foreach ($response[OCIDataTypes::OCI_PARAMS] as $k => $v) {
            if (array_key_exists($k, $request[OCIDataTypes::OCI_PARAMS])) {
                $request[OCIDataTypes::OCI_PARAMS][$k] = $v;
            }
        }
        return $request;
    }

    // Creates CSV from request command array
    private function createConfigCSV($request = ['schema' => null, 'command' => null]) {
        $output = $request['schema'] . '::' . $request['command'][OCIDataTypes::OCI_NAME];
        foreach ($request['command'][OCIDataTypes::OCI_PARAMS] as $k => $v) {
            $output .= (empty($v)) ? ',' : ",$v";
        }
        return $output;
    }

    // Returns command to remove the device from the user profile
    private function removeDeviceFromUser($userId) {
        $request = OCISchemaUser::UserModifyRequest17sp4($userId,
            null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, 'xsi:nil');
        return ['schema' => 'OCISchemaUser', 'command' => $request];
    }

    // Returns command to add the device from the user profile
    private function addDeviceToUser($deviceName, $userId, $phoneNumber, $extension=null, $linePort) {
        $deviceName = OCIBuilder::buildSearch([
                OCIDataTypes::OCI_PARAMS => [
                    'deviceLevel' => 'Group',
                    'deviceName' => $deviceName
                ]
            ]
        );
        $accessDevice = OCIBuilder::buildSearch([
                OCIDataTypes::OCI_PARAMS => [
                    'accessDevice' => $deviceName,
                    'linePort' => $linePort
                ]
            ]
        );
        $accessDeviceEnpoint = OCIBuilder::buildSearch([
                OCIDataTypes::OCI_PARAMS => [
                    'accessDeviceEndpoint' => $accessDevice
                ]
            ]
        );
        $extension = (!empty($extension)) ? $extension : OCIDataTypes::XSI_NIL;
        $request = OCISchemaUser::UserModifyRequest17sp4(
            $userId, null, null, null, null, null, null, $phoneNumber, $extension,
            null, null, null, null, null, null, null, $accessDeviceEnpoint
        );
        return ['schema' => 'OCISchemaUser', 'command' => $request];
    }

    private function setPrimaryLine($device) {
        $request = OCISchemaGroup::GroupAccessDeviceModifyUserRequest(
            $device['migrationCommand'][OCIDataTypes::OCI_PARAMS]['serviceProviderId'],
            $device['migrationCommand'][OCIDataTypes::OCI_PARAMS]['groupId'],
            $device['migrationCommand'][OCIDataTypes::OCI_PARAMS]['deviceName'],
            $device['linePort'],
            'true'
        );
        return ['schema' => 'OCISchemaGroup', 'command' => $request];
    }

    // Returns command to add a group access device
    private function createGroupDevice($device = []) {
        $request = OCISchemaGroup::GroupAccessDeviceAddRequest14();
        $replaced = array_replace($request[OCIDataTypes::OCI_PARAMS], $device['migrationCommand']);
        return ['schema' => 'OCISchemaGroup', 'command' => $replaced];
    }

    // Returns command to delete a group access device
    private function deleteGroupDevice($device = []) {
        $sp_id = $device['device']->accessDeviceTable['row']['col'][0];
        $group_id = $device['device']->accessDeviceTable['row']['col'][2];
        $device_name = ($this->flipped_map)
            ? $device['device']->accessDeviceTable['row']['col'][6]
            : $device['device']->accessDeviceTable['row']['col'][3];
        $request = OCISchemaGroup::GroupAccessDeviceDeleteRequest($sp_id, $group_id, $device_name);
        return ['schema' => 'OCISchemaGroup', 'command' => $request];
    }

    // Backs up original device config, better to just call flipMap() and generate the CSV.
    public function backupDevice($device = []) {
        $request = $this->map($device['deviceDetail'], OCISchemaGroup::GroupAccessDeviceAddRequest14());
        $table = $this->mapTable($device['device'], $request)[0][OCIDataTypes::OCI_PARAMS];
        $command = $this->map($table, $request);
        return ['schema' => 'OCISchemaGroup', 'command' => $command];
    }

    public function createRegistrationURI($userDetail, $device) {
        $number = (!empty($userDetail->phoneNumber)) ? $userDetail->phoneNumber : $userDetail->extension;
        $formatted = (substr($number, 0, 1) == $userDetail->nationalPrefix)
            ? $userDetail->countryCode.substr($number, strlen($userDetail->nationalPrefix))
            : $number;
        $unique = substr(uniqid(), -4);
        $this->client->send(OCISchemaGroup::GroupGetRequest14sp7(
            $device['device']->accessDeviceTable['row']['col'][0],
            $device['device']->accessDeviceTable['row']['col'][2]
        ));
        $response = $this->client->getResponse();
        return "$formatted-$unique@{$response->defaultDomain}";
    }

    // Create the migration CSV files
    public function createMigrationCSV() {
        $output = '';
        if (!$this->getDevices()) {
            $this->errorControl->addError('createMigrationCSV(): No devices to process');
            return false;
        }
        foreach ($this->getDevices() as $device) {
            $output .= "#  {$device['deviceDetail']->macAddress}\n";
            $output .= $this->createConfigCSV($this->rebootDevice($device)) . "\n";
            $assoc_to_user = false;
            if (!empty($device['users'])) {
                foreach ($device['users'] as $userId => $userDetail) {
                    $output .= $this->createConfigCSV($this->removeDeviceFromUser($userId)) . "\n";
                    $assoc_to_user = true;
                }
            }
            $output .= $this->createConfigCSV($this->deleteGroupDevice($device)) . "\n";
            $output .= $this->createConfigCSV($this->createGroupDevice($device)) . "\n";
            if ($assoc_to_user) {
                foreach ($device['users'] as $userId => $userDetail) {
                    $device['linePort'] = $this->createRegistrationURI($userDetail, $device);
                    $output .= $this->createConfigCSV(
                            $this->addDeviceToUser(
                                $device['deviceDetail']->macAddress,
                                $userId,
                                $userDetail->phoneNumber,
                                $userDetail->extension,
                                $device['linePort']
                            )
                        ) . "\n";
                }
                if (count($device['userList']->deviceUserTable['row']) == 1) {
                    $output .= $this->createConfigCSV($this->setPrimaryLine($device)) . "\n";
                };
            }
        }
        return $output;
    }
}
