<html>
 <head> 
 <style type="text/css">
 	body { background-color: #fff; border-top: solid 10px #000;
 	    color: #333; font-size: .85em; margin: 20; padding: 20;
 	    font-family: "Segoe UI", Verdana, Helvetica, Sans-Serif;
 	}
 	h1, h2, h3,{ color: #000; margin-bottom: 0; padding-bottom: 0; }
 	h1 { font-size: 2em; }
 	h2 { font-size: 1.75em; }
 	h3 { font-size: 1.2em; }
 	table { margin-top: 0.75em; }
 	th { font-size: 1.2em; text-align: left; border: none; padding-left: 0; }
 	td { padding: 0.25em 2em 0.25em 0em; border: 0 none; }
 </style>
 <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
<Title>Registration Form</Title>
 </head>
 <body>
 <script type="text/javascript">
    function processImage() {
        // **********************************************
        // *** Update or verify the following values. ***
        // **********************************************
 
        // Replace <Subscription Key> with your valid subscription key.
        var subscriptionKey = "f423499a5fb1408fa6aca238baa2e32a";
 
        // You must use the same Azure region in your REST API method as you used to
        // get your subscription keys. For example, if you got your subscription keys
        // from the West US region, replace "westcentralus" in the URL
        // below with "westus".
        //
        // Free trial subscription keys are generated in the "westus" region.
        // If you use a free trial subscription key, you shouldn't need to change
        // this region.
        var uriBase =
            "https://southeastasia.api.cognitive.microsoft.com/vision/v2.0/analyze";
 
        // Request parameters.
        var params = {
            "visualFeatures": "Categories,Description,Color",
            "details": "",
            "language": "en",
        };
 
        // Display the image.
        var sourceImageUrl = document.getElementById("inputImage").value;
        document.querySelector("#sourceImage").src = sourceImageUrl;
 
        // Make the REST API call.
        $.ajax({
            url: uriBase + "?" + $.param(params),
 
            // Request headers.
            beforeSend: function(xhrObj){
                xhrObj.setRequestHeader("Content-Type","application/json");
                xhrObj.setRequestHeader(
                    "Ocp-Apim-Subscription-Key", subscriptionKey);
            },
 
            type: "POST",
 
            // Request body.
            data: '{"url": ' + '"' + sourceImageUrl + '"}',
        })
 
        .done(function(data) {
            // Show formatted JSON on webpage.
            $("#responseTextArea").val(JSON.stringify(data, null, 2));
        })
 
        .fail(function(jqXHR, textStatus, errorThrown) {
            // Display error message.
            var errorString = (errorThrown === "") ? "Error. " :
                errorThrown + " (" + jqXHR.status + "): ";
            errorString += (jqXHR.responseText === "") ? "" :
                jQuery.parseJSON(jqXHR.responseText).message;
            alert(errorString);
        });
    };
</script>
	<h1>Analisa Gambar Tokoh:</h1>
	<form method="post" action="index2.php" enctype="multipart/form-data">
		<input type="file" name="myfile" id="myfile">
		<input type="submit" name="submit" value="Upload">
	</form>
	<br>	
	<!-- <form method="post" action="index2.php" enctype="multipart/form-data">
		<input type="submit" name="load_data" id="load" value="Load Data" />
	</form> -->
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
	echo "<h3>Daftar Upload Blob</h3>";
    if (isset($_POST['submit'])) {
        try {
			$file_name = $_FILES['myfile']['tmp_name'];
			//$file_tmp = $_FILES['image']['tmp_name'];
			$fileToUpload = $_FILES['myfile']['name'];
			//move_uploaded_file($file_tmp,"images/".$file_name);
			
			// Create container.
			$blobClient->createContainer($containerName, $createContainerOptions);

			// Getting local file so that we can upload it to Azure
			$myfile = fopen($fileToUpload, "w") or die("Unable to open file!");
			fclose($myfile);
					
			$content = fopen($fileToUpload, "r");

			//Upload blob
			$blobClient->createBlockBlob($containerName, $fileToUpload, $content);

			// List blobs.
			$listBlobsOptions = new ListBlobsOptions();
			$listBlobsOptions->setPrefix("");	

			do{
				$result = $blobClient->listBlobs($containerName, $listBlobsOptions);				
				echo '<table>
                <tr>
                    <th>Nama</th>
					<th>Url</th>
					<th>Aksi</th>
                </tr>';
				foreach ($result->getBlobs() as $blob)
				{
					echo "<tr><td>".$blob->getName()."</td>";
					echo "<td>".$blob->getUrl()."</td>";
					echo "<td>Aksi</td></tr>";

					// echo "Tampikan data gan : ".$blob->getName().": ".$blob->getUrl()."<br />";
				}
			
				$listBlobsOptions->setContinuationToken($result->getContinuationToken());
			} while($result->getContinuationToken());
			echo "</table><br />";
			// Get blob.
			// echo "This is the content of the blob uploaded: ";
			// $blob = $blobClient->getBlob($containerName,$fileToUpload);
			// fpassthru($blob->getContentStream());
			// echo "<br />";
			
        } catch(ServiceException $e){
			// Handle exception based on error codes and messages.
			// Error codes and messages are here:
			// http://msdn.microsoft.com/library/azure/dd179439.aspx
			$code = $e->getCode();
			$error_message = $e->getMessage();
			echo $code.": ".$error_message."<br />";
		}

    }
 ?>
 <br>
<h1>Analyze image:</h1>
<input type="text" name="inputImage" id="inputImage"
    value="http://upload.wikimedia.org/wikipedia/commons/3/3c/Shaki_waterfall.jpg" />
<button onclick="processImage()">Analyze image</button>
<br><br>
<div id="wrapper" style="width:1020px; display:table;">
    <div id="jsonOutput" style="width:600px; display:table-cell;">
        Response:
        <br><br>
        <textarea id="responseTextArea" class="UIInput"
                  style="width:580px; height:350px;"></textarea>
    </div>
    <div id="imageDiv" style="width:420px; display:table-cell;">
        Source image:
        <br><br>
        <img id="sourceImage" width="400" />
    </div>
</div>
 </body>
 </html>