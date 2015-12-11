<?php
require_once '../Broadworks_OCI-P/common.php';
Factory::getOCISchemaServiceProvider();
Factory::getOCISchemaGroup();

if (!isset($argv[1])) die("Provide service provider id as second argument.");

$mongo = new MongoClient('mongodb://pillock.net:27017');
$collection = $mongo->selectDB($argv[1])->selectCollection('GroupGetRequest14sp7');

$client = CoreFactory::getOCIClient('http://bsews1.ipt.intechnology.co.uk/webservice/services/ProvisioningService');
$client->login('luke_script', 'waiodjjAWDOAWdlaiojhawiopjh4');
$client->send(OCISchemaGroup::GroupGetListInServiceProviderRequest($argv[1]));

if ($client->getResponse()) {
    foreach ($client->getResponse()->groupTable['row'] as $row) {
        $client->send(OCISchemaGroup::GroupGetRequest14sp7($argv[1], $row['col'][0]));
        $collection->insert([$row['col'][0] => $client->getResponse()]);
    }
}