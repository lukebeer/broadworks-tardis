<?php

class ServiceMeta
{

    public $serviceName;
    public $required;
    public $commands;

    public function __construct($serviceName, $className, $requires, $commands)
    {
        $this->serviceName = $serviceName;
        $this->requires = $requires;
        $this->commands = $commands;
    }

    public function getServiceName()
    {
        return $this->serviceName;
    }

    public function getCommands()
    {
        return $this->commands;
    }

}

$serviceMap = [
    'Alternate Numbers' => new ServiceMeta(
        'Alternate Numbers',
        'OCISchemaServiceAlternateNumbers',
        'Services/OCISchemaServiceAlternateNumbers.php',
        [
            'UserAlternateNumbersGetRequest17'
        ]
    ),
    'Anonymous Call Rejection' => new ServiceMeta(
        'Anonymous Call Rejection',
        'OCISchemaServiceAnonymousCallRejection',
        'Services/OCISchemaServiceAnonymousCallRejection.php',
        [
            'UserAnonymousCallRejectionGetRequest'
        ]
    ),
    'Attendant Console' => new ServiceMeta(
        'Attendant Console',
        'OCISchemaServiceAttendantConsole',
        'Services/AttendantConsole.php',
        [
            'UserAttendantConsoleGetAvailableUserListRequest',
            'UserAttendantConsoleGetRequest14sp2'
        ]
    ),
    'Authentication' => new ServiceMeta('Authentication', 'OCISchemaServiceAuthentication', 'Services/OCISchemaServiceAuthentication.php',
        ['UserAuthenticationGetRequest']),
    'Automatic Callback' => new ServiceMeta('Automatic Callback', 'OCISchemaServiceAutomaticCallback', 'Services/OCISchemaServiceAutomaticCallback.php',
        ['UserAutomaticCallbackGetRequest']),
    'Automatic Hold/Retrieve' => new ServiceMeta('Automatic Hold/Retrieve', '', 'Services/OCISchemaService', []),
    'Barge-in Exempt' => new ServiceMeta('Barge-in Exempt', '', 'Services/OCISchemaService', []),
    'Basic Call Logs' => new ServiceMeta('Basic Call Logs', '', 'Services/OCISchemaService', []),
    'BroadWorks Agent' => new ServiceMeta('BroadWorks Agent', '', 'Services/OCISchemaService', []),
    'BroadWorks Supervisor' => new ServiceMeta('BroadWorks Supervisor', '', 'Services/OCISchemaService', []),
    'Busy Lamp Field' => new ServiceMeta('Busy Lamp Field', '', 'Services/OCISchemaService', []),
    'Call Center - Standard' => new ServiceMeta('Call Center - Standard', '', 'Services/OCISchemaService', []),
    'Call Forwarding Always' => new ServiceMeta('Call Forwarding Always', '', 'Services/OCISchemaService', []),
    'Call Forwarding Busy' => new ServiceMeta('Call Forwarding Busy', '', 'Services/OCISchemaService', []),
    'Call Forwarding No Answer' => new ServiceMeta('Call Forwarding No Answer', '', 'Services/OCISchemaService', []),
    'Call Forwarding Not Reachable' => new ServiceMeta('Call Forwarding Not Reachable', '', 'Services/OCISchemaService', []),
    'Call Forwarding Selective' => new ServiceMeta('Call Forwarding Selective', '', 'Services/OCISchemaService', []),
    'Call Notify' => new ServiceMeta('Call Notify', '', 'Services/OCISchemaService', []),
    'Call Return' => new ServiceMeta('Call Return', '', 'Services/OCISchemaService', []),
    'Call Transfer' => new ServiceMeta('Call Transfer', '', 'Services/OCISchemaService', []),
    'Call Waiting' => new ServiceMeta('Call Waiting', '', 'Services/OCISchemaService', []),
    'Calling Line ID Blocking Override' => new ServiceMeta('Calling Line ID Blocking Override', '', 'Services/OCISchemaService', []),
    'Calling Line ID Delivery Blocking' => new ServiceMeta('Calling Line ID Delivery Blocking', '', 'Services/OCISchemaService', []),
    'Calling Name Delivery' => new ServiceMeta('Calling Name Delivery', '', 'Services/OCISchemaService', []),
    'Calling Name Retrieval' => new ServiceMeta('Calling Name Retrieval', '', 'Services/OCISchemaService', []),
    'Calling Number Delivery' => new ServiceMeta('Calling Number Delivery', '', 'Services/OCISchemaService', []),
    'Calling Party Category' => new ServiceMeta('Calling Party Category', '', 'Services/OCISchemaService', []),
    'Client Call Control' => new ServiceMeta('Client Call Control', '', 'Services/OCISchemaService', []),
    'Client License 16' => new ServiceMeta('Client License 16', '', 'Services/OCISchemaService', []),
    'Client License 17' => new ServiceMeta('Client License 17', '', 'Services/OCISchemaService', []),
    'Client License 18' => new ServiceMeta('Client License 18', '', 'Services/OCISchemaService', []),
    'Client License 3' => new ServiceMeta('Client License 3', '', 'Services/OCISchemaService', []),
    'Client License 4' => new ServiceMeta('Client License 4', '', 'Services/OCISchemaService', []),
    'CommPilot Call Manager' => new ServiceMeta('CommPilot Call Manager', '', 'Services/OCISchemaService', []),
    'CommPilot Express' => new ServiceMeta('CommPilot Express', '', 'Services/OCISchemaService', []),
    'Connected Line Identification Presentation' => new ServiceMeta('Connected Line Identification Presentation', '', 'Services/OCISchemaService', []),
    'Connected Line Identification Restriction' => new ServiceMeta('Connected Line Identification Restriction', '', 'Services/OCISchemaService', []),
    'Directed Call Pickup' => new ServiceMeta('Directed Call Pickup', '', 'Services/OCISchemaService', []),
    'Directed Call Pickup with Barge-in' => new ServiceMeta('Directed Call Pickup with Barge-in', '', 'Services/OCISchemaService', []),
    'Diversion Inhibitor' => new ServiceMeta('Diversion Inhibitor', '', 'Services/OCISchemaService', []),
    'Do Not Disturb' => new ServiceMeta('Do Not Disturb', '', 'Services/OCISchemaService', []),
    'External Calling Line ID Delivery' => new ServiceMeta('External Calling Line ID Delivery', '', 'Services/OCISchemaService', []),
    'Flash Call Hold' => new ServiceMeta('Flash Call Hold', '', 'Services/OCISchemaService', []),
    'Hoteling Guest' => new ServiceMeta('Hoteling Guest', '', 'Services/OCISchemaService', []),
    'Hoteling Host' => new ServiceMeta('Hoteling Host', '', 'Services/OCISchemaService', []),
    'In-Call Service Activation' => new ServiceMeta('In-Call Service Activation', '', 'Services/OCISchemaService', []),
    'Intercept User' => new ServiceMeta('Intercept User', '', 'Services/OCISchemaService', []),
    'Internal Calling Line ID Delivery' => new ServiceMeta('Internal Calling Line ID Delivery', '', 'Services/OCISchemaService', []),
    'Last Number Redial' => new ServiceMeta('Last Number Redial', '', 'Services/OCISchemaService', []),
    'Malicious Call Trace' => new ServiceMeta('Malicious Call Trace', '', 'Services/OCISchemaService', []),
    'Multiple Call Arrangement' => new ServiceMeta('Multiple Call Arrangement', '', 'Services/OCISchemaService', []),
    'N-Way Call' => new ServiceMeta('N-Way Call', '', 'Services/OCISchemaService', []),
    'Outlook Integration' => new ServiceMeta('Outlook Integration', '', 'Services/OCISchemaService', []),
    'Polycom Phone Services' => new ServiceMeta('Polycom Phone Services', '', 'Services/OCISchemaService', []),
    'Priority Alert' => new ServiceMeta('Priority Alert', '', 'Services/OCISchemaService', []),
    'Push to Talk' => new ServiceMeta('Push to Talk', '', 'Services/OCISchemaService', []),
    'Remote Office' => new ServiceMeta('Remote Office', '', 'Services/OCISchemaService', []),
    'Selective Call Acceptance' => new ServiceMeta('Selective Call Acceptance', '', 'Services/OCISchemaService', []),
    'Selective Call Rejection' => new ServiceMeta('Selective Call Rejection', '', 'Services/OCISchemaService', []),
    'Sequential Ring' => new ServiceMeta('Sequential Ring', '', 'Services/OCISchemaService', []),
    'Service Scripts User' => new ServiceMeta('Service Scripts User', '', 'Services/OCISchemaService', []),
    'Shared Call Appearance' => new ServiceMeta('Shared Call Appearance', '', 'Services/OCISchemaService', []),
    'Shared Call Appearance 10' => new ServiceMeta('Shared Call Appearance 10', '', 'Services/OCISchemaService', []),
    'Shared Call Appearance 15' => new ServiceMeta('Shared Call Appearance 15', '', 'Services/OCISchemaService', []),
    'Shared Call Appearance 20' => new ServiceMeta('Shared Call Appearance 20', '', 'Services/OCISchemaService', []),
    'Shared Call Appearance 25' => new ServiceMeta('Shared Call Appearance 25', '', 'Services/OCISchemaService', []),
    'Shared Call Appearance 30' => new ServiceMeta('Shared Call Appearance 30', '', 'Services/OCISchemaService', []),
    'Shared Call Appearance 35' => new ServiceMeta('Shared Call Appearance 35', '', 'Services/OCISchemaService', []),
    'Shared Call Appearance 5' => new ServiceMeta('Shared Call Appearance 5', '', 'Services/OCISchemaService', []),
    'Simultaneous Ring Personal' => new ServiceMeta('Simultaneous Ring Personal', '', 'Services/OCISchemaService', []),
    'Speed Dial 100' => new ServiceMeta('Speed Dial 100', '', 'Services/OCISchemaService', []),
    'Speed Dial 8' => new ServiceMeta('Speed Dial 8', '', 'Services/OCISchemaService', []),
    'Three-Way Call' => new ServiceMeta('Three-Way Call', '', 'Services/OCISchemaService', []),
    'Two-Stage Dialing' => new ServiceMeta('Two-Stage Dialing', '', 'Services/OCISchemaService', []),
    'Video Add-On' => new ServiceMeta('Video Add-On', '', 'Services/OCISchemaService', []),
    'Video On Hold User' => new ServiceMeta('Video On Hold User', '', 'Services/OCISchemaService', []),
    'Voice Messaging User' => new ServiceMeta('Voice Messaging User', '', 'Services/OCISchemaService', [])
];