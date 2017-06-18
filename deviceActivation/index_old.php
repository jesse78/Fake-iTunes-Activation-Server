<?php
$serial= $_POST["AppleSerialNumber"];
$guid= $_POST["guid"];
$activation= $_POST["activation-info"];

if(!isset($activation))
{
	echo 'Activation info not found!';
	exit;
}

// load and decode activation info 
$encodedrequest = new DOMDocument;
$encodedrequest->loadXML($activation);
$activationDecoded= base64_decode($encodedrequest->getElementsByTagName('data')->item(0)->nodeValue);

$decodedrequest = new DOMDocument;
$decodedrequest->loadXML($activationDecoded);
$nodes = $decodedrequest->getElementsByTagName('dict')->item(0)->getElementsByTagName('*');

for ($i = 0; $i < $nodes->length - 1; $i=$i+2)
{
	switch ($nodes->item($i)->nodeValue)
	{
		case "ActivationRandomness": $activationRamdomess = $nodes->item($i + 1)->nodeValue; break;
		case "DeviceCertRequest": $deviceCertRequest=base64_decode($nodes->item($i + 1)->nodeValue); break;
		case "DeviceClass": $deviceClass=strtolower($nodes->item($i + 1)->nodeValue); break;
		case "UniqueDeviceID": $uniqueDiviceID = $nodes->item($i + 1)->nodeValue; break;
	}
}

// save xml requests
$encodedrequest->save('requests/'.$uniqueDiviceID.'.xml');
$decodedrequest->save('requests/decoded/'.$uniqueDiviceID.'.xml');

$privkey = array(file_get_contents('certs/iPhoneDeviceCA_private.pem'),"minacriss");
$mycert = file_get_contents('certs/iPhoneDeviceCA.pem');

$config = array('config'=>'C:/openserver/modules/php/PHP-5.5.10/extras/ssl/openssl.cnf'); 

$usercert = openssl_csr_sign($deviceCertRequest,$mycert,$privkey,365, $config, '6');

openssl_x509_export($usercert,$certout);
file_put_contents('serverCASigned.crt',$certout);
$deviceCertificate=base64_encode($certout);
$accountToken='{'."\n\t".'"ActivationRandomness" = "'.$activationRamdomess.'";'."\n\t".'"UniqueDeviceID" = "'.$uniqueDiviceID.'";'."\n".'}';

$accountTokenBase64=base64_encode($accountToken);
$pkeyid = openssl_pkey_get_private(file_get_contents("certs/private_key.pem"));

// compute signature
openssl_sign($accountTokenBase64, $signature, $pkeyid);

// free the key from memory
openssl_free_key($pkeyid);
$accountTokenSignature= base64_encode($signature);
$accountTokenCertificateBase64='LS0tLS1CRUdJTiBDRVJUSUZJQ0FURS0tLS0tCk1JSURaekNDQWsrZ0F3SUJBZ0lCQWpBTkJna3Foa2lHOXcwQkFRVUZBREI1TVFzd0NRWURWUVFHRXdKVlV6RVQKTUJFR0ExVUVDaE1LUVhCd2JHVWdTVzVqTGpFbU1DUUdBMVVFQ3hNZFFYQndiR1VnUTJWeWRHbG1hV05oZEdsdgpiaUJCZFhSb2IzSnBkSGt4TFRBckJnTlZCQU1USkVGd2NHeGxJR2xRYUc5dVpTQkRaWEowYVdacFkyRjBhVzl1CklFRjFkR2h2Y21sMGVUQWVGdzB3TnpBME1UWXlNalUxTURKYUZ3MHhOREEwTVRZeU1qVTFNREphTUZzeEN6QUoKQmdOVkJBWVRBbFZUTVJNd0VRWURWUVFLRXdwQmNIQnNaU0JKYm1NdU1SVXdFd1lEVlFRTEV3eEJjSEJzWlNCcApVR2h2Ym1VeElEQWVCZ05WQkFNVEYwRndjR3hsSUdsUWFHOXVaU0JCWTNScGRtRjBhVzl1TUlHZk1BMEdDU3FHClNJYjNEUUVCQVFVQUE0R05BRENCaVFLQmdRREZBWHpSSW1Bcm1vaUhmYlMyb1BjcUFmYkV2MGQxams3R2JuWDcKKzRZVWx5SWZwcnpCVmRsbXoySkhZdjErMDRJekp0TDdjTDk3VUk3ZmswaTBPTVkwYWw4YStKUFFhNFVnNjExVApicUV0K25qQW1Ba2dlM0hYV0RCZEFYRDlNaGtDN1QvOW83N3pPUTFvbGk0Y1VkemxuWVdmem1XMFBkdU94dXZlCkFlWVk0d0lEQVFBQm80R2JNSUdZTUE0R0ExVWREd0VCL3dRRUF3SUhnREFNQmdOVkhSTUJBZjhFQWpBQU1CMEcKQTFVZERnUVdCQlNob05MK3Q3UnovcHNVYXEvTlBYTlBIKy9XbERBZkJnTlZIU01FR0RBV2dCVG5OQ291SXQ0NQpZR3UwbE01M2cyRXZNYUI4TlRBNEJnTlZIUjhFTVRBdk1DMmdLNkFwaGlkb2RIUndPaTh2ZDNkM0xtRndjR3hsCkxtTnZiUzloY0hCc1pXTmhMMmx3YUc5dVpTNWpjbXd3RFFZSktvWklodmNOQVFFRkJRQURnZ0VCQUY5cW1yVU4KZEErRlJPWUdQN3BXY1lUQUsrcEx5T2Y5ek9hRTdhZVZJODg1VjhZL0JLSGhsd0FvK3pFa2lPVTNGYkVQQ1M5Vgp0UzE4WkJjd0QvK2Q1WlFUTUZrbmhjVUp3ZFBxcWpubTlMcVRmSC94NHB3OE9OSFJEenhIZHA5NmdPVjNBNCs4CmFia29BU2ZjWXF2SVJ5cFhuYnVyM2JSUmhUekFzNFZJTFM2alR5Rll5bVplU2V3dEJ1Ym1taWdvMWtDUWlaR2MKNzZjNWZlREF5SGIyYnpFcXR2eDNXcHJsanRTNDZRVDVDUjZZZWxpblpuaW8zMmpBelJZVHh0UzZyM0pzdlpEaQpKMDcrRUhjbWZHZHB4d2dPKzdidFcxcEZhcjBaakY5L2pZS0tuT1lOeXZDcndzemhhZmJTWXd6QUc1RUpvWEZCCjRkK3BpV0hVRGNQeHRjYz0KLS0tLS1FTkQgQ0VSVElGSUNBVEUtLS0tLQo=';
$fairPlayKeyData='LS0tLS1CRUdJTiBDT05UQUlORVItLS0tLQpBQUVBQVIvcWRpY3lUdWJtMmxKTndMV1ZaT0xQSnpTSWF1MGJuT1lPSE10alZxc242dTFuY0Urb0ZQNkQ3VjNWCmplekJxQWNhRVpxUGNOT09yK3hFM2NkL1I0K1Q4OHMwSitFa0pQNnRPZzQ5U215ZkZUMlg0UDdYZExTNndEalAKY3piRmRDU0hpTVZmREJhY1pUaWxPNGNsdHllS3JzZHpLTlI5L3J5VXQ4TnJkY0VJd2lHWTBjYjNpcExLUnhHUwpYSWFMMnpYMy9HeE14UW0yRzdzL0IvWDBkdWEwd084enB6ZXE1bHkwc1lPQjE5cUdwaytKQ0hSaUtyUC9neFRaClJjZC9tTjVaM25WUEY4Qld2VEQ5UElvYldDZENxc3dCZzBvK1VyNnExZHFsZEpPM0FSOEFWTzFLUEFrVC8wV1QKdkR0MFpBbDJod3JEclpXdHJSd3RDNUlXZi9DY2UwaDZ0UXB4bDM3akFBWkdqcWNFM3F5dG4rdmh1SVQ2WklTUQpyK2x0T1B0Mk5vK3plVFh2TVExalJWUXlyRzFCNzRMWEpGcU1nQytGZGgzMDYvamRoMEtkeEVoeHdHanR4VGpICk5YRkhhV2Y0Nm9UaGVmWTBDM3NSclh1cENRSjg1ODNiRWFuUG8yUk1FL1dkY0pDODJFeEZma3FGRjNPSkU5dy8KV2w3NkFUZlVGaUVYRUFpUHVOQXk4Zlhhazk0Y3FyREhXeS9YbTFRV0o3Rnd4eDYzM2RnUXBFVWExSjBMaTNYZwpqaWJmczZQdDdpUkUzK3ZhTWViVW1BajZWZnczUjBQL253SzhzNnhubDJ1MUZsdEdXTkQxRWdoRVNEM1ExRk5mCkxlVWpOL1gwVmE0TEFzU0tGZ2NPSlloRi9renRLUFFqd0ZVNVFtd1FSeUI3aVhHM3lDbmdFZml6d3hhVEtUQzUKRmZFbi8xa3JlYndtOGZ3bW04NjllL3ZhTk81K285MFFibG9weDNUbnFRUWwyalYyZjhoa3FlYTlpUWRoL0JlTwpLUjVmcjR0bW9PSGNuUS9tRVNVZEUwdUcrRjhteGRBNVlUTzRhaElzaEZZajlEZzFVQkQvNGZHdWxkaCszZU50CkJQUVVveG5jTWd5VnFMMFRjaE90TXFOc3NnYkZXemUwRHBiMWU3OVVHUlJqdXN0QmlFTG9vY2s4amxtRWdwclgKZ3dLSmU5dkVqMDQ3Y2FUS2NSci9zKzN3b1ZkUWNQNDdZVEw0aVZKZ01jRHlZRFNGYk5lc3JXdlZ1KzhPZlJ0SwpUSEM0T2xQTmZWRTNXNXQyRWYwL0JlVERnL0FiUzUrSWNhSDdpeUhVZWRHWmxkRHpCWnhRMzdRRUNaYUZpUnpiCksrZWNXbWNMOXk3QnRoNGtaV3hJOE9vSzc3akQrb1JmWlVIZHM5OXNWbnNGZVRuQUVyL0RzaXVwTnlTRzZSdEcKVXJpOWZnRUYzUjJEb0lWaTlxQjdIUmJnM1VFTnZORlVFSVQ4VTdkb1lFVFBJSEVCUlVUU3k0MnhvbnVKNmxCNgpuOEEzaVpBTkR3N2ZzZWJUVzF6bnZuYmJGcC81YUhzMFJVNmNRenBTRlRIanRKb1hSQ091Y2RBRDNmY3VhMWhYCk14WENYV0drWDJOZnA2OFQvV0J4K0tlTDB0NGRFOXZrVnV4aEhjTjFZYS95OWZ2eFZZQmpSSVBEQXNBSGFhUnMKY05oZUdpTFNCTWh0Ui9kblVUMnA1aHhDRWNobnRjSTI5K21mYlV2VXIydVNrV3I4dFJVV0I0YjFZdmlVbUJScQpuQUV4L29WRHJlTDcvMnUxY0FhaHRhYWdaanBRUzlBNmhBSHA4RWVJNkg5dnZxcUtHMXY0TW9qa3NnalNlWDBuCnRWcHl0Yjg4TFZxNHRRNmp6U21BcXNzbmRzNmgwZzZCUHpFSWxFdDlLWWZLeURhbXZyOXM0czRZaldDcEgxT2UKL2ZMbEhYUzRURUMwOXdUYnpjQWw4dmZqUFpMdmpnMURyalZsUWU5K1FINGgrMElECi0tLS0tRU5EIENPTlRBSU5FUi0tLS0tCg==';

//header('Content-type: text/xml');
echo '
<!DOCTYPE html>
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="keywords" content="iTunes Store" />
	<meta name="description" content="iTunes Store" />
	<title>iPhone Activation</title>
	<link href="http://static.ips.apple.com/ipa_itunes/stylesheets/shared/common-min.css" charset="utf-8" rel="stylesheet" />
	<link href="http://static.ips.apple.com/deviceservices/stylesheets/styles.css" charset="utf-8" rel="stylesheet" />
	<link href="http://static.ips.apple.com/ipa_itunes/stylesheets/pages/IPAJingleEndPointErrorPage-min.css" charset="utf-8" rel="stylesheet" />
	<script id="protocol" type="text/x-apple-plist">
	<plist version="1.0">
	  <dict>
		<key>'.$deviceClass.'-activation</key>
		<dict>
		  <key>activation-record</key>
		  <dict>
			<key>FairPlayKeyData</key>
			<data>'.$fairPlayKeyData.'</data>
			<key>AccountTokenCertificate</key>
			<data>'.$accountTokenCertificateBase64.'</data>
			<key>DeviceCertificate</key>
			<data>'.$deviceCertificate.'</data>
			<key>AccountTokenSignature</key>
			<data>'.$accountTokenSignature.'</data>
			<key>AccountToken</key>
			<data>'.$accountTokenBase64.'</data>
		  </dict>
		  <key>unbrick</key>
		  <true/>
		</dict>
	  </dict>
	</plist>
	</script>
	<script>
	var protocolElement = document.getElementById("protocol");
	var protocolContent = protocolElement.innerText;iTunes.addProtocol(protocolContent);
	</script>
	</head>
	<body>
	</body>
	</html>
';
?>