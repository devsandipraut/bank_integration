<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Storage;

class BankIntegrationController extends Controller
{
    public function bank_integration(Request $request)
    {
      	date_default_timezone_set("Asia/Calcutta"); 

      	$checkBankExist =  DB::table('integrated_bank_lists')->where('id',$request->bank_id)->get()->first();
      	if (!empty($checkBankExist)) {
      		$validate = Validator::make($request->all(), [
	            'fname'  =>"required",
	            'lname'  =>"required",
	            'pancard'  =>"required",
	            'mobile'  =>"required",
	            'dob'  =>"required|date_format:d-m-Y",
	        ],[
	            'fname' =>'This field is mandatory',
	            'lname' =>'This field is mandatory',
	            'pancard' =>'This field is mandatory',
	            'mobile.required' =>'This field is mandatory',
	            'dob' =>'Date format should be in dd/mm/yyyy format',
	        ]);

	        if($validate->fails()){
	            return response()->json(['status' => 201,'message' => 'validation error','errors' => $validate->errors()]);
	        }else {

	        		$validate = Validator::make($request->all(),[
					    'dob' => 'required|date|before_or_equal:-21 years|after_or_equal:-55 years',
					    'monthly_income' => 'required|numeric|min:20000',
					    'salary' => 'required',
					    'pancard' => 'required',
					], [
					    'dob.required' => 'The date of birth is required.',
					    'dob.date' => 'The date of birth must be a valid date.',
						'dob.before_or_equal' => 'The age should be 21 years or older.',
					    'dob.after_or_equal' => 'The age should be 55 years or younger.',
					    'monthly_income.required' => 'The monthly income field is required.',
					    'monthly_income.numeric' => 'The monthly income must be a number.',
					    'monthly_income.min' => 'The monthly income must be at least 20,000.',
					    'salary.required' => 'The salary is required.',
					    'pancard.required' => 'The pancard is required.',
					]);
	        		$pincode = $request->pincode;
	        		$pancard = $request->pancard;
	        		$name = $request->fname;
	        		$mobile = $request->mobile;
	        		$dob = $request->dob;
	        		$monthly_income = $request->monthly_income;

	        		if($validate->fails()){
				        return response()->json(['status' => 201,'message' => 'validation error','errors' => $validate->errors()]);
				    } else{
				      	// demographic filter
			        	if (!empty($pincode)) {
			        		$checkPincodeExist =  DB::table('incred_igl_pincode_lists')->where('pincode',$pincode)->get()->first();
			        		if (!empty($checkPincodeExist)) {
								
								$demographicFilterResponse = response([
					                'msg' => "Demographic Filter!",
					                'status' => 200,
				            	], 200);

			        		}else{
			        			$demographicFilterResponse = response([
					                'msg' => "Pincode not found!",
					                'status' => 201,
				            	], 201);
			        		}
			        	}else{
			        		$demographicFilterResponse = response([
				                'msg' => "Please provide a pincode to use the demographic filter. Pincode not found!",
				                'status' => 201,
				            ], 201);	
			        	}

				        // end demographic filter

				        // credit bureau filter/rules
			        	if (!empty($pancard)) {

			        		
							// $name = 'MAHESH';
							// $mobile = '8237725546';
							// $pancard = 'BNQPD1278M';

							$name = 'Mohit';
							$mobile = '9820079956';
							$pancard = 'AAIPB5999B';

							// $salary = '20000';

							$pancard = strtoupper($pancard);

						    $json ='{
							    "RequestHeader": {
							        "CustomerId": "7362",
							        "UserId": "STS_EREVBA",
							        "Password": "W3#QeicsB",
							        "MemberNumber": "027FP26543",
							        "SecurityCode": "3DD",
							        "ProductCode": [
							            "PCRLT"
							        ]
							    },
							    "RequestBody": {
							        "InquiryPurpose": "00",
							        "TransactionAmount": "0",
							        "FirstName": "'.$name.'",
							        "InquiryPhones": [
							            {
							                "seq": "1",
							                "Number": "'.$mobile.'",
							                "PhoneType": [
							                    "M"
							                ]
							            }
							        ],
							        "IDDetails": [
							          
							            {
							                "seq": "1",
							                "IDValue": "'.$pancard.'",
							                "IDType": "P",
							                "Source": "Inquiry"
							            }
							        ]
							       
							    },
							    "Score": [
							        {
							            "Type": "ERS",
							            "Version": "4.0"
							        }
							    ]
							}';

							  //print_r(json_decode($json));exit();

						    $curl = curl_init();
							curl_setopt_array($curl, array(
							  CURLOPT_URL => 'https://ists.equifax.co.in/cir360service/cir360report',
							  CURLOPT_RETURNTRANSFER => true,
							  CURLOPT_ENCODING => '',
							  CURLOPT_MAXREDIRS => 10,
							  CURLOPT_TIMEOUT => 0,
							  CURLOPT_FOLLOWLOCATION => false,
							  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
							  CURLOPT_CUSTOMREQUEST => 'POST',
							  CURLOPT_POSTFIELDS => $json,
							  CURLOPT_HTTPHEADER => array(
							    'Content-Type: application/json',
							    'Cookie: TS0185b412=0191ea91a4ad52be35126dea6d5a0ab139df1c7f541a2b509bab8247ddd3683e1e02e72a715a018da469bb480e3464c2073b244b84'
							  ),
							));
							$response = curl_exec($curl);
					        curl_close($curl);
					        
					        $creditBEquiReportInJson = json_encode($response);
					        $data = json_decode($response,true);

					        $score = isset($data['CCRResponse']['CIRReportDataLst'][0]['CIRReportData']['ScoreDetails'][0]['Value']) ? $data['CCRResponse']['CIRReportDataLst'][0]['CIRReportData']['ScoreDetails'][0]['Value'] : 00;
					        // $personalLoanAcc = isset($data['CCRResponse']['CIRReportDataLst'][0]['CIRReportData']['RetailAccountDetails']) ? $data['CCRResponse']['CIRReportDataLst'][0]['CIRReportData']['RetailAccountDetails'] : 00;
					        
					        if($score < 650){
					            $score = "CIBIL Score is less than 650";
					        }
					        $bureauHistoryCount = 0;
					        $totalBalance = 0;

					        // Calculate the date 6 months ago from the current date
					        $sixMonthsAgo = strtotime('-6 months');
					        $threeMonthsAgo = strtotime('-3 months');
					        $twelveMonthsAgo = strtotime('-12 months');
					        $twentyFourMonthsAgo = strtotime('-24 months');
					        $rejected = $rejected2 = $rejected3 = false;
					        $currentDate = new \DateTime();
					        // print_r($currentDate); exit();
					        $loanCount = $creditCardCount = 0;
					        $i = $j = $total_emi = 0;
					        $matchingAccounts = [];
					        $foundAccount = false;
					        $dpd24PaymentStatusesToCheck = ['SMA', 'SUB', 'DBT', 'LSS', '90+'];
					        $dpd24WillfulPaymentStatusesToCheck = ['WDF', 'SF'];
					        $availedLoan = $existingCardAcc = $dpd30 = $dpd60 = $overdue = $dpd24PayStatus = $dpd24Willful ='rejected';

					        $oldestAccountDate = isset($data['CCRResponse']['CIRReportDataLst'][0]['CIRReportData']['RetailAccountsSummary']['OldestAccount']) ? $data['CCRResponse']['CIRReportDataLst'][0]['CIRReportData']['RetailAccountsSummary']['OldestAccount'] : '';
					        
					        $matches = [];
					        if (preg_match('/(\d{2}-\d{2}-\d{4})/', $oldestAccountDate, $matches)) {
					            $dateString = $matches[1];
					            $oldestAccountDateTime = new \DateTime($dateString);

					            // print_r($oldestAccountDateTime); exit(); 
					            if ($oldestAccountDateTime > $twelveMonthsAgo) {
					                $bureauHistory = "approved";
					            } else {
					                $bureauHistory = "rejected";
					            }
					        }else{
					            $bureauHistory = "rejected";
					        }
					        if (isset($data['CCRResponse']['CIRReportDataLst']) && is_array($data['CCRResponse']['CIRReportDataLst'])) { 
						        foreach ($data['CCRResponse']['CIRReportDataLst'] as $reportData) {
						            foreach ($reportData['CIRReportData']['RetailAccountDetails'] as $accountDetail) {
						                
						                if($accountDetail['Open'] == 'Yes'){
						            
						                	$AccountType =  $accountDetail['AccountType'];
						                    $InterestRate =  $accountDetail['InterestRate'];
						                    $RepaymentTenure =  $accountDetail['RepaymentTenure'];
						                    $InstallmentAmount =  $accountDetail['InstallmentAmount'];
						                    $Balance =  $accountDetail['Balance'];
						                    
						                    if(($accountDetail['AccountType']=="Business Loan" || $accountDetail['AccountType']=="Business Loan - Unsecured" || $accountDetail['AccountType']=="Loan against Shares/Securities" || $accountDetail['AccountType']== "Two-wheeler Loan" || $accountDetail['AccountType']=="Overdraft" || $accountDetail['AccountType']=="Mudra Loans - Shishu/Kishor/Tarun" || $accountDetail['AccountType']=="Loan Against Bank Deposits") && $RepaymentTenure==""){
						                        $RepaymentTenure =  "36";    
						                    }
						                    if($accountDetail['AccountType']=="Housing Loan" && $RepaymentTenure==""){
						                        $RepaymentTenure =  "240";
						                    }
						                    if($accountDetail['AccountType']=="Auto Loan" && $RepaymentTenure==""){
						                        $RepaymentTenure =  "84";
						                    }
						                    if($accountDetail['AccountType']=="Personal Loan" && $RepaymentTenure==""){
						                        $RepaymentTenure =  "60";
						                    }
						                    if(($accountDetail['AccountType']=="Loan Against Property" || $accountDetail['AccountType']=="MicroFinance Housing Loan") && $RepaymentTenure==""){
						                        $RepaymentTenure =  "120";
						                    }
						                    if($accountDetail['AccountType']=="Property Loan" && $RepaymentTenure==""){
						                        $RepaymentTenure =  "240";
						                    }
						                    if($accountDetail['AccountType']=="Used Car Loan" && $RepaymentTenure==""){
						                        $RepaymentTenure =  "60";
						                    }
						                                              
						                    if(($accountDetail['AccountType']=="Education Loan") && $RepaymentTenure==""){
						                        $RepaymentTenure =  "96";
						                    }
						                                              
						                    if(($accountDetail['AccountType']=="Gold Loan" || $accountDetail['AccountType']=="Consumer Loan" || $accountDetail['AccountType']=="Other") && $RepaymentTenure==""){
						                        $RepaymentTenure =  "24";
						                    }
						                                                  
						                    if(($accountDetail['AccountType']=="Commercial Vehicle Loan" || $accountDetail['AccountType']=="Tractor Loan") && $RepaymentTenure==""){
						                        $RepaymentTenure =  "48";
						                    }
						                    if($accountDetail['AccountType']=="Business Loan" || $accountDetail['AccountType']=="Business Loan - Unsecured" && $InterestRate==""){
						                        $InterestRate =  "19";
						                    }
						                                      
						                    if($accountDetail['AccountType']=="Housing Loan" && $InterestRate==""){
						                        $InterestRate =  "8";
						                    }
						                                                  
						                    if($accountDetail['AccountType']=="Auto Loan" && $InterestRate==""){
						                        $InterestRate =  "7.5";
						                    }
						                                                  
						                    if($accountDetail['AccountType']=="Personal Loan" && $InterestRate==""){
						                        $InterestRate =  "11";
						                    }
						                                                  
						                    if(($accountDetail['AccountType']=="Loan Against Property" || $accountDetail['AccountType']=="Education Loan" || $accountDetail['AccountType']=="MicroFinance Housing Loan") && $InterestRate==""){
						                        $InterestRate =  "9";
						                    }
						                                                  
						                    if($accountDetail['AccountType']=="Property Loan" && $InterestRate==""){
						                        $InterestRate =  "8";
						                    }
						                                                  
						                    if($accountDetail['AccountType']=="Used Car Loan" && $InterestRate==""){
						                        $InterestRate =  "11.5";
						                    }
						                                                  
						                    if(($accountDetail['AccountType']=="Loan against Shares/Securities" || $accountDetail['AccountType']=="Loan Against Bank Deposits") && $InterestRate==""){
						                        $InterestRate =  "12";
						                    }
						                                                  
						                    if(($accountDetail['AccountType']=="Two-wheeler Loan" || $accountDetail['AccountType']=="Mudra Loans - Shishu/Kishor/Tarun") && $InterestRate==""){
						                        $InterestRate =  "15";
						                    }
						                                                  
						                    if(($accountDetail['AccountType']=="Overdraft" || $accountDetail['AccountType']=="Gold Loan") && $InterestRate==""){
						                        $InterestRate =  "18";
						                    }
						                                                  
						                    if($accountDetail['AccountType']=="Consumer Loan" && $InterestRate==""){
						                        $InterestRate =  "10";
						                    }
						                                                  
						                    if(($accountDetail['AccountType']=="Other" || $accountDetail['AccountType']=="Tractor Loan") && $InterestRate==""){
						                        $InterestRate =  "20";
						                    }
						                                                  
						                    if($accountDetail['AccountType']=="Commercial Vehicle Loan" && $InterestRate==""){
						                        $InterestRate =  "13";
						                    }
						                    
						                    $roi = $InterestRate/12/100;
						                    $pow1 = pow((1 + $roi),$RepaymentTenure);
						                    $EMI= $accountDetail['SanctionAmount'] * $roi * $pow1 / ($pow1 - 1);
						                    
						                    if((is_nan($EMI) == 1 || is_infinite($EMI)==1) && $InstallmentAmount=="" && $accountDetail['AccountType']!="Credit Card"){
						                                                        
						                        $InstallmentAmount = 0;
						                    }elseif($InstallmentAmount=="" && $accountDetail['AccountType']!="Credit Card"){
						                                                
						                        $InstallmentAmount = round($EMI);
						                                                    
						                    }elseif($InstallmentAmount!="" && $accountDetail['AccountType']=="Credit Card"){
						                                                    
						                        $InstallmentAmount = $InstallmentAmount;
						                                                    
						                    }elseif($accountDetail['AccountType']=="Credit Card" && $accountDetail['Balance']>0){
						                                                        
						                        $InstallmentAmount = round($accountDetail['Balance']*5/100);
						                                                    
						                    }elseif($accountDetail['AccountType']=="Credit Card" && $accountDetail['Balance']<=0){
						                                                        
						                        $InstallmentAmount = 0;
						                                                    
						                    }
						                    
						                    $total_emi += $InstallmentAmount;
						                    $accountType = $accountDetail['AccountType'];

						                    if ($accountType === 'Credit Card' || $accountType === 'Personal Loan') {
						                        $balance = (int)$account['Balance'];
						                
						                        if ($balance > 20000) {
						                            $matchingAccounts[] = [
						                                'AccountType' => $accountType,
						                                'Balance' => $balance,
						                            ];
						                            $existingCardAcc = 'approved';
						                        }else{
						                            $existingCardAcc = 'rejected';
						                        }
						                    }
						                    
						                    $dateOpened = new DateTime($accountDetail['DateOpened']);

						                    // Calculate the difference in months between the current date and the date the account was opened
						                    $monthsDifference = $currentDate->diff($dateOpened)->m;
						                
						                    // Check if the account was opened in the last 6 months
						                    if ($monthsDifference <= 6) {
						                        // Check if it's a credit card 
						                        if ($accountDetail['AccountType'] === 'Credit Card' && (int)$accountDetail['CreditLimit'] >= 50000) {
						                            $creditCardCount++;
						                        }
						                        // Check if it's a loan
						                        elseif ($accountDetail['AccountType'] === 'Personal Loan' && (int)$accountDetail['SanctionAmount'] >= 50000) {
						                            $loanCount++;
						                        }
						                    }
						                    
						                    // Iterate through "History48Months"
						                    foreach ($accountDetail['History48Months'] as $history) {
						                            // Assuming "SanctionAmount" is the field that represents the sanction amount
						                            $sanctionAmount = intval($history['SanctionAmount']);
						            
						                            $historyDate = strtotime($history['key']);
						            
						                            // Check if the history entry is within the last 6 months
						                            if ($historyDate >= $sixMonthsAgo) {
						                                $loanCountLast6Months++;
						                                $totalSanctionAmountLast6Months += $sanctionAmount;
						                            }
						                            
						                            
						                            // deliquency rules engin
						                            $key = $history['key'];
						                            $date = DateTime::createFromFormat('m-y', $key);
						                            $timestamp = $date->getTimestamp();
						                            
						                            if ($timestamp >= $threeMonthsAgo) {
						                                if ($history['PaymentStatus'] > 30) {
						                                    $dpd30_1 = "Application rejected for {$accountDetail['seq']}, Month: $key, PaymentStatus: {$history['PaymentStatus']}<br>";
						                                    $dpd30 = "rejected";
						                                    $rejected = true;
						                                } else {
						                                    $dpd30 = "approved";
						                                }
						                                $i++;
						                                if ($i == 3 || $rejected) {
						                                    break 2; 
						                                }
						                            }
						                    
						                            if ($timestamp >= $twelveMonthsAgo) {
						                                if ($history['PaymentStatus'] > 60) {
						                                    $dpd60_1 = "Application rejected for {$accountDetail['seq']}, Month: $key, PaymentStatus: {$history['PaymentStatus']}<br>";
						                                    $dpd60 = "rejected";
						                                    $rejected2 = true;
						                                } else {
						                                    $dpd60 = "approved";
						                                }
						                                $j++;
						                                if ($j == 12 || $rejected2) {
						                                    break 2; 
						                                }
						                            }
						                            
						                            if ($timestamp >= $twentyFourMonthsAgo) {
						                                if (($history['PaymentStatus'] == 'WDF') || ($history['PaymentStatus'] == 'SF') ) {
						                                    $dpd24Willful = "rejected";
						                                    $rejected3 = true;
						                                } else {
						                                    $dpd24Willful = "approved";
						                                }
						                                $j++;
						                                if ($j == 24 || $rejected3) {
						                                    break 2; 
						                                }
						                            }
						                            
						                        $totalPastDue = (int) $history['TotalPastDue']; 
						    
						                        if ($totalPastDue >= 10000) {
						                            $overdueFound = true;
						                            break 2; 
						                        }
						                        
						            
						                    }
						                    
						                    $last24MonthsHistory = array_slice($accountDetail['History48Months'], -24);

						                    foreach ($last24MonthsHistory as $historyEntry) {
						                        $paymentStatus = $historyEntry['PaymentStatus'];
						                
						                        if (in_array($paymentStatus, $dpd24WillfulPaymentStatusesToCheck)) {
						                            $dpd24WillfulFoundAccount = true;
						                            break 2; 
						                        }
						                    }
						                    
						                    $last24MonthsHistory = array_slice($accountDetail['History48Months'], -24);

						                    foreach ($last24MonthsHistory as $historyEntry) {
						                        $paymentStatus = $historyEntry['PaymentStatus'];
						                
						                        if (in_array($paymentStatus, $dpd24PaymentStatusesToCheck)) {
						                            $foundAccount = true;
						                            break 2; 
						                        }
						                    }
						                }
						            }
						        }

						        if ($foundAccount) {
						            $dpd24PayStatus = "rejected";
						        } else {
						            $dpd24PayStatus = "approved";
						        }
						        
						        if ($dpd24WillfulFoundAccount) {
						            $dpd24Willful = "rejected";
						        } else {
						            $dpd24Willful = "approved";
						        }
						        
						        if ($totalBalance > 20000) {
						            $existingCardAcc = 'There are existing Credit Card and Personal Loan accounts with a total balance greater than 20,000.';
						        } else {
						            $existingCardAcc = 'No existing Credit Card or Personal Loan accounts with a total balance greater than 20,000 found.';
						        }
						        
						        if ($loanCount > 2 || $creditCardCount > 2) {
						            $availedLoan = "rejected";
						        } else {
						            $availedLoan = "approved";
						        }

						        
						        if ($overdueFound) {
						            $overdue = "rejected";
						        } else {
						            $overdue = "approved";
						        }
						    }
					        
					        // FOIR rules
					        if($monthly_income > 20000){
					            if ($monthly_income >= 20000 && $monthly_income <= 30000) {
					                $foir = 40; // FOIR is 40%
					            } else {
					                $foir = 55; // FOIR is 55%
					            }

					            $foir_amt = ($monthly_income * $foir) / 100;
            					$dis_income = $foir_amt-$total_emi;

					        }else{
					            $foir_amt = 'rejected';
					        }
					        
					        
					        
					        if(($score < 650) && ($bureauHistory == 'approved') && ($availedLoan == 'approved') && ($dpd24PayStatus == 'approved') ){
					            $msg = 'Credit Bureau Equifex Report :- You are eligible for a loan.';
					        }else{
					            $msg = 'Credit Bureau Equifex Report :- You are not eligible for a loan.';
					        }

					        $secondRuleEngine = [
					            'msg' => $msg,
					            'cibilScore' => $score,
					            'bureauHistory' => $bureauHistory,
					            'availedLoan' => $availedLoan,
					            'existingCardAcc' => $existingCardAcc,
					            'status' => 200,
					            'equifexJsonData' => $creditBEquiReportInJson,
					        ];	
					        
					        if(($dpd30 != "approved") || ($dpd60 != 'approved') || ($overdue != 'approved')){
					            
					            $msg = 'Deliquency Equifex Report :- You are not eligible for a loan.';
					        }else{
					            $msg = 'Deliquency Equifex Report :- You are eligible for a loan.';
					        }
					        
					        $thirdRuleEngine = [
					            'msg' => $msg,
					            'dpd30' => $dpd30,
					            'dpd60' => $dpd60,
					            'overdue' => $overdue,
					            'dpd24PayStatus' => $dpd24PayStatus,
					            'dpd24Willful' => $dpd24Willful,
					            'status' => 200,
					        ];
					        
					        $forthRuleEngine = [
								'foirAmount' => $foir_amt,
								'disIncome' => $dis_income,
								'totalEMI' => $total_emi,
								'status' => 200,
							];


			        	}

					}

					if ($demographicFilterResponse || $secondRuleEngine || $thirdRuleEngine || $forthRuleEngine) {
						return response([
					        'msg' => "Data found!",
					        'demographicFilterResponse' => $demographicFilterResponse,
					        'creditBureauRules' => $secondRuleEngine,
					        'deliquencyRules' => $thirdRuleEngine,
					        'foirRules' => $forthRuleEngine,
					        'status' => 200,
			        	], 200);	
					} else{
						return response([
					        'msg' => "Data Not found!",
					        'status' => 201,
			        	], 201);	
					}
		    }
      	}else{
      		return response([
				'msg' => "Bank not found!",
				'status' => 201,
		    ], 201);
      	}
	        
    }


}
