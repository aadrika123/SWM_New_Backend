<?php

namespace App\Repository;

use App\Models\Consumer;
use App\Models\Demand;
use App\Models\Apartment;
use App\Models\ConsumerType;
use App\Models\ConsumerCategory;
use App\Models\ConsumerDeactivateDeatils;
use App\Models\ConsumerEditLog;
use App\Models\Transaction;
use App\Models\TransactionDetails;
use App\Models\TransactionDeactivate;
use App\Models\GeoLocation;
use App\Models\CosumerReminder;
use App\Models\Collections;
use App\Models\TransactionVerification;
use App\Models\TransactionModeChange;
use App\Models\BankCancel;
use App\Models\BankCancelDetails;
use App\Models\PaymentDeny;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\Api\Helpers;
use PhpOption\None;
use Carbon\Carbon;

/**
 * | Created On-08-09-2022 
 * | Created By-
 * | Created For- Consumer related api 
 */
class ConsumerRepository
{

    use Helpers;

    protected $dbConn;
    protected $Consumer;
    protected $Demand;
    protected $Apartment;
    protected $ConsumerType;
    protected $ConsumerCategory;
    protected $ConsumerDeactivateDeatils;
    protected $Transaction;
    protected $TransactionDetails;
    protected $TransactionDeactivate;
    protected $GeoLocation;
    protected $CosumerReminder;
    protected $Collections;
    protected $TransactionVerification;
    protected $BankCancel;
    protected $BankCancelDetails;
    protected $PaymentDeny;
    protected $TransactionModeChange;
    protected $ConsumerEditLog;

    public function __construct(Request $request)
    {
        $this->dbConn = $this->GetSchema($request->bearerToken());

        $this->Consumer = new Consumer($this->dbConn);
        $this->Demand = new Demand($this->dbConn);
        $this->Apartment = new Apartment($this->dbConn);
        $this->ConsumerType = new ConsumerType($this->dbConn);
        $this->ConsumerCategory = new ConsumerCategory($this->dbConn);
        $this->ConsumerDeactivateDeatils = new ConsumerDeactivateDeatils($this->dbConn);
        $this->Transaction = new Transaction($this->dbConn);
        $this->TransactionDetails = new TransactionDetails($this->dbConn);
        $this->TransactionDeactivate = new TransactionDeactivate($this->dbConn);
        $this->GeoLocation = new GeoLocation($this->dbConn);
        $this->CosumerReminder = new CosumerReminder($this->dbConn);
        $this->Collections = new Collections($this->dbConn);
        $this->TransactionVerification = new TransactionVerification($this->dbConn);
        $this->BankCancel = new BankCancel($this->dbConn);
        $this->BankCancelDetails = new BankCancelDetails($this->dbConn);
        $this->PaymentDeny = new PaymentDeny($this->dbConn);
        $this->TransactionModeChange = new TransactionModeChange($this->dbConn);
        $this->ConsumerEditLog = new ConsumerEditLog($this->dbConn);
    }

    public function ConsumerList(Request $request)
    {
        //echo $userId= $request->user()->id;
        try
        {   
            $dbConn = $this->GetSchema($request->bearerToken());
            $conArr = array();
            if(isset($request->id) || isset($request->consumerNo) || isset($request->consumerName) || isset($request->mobileNo))
            {
                if(isset($request->id))
                {
                    $field = 'tbl_consumer.id';
                    $operator = '=';
                    $value = $request->id;
                }

                if(isset($request->consumerNo))
                {
                    $field = 'consumer_no';
                    $operator = '=';
                    $value = $request->consumerNo;
                }

                if(isset($request->consumerName))
                {
                    $field = 'tbl_consumer.name';
                    $operator = 'like';
                    $value = '%'.$request->consumerName.'%';
                }

                if(isset($request->mobileNo))
                {
                    $field = 'mobile_no';
                    $operator = '=';
                    $value = $request->mobileNo;
                }
                
                $consumerList = $this->Consumer->join('tbl_consumer_category', 'tbl_consumer.consumer_category_id', '=', 'tbl_consumer_category.id')
                                ->join('tbl_consumer_type', 'tbl_consumer.consumer_type_id', '=', 'tbl_consumer_type.id')
                                ->select(DB::raw('tbl_consumer.*, tbl_consumer_category.name as category, tbl_consumer_type.name as type'));
                if(isset($request->wardNo)) 
                    $consumerList = $consumerList->where('ward_no', $request->wardNo);
                $consumerList = $consumerList->where($field, $operator, $value)
                                ->paginate(
                                    $perPage = 1000, $columns = ['*'], $pageName = 'consumers'
                                );
                
                //echo "<pre/>";print_r($consumerList);
                foreach($consumerList as $consumer)
                {
                    $demand = $this->Demand->where('consumer_id', $consumer->id)
                                ->where('paid_status', 0)
                                ->where('deactivate_status', 0)
                                ->orderBy('id', 'asc')
                                ->get();
                    $total_tax = 0.00;
                    $demand_upto = '';
                    $paid_status = 'Paid';
                    $monthlyDemand = 0;
                    $demand_form = '';
                    $i = 0;
                    foreach($demand as $dmd)
                    {
                        if($i == 0)
                            $demand_form = date('d-m-Y', strtotime($dmd->payment_from));
                        $i++;
                        $demand_upto = date('d-m-Y', strtotime($dmd->payment_to));
                        $monthlyDemand = $dmd->total_tax;
                        $total_tax += $dmd->total_tax;
                        $paid_status = 'Unpaid';
                    }
                    //
                    
                    $con['id'] = $consumer->id;
                    $con['wardNo'] = $consumer->ward_no;
                    $con['holdingNo'] = $consumer->holding_no;
                    $con['consumerName'] = $consumer->name;
                    $con['apartmentId'] = $consumer->apt_mstr_id;
                    $con['consumerNo'] = $consumer->consumer_no;
                    $con['Address'] = $consumer->address;
                    $con['ps'] = $consumer->police_station;
                    $con['landmark'] = $consumer->landmark;
                    $con['houseNo'] = $consumer->house_no;
                    $con['pinCode'] = $consumer->pincode;
                    $con['locality'] = $consumer->locality;
                    $con['cansumerCategory'] = $consumer->category;
                    $con['cansumerType'] = $consumer->type;
                    $con['mobileNo'] = $consumer->mobile_no;
                    $con['activeDemandDetails'] = $demand;
                    $con['monthlyDemand'] = $monthlyDemand;
                    $con['totalDemand'] = $total_tax;
                    $con['demandFrom'] = $demand_form;
                    $con['demandUpto'] = $demand_upto;
                    $con['paidStatus'] = $paid_status;
                    $con['applyBy'] = ($consumer->user_id)?$this->GetUserDetails($consumer->user_id)->name:'';
                    $con['applyDate'] = date("d-m-Y", strtotime($consumer->entry_date));
                    $con['status'] = ($consumer->deactivate_status == 0)?'Active':'Deactive';

                    $conArr[] = $con;

                }
                return response()->json(['status'=> True, 'data'=>$conArr, 'msg'=> ''], 200);
            }else{
                return response()->json(['status'=> False, 'data'=>$conArr, 'msg'=> 'Undefined parameter supply'], 200);
            }
            
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function ApartmentList(Request $request)
    {

        try
        {   

            $conArr = array();
            
            if(isset($request->apartmentId) || isset($request->apartmentName))
            {
                if(isset($request->apartmentId))
                {
                    $field = 'id';
                    $operator = '=';
                    $value = $request->apartmentId;
                }

                if(isset($request->apartmentName))
                {
                    $field = 'apt_name';
                    $operator = 'like';
                    $value = '%'.$request->apartmentName.'%';
                }

                $apartmentList = $this->Apartment->where($field, $operator, $value)
                                    ->orderBy('apt_name', 'ASC')
                                    ->paginate(100);
                
                foreach($apartmentList as $apartment)
                {

                    // $demand = DB::connection($this->schema)->table('tbl_demand')
                    //             ->select(DB::raw('consumer_id,sum(total_tax) as total_tax,paid_status,tbl_demand.deactivate_status,max(demand_date) as demand_upto'))
                    //             ->join('tbl_consumer', 'tbl_demand.id', '=', 'tbl_demand.consumer_id')
                    //             ->where('tbl_consumer.apt_mstr_id', $apartment->id)
                    //             ->where('paid_status', 0)
                    //             ->where('tbl_demand.deactivate_status', 0)
                    //             ->groupByRaw('consumer_id,paid_status,deactivate_status,tbl_consumer.deactivate_status')
                    //             ->first();
                    
                    $demand = $this->GetDemand($this->dbConn, $apartment->id, 'Apartment');
                    $con['id'] = $apartment->id;
                    $con['wardNo'] = $apartment->ward_no;
                    $con['apartmentName'] = $apartment->apt_name;
                    $con['apartmentCode'] = $apartment->apt_code;
                    $con['address'] = $apartment->apt_address;
                    $con['mobileNo'] = $apartment->mobile_no;
                    $con['totalDemand'] = ($demand)?$demand['demandAmt']:'0.00';
                    $con['demandFrom'] = ($demand)?$demand['demandFrom']:'';
                    $con['demandUpto'] = ($demand)?$demand['demandUpto']:'';
                    $con['paidStatus'] = ($demand)?'Unpaid':'Paid';
                    $con['status'] = ($apartment->deactive_status == 0)?'Active':'Deactive';


                    $conArr[] = $con;

                }
                return response()->json(['status'=> True, 'data'=>$conArr, 'msg'=> ''], 200);
            }else{
                return response()->json(['status'=> False, 'data'=>$conArr, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function getApartmentDetails(Request $request)
    {

        try
        {   
            
            $conArr = array();
            if(isset($request->id))
            {

                $apartments = $this->Apartment->leftJoin('tbl_consumer as c', 'tbl_apt_details_mstr.id', '=', 'c.apt_mstr_id')
                                    ->select(DB::raw('tbl_apt_details_mstr.*, c.id as consumer_id, c.police_station, c.landmark,  c.house_no, c.pincode, c.locality, c.name as consumer_name, c.mobile_no as contactno, c.holding_no, c.consumer_no'))
                                    ->where('tbl_apt_details_mstr.id', $request->id)
                                    ->where('c.deactivate_status', 0)
                                    ->get();
                $apt_tot_tax = 0;
                $aptmonthlyDemand = 0;
                foreach($apartments as $apartment)
                {

                    $demand = $this->Demand->where('consumer_id', $apartment->consumer_id)
                                ->where('paid_status', 0)
                                ->where('deactivate_status', 0)
                                ->get();
                                
                    $total_tax = 0.00;
                    $demand_upto = '';
                    $paid_status = 'True';
                    $monthlyDemand = 0;
                    $demand_form = ''; 
                    $i = 0;     
                    foreach($demand as $dmd)
                    {
                        if($i == 0)
                            $demand_form = date('d-m-Y', strtotime($dmd->payment_from));
                        $i++;
                        $total_tax += $dmd->total_tax;
                        $demand_upto = date('d-m-Y', strtotime($dmd->payment_to));
                        $paid_status = 'False';
                        $monthlyDemand = $dmd->total_tax;
                    }

                    $apt_tot_tax += $total_tax;
                    $aptmonthlyDemand += $monthlyDemand;
                    $con['id'] = $apartment->id;
                    $con['wardNo'] = $apartment->ward_no;
                    $con['apartmentName'] = $apartment->apt_name;
                    $con['apartmentCode'] = $apartment->apt_code;
                    $con['consumerId'] = $apartment->consumer_id;
                    $con['consumerName'] = $apartment->consumer_name;
                    $con['consumerNo'] = $apartment->consumer_no;
                    $con['consumerMobileNo'] = $apartment->contactno;
                    $con['holdingNo'] = $apartment->holding_no;
                    $con['address'] = $apartment->apt_address;
                    $con['mobileNo'] = $apartment->mobile_no;
                    $con['ps'] = $apartment->police_station;
                    $con['landmark'] = $apartment->landmark;
                    $con['houseNo'] = $apartment->house_no;
                    $con['pinCode'] = $apartment->pincode;
                    $con['locality'] = $apartment->locality;
                    $con['activeDemandDetails'] = $demand;
                    $con['monthlyDemand'] = $monthlyDemand;
                    $con['totaldemand'] = $total_tax;
                    $con['demandFrom'] = $demand_form;
                    $con['demandUpto'] = $demand_upto;
                    $con['paidStatus'] = $paid_status;
                    $con['applyBy'] = ($apartment->user_id)?$this->GetUserDetails($apartment->user_id)->name:'';
                    $con['applyDate'] = date("d-m-Y", strtotime($apartment->entry_date));


                    $conArr[] = $con;
                }

                return response()->json(['status'=> True, 'data'=>$conArr, 'totalAptDemand'=> $apt_tot_tax, 'totalAptMonthlyDemand'=>$aptmonthlyDemand, 'msg'=> ''], 200);
            }
            else{
                return response()->json(['status'=> False, 'data'=>$conArr, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function ConsumerAdd(Request $request)
    {
        
        try {

            $validator = Validator::make($request->all(), [
                'consumerName' => 'required',
                'wardNo' => 'required',
                'holdingNo' => 'required',
                'mobileNo' => 'required',
                'ps' => 'required',
                'landmark' => 'required',
                'address' => 'required',
                'locality' => 'required',
                'pinCode' => 'required',
                'consumerCategory' => 'required',
                'consumerType' => 'required',
                'demandFrom' => 'required',
            ]);
            if ($validator->fails()) {    
                return response()->json(['status'=> False, 'msg' => $validator->messages()]);
            }

            $apartId = Null;
            $apartCode = '';
            $apartCount = 0;
            $consumerNo = '';
            $apartName = '';
            $userId= $request->user()->id;
            
            
           
            $consumer = $this->Consumer;
            $consumer->ward_no = $request->wardNo;
            $consumer->holding_no = $request->holdingNo;
            $consumer->name = $request->consumerName;
            $consumer->mobile_no = $request->mobileNo;
            $consumer->owner_id = 0;
            $consumer->police_station = $request->ps;
            $consumer->landmark = $request->landmark;
            $consumer->house_no = $request->houseNo;
            $consumer->address = $request->address;
            $consumer->locality = $request->locality;
            $consumer->pincode = $request->pinCode;
            $consumer->consumer_category_id = $request->consumerCategory;
            $consumer->consumer_type_id = $request->consumerType;
            $consumer->user_id = $userId;
            $consumer->entry_date = date('Y-m-d');
            $consumer->creation_date = date('Y-m-d');
            $consumer->created_by = $userId;
            $consumer->date_time = date('Y-m-d H:i:s');
            $consumer->deactivate_status = 0;
            $consumer->save();


            $consumerUpdate = $this->Consumer->find($consumer->id);
            
            if(isset($request->apartmentId))
            {

                $apart = $this->Apartment->select('apt_code', 'apt_name')->where('id', $request->apartmentId)->first();
                
                $getConsum = $this->Consumer->select('consumer_no')->where('apt_mstr_id', $request->apartmentId);
                $oldConsumerNo = $getConsum->first();
                $apartId = $request->apartmentId;
                $apartCode = $apart->apt_code;
                $apartName = $apart->apt_name;
                if($getConsum->count()> 0)
                {
                    $apartCount = $getConsum->count()+1;
                    $consumerNo = substr($oldConsumerNo->consumer_no, 0, 10).str_pad($apartCount, 5, "0", STR_PAD_LEFT);
                }else{
                    $serialNo='0001';
                    $wardCreated= str_pad($request->wardNo, 2, "0", STR_PAD_LEFT);
                    $consumerTypeCreated= str_pad($request->consumerType, 2, "0", STR_PAD_LEFT);
                    $randCreated= str_pad($consumer->id, 5, "0", STR_PAD_LEFT);
                    
                    $consumerNo = $wardCreated.$request->consumerCategory.$consumerTypeCreated.$randCreated.$serialNo;
                }
            }

            if((!isset($request->apartmentId) || empty($request->apartmentId)) || $apartCount == 1)
            {
                $serialNo='0001';
                $wardCreated= str_pad($request->wardNo, 2, "0", STR_PAD_LEFT);
                $consumerTypeCreated= str_pad($request->consumerType, 2, "0", STR_PAD_LEFT);
                $randCreated= str_pad($consumer->id, 5, "0", STR_PAD_LEFT);
                
                $consumerNo = $wardCreated.$request->consumerCategory.$consumerTypeCreated.$randCreated.$serialNo;
            }
            $consumerUpdate->apt_code = $apartCode;
            $consumerUpdate->apt_mstr_id = $apartId;
            $consumerUpdate->consumer_no = $consumerNo ;
            $consumerUpdate->entry_type = 1;
            $consumerUpdate->save();


            $consumerType = $this->ConsumerType->select('rate')
                            ->where('id', $request->consumerType)
                            ->first();
            //Generate Demand
            $demand = $this->GenerateDemand($this->dbConn, $consumer->id, $consumerType->rate, $request->demandFrom, $userId);
            //
            

            $response = array();
            $response['consumerId'] = $consumer->id;
            $response['wardNo'] = $request->wardNo;
            $response['apartmentId'] = $request->apartmentId;
            $response['holdingNo'] = $request->holdingNo;
            $response['consumerNo'] = $consumerNo;
            $response['consumerName'] = $request->consumerName;
            $response['apartmentName'] = $apartName;
            $response['mobileNo'] = $request->mobileNo;
            $response['ps'] = $request->ps;
            $response['landmark'] = $request->landmark;
            $response['houseNo'] = $request->houseNo;
            $response['address'] = $request->address;
            $response['locality'] = $request->locality;
            $response['pinCode'] = $request->pinCode;
            $response['consumerCategory'] = $this->ConsumerCategory->select('name')->first()->name;
            $response['consumerType'] = $consumerType->name;
            $response['demandFrom'] = $request->demandFrom;
            $response['demandDetails'] = $demand;
            $response['appliedBy'] = $userId;
            $response['appliedDate'] = date('Y-m-d');

            return response()->json(['status' => true, 'data' => $response, 'msg' => "Consumer created and demand generated successfully"], 200);
        } catch (Exception $e) {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }


    public function RenterFormData(Request $request)
    {

        try
        {   

            $response = array();

            if(isset($request->consumerId))
            {

                $getconsumer = $this->Consumer->where('id', $request->consumerId)
                                    ->first();

                $response['wardNo'] = $getconsumer->ward_no;
                $response['ownerName'] = $getconsumer->name;
                $response['holdingNo'] = $getconsumer->holding_no;
                $response['consumerCategoryList'] = $this->ConsumerCategory->get();


                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function EditConsumerDetailsById(Request $request)
    {
        
        try
        {   
            $response = array();
            if(isset($request->id))
            {
                
                $response = (array)$this->ConsumerList($request)->original['data'][0];
                
                if(count($response)> 0 && !empty($response['apartmentId']))
                {
                    $apart = $this->Apartment->select('apt_code', 'apt_name')
                                ->where('id', $response['apartmentId'])
                                ->first();
                    
                    $response['apartmentName'] = $apart->apt_name;
                    $response['apartmentCode'] = $apart->apt_code;
                }
                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }



    public function DeactivateConsumer(Request $request)
    {
        $userId= $request->user()->id;
        try
        {   
            $response = array();
            if(isset($request->consumerId))
            {
                
                $consDtls = $this->ConsumerDeactivateDeatils;
                $consDtls->consumer_id = $request->consumerId;
                $consDtls->remarks = ($request->remarks)?$request->remarks:"";
                $consDtls->deactivated_by = $userId;
                $consDtls->deactivation_date = date('Y-m-d');
                $consDtls->ip_address = $request->ip();
                $consDtls->timestamp = date('Y-m-d H:i:s');
                $consDtls->save();
                
                if($consDtls->id > 0)
                {
                    $consumer = $this->Consumer;
                    $consumer = $consumer->find($request->consumerId);
                    $consumer->deactivate_status = 1;
                    $consumer->save();

                    $this->Demand->where('consumer_id', $request->consumerId)
                            ->where('paid_status', 0)
                            ->update(['deactivate_status'=> 1]);

                    return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Deactivated Successfully'], 200);
                }else{
                    return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Deactivate issue, please check'], 200);
                }
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function PaymentData(Request $request)
    {
        try
        {   
            $response = array();
            if(isset($request->consumerId))
            {

                $demand = $this->Demand->where('consumer_id', $request->consumerId)
                                ->where('paid_status', 0)
                                ->where('deactivate_status', 0)
                                ->orderBy('id', 'asc')
                                ->get();
                
                $totalDmd = 0;
                $paymentUptoMonth = '';
                foreach($demand as $dmd)
                {
                    $totalDmd += $dmd->total_tax;
                    $paymentUptoMonth = date('M', strtotime($dmd->payment_to));
                }
                $response['demand'] = $demand;
                $response['totaldemand'] = $totalDmd;
                $response['paymentUptoMonth'] = $paymentUptoMonth;
                

                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function CalculatedAmount(Request $request)
    {
        try
        {   
            $response = array();
            if((isset($request->consumerId) || isset($request->apartmentId)) && isset($request->payUpto))
            {

                $demand = $this->Demand;
                
                if(isset($request->apartmentId))
                {
                    $demand = $demand->join('tbl_consumer as c', 'tbl_demand.consumer_id', '=', 'c.id')
                                    ->where('c.apt_mstr_id', $request->apartmentId);
                }else{
                    $demand = $demand->where('consumer_id', $request->consumerId);
                }
                $demand = $demand->where('paid_status', 0)
                                ->where('tbl_demand.deactivate_status', 0)
                                ->whereDate('tbl_demand.payment_to', '<=', $request->payUpto)
                                ->orderBy('tbl_demand.id', 'asc')
                                ->sum('total_tax');
                
                $totalDmd = $demand;
                $paymentUptoDate = date('Y-m-t', strtotime($request->payUpto));
                
                $response['totaldemand'] = $totalDmd;
                $response['paymentUptoDate'] = $paymentUptoDate;
                

                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    // public function DashboardData(Request $request)
    // {
    //     try
    //     {   
    //         $response = array();

    //         $Consumer = Consumer::query();
    //         $totalDmd = Demand::query()->where('paid_status', 0)
    //                             ->where('deactivate_status', 0);
    //         $Collection = Transaction::query()->select('pad_status', 'transaction_date', 'total_payable_amt')
    //                                             ->leftjoin('tbl_transaction_deactivate', 'tbl_transaction_deactivate.transaction_id', '=', 'tbl_transaction.id')
    //                                             ->whereNull('transaction_id');
            
    //         if(isset($request->month) && isset($request->year))
    //         {
    //             $From = $request->year.'-'.$request->month.'-01';
    //             $Upto = date('Y-m-t', strtotime($From));
    //             $Consumer = $Consumer->whereBetween('entry_date', [$From, $Upto]);
    //             $totalDmd = $totalDmd->whereBetween('demand_date', [$From, $Upto]);
    //             $Collection = $Collection->whereBetween('transaction_date', [$From, $Upto]);
    //         }

    //         $Consumer = $Consumer->get();
    //         $TotalConsumer = $Consumer->count();
    //         $totalDmd = $totalDmd->sum('total_tax');

    //         $Collection = $Collection->get();
    //         $totalCollection = 0;
    //         $pendingCollection = 0;
    //         $totalResidential = 0;
    //         $totalcom1 = 0;
    //         $totalcom2 = 0;
            
    //         foreach($Collection as $coll)
    //             if($coll->pad_status != 0 )
    //             {
    //                 $totalCollection += $coll->total_payable_amt;
    //             }else{
    //                 $pendingCollection += $coll->total_payable_amt;
    //             }

    //         foreach($Consumer as $con)
    //         {
    //             if($con->consumer_category_id == 1)
    //                 $totalResidential += 1;
    //             if($con->consumer_category_id == 2)
    //                 $totalcom1 += 1;
    //             if($con->consumer_category_id == 3)
    //                 $totalcom2 += 1;
    //         }


    //         $response['totalDemand'] = $totalDmd;
    //         $response['totalConsumer'] = $TotalConsumer;
    //         $response['totalCollection'] = $totalCollection;
    //         $response['pendingCollection'] = $pendingCollection;
    //         $response['totalResidenstialConsumer'] = $totalResidential;
    //         $response['totalCommercial1Consumer'] = $totalcom1;
    //         $response['totalCommercial2Consumer'] = $totalcom2;
            

    //         return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
    //     } 
    //     catch (Exception $e) 
    //     {
    //         return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
    //     }
        
    // }

    public function DashboardData(Request $request)
    {
        try
        {   
            $response = array();

            if(isset($request->fromDate) && isset($request->toDate))
            {

                $From = date('Y-m-d', strtotime($request->fromDate));
                $Upto = date('Y-m-d', strtotime($request->toDate));
                //$Consumer = Consumer::whereBetween('entry_date', [$From, $Upto])->get();
                $sql = "SELECT * from tbl_consumer where entry_date between '$From' and '$Upto'";
                
                $Consumer = DB::connection($this->dbConn)->select($sql);

                $totalDmd = $this->Demand->where('paid_status', 0)
                                    ->where('deactivate_status', 0)
                                    ->whereBetween('demand_date', [$From, $Upto])
                                    ->sum('total_tax');

                $Collection = $this->Transaction->select('pad_status', 'transaction_date', 'total_payable_amt')
                                                    ->leftjoin('tbl_transaction_deactivate', 'tbl_transaction_deactivate.transaction_id', '=', 'tbl_transaction.id')
                                                    ->whereNull('transaction_id')
                                                    ->whereBetween('transaction_date', [$From, $Upto])->get();
                
            

                $TotalConsumer = 0;

                $totalCollection = 0;
                $pendingCollection = 0;
                $totalResidential = 0;
                $totalcom1 = 0;
                $totalcom2 = 0;
                
                foreach($Collection as $coll)
                {
                    if($coll->pad_status != 0 )
                    {
                        $totalCollection += $coll->total_payable_amt;
                    }else{
                        $pendingCollection += $coll->total_payable_amt;
                    }
                }

                foreach($Consumer as $con)
                {
                    $TotalConsumer += 1;
                    if($con->consumer_category_id == 1)
                        $totalResidential += 1;
                    if($con->consumer_category_id == 2)
                        $totalcom1 += 1;
                    if($con->consumer_category_id == 3)
                        $totalcom2 += 1;
                }


                $response['totalDemand'] = $totalDmd;
                $response['totalConsumer'] = $TotalConsumer;
                $response['totalCollection'] = $totalCollection;
                $response['pendingCollection'] = $pendingCollection;
                $response['totalResidenstialConsumer'] = $totalResidential;
                $response['totalCommercial1Consumer'] = $totalcom1;
                $response['totalCommercial2Consumer'] = $totalcom2;
            }

            return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }


    public function GetTrancation(Request $request)
    {

        try
        {   

            $response = array();
            if(isset($request->transactionNo))
            {
                $sql = "SELECT t.*,c.name as consumer_name,consumer_no,a.apt_code,a.apt_name,u.name as transaction_by,
                c.ward_no, c.holding_no, c.address,c.apt_mstr_id,u.contactno
                FROM tbl_transaction t
                LEFT JOIN tbl_consumer c on t.consumer_id=c.id
                LEFT JOIN tbl_apt_details_mstr a on t.apt_mstr_id=a.id
                JOIN db_master.view_user_mstr u on t.user_id=u.id
                WHERE t.transaction_no ='".$request->transactionNo."'";
                
                
                $tran = DB::connection($this->dbConn)->select($sql);

                if($tran)
                {
                    
                    $tran = $tran[0];
                    $dmddtl = $this->GetMonthlyFee($this->dbConn, $tran->consumer_id, 'Consumer');
                    $response['transactionNo'] = $tran->transaction_no;
                    $response['transactionDate'] = date('d-m-Y', strtotime($tran->transaction_date));
                    $response['transactionAmount'] = $tran->total_payable_amt;
                    $response['transactionBy'] = $tran->transaction_by;
                    $response['consumerNo'] = $tran->consumer_no;
                    $response['consumerName'] = $tran->consumer_name;
                    $response['apartmentCode'] = $tran->apt_code;
                    $response['apartmentName'] = $tran->apt_name;
                    $response['wardNo'] = $tran->ward_no;
                    $response['holdingNo'] = $tran->holding_no;
                    $response['address'] = $tran->address;
                    $response['totalDemand'] = $tran->total_demand_amt;
                    $response['remainingAmount'] = $tran->total_remaining_amt;
                    $response['paidStatus'] = ($tran->pad_status == 1)? "Paid":"Pending";
                    $response['paymentMode'] = $tran->payment_mode;
                    $response['tcName'] = $tran->transaction_by;
                    $response['tcMobileNo'] = $tran->contactno;
                    $response['monthlyFee'] = $dmddtl['monthlyFee'];
                    $response['paymentTill'] = $dmddtl['paymentTill'];
                }

                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }


    public function TransactionDeactivate(Request $request)
    {
        
        try
        {   
            
            $userId= $request->user()->id;
            $status = '';
            $data = '';
            $msg = '';
            
            $validator = Validator::make($request->all(), [
                'transactionNo' => 'required',
                'receiptFile' => 'mimes:jpeg,png,jpg,png,pdf|max:1024',
                'remarks' => 'required'
            ]);
            
            if ($validator->fails()) {    
                return response()->json(['status'=> False, 'msg' => $validator->messages()]);
            }
            
            if(isset($request->transactionNo))
            {

                $tran = $this->Transaction->select('id', 'pad_status', 'payment_mode')
                                    ->where('transaction_no', $request->transactionNo)
                                    ->where('pad_status', '!=', 0)
                                    ->first();
                
                if($tran)
                {
                    if($tran->payment_mode != "Cash" && $tran->pad_status != 2)
                    {
                        $status = True;
                        $data = '';
                        $msg = "Transaction Cant be deactivate because of Tranction No.".$request->transactionNo. "Cleared fom bank end.";
                        return response()->json(['status'=> True,  'msg'=> $msg], 200);
                    }
                    else
                    {
                        $filePath = '';
                        if(!empty($request->receiptFile))
                        {
                            $filePath = md5($request->transactionNo).'.'.$request->receiptFile->extension();
                            $request->receiptFile->move(public_path('uploads'), $filePath);
                        }

                        $transDeactivate = $this->TransactionDeactivate;
                        $transDeactivate->transaction_id = $tran->id;
                        $transDeactivate->date = date('Y-m-d');
                        $transDeactivate->remarks = ($request->remarks)?$request->remarks:"";
                        $transDeactivate->img_path = $filePath;
                        $transDeactivate->stampdate = date('Y-m-d H:i:s');
                        $transDeactivate->user_id = $userId;
                        $transDeactivate->ip_address = $request->ip();
                        $transDeactivate->save();
                        
                        if($transDeactivate->id > 0)
                        {
                            $tran->pad_status = 0;
                            $tran->save();
                        }
                        
                        $status = True;
                        $msg = "Deactivated Successfully";
                    }
                }
                else
                {
                    $status = False;
                    $msg = "Transaction No. not found";
                }
                
            }else{
                $status = False;
                $msg = "Undefined parameter supply";
            }
            return response()->json(['status'=> $status, 'data'=>$data, 'msg'=> $msg], 200);
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function AddRenter(Request $request)
    {
        
        try {

            $validator = Validator::make($request->all(), [
                'consumerId' => 'required',
                'consumerName' => 'required',
                'wardNo' => 'required',
                'holdingNo' => 'required',
                'mobileNo' => 'required',
                'ps' => 'required',
                'landmark' => 'required',
                'address' => 'required',
                'locality' => 'required',
                'pinCode' => 'required',
                'consumerCategory' => 'required',
                'consumerType' => 'required',
                'demandFrom' => 'required',
            ]);
            if ($validator->fails()) {    
                return response()->json(['status'=> False, 'msg' => $validator->messages()]);
            }


            $apartId = Null;
            $apartName = '';
            $apartCode = '';
            $userId= $request->user()->id;
            $response = array();

            $getConsumer = $this->Consumer->where('name', $request->consumerName)
                                    ->where('mobile_no', $request->mobileNo)
                                    ->where('consumer_category_id', $request->consumerCategory)
                                    ->where('consumer_type_id', $request->consumerType)
                                    ->where('ward_no', $request->wardNo)
                                    ->where('deactivate_status', 0)
                                    ->get();
            
            if($getConsumer->count() == 0)
            {

                if(isset($request->apartmentId))
                {
                    $apart = $this->Apartment->select('apt_code', 'apt_name')->where('id', $request->apartmentId)->first();
                    $apartId = $request->apartmentId;
                    $apartCode = $apart->apt_code;
                    $apartName = $apart->apt_name;
                }

            
                $consumer = $this->Consumer;
                $consumer->ward_no = $request->wardNo;
                $consumer->apt_mstr_id = $apartId;
                $consumer->apt_code = $apartCode;
                $consumer->holding_no = $request->holdingNo;
                $consumer->name = $request->consumerName;
                $consumer->mobile_no = $request->mobileNo;
                $consumer->owner_id = $request->consumerId;
                $consumer->police_station = $request->ps;
                $consumer->landmark = $request->landmark;
                $consumer->house_no = $request->houseNo;
                $consumer->address = $request->address;
                $consumer->locality = $request->locality;
                $consumer->pincode = $request->pinCode;
                $consumer->consumer_category_id = $request->consumerCategory;
                $consumer->consumer_type_id = $request->consumerType;
                $consumer->user_id = $userId;
                $consumer->entry_date = date('Y-m-d');
                $consumer->creation_date = date('Y-m-d');
                $consumer->created_by = $userId;
                $consumer->date_time = date('Y-m-d H:i:s');
                $consumer->deactivate_status = 0;
                $consumer->save();
            
                $consumerUpdate = $consumer->find($consumer->id);

                $serialNo='0001';
                $wardCreated= str_pad($request->wardNo, 2, "0", STR_PAD_LEFT);
                $consumerTypeCreated= str_pad($request->consumerType, 2, "0", STR_PAD_LEFT);
                $randCreated= str_pad($consumer->id, 5, "0", STR_PAD_LEFT);
                $consumerNo = $wardCreated.$request->consumerCategory.$consumerTypeCreated.$randCreated.$serialNo;
                
                $consumerUpdate->consumer_no = $consumerNo;

                $consumerUpdate->entry_type = 1;
                $consumerUpdate->save();


                $consumerType = $this->ConsumerType->select('rate', 'name')
                                ->where('id', $request->consumerType)
                                ->first();

                $demand = $this->GenerateDemand($this->dbConn, $consumer->id, $consumerType->rate, $request->demandFrom, $userId);

                $response['wardNo'] = $request->wardNo;
                $response['apartmentId'] = $request->apartmentId;
                $response['holdingNo'] = $request->holdingNo;
                $response['consumerName'] = $request->consumerName;
                $response['consumerNo'] = $consumerNo;
                $response['apartmentName'] = $apartName;
                $response['mobileNo'] = $request->mobileNo;
                $response['ps'] = $request->ps;
                $response['landmark'] = $request->landmark;
                $response['houseNo'] = $request->houseNo;
                $response['address'] = $request->address;
                $response['locality'] = $request->locality;
                $response['pinCode'] = $request->pinCode;
                $response['consumerCategory'] = $this->ConsumerCategory->select('name')->first()->name;
                $response['consumerType'] = $consumerType->name;
                $response['demandFrom'] = $request->demandFrom;
                $response['demandDetails'] = $demand;
                $response['appliedBy'] = ($userId)?$this->GetUserDetails($userId)->name:"";
                $response['appliedDate'] = date('Y-m-d');
                $msg = "Reanter created and demand generated successfully";
            }else{
                $msg = "Renter already exist";
            }

            return response()->json(['status' => true, 'data' => $response, 'msg' => $msg], 200);
        } catch (Exception $e) {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }

    public function makePayment(Request $request)
    {
        
        try 
        {
            if(isset($request->consumerId))
            {

                $userId= $request->user()->id;
                $consumerId = $request->consumerId;
                $totalPayableAmt = $request->paidAmount;
                $transcationDate = date('Y-m-d');
                $date_time = date("Y-m-d H:i:s");
                $paidUpto = date('Y-m-d', strtotime($request->paidUpto));
                $getTc = $this->GetUserDetails($userId);
                
                $consumer = $this->Consumer->select('tbl_consumer.*', 'a.apt_name', 'a.apt_code', 'cc.name as category', 'ct.rate', 'apt_address')
                                    ->join('tbl_consumer_category as cc', 'tbl_consumer.consumer_category_id', '=', 'cc.id')
                                    ->join('tbl_consumer_type as ct', 'tbl_consumer.consumer_type_id', '=', 'ct.id')
                                    ->leftjoin('tbl_apt_details_mstr as a', 'tbl_consumer.apt_mstr_id', '=', 'a.id')
                                    ->where('tbl_consumer.id', $consumerId)
                                    ->first();

                $totalDemandAmt = $this->Demand->where('consumer_id', $consumerId)
                                ->where('paid_status', 0)
                                ->where('deactivate_status', 0)
                                ->sum('total_tax');
                
                $remainingAmt = $totalDemandAmt - $totalPayableAmt;


                $transcation = $this->Transaction->where('consumer_id', $consumerId);

                $lastpayment = $transcation->select('total_payable_amt')->where('pad_status', '1')->orderBy('id', 'desc')->first();
                
                $transcation = $transcation->whereDate('transaction_date','=', $transcationDate)
                                            ->where('total_payable_amt', $totalPayableAmt)
                                            ->get();
                $paidStatus = 1;
                
                if($request->paymentMode == 'Cheque' || $request->paymentMode == 'Dd')
                    $paidStatus = 2;

                if($transcation->count() == 0 && $totalPayableAmt > 0 )
                {
                    $trans = $this->Transaction;
                    $trans->transaction_date = $transcationDate;
                    $trans->total_demand_amt = $totalDemandAmt;
                    $trans->total_payable_amt = $totalPayableAmt;
                    $trans->total_remaining_amt = $remainingAmt;
                    $trans->discount = 0;
                    $trans->penalty = 0;
                    $trans->payment_mode = $request->paymentMode;
                    $trans->pad_status = $paidStatus;
                    $trans->consumer_id = $consumerId;
                    $trans->user_id = $userId;
                    $trans->ip_address = $request->ip();
                    $trans->stamp_date = $date_time;
                    $trans->save();
                    
                    if($trans->id > 0)
                    {
                        $trans->transaction_no = $userId.date("dmY").$trans->id;
                        $trans->save();

                        if($request->paymentMode == 'Cheque' || $request->paymentMode == 'Dd')
                        {
                            $transdtls = $this->TransactionDetails;
                            $transdtls->consumer_id = $consumerId;
                            $transdtls->transaction_id = $trans->id;
                            $transdtls->bank_name = $request->bankName;
                            $transdtls->branch_name = $request->branchName;
                            $transdtls->cheque_no = $request->chequeNo;
                            $transdtls->cheque_date = date('Y-m-d', strtotime($request->chequeDate));
                            $transdtls->apt_mstr_id = $consumer->apt_mstr_id;
                            $transdtls->save();
                        }

                        $sql = "INSERT INTO tbl_collection (consumer_id, demand_id, transaction_id, total_tax, payment_from, payment_to, user_id, stamdate)
                        SELECT consumer_id, id, '".$trans->id."', total_tax, payment_from, payment_to, '".$userId."', '".$date_time."' FROM tbl_demand 
                        WHERE consumer_id='$consumerId' and (payment_to <='".$paidUpto."') and paid_status='0'";
                        
                        DB::connection($this->dbConn)->select($sql);

                        $this->Demand->where('consumer_id', $consumerId)
                                ->where('payment_to', '<=', $paidUpto)
                                ->update(['paid_status' => 1]);
                        
                        $response['consumerName'] = $consumer->name;
                        $response['consumerCategory'] = ($consumer->category)?$consumer->category:'RESIDENTIAL';
                        $response['consumerNo'] = $consumer->consumer_no;
                        $response['apartmentName'] = $consumer->apt_name;
                        $response['apartmentCode'] = $consumer->apt_code;
                        $response['address'] = ($consumer->apt_address)?$consumer->apt_address:$consumer->address;
                        $response['transactionId'] = $trans->id;
                        $response['transactionDate'] = $transcationDate;
                        $response['transactionTime'] =  date("h:i A");
                        $response['transactionNo'] = $userId.date("dmY").$trans->id;
                        $response['holdingNo'] = $consumer->holding_no;
                        $response['mobileNo'] = $consumer->mobile_no;
                        $response['monthlyRate'] = $consumer->rate;
                        $response['demandAmount'] = $totalDemandAmt;
                        $response['receivedAmount'] = $totalPayableAmt;
                        $response['remainingAmount'] = $remainingAmt;
                        $response['paidUpto'] = $request->paidUpto;
                        $response['previousPaidAmount'] = ($lastpayment)?$lastpayment->total_payable_amt:"0.00";
                        $response['tcName'] = $getTc->name;
                        $response['tcMobile'] = $getTc->contactno;
                        return response()->json(['status'=> True, 'data'=>$response, 'msg'=> 'Payment Done Successfully'], 200);
                    }
                    
                }
                else{
                    return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'This user payment today not updated..'], 200);
                }
            }
            
        } 
        catch (Exception $e) {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }


    public function GeoLocation(Request $request)
    {
        try
        {   
            $response = array();
            if(isset($request->consumerId))
            {
                $geoLocation = $this->GeoLocation->select('latitude', 'longitude')
                                            ->where('consumer_id', $request->consumerId)
                                            ->first();

                $consumer = $this->Consumer->select('tbl_consumer.ward_no', 'tbl_consumer.name', 'apt_name', 'consumer_no', 'a.apt_code')
                                    ->leftjoin('tbl_apt_details_mstr as a', 'tbl_consumer.apt_mstr_id', 'a.id')
                                    ->where('tbl_consumer.id', $request->consumerId)
                                    ->first();
                
                
                $response['wardNo'] = $consumer->ward_no;
                $response['consumerName'] = $consumer->name;
                $response['consumerNo'] = $consumer->consumer_no;
                $response['apartmentName'] = $consumer->apt_name;
                $response['apartmentCode'] = $consumer->apt_code;
                $response['latitude'] = ($geoLocation)?$geoLocation->latitude:"";
                $response['longitude'] = ($geoLocation)?$geoLocation->longitude:"";
                
                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function TransactionModeChange(Request $request)
    {
        try
        {
            if(isset($request->transactionNo) && isset($request->mode))
            {
                $userId = $request->user()->id;
                $trans = $this->Transaction->where('transaction_no', $request->transactionNo)->first();
                if($trans == null)
                {
                    return response()->json(['status'=> False, 'data'=>'', 'msg'=> 'No Transaction detail fond on this tranc'], 200);
                }

                if(($trans->payment_mode == 'Cheque' && $trans->pad_status == '2') || $trans->payment_mode == 'Cash')
                {
                    $trans->payment_mode = $request->mode;
                    if($request->mode == 'Cheque')
                        $trans->pad_status = 2;
                    else
                        $trans->pad_status = 1;
                    

                    $modechange = $this->TransactionModeChange;
                    $modechange->transaction_id = $trans->id;
                    $modechange->date = Carbon::now();
                    $modechange->remarks = $request->remarks;
                    $modechange->ip_address = $request->ip();
                    $modechange->previous_mode = $trans->payment_mode;
                    $modechange->current_mode = $request->mode;
                    $modechange->user_id = $userId;

                    if($modechange->id>0)
                    {
                        $trans->remarks = $request->remarks;
                        $trans->save();
                        
                        if($request->mode == 'Cheque')
                        {
                            $transdtls = $this->Transaction;
                            $transdtls->consumer_id = $trans->consumer_id;
                            $transdtls->transaction_id = $trans->id;
                            $transdtls->bank_name = $request->bankName;
                            $transdtls->branch_name = $request->branchName;
                            $transdtls->cheque_no = $request->chequeNo;
                            $transdtls->cheque_date = $request->chequeDate;
                            $transdtls->apt_mstr_id = $trans->apt_mstr_id;
                            $transdtls->save();
                        }

                        return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Payment Mode changed successfully'], 200);
                    }
                }else{
                    return response()->json(['status'=> False, 'data'=>'', 'msg'=> 'Payment Mode can not change'], 200);
                }

                
            }
        }
        catch(Exception $e)
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }

    public function AllTransaction(Request $request)
    {
        try
        {   
            $response = array();
            if(isset($request->wardNo) && isset($request->userId))
            {

                $transactions = $this->Transaction->select('transaction_date','tbl_transaction.transaction_no', 'total_payable_amt', 'c.name', 'c.consumer_no','cn.name as consumer_name', 'cn.consumer_no as consumer_no1')
                                    ->leftjoin('tbl_consumer as c', 'tbl_transaction.consumer_id', 'c.id')
                                    ->leftjoin('tbl_consumer as cn', 'tbl_transaction.apt_mstr_id', 'cn.apt_mstr_id')
                                    ->where('c.ward_no', $request->wardNo)
                                    ->where('tbl_transaction.user_id', $request->userId);
                                    

                if($request->date)
                    $transactions = $transactions->whereDate('transaction_date','=', date('Y-m-d', strtotime($request->date)));
                
                $transactions = $transactions->get();
                
                foreach($transactions as $trans)
                {
                    $val['consumerName'] = $trans->name;
                    $val['Amount'] = $trans->total_payable_amt;
                    $val['transactionDate'] = date('d-m-Y', strtotime($trans->transaction_date));
                    $val['consumerNo'] = $trans->consumer_no;
                    $val['transactionNo'] = $trans->transaction_no;
                    $response[] = $val;
                }
                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        }
        catch(Exception $e)
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }

    public function AllCollectionSummary(Request $request)
    {
        try
        {   
            $response = array();
            if(isset($request->userId))
            {
                $sql = "SELECT t.user_id, name, user_type, contactno, sum(total_payable_amt) as total_amt,
                sum(CASE when payment_mode = 'Cash' then total_payable_amt else 0 end) as cash_amount,
                sum(CASE when payment_mode = 'Cheque' then total_payable_amt else 0 end) as cheque_amount,
                sum(CASE when payment_mode = 'Paytm' then total_payable_amt else 0 end) as paytm_amount
                FROM tbl_transaction as t
                JOIN db_master.view_user_mstr as u on t.user_id=u.id
                WHERE t.user_id=".$request->userId." and pad_status in(1,2) group by t.user_id";
                
                $collection = DB::connection($this->dbConn)->select($sql);
                
                if($collection)
                {
                    $collection = $collection[0];
                
                    $response['tcName'] = $collection->name;
                    $response['designation'] = $collection->user_type;
                    $response['mobileNo'] = $collection->contactno;
                    $response['cash'] = $collection->cash_amount;
                    $response['cheque'] = $collection->cheque_amount;
                    $response['paytm'] = $collection->paytm_amount;
                    $response['totalAmount'] = $collection->total_amt;
                }
                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        }
        catch(Exception $e)
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }

    public function ConsumerUpdate(Request $request)
    {
        try
        {
            $userId= $request->user()->id;
            if(isset($request->consumerId) || isset($request->apartmentId))
            {
                $consumer = $this->Consumer;
                if(isset($request->consumerId))
                    $consumer = $consumer->where('id', $request->consumerId);
                
                if(isset($request->apartmentId))
                    $consumer = $consumer->where('apt_mstr_id', $request->apartmentId);
                
                $consumer = $consumer->first();
                
                $consumerLog = $this->ConsumerEditLog;
                $consumerLog->consumer_id = $consumer->id;
                $consumerLog->old_ward_id = $consumer->ward_no;
                $consumerLog->ward_no = $consumer->ward_no;
                $consumerLog->name = $consumer->name;
                $consumerLog->previous_consumer_name = $consumer->name;
                $consumerLog->previous_policestation = $consumer->police_station;
                $consumerLog->police_station = $consumer->police_station;
                $consumerLog->previous_houseno = $consumer->house_no;
                $consumerLog->house_no = $consumer->house_no;
                $consumerLog->previous_holding_no = $consumer->holding_no;
                $consumerLog->holding_no = $consumer->holding_no;
                $consumerLog->mobile_no = $consumer->mobile_no;
                $consumerLog->previous_mobile_no = $consumer->mobile_no;
                $consumerLog->previous_landmark = $consumer->landmark;
                $consumerLog->landmark = $consumer->landmark;
                $consumerLog->previous_address = $consumer->address;
                $consumerLog->address = $consumer->address;
                $consumerLog->previous_consumer_category_id = $consumer->consumer_category_id;
                $consumerLog->consumer_category_id = $consumer->consumer_category_id;
                $consumerLog->consumer_type_id = $consumer->consumer_type_id;
                $consumerLog->previous_consumer_type_id = $consumer->consumer_type_id;
                $consumerLog->consumer_no = $consumer->consumer_no;
                $consumerLog->previous_consumer_no = $consumer->consumer_no;
                $consumerLog->previous_pincode = $consumer->pincode;
                $consumerLog->pincode = $consumer->pincode;
                $consumerLog->previous_locality = $consumer->locality;
                $consumerLog->locality = $consumer->locality;
                $consumerLog->user_id = $userId;
                $consumerLog->ip_address = $request->ip();
                $consumerLog->remarks = $request->remarks;
                $consumerLog->edit_date = Carbon::now();
                $consumerLog->date_time = Carbon::now();



                $oldConsumerTypeId = $consumer->consumer_type_id;
                foreach($request->request as $key=>$value)
                {
                    //echo $key;
                    if(($key != 'consumerId') && ($key != 'apartmentId') && ($key != 'consumerTypeId') && ($key !=  'demandFrom'))
                    {
                        $field_name = strtolower(preg_replace("/([^A-Z-])([A-Z])/", "$1_$2", $key));
                        $consumer->{$field_name} = $value;
                        $consumerLog->{$field_name} = $value;
                    }
                    
                }
                $consumerLog->save();
                $consumer->save();
                
                if(isset($request->demandFrom) && $request->consumerTypeId != $oldConsumerTypeId)
                {
                    $consumer->consumer_type_id = $request->consumerTypeId;
                    $consumer->save();
                    $consumerType = $this->ConsumerType->select('rate')
                            ->where('id', $request->consumerTypeId)
                            ->first();

                    $dmddata = $this->Demand->where('consumer_id', $consumer->id)
                                    ->where('paid_status', 0)
                                    ->where('deactivate_status', 0);
                    
                    if($dmddata->count() > 0)
                    {
                        $dmddata = $dmddata->update(['deactivate_status'=> 1]);
                        $this->GenerateDemand($this->dbConn, $consumer->id, $consumerType->rate, $request->demandFrom, $userId);
                        
                    }
                }
                $consumer->save();
                
                return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Consumer Updated Successfully'], 200);
            }else{
                return response()->json(['status'=> False, 'data'=>'', 'msg'=> 'Undefined parameter supply'], 200);
            }
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }
    
    public function AddCosumerReminder(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'consumerId' => 'required',
                'userId' => 'required',
                'reminderDate' => 'required',
            ]);
            if ($validator->fails()) {    
                return response()->json(['status'=> False, 'msg' => $validator->messages()]);
            }
            
            if(isset($request->consumerId))
            {
                $reminder = $this->CosumerReminder;
                $reminder->consumer_id = $request->consumerId;
                $reminder->user_id = $request->userId;
                $reminder->reminder_date = date('Y-m-d', strtotime($request->reminderDate));
                $reminder->remarks = ($request->remarks)?$request->remarks:"";
                $reminder->ip_address = $request->ip();
                $reminder->status = 1;
                $reminder->save();
                
                if($reminder->id > 0)
                {
                    return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Consumer reminder added Successfully'], 200);
                }
            }
            
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }

    public function GetCosumerReminder(Request $request)
    {
        try
        { 
            if(isset($request->consumerId))
            {
                $response = array();
                $reminder = $this->CosumerReminder->where('consumer_id', $request->consumerId)
                                            ->where('status', 1)
                                            ->orderBy('id', 'desc')
                                            ->first();
                
                if($reminder)
                {
                    $response['tcName'] = ($reminder->user_id)?$this->GetUserDetails($reminder->user_id)->name:'';
                    $response['reminderDate'] = date('d-m-Y', strtotime($reminder->reminder_date));
                    $response['remarks'] = $reminder->remarks;
                    $response['ipAddress'] = $reminder->ip_address;
                    $response['createdDateTime'] = date('d-m-Y h:i A', strtotime($reminder->stamp_datetime));

                    return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                }
                else{
                    return response()->json(['status'=> True, 'data'=>$response, 'msg'=> 'No Record Found'], 200);
                }
                
            }
            else{
                return response()->json(['status'=> False, 'data'=>'', 'msg'=> 'Undefined parameter supply'], 200);
            }
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }


    public function makeApartmentPayment(Request $request)
    {
        
        try 
        {
            if(isset($request->paymentMode) && $request->paymentMode == 'Cheque')
            {
                $validator = Validator::make($request->all(), [
                    'chequeNo' => 'required',
                    'chequeDate' => 'required',
                    'bankName' => 'required',
                    'branchName' => 'required',
                ]);
                if ($validator->fails()) {    
                    return response()->json(['status'=> False, 'msg' => $validator->messages()]);
                }
            }
            

            if(isset($request->apartmentId) && isset($request->paymentMode))
            {
                $userId = $request->user()->id;
                $apartmentId = $request->apartmentId;
                $totalPayableAmt = $request->paidAmount;
                $transcationDate = date('Y-m-d');
                $date_time = date("Y-m-d H:i:s");
                $paymentMode = $request->paymentMode;
                $paidUpto = date('Y-m-d', strtotime($request->paidUpto));
                $getTc = $this->GetUserDetails($userId);
                
                $totalDemandAmt = $this->Consumer->join('tbl_demand as d', 'd.consumer_id', '=', 'tbl_consumer.id')
                                            ->where('tbl_consumer.apt_mstr_id', $apartmentId)
                                            ->where('d.paid_status', 0)
                                            ->where('d.deactivate_status', 0)
                                            ->sum('d.total_tax');
            
                $remainingAmt = $totalDemandAmt - $totalPayableAmt;

                $transcation = $this->Transaction->where('apt_mstr_id', $apartmentId);

                $lastpayment = $transcation->select('total_payable_amt')->where('pad_status', '1')->orderBy('id', 'desc')->first();
                
                $transcation = $transcation->whereDate('transaction_date','=', $transcationDate)
                                            ->where('total_payable_amt', $totalPayableAmt)
                                            ->get();
                $paidStatus = 1;
                $paymentFrom = date('Y').'01-01';
                if($paymentMode == 'Cheque')
                    $paidStatus = 2;
                
                $response = array();
                if($transcation->count() == 0 && $totalPayableAmt > 0 )
                {
                    $trans = $this->Transaction;
                    $trans->transaction_date = $transcationDate;
                    $trans->total_demand_amt = $totalDemandAmt;
                    $trans->total_payable_amt = $totalPayableAmt;
                    $trans->total_remaining_amt = $remainingAmt;
                    $trans->discount = 0;
                    $trans->penalty = 0;
                    $trans->payment_mode = $paymentMode;
                    $trans->pad_status = $paidStatus;
                    $trans->apt_mstr_id = $apartmentId;
                    $trans->consumer_id = 0;
                    $trans->user_id = $userId;
                    $trans->ip_address = $request->ip();
                    $trans->stamp_date = $date_time;
                    $trans->save();
                    
                    if($trans->id > 0)
                    {
                        $trans->transaction_no = $userId.date("dmY").$trans->id;
                        $trans->save();

                        if($request->paymentMode == 'Cheque')
                        {
                            $transdtls = $this->TransactionDetails;
                            $transdtls->consumer_id = 0;
                            $transdtls->apt_mstr_id = $apartmentId;
                            $transdtls->transaction_id = $trans->id;
                            $transdtls->bank_name = $request->bankName;
                            $transdtls->branch_name = $request->branchName;
                            $transdtls->cheque_no = $request->chequeNo;
                            $transdtls->cheque_date = date('Y-m-d', strtotime($request->chequeDate));
                            $transdtls->save();
                        }


                        $collectionsql = "INSERT INTO tbl_collection (consumer_id, demand_id, transaction_id, total_tax, payment_from, payment_to, user_id, stamdate, apt_mstr_id)
                        SELECT consumer_id, d.id, '".$trans->id."', d.total_tax, d.payment_from, d.payment_to, '".$userId."', '".$date_time."', c.apt_mstr_id FROM tbl_demand as d
                        JOIN tbl_consumer as c on d.consumer_id=c.id
                        WHERE c.apt_mstr_id='$apartmentId' and (d.payment_to <='".$paidUpto."') and d.paid_status='0'";

                        DB::connection($this->dbConn)->select($collectionsql);

                        $this->Consumer->join('tbl_demand as d', 'd.consumer_id', '=', 'tbl_consumer.id')
                                ->where('tbl_consumer.apt_mstr_id', $apartmentId)
                                ->where('d.payment_to', '<=', $paidUpto)
                                ->where('d.paid_status', '=', 0)
                                ->where('d.deactivate_status', '=', 0)
                                ->update(['d.paid_status' => 1]);
                        
                        $sql = "SELECT a.apt_name, a.apt_code, sum(ct.rate) as monthly_rate FROM `tbl_apt_details_mstr` a
                        join tbl_consumer c on c.apt_mstr_id=a.id
                        join tbl_consumer_type ct on c.consumer_type_id=ct.id where a.id=".$apartmentId." group by a.apt_name, a.apt_code";
                        
                        $aprtment = DB::connection($this->dbConn)->select($sql);
                        
                        if($aprtment){
                            
                            $aprtment = $aprtment[0];
                            $response['consumerCategory'] = 'RESIDENTIAL';
                            $response['apartmentName'] = $aprtment->apt_name;
                            $response['apartmentCode'] = $aprtment->apt_code;
                            $response['transactionId'] = $trans->id;
                            $response['transactionDate'] = $transcationDate;
                            $response['transactionTime'] = Carbon::create($date_time)->format('h:i A');
                            $response['transactionNo'] = $userId.date("dmY").$trans->id;
                            $response['monthlyRate'] = $aprtment->monthly_rate;
                            $response['demandAmount'] = $totalDemandAmt;
                            $response['receivedAmount'] = $totalPayableAmt;
                            $response['remainingAmount'] = $remainingAmt;
                            $response['paidUpto'] = $request->paidUpto;
                            $response['previousPaidAmount'] = ($lastpayment)?$lastpayment->total_payable_amt:"0.00";
                            $response['tcName'] = $getTc->name;
                            $response['tcMobile'] = $getTc->contactno;
                        }
                        return response()->json(['status'=> True, 'data'=>$response, 'msg'=> 'Payment Done Successfully'], 200);
                    }
                    
                }
                else{
                    return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'This user payment today not updated..'], 200);
                }
            }else{
                return response()->json(['status'=> False, 'data'=>'', 'msg'=> 'Undefined parameter suppied or lack of information missing'], 200);
            }
            
        } 
        catch (Exception $e) {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }



    public function DeactivateApartment(Request $request)
    {
        $userId= $request->user()->id;
        try
        {   
            $response = array();
            if(isset($request->apartmentId))
            {
                $allConsumer = $this->Consumer->where('apt_mstr_id', $request->apartmentId)
                                        ->get();
                
                if($allConsumer)
                {                        
                    
                    foreach($allConsumer as $con)
                    {
                        $consDtls = $this->ConsumerDeactivateDeatils->insert([
                            'consumer_id' => $con->id,
                            'remarks' => ($request->remarks)?$request->remarks:"",
                            'deactivated_by' => $userId,
                            'deactivation_date' => date('Y-m-d'),
                            'ip_address' => $request->ip(),
                            'timestamp' => date('Y-m-d H:i:s')
                        ]);
                        if($consDtls)
                        {
                            $con->deactivate_status = 0;
                            $con->save();
                            $sql = "Update tbl_demand set deactivate_status=1 where consumer_id=".$con->id." and paid_status=0 and deactivate_status=0";
                            DB::connection($this->dbConn)->select($sql);
                            
                        }
                    
                    }
                    
                    $this->Apartment->where('id', $request->apartmentId)
                            ->update(['deactive_status'=> 1]);

                    return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Apartment Deactivated Successfully'], 200); 
                }else{
                    return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Apartment not found'], 200);
                }   
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }

    public function GetCaseVerificationList(Request $request)
    {
        try
        {   
            $response = array();
            if(isset($request->tcId) and isset($request->fromDate) and isset($request->toDate))
            {
                $tcId = $request->tcId;
                $fromDate = date('Y-m-d', strtotime($request->fromDate));
                $toDate = date('Y-m-d', strtotime($request->toDate));

                $sql = "SELECT t.user_id, name, user_type, contactno,transaction_date,
                sum(CASE when payment_mode = 'Cash' then total_payable_amt else 0 end) as cash_amount,
                sum(CASE when payment_mode = 'Cheque' then total_payable_amt else 0 end) as cheque_amount,
                sum(CASE when payment_mode = 'DD' then total_payable_amt else 0 end) as dd_amount
                FROM tbl_transaction as t
                left JOIN tbl_transaction_verification tv on tv.transaction_id=t.id 
                JOIN db_master.view_user_mstr as u on t.user_id=u.id 
                WHERE t.user_id=".$tcId." and (transaction_date between '$fromDate' and '$toDate') group by t.user_id, name, user_type, contactno,transaction_date";

                $collections = DB::connection($this->dbConn)->select($sql);
                foreach($collections as $collection)
                {
                    $total_amt = $collection->cash_amount + $collection->cheque_amount + $collection->dd_amount;
                    $val['tcName'] = $collection->name;
                    $val['designation'] = $collection->user_type;
                    $val['mobileNo'] = $collection->contactno;
                    $val['totalAmount'] = $total_amt;
                    $val['cashAmount'] = $collection->cash_amount;
                    $val['chequeAmount'] = $collection->cheque_amount;
                    $val['ddAmount'] = $collection->dd_amount;
                    $val['transactionDate'] = ($collection->transaction_date)?date('d-m-Y', strtotime($collection->transaction_date)):'0';
                    $response[] = $val;
                }
                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        }
        catch(Exception $e)
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }


    public function getCashVerificationFullDetails(Request $request)
    {
        try
        {   
            $response = array();
            
            if(isset($request->tcId) and isset($request->date))
            {
                $tcId = $request->tcId;
                $date = date('Y-m-d', strtotime($request->date));

                $sql = "SELECT c.*,a.apt_name,a.apt_code,a.ward_no as award,t.id as transId,t.transaction_no,t.payment_mode,total_payable_amt,tv.verify_status,tv.verify_by,verify_date,u.name as verify_by
                FROM tbl_transaction as t
                LEFT JOIN tbl_consumer c on t.consumer_id=c.id 
                LEFT JOIN tbl_apt_details_mstr a on t.apt_mstr_id=a.id
                LEFT JOIN tbl_transaction_verification tv on tv.transaction_id=t.id 
                JOIN db_master.view_user_mstr as u on tv.verify_by=u.id
                WHERE t.user_id=".$tcId." and transaction_date='$date' order by t.id desc";
                
                $collections = DB::connection($this->dbConn)->select($sql);
                
                $totalCash=0;
                $totalCheque=0;
                $totaldd=0;
                $transaction = array();
                foreach($collections as $collection)
                {
                    $coll = $this->Collections->where('transaction_id', $collection->transId);
                    $firstrecord = $coll->orderBy('id','asc')->first();
                    $lastrecord = $coll->latest('id')->first();
                    
                    if($collection->payment_mode == 'Cash')
                        $totalCash += $collection->total_payable_amt;

                    if($collection->payment_mode == 'Cheque')
                        $totalCheque += $collection->total_payable_amt;
                    
                    if($collection->payment_mode == 'DD')
                        $totalCheque += $collection->totaldd;
                    
                    $val['transactionId'] = $collection->transId;
                    $val['transactionNo'] = $collection->transaction_no;
                    $val['paymentMode'] = $collection->payment_mode;
                    $val['wardNo'] = ($collection->ward_no)?$collection->ward_no:$collection->award;
                    $val['holdingNo'] = $collection->holding_no;
                    $val['consumerNo'] = $collection->consumer_no;
                    $val['consumerName'] = $collection->name;
                    $val['apartmentName'] = $collection->apt_name;
                    $val['apartmentCode'] = $collection->apt_code;
                    $val['paidAmount'] = $collection->total_payable_amt;
                    $val['paidUpto'] = '';
                    $val['verifyStatus'] = ($collection->verify_status == 1)?'Verified':'Unverified';
                    $val['verifiedBy'] = $collection->verify_by;
                    $val['verifiedOn'] = date('d-m-Y', strtotime($collection->verify_date));
                    $val['demandFrom'] = ($firstrecord)?Carbon::create($firstrecord->payment_from)->format('d-m-Y'):'';
                    $val['demandUpto'] = ($firstrecord)?Carbon::create($lastrecord->payment_to)->format('d-m-Y'):'';
                    $transaction[] = $val;
                }
                
                $response['transactionList'] = $transaction;
                $response['cashAmount'] = $totalCash;
                $response['chequeAmount'] = $totalCheque;
                $response['ddAmount'] = $totaldd;
                $response['totalAmount'] = $totalCash + $totalCheque + $totaldd;
                
                return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
                
            }else{
                return response()->json(['status'=> False, 'data'=>$response, 'msg'=> 'Undefined parameter supply'], 200);
            }
        }
        catch(Exception $e)
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
    }

    
    public function CashVerification(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'transactionIds' => 'required|array'
            ]);
            
            if ($validator->fails()) {    
                return response()->json(['status'=> False, 'msg' => $validator->messages()]);
            }
            
            if(isset($request->transactionIds))
            {
                $userId= $request->user()->id;
                $transactionIds = $request->transactionIds;
                
                foreach($transactionIds as $trans)
                {
                    $t = $this->Transaction->select('total_payable_amt')
                                    ->where('id', $trans)
                                    ->first();

                    $tverify = $this->TransactionVerification;
                    $tverify->transaction_id = $trans;
                    $tverify->verify_status = 1;
                    $tverify->verify_date = date('Y-m-d H:i:s');
                    $tverify->verify_by = $userId;
                    $tverify->ip_address = $request->ip();
                    $tverify->amount = $t->total_payable_amt;
                    $tverify->remarks = "Payment Verified";
                    $tverify->save();
                }
                
                return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Verification successfully'], 200);

            }
            
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }


    public function ClearanceForm(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'transactionId' => 'required',
                'status' => 'required',
            ]);
            
            if ($validator->fails()) {    
                return response()->json(['status'=> False, 'msg' => $validator->messages()]);
            }
            
            if(isset($request->transactionId))
            {
                $userId = $request->user()->id;
                $transactionId = $request->transactionId;
                $status = $request->status;
                $clearanceDate = isset($request->clearanceDate)?$request->clearanceDate:'';
                $cancelationDate = isset($request->cancelationDate)?$request->cancelationDate:'';
                $cancelationCharge = isset($request->cancelationCharge)?$request->cancelationCharge:'';
                $reason = isset($request->reason)?$request->reason:'Clear';

                $reconcile_date = ($clearanceDate)?$clearanceDate:$cancelationDate;
                

                $trans = $this->Transaction->find($transactionId);

                if($trans->pad_status == 0)
                    return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Transaction Already Deactivate!!'], 200);
                
                
                
                $bkcancel = $this->BankCancel;
                $bkcancel->consumer_id = $trans->consumer_id;
                $bkcancel->transaction_id = $transactionId;
                $bkcancel->remarks = $reason;
                $bkcancel->reconcilition_date = date('Y-m-d', strtotime($reconcile_date));
                $bkcancel->stampdate = date('Y-m-d H:i:s');
                $bkcancel->user_id = $userId;
                $bkcancel->ip_address = $request->ip();
                $bkcancel->apt_mstr_id = $trans->apt_mstr_id;
                $bkcancel->save();

                if($bkcancel->id && $status == 'bounce')
                {
                    $bkcanceldtl = $this->BankCancelDetails;
                    $bkcanceldtl->consumer_id = $trans->consumer_id;
                    $bkcanceldtl->bank_cancel_id = $bkcancel->id;
                    $bkcanceldtl->amount = $cancelationCharge;
                    $bkcanceldtl->stamdate = date('Y-m-d H:i:s');
                    $bkcanceldtl->save();

                    if($bkcanceldtl->id)
                    {
                        $trans->pad_status = 3;
                        $trans->save();
                    

                        $this->Collections->where('transaction_id', $transactionId)
                                                ->update(['deactivate_status' => 1]);

                        $refreance_ids = array();
                        if(empty($trans->consumer_id) || $trans->consumer_id == 0)
                        {
                            $consumers = $this->Consumer->select('id')
                                                ->where('apt_mstr_id', $trans->apt_mstr_id)
                                                ->get();
                            foreach($consumers as $consumer)
                                $refreance_ids[] = $consumer->id;
                        }else{
                            $refreance_ids[] = $trans->consumer_id;
                        }

                        $this->Demand->whereIn('consumer_id', $refreance_ids)
                                ->where('paid_status', 1)
                                ->update(['paid_status'=>0]);
                        

                    }

                }

                if($bkcancel->id && $status == 'clear')
                {
                    $trans->pad_status = 1;
                    $trans->save();
                }
                
                return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Bank Reconciliation successfully'], 200);

            }
            
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }


    public function BankReconciliationList(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'fromDate' => 'required',
                'toDate' => 'required',
            ]);
            
            if ($validator->fails()) {    
                return response()->json(['status'=> False, 'msg' => $validator->messages()]);
            }
            $response = array();
            $whereparam = "";
            $From = Carbon::create($request->fromDate)->format('Y-m-d');
            $Upto = Carbon::create($request->toDate)->format('Y-m-d');
            if(isset($request->paymentMode) && $request->paymentMode <> 'all')
                $whereparam .= "and t.payment_mode='".ucfirst($request->paymentMode)."'";
            
            if(isset($request->verificationType) && $request->verificationType != 'all')
            {
                if($request->verificationType == 'pending')
                    $whereparam .= ' and reconcilition_date is null';
                
                if($request->verificationType == 'clear')
                    $whereparam .= ' and bank_cancel_id is null';
                
                if($request->verificationType == 'bounce')
                    $whereparam .= ' and bank_cancel_id is not null';

            }

            if(isset($request->chequeNo))
                $whereparam .= "and cheque_no='".$request->chequeNo."'" ;
            
            if(isset($request->ddNo))
                $whereparam .= "and cheque_no='".$request->ddNo."'" ;
            
            $sql = "SELECT t.consumer_id,t.id as transId, t.apt_mstr_id,bank_cancel_id,reconcilition_date,transaction_no,transaction_date,payment_mode,cheque_no, cheque_date, bank_name,branch_name, total_payable_amt,bc.remarks,t.user_id 
            FROM  tbl_transaction t
            LEFT JOIN tbl_bank_cancel bc on bc.transaction_id=t.id
            LEFT JOIN tbl_bank_cancel_details bd on bd.bank_cancel_id=bc.id
            LEFT JOIN tbl_transaction_details td on td.transaction_id=t.id
            WHERE (transaction_date BETWEEN '$From' and '$Upto') and t.pad_status>0 ". $whereparam;

            $transactions = DB::connection($this->dbConn)->select($sql);
            
            foreach($transactions as $transaction)
            {
                $collection = $this->Collections->where('transaction_id', $transaction->transId);
                $firstrecord = $collection->orderBy('id','asc')->first();
                $lastrecord = $collection->latest('id')->first();
                
                $verificationType = ($transaction->bank_cancel_id)?'Bounce':'Clear';
                if($transaction->consumer_id)
                    $refdata = $this->Consumer->find($transaction->consumer_id);
                else
                    $refdata = $this->Consumer->where('apt_mstr_id', $transaction->apt_mstr_id)->first();
                
                $val['wardNo'] = $refdata->ward_no;
                $val['tranId'] = $transaction->transId;
                $val['tranNo'] = $transaction->transaction_no;
                $val['tranDate'] = Carbon::create($transaction->transaction_date)->format('d-m-Y');
                $val['paymentMode'] = $transaction->payment_mode;
                $val['chequeNo'] = $transaction->cheque_no;
                $val['chequeDate'] = ($transaction->cheque_date)?Carbon::create($transaction->cheque_date)->format('d-m-Y'):'';
                $val['bankName'] = $transaction->bank_name;
                $val['branchName'] = $transaction->branch_name;
                $val['tranAmount'] = $transaction->total_payable_amt;
                $val['clearanceDate'] = ($transaction->reconcilition_date)?Carbon::create($transaction->reconcilition_date)->format('d-m-Y'):'';
                $val['remarks'] = $transaction->remarks;
                $val['tcName'] = ($transaction->user_id)?$this->GetUserDetails($transaction->user_id)->name:'';
                $val['verificationType'] = ($transaction->reconcilition_date)?$verificationType:'Pending';
                $val['demandFrom'] = ($firstrecord)?Carbon::create($firstrecord->payment_from)->format('d-m-Y'):'';
                $val['demandUpto'] = ($firstrecord)?Carbon::create($lastrecord->payment_to)->format('d-m-Y'):'';
                $response[] =$val;
            }
            return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);

            
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }


    public function ConsumerListByCategory(Request $request)
    {
        
        try
        {   
            
            $conArr = array();
            if(isset($request->wardNo) || isset($request->consumerCategory) || isset($request->consumerType) || isset($request->ulbId) ||isset($request->mobileNo)|| isset($request->consumerName))
            {
                
                $consumerList = $this->Consumer->join('tbl_consumer_category', 'tbl_consumer.consumer_category_id', '=', 'tbl_consumer_category.id')
                                ->join('tbl_consumer_type', 'tbl_consumer.consumer_type_id', '=', 'tbl_consumer_type.id')
                                ->select(DB::raw('tbl_consumer.*, tbl_consumer_category.name as category, tbl_consumer_type.name as type'));
                
                if(isset($request->wardNo))               
                    $consumerList = $consumerList->where('tbl_consumer.ward_no', $request->wardNo);
                
                if(isset($request->consumerCategory))               
                    $consumerList = $consumerList->where('tbl_consumer.consumer_category_id', $request->consumerCategory);  
                
                if(isset($request->consumerType))               
                    $consumerList = $consumerList->where('tbl_consumer.consumer_type_id', $request->consumerType);
                
                if (isset($request->mobileNo))
                    $consumerList = $consumerList->where('swm_consumers.mobile_no', $request->mobileNo);

                if (isset($request->consumerName))
                    $consumerList = $consumerList->where('swm_consumers.name', 'like', '%' . $request->consumerName . '%');

                
                
                
                $consumerList = $consumerList->paginate(
                                    $perPage = 10, $columns = ['*'], $pageName = 'consumers'
                                );
                
                //echo "<pre/>";print_r($consumerList);
                foreach($consumerList as $consumer)
                {
                    $demand = $this->Demand->where('consumer_id', $consumer->id)
                                ->where('paid_status', 0)
                                ->where('deactivate_status', 0)
                                ->orderBy('id', 'asc')
                                ->get();
                    $total_tax = 0.00;
                    $demand_upto = '';
                    $paid_status = 'True';
                    foreach($demand as $dmd)
                    {
                        $total_tax += $dmd->total_tax;
                        $demand_upto = $dmd->demand_date;
                        $paid_status = 'False';
                    }
                    //
                    
                    $con['id'] = $consumer->id;
                    $con['wardNo'] = $consumer->ward_no;
                    $con['holdingNo'] = $consumer->holding_no;
                    $con['consumerName'] = $consumer->name;
                    $con['apartmentId'] = $consumer->apt_mstr_id;
                    $con['consumerNo'] = $consumer->consumer_no;
                    $con['Address'] = $consumer->address;
                    $con['ps'] = $consumer->police_station;
                    $con['landmark'] = $consumer->landmark;
                    $con['houseNo'] = $consumer->house_no;
                    $con['pinCode'] = $consumer->pincode;
                    $con['locality'] = $consumer->locality;
                    $con['cansumerCategory'] = $consumer->category;
                    $con['cansumerType'] = $consumer->type;
                    $con['mobileNo'] = $consumer->mobile_no;
                    $con['activeDemandDetails'] = $demand;
                    $con['totalDemand'] = $total_tax;
                    $con['demandUpto'] = $demand_upto;
                    $con['paidStatus'] = $paid_status;
                    $con['applyBy'] = ($consumer->user_id)?$this->GetUserDetails($consumer->user_id)->name:'';
                    $con['applyDate'] = date("d-m-Y", strtotime($consumer->entry_date));
                    $con['status'] = ($consumer->deactivate_status == 0)?'Active':'Deactive';

                    $conArr[] = $con;

                }
                return response()->json(['status'=> True, 'data'=>$conArr, 'msg'=> ''], 200);
            }else{
                return response()->json(['status'=> False, 'data'=>$conArr, 'msg'=> 'Undefined parameter supply'], 200);
            }
            
        } 
        catch (Exception $e) 
        {
            return response()->json(['status'=> False, 'data'=>'', 'msg'=> $e], 400);
        }
        
    }


    public function PaymentDeny(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'remarks' => 'required',
            ]);
            
            if ($validator->fails()) {    
                return response()->json(['status'=> False, 'msg' => $validator->messages()]);
            }
            
            if(isset($request->consumerId) || isset($request->apartmentId))
            {
                $userId= $request->user()->id;
                $consumerId = $request->consumerId;
                $apartmentId = $request->apartmentId;

                if($consumerId)
                {
                    $outsAmt = $this->GetDemand($this->dbConn, $consumerId, 'Consumer');
                }else{
                    $outsAmt = $this->GetDemand($this->dbConn, $apartmentId, 'Apartment');
                }
                
                $deny = $this->PaymentDeny;
                $deny->user_id = $userId;
                $deny->consumer_id = ($consumerId)?$consumerId:0;
                $deny->deny_date = date('Y-m-d H:i:s');
                $deny->status = 1;
                $deny->outstanding_amt = $outsAmt['demandAmt'];
                $deny->denied_reason = $request->remarks;
                $deny->apt_mstr_id = ($apartmentId)?$apartmentId:null;
                $deny->save();
                
                return response()->json(['status'=> True, 'data'=>'', 'msg'=> 'Payment Denied successfully'], 200);

            }
            
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }

    
    public function PaymentDenyList(Request $request)
    {
        try
        {

            $response = array();
            if(isset($request->consumerId) || isset($request->apartmentId))
            {

                
                $consumerId = $request->consumerId;
                $apartmentId = $request->apartmentId;

                $deny = $this->PaymentDeny->where('status', 1);
                if($consumerId)
                {
                    $deny = $deny->where('consumer_id', $consumerId);
                    
                }else{
                    $deny = $deny->where('apt_mstr_id', $apartmentId);
                }
                
                $deny = $deny->get();
                foreach($deny as $d)
                {
                    $val['denyBy'] = $this->GetUserDetails($d->user_id)->name;
                    $val['denyDate'] = date('d-m-Y h:i A', strtotime($d->deny_date));
                    $val['outstandingAmount'] = $d->outstanding_amt;
                    $val['remarks'] = $d->denied_reason;
                    $response[] = $val;
                }
 
            }
            return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
            
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }


    public function GetReprintData(Request $request)
    {
        try
        {

            $response = array();
            if(isset($request->transactionNo))
            {
                $transactionNo = $request->transactionNo;

                $sql = "SELECT t.transaction_no,t.transaction_date,c.ward_no,c.name,c.address,a.apt_name, a.apt_code, c.consumer_no, a.apt_address, a.ward_no as apt_ward, 
                t.total_payable_amt, cl.payment_from, cl.payment_to, t.payment_mode,td.bank_name, td.branch_name, td.cheque_no, td.cheque_date, 
                t.total_demand_amt, t.total_remaining_amt, c.holding_no, t.stamp_date, t.apt_mstr_id, ct.rate,cc.name as consumer_category,t.user_id
                FROM tbl_transaction t
                LEFT JOIN tbl_consumer c on t.consumer_id=c.id
                LEFT JOIN tbl_consumer_type ct on c.consumer_type_id=ct.id
                LEFT JOIN tbl_consumer_category cc on c.consumer_category_id=cc.id
                LEFT JOIN tbl_apt_details_mstr a on t.apt_mstr_id=a.id
                JOIN (
                    SELECT min(payment_from) as payment_from, max(payment_to) as payment_to,
                    transaction_id 
                    FROM tbl_collection 
                    GROUP BY transaction_id
                ) cl on cl.transaction_id=t.id 
                LEFT JOIN tbl_transaction_details td on td.transaction_id=t.id
                WHERE t.transaction_no='".$transactionNo."'";

                $transaction = DB::connection($this->dbConn)->select($sql);
                
                if($transaction)
                {
                    $transaction = $transaction[0];
                    $consumerCount = 0;
                    $monthlyRate = $transaction->rate;
                    if($transaction->apt_mstr_id)
                    {
                        $consumer = $this->Consumer->join('tbl_consumer_type as ct', 'ct.id', '=', 'tbl_consumer.consumer_type_id')
                                                        ->where('apt_mstr_id', $transaction->apt_mstr_id)
                                                        ->where('deactivate_status', 0);
                        $consumerCount = $consumer->count();
                        $monthlyRate = $consumer->sum('rate');
                    }
                    $getTc = $this->GetUserDetails($transaction->user_id);

                    $response['transactionDate'] = Carbon::create($transaction->transaction_date)->format('Y-m-d');
                    $response['transactionTime'] = Carbon::create($transaction->stamp_date)->format('h:i A') ;
                    $response['transactionNo'] = $transaction->transaction_no;
                    $response['consumerName'] = $transaction->name;
                    $response['consumerNo'] = $transaction->consumer_no;
                    $response['consumerCategory'] = ($transaction->consumer_category)?$transaction->consumer_category:'RESIDENTIAL';
                    $response['apartmentName'] = $transaction->apt_name;
                    $response['apartmentCode'] = $transaction->apt_code;
                    $response['ReceiptWard'] = ($transaction->apt_ward)?$transaction->apt_ward:$transaction->ward_no;
                    $response['holdingNo'] = $transaction->holding_no;
                    $response['address'] = ($transaction->apt_address)?$transaction->apt_address:$transaction->address;
                    $response['paidFrom'] = $transaction->payment_from;
                    $response['paidUpto'] = $transaction->payment_to;
                    $response['paymentMode'] = $transaction->payment_mode;
                    $response['bankName'] = $transaction->bank_name;
                    $response['branchName'] = $transaction->branch_name;
                    $response['chequeNo'] = $transaction->cheque_no;
                    $response['chequeDate'] = $transaction->cheque_date;
                    $response['noOfFlats'] = $consumerCount;
                    $response['monthlyRate'] = $monthlyRate;
                    $response['demandAmount'] = ($transaction->total_demand_amt)?$transaction->total_demand_amt:0;
                    $response['paidAmount'] = ($transaction->total_payable_amt)?$transaction->total_payable_amt:0;
                    $response['remainingAmount'] = ($transaction->total_remaining_amt)?$transaction->total_remaining_amt:0;
                    $response['tcName'] = $getTc->name;
                    $response['tcMobile'] = $getTc->contactno;
                }
            }

            
            return response()->json(['status'=> True, 'data'=>$response, 'msg'=> ''], 200);
            
        }
        catch(Exception $e)
        {
            return response()->json(['status' => False, 'data'=> '', 'msg'=>$e], 400);
        }
    }



}