<?php
require_once '../Broadworks_OCI-P/common.php';
Factory::getOCISchemaServiceProvider();
if (!isset($argv[1])) die("Provide service provider id as second argument.\n");

$mongo = new MongoClient('mongodb://pillock.net:27017');
$collection = $mongo->selectDB($argv[1])->selectCollection('UserGetListInServiceProviderRequest');

$client = CoreFactory::getOCIClient('http://bsews1.ipt.intechnology.co.uk/webservice/services/ProvisioningService');
$client->login('luke_script', 'waiodjjAWDOAWdlaiojhawiopjh4');
$client->send(OCISchemaUser::UserGetListInServiceProviderRequest($argv[1]));

if ($client->getResponse()) {
    foreach ($client->getResponse()->userTable['row'] as $row) {
        $userId = $row['col'][0];
        $client->send(OCISchemaUser::UserGetRequest17sp4($userId));
        $data = $client->getResponse();
        $data->userId = $userId;
        $collection->insert([bin2hex($userId) => $data]);
    }
}