<?php
// spl_autoload_register(function ($class) {
//     require_once str_replace("\\", "/", $class) . ".php";
// });

require_once 'vendor/autoload.php';
require_once "./random_string.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

$connectionString = "DefaultEndpointsProtocol=https;AccountName=".getenv('ACCOUNT_NAME').";AccountKey=".getenv('ACCOUNT_KEY');

$blobRestProxy = SBlobRestProxy::createBlobService($connectionString);
$containerName = "blockblobs".generateRandomString();
$file_name = $argv[1];
$blob_name = basename($file_name);

$block_list = new BlockList();

define('CHUNK_SIZE', 4 * 1024 * 1024);

try {
    $fptr = fopen($file_name, "rb");
    $index = 1;
    while (!feof($fptr)) {
        $block_id = base64_encode(str_pad($index, 6, "0", STR_PAD_LEFT));
        $block_list->addUncommittedEntry($block_id);
        $data = fread($fptr, CHUNK_SIZE);
        $blobRestProxy->createBlobBlock($containerName, $blob_name, $block_id, $data);
        ++$index;
    }

    $blobRestProxy->commitBlobBlocks($containerName, $blob_name, $block_list);
} catch (ServiceException $e) {
    $code = $e->getCode();
    $error_message = $e->getMessage();
    echo $code.": ".$error_message."<br />";
}
?>