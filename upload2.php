<?php

require_once 'vendor/autoload.php';
require_once "./random_string.php";

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use MicrosoftAzure\Storage\Blob\Models\CreateContainerOptions;
use MicrosoftAzure\Storage\Blob\Models\PublicAccessType;

$connectionString = "DefaultEndpointsProtocol=https;AccountName=".getenv('ACCOUNT_NAME').";AccountKey=".getenv('ACCOUNT_KEY');

// Create blob client.
$blobClient = BlobRestProxy::createBlobService($connectionString);



$createContainerOptions = new CreateContainerOptions();

$createContainerOptions->setPublicAccess(PublicAccessType::CONTAINER_AND_BLOBS);

// Set container metadata.
$createContainerOptions->addMetaData("key1", "value1");
$createContainerOptions->addMetaData("key2", "value2");

$containerName = "blockblobs".generateRandomString();

$fileToUpload = $_FILES['file']['tmp_name'];
 
if(!isset($fileToUpload)){
    echo 'Pilih file gambar';
}else{
    $image 		= addslashes(file_get_contents($_FILES['image']['tmp_name']));
    $image_name	= addslashes($_FILES['image']['name']);
    $image_size	= getimagesize($_FILES['image']['tmp_name']);

    if($image_size == false){
        echo 'File yang anda pilih tidak gambar';
    }else{
        $blobClient->createContainer($containerName, $createContainerOptions);

        // Getting local file so that we can upload it to Azure
        $myfile = fopen($fileToUpload, "w") or die("Unable to open file!");
        fclose($myfile);
        
        # Upload file as a block blob
        echo "Uploading BlockBlob: ".PHP_EOL;
        echo $fileToUpload;
        echo "<br />";
        
        $content = fopen($fileToUpload, "r");

        //Upload blob
        $blobClient->createBlockBlob($containerName, $fileToUpload, $content);
        $listBlobsOptions = new ListBlobsOptions();
        $listBlobsOptions->setPrefix("presiden_sukarno");

        echo "These are the blobs present in the container: ";

        do{
            $result = $blobClient->listBlobs($containerName, $listBlobsOptions);
            foreach ($result->getBlobs() as $blob)
            {
                echo $blob->getName().": ".$blob->getUrl()."<br />";
            }
        
            $listBlobsOptions->setContinuationToken($result->getContinuationToken());
        } while($result->getContinuationToken());
        echo "<br />";

        // Get blob.
        echo "This is the content of the blob uploaded: ";
        $blob = $blobClient->getBlob($containerName, $fileToUpload);
        fpassthru($blob->getContentStream());
        echo "<br />";
        echo 'Gambar berhasil di upload.<p>Gambar anda: '.$blob->getUrl().'</p>';
    }    
}

?>
<h1>Analisa Gambar Tokoh:</h1>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="image" id="myfile">
    <input type="submit" name="submit" value="Upload">
</form>
