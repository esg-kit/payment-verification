<?php
//$google['admin_email_api'] = '63907055777-soob3gj6hqrff936vkcr2g5krkislpfc@developer.gserviceaccount.com';
//$config['package'] = 'com.eastsidegames.trailerparkboys';

$platform = $_GET['platform'];
$platformId = $_GET['platformId'];
$receipt = $_GET['receipt'];
$productIdentifier = $_GET['productIdentifier'];
$transactionIdentifier = $_GET['transactionIdentifier'];
$package = $_GET['package'];


if (!isset($platform) || 
    !isset($platformId) || 
    !isset($receipt) || 
    !isset($productIdentifier) ||
    !isset($transactionIdentifier) ||
    !isset($package)) {
        echo 'MISSING_PARAMETERS';
        exit();
    }

if ($platform == 'android') {
    $status = verifyAndroid($transactionIdentifier, $productIdentifier, $receipt, $package);
} else if ($platform == 'ios') {
    $status = verifyIOS($receipt);
}

return $status;
    
function verifyAndroid($transactionIdentifier, $productIdentifier, $receipt, $package)
{
	require_once ('./vendor/autoload.php');

	$clientEmail = '63907055777-soob3gj6hqrff936vkcr2g5krkislpfc@developer.gserviceaccount.com';
	$privateKey  = file_get_contents('./vendor/google/googlePlayService.p12', true);
	$scopes      = array('https://www.googleapis.com/auth/androidpublisher');
	$credentials = new Google_Auth_AssertionCredentials($clientEmail, $scopes, $privateKey);
	$client      = new Google_Client();
	$client->setAssertionCredentials($credentials);
	if ($client->getAuth()->isAccessTokenExpired()) {
		$client->getAuth()->refreshTokenWithAssertion();
	}
        
	$products = new Google_Service_AndroidPublisher($client);
        
	try {
		$response = $products->purchases_products->get($package, $productIdentifier, $transactionIdentifier);
		return true;
	} catch (\Exception $e) {
		return false;
	}
}
    
function verifyIOS($receipt)
{

        // App review tests purchases in sandbox, so production builds may be used in either
        
        $productionEndPoint = 'https://buy.itunes.apple.com/verifyReceipt';
        $sandBoxEndPoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        
        if (strpos($receipt, '{') !== false) {
            $receipt = base64_encode($receipt);
        }
        
        $result = getVerifyStatus($productionEndPoint, $receipt);
        if ($result == null) {
            return false;
        }
        
        $decodedResult = json_decode($result);

        if (!is_object($decodedResult)) {
            return false;
        }
        
        if (!isset($decodedResult->status) || $decodedResult->status == 21007) {
            $result = getVerifyStatus($sandBoxEndPoint, $receipt);
            if ($result == null) {
                return false;
            }
            
            $decodedResult = json_decode($result);
        
            if (!is_object($decodedResult)) {
                return false;
            }
            
            if (!isset($decodedResult->status) || $decodedResult->status != 0) {
                return false;
            }
        } else if (!isset($decodedResult->status) || $decodedResult->status != 0) {
            return false;
        }
        
        return true;
    }

        
function getVerifyStatus($endPoint, $receipt)
    {
        $ch = curl_init($endPoint);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('receipt-data' => $receipt)));
        $result = curl_exec($ch);
        $errno  = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);
        
        if ($errno != 0) {
            error_log("CURL Error");
            return null;
        }
        
        return $result;
    }