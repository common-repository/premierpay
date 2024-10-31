<?php



function prp_login($username,$password)
{

    $response = wp_remote_post('https://api.premierwallets.com/api/MerchantLogin', array(
    'method'      => 'POST',
	'timeout'     => 45,
	'redirection' => 5,
	'httpversion' => '1.0',
	'blocking'    => true,
    'headers' => array(
        'Content-Type'=> 'application/json',
			'Content-Length' =>' 0',
			'MachineID'=> 'ds@#13ds!WE4C#FW$672@',
			'ChannelID'=> '104',
			'DeviceType'=> '205',
               'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
    ),) );
	if ( is_wp_error( $response ) )
	{
    	$error_message = $response->get_error_message();
    	echo "Something went wrong: $error_message";
    } 
    else
    {

        $responsarr = json_decode($response['body'], true);
        $token = $responsarr['Data']['Token'];
        $code = $responsarr['Response']['Code'];
        	return [
    		'code' => $code,
    		'token' => $token
    	];
 }
}


function prp_pushPayment($token,$amount,$CustomerWalletID,$MerchantID)
	{
		
		if(strlen((string)$CustomerWalletID)>9)
		{
		  if(strlen((string)$CustomerWalletID)==10 || strlen((string)$CustomerWalletID)==12)
				{
						
				   if (substr($CustomerWalletID, 0,2) == '06')
						{
							 $CustomerWalletID = str_replace('06', '002526', $CustomerWalletID);
							 //echo $CustomerWalletID;
								
						} 
				   else if(substr($CustomerWalletID, 0,3) == '252')
						{
						 $CustomerWalletID = str_replace('252', '002526', $CustomerWalletID);
						// echo $CustomerWalletID.'   '.'252';
							
						}
				   else{
						 wc_add_notice( 'Please enter the Phone Number for Billing (Format: 00252615080326 )', 'error' );
							 return;
				   }
			   }
   
		}
	    $url = 'https://api.premierwallets.com/api/PushPayment';
        $headers = array( 'Authorization' => 'Bearer ' . $token, 
            'Content-Type' => 'application/json',
            'MachineID' => 'ds@#13ds!WE4C#FW$672@',
			'ChannelID' => '104',
			'DeviceType' => '205');
        $fields = array(
            'body' => json_encode(
                array(
                    'CustomerWalletID' => $CustomerWalletID,
        			'Amount' => $amount,
        			'Remarks' => '',
        			'Category'=> '1',
        			'LoginUserName'=> $MerchantID
                )
            ),
            'headers' => $headers,
            'method'   => 'POST',
            'timeout'  => 45,
            'httpversion' => '1.0',
             'sslverify' => false,
             'data_format' => 'body'
        );
        
        $response = wp_remote_post($url,$fields);
        
        if ( is_wp_error( $response ) ) {
             $error_message = $response->get_error_message();
			 echo esc_attr( $error_message ) ;
        } 
        else {
             $api_status2 = json_decode($response['body'], true);
              global $transaction_id;
              global $error_message;
    		$code = $api_status2['Response']['Code'];
    	
    		if($code != '001'){
    			$error_message =$api_status2['Response']['Errors'][0]['Message'];
    			$transaction_id ='';
    			
    		}
    		else{
    			$error_message ='';
    			$transaction_id =$api_status2['Data']['TransactionID'];
    			
    		
    		}
    		return [
    			'code' => $code,
    			'TransactionID' => $transaction_id,
    			 'error_message' =>$error_message
    		];
        }
  
		
	}


        	
function prp_callBackApi($token,$transaction_id,$MerchantID)
        	{
                
        	   
            $url = 'https://api.premierwallets.com/api/GetPaymentDetails';
             $headers = array( 'Authorization' => 'Bearer ' . $token, 
                'Content-Type' => 'application/json',
                'MachineID' => 'ds@#13ds!WE4C#FW$672@',
    			'ChannelID' => '104',
    			'DeviceType' => '205');
            $fields = array(
            'body' => json_encode(
                array(
                    'TransactionID'  =>$transaction_id,
                     'LoginUserName' => $MerchantID
                        )
                    ),
                'headers' => $headers,
                'method'   => 'POST',
                'timeout'  => 45,
                'httpversion' => '1.0',
                 'sslverify' => false,
                 'data_format' => 'body'
             );
        
        $response = wp_remote_post($url,$fields);
        if ( is_wp_error( $response ) ) {
             $error_message = $response->get_error_message();
			 echo esc_attr( $error_message );
        } 
        else {
                   $api_status_callBack = json_decode($response['body'], true);
            		global $status_callBack;
            		$status_callBack = $api_status_callBack['Data']['Status'];
            		$code_callBack = $api_status_callBack['Response']['Code'];
        			return [
        			'codeCallback' => $code_callBack,
        			'status' => $status_callBack
        		];
        }
           
	}
	
function prp_Pay_rest($username,$password,$amount,$CustomerWalletID,$MerchantID) 
{
		$code1 = '';
		$code = '';
		$callbackstatus ='';
        $reststatus='' ;
		 $code1 =prp_login($username,$password);
		 if ($code1['code'] === "001") 
		  {
			$code = prp_pushPayment($code1['token'],$amount,$CustomerWalletID,$MerchantID);
    			  $trnsaction_code =$code['code'];
    			  $error_message =$code['error_message'];
				if ($code['code'] == "001") 
				{
				    sleep(10);
				  $callbackstatus = prp_callBackApi($code1['token'],$code['TransactionID'],$MerchantID);
						if($callbackstatus['codeCallback']  != "001" and $callbackstatus['status']  !='Executed')
						{
							$reststatus= $callbackstatus['status'] ;
						}
						else{
							$reststatus= $callbackstatus['status'] ;
						}
					
				}
				else{
					$error_message= $error_message;
				}

                return [
        			'reststatus' => $reststatus,
        			'error_message' => $error_message,
                    'restcode'=>$trnsaction_code
        		];
				
		}
	
}