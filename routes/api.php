<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConsumerController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/



Route::group(['middleware' => ['json.response', 'auth_maker']], function () {
    // Your Protected Route is Here
    // Route::get('test', function () {
    //     return 'Success';
    // });
    Route::controller(AuthController::class)->group(function () {
        Route::get('swm/getHomePageData/{userId}', 'GetHomePageData');  // Route for get last Login Details
    
    });

    Route::controller(AuthController::class)->group(function () {
        //Route::post('swm/updateUser', 'UpdateUser');                    // Route for update user
        Route::post('swm/getTcList', 'getTcList');                      // Route for get tc list ulb wise
        // Menu Permission
        Route::post('swm/postMenuPermission', 'MenuPermission');            
        Route::get('swm/getMenuPermissionList', 'MenuPermissionList');
        Route::post('swm/getMenuPermissionById', 'MenuPermissionList');
        Route::post('swm/updateMenuPermission', 'UpdateMenuPermission');
        Route::post('swm/getMenuPermissionByUserType', 'MenuPermissionByUserType');

        // route for the users
        # edited by sam
        Route::post('swm/getAlluser', 'getAllUser');                    // Route for get all user
        //Route::post('swm/createUser', 'CreateUser');                    // Route for create user
        Route::post('swm/getUserFormDate', 'getUserFormDate');          // Route for user form data
        # ended here                  
    });




    Route::controller(ConsumerController::class)->group(function () {
        Route::get('swm/getConsumerList', 'GetConsumerList', function () {
            return 'Success';
        });
        Route::get('swm/getConsumerDetailsById/{id}', 'GetConsumerList');
        Route::get('swm/getApartmentList', 'GetApartmentList');
        Route::get('swm/getApartmentDetailsById/{id}', 'GetApartmentDetailsById');
        Route::post('swm/postConsumerAdd', 'postConsumerAdd');
        Route::get('swm/getRenterFormData/{consumerId}', 'GetRenterFormData');
        Route::get('swm/getEditConsumerDetailsbyId/{id}', 'getEditConsumerDetailsById');
        Route::post('swm/postDeactivateConsumer', 'postDeactivateConsumer');
        Route::post('swm/getPaymentData', 'getPaymentData');
        Route::post('swm/postPayment', 'MakePayment');
        Route::post('swm/getCalculatedAmount', 'getCalculatedAmount');
        Route::post('swm/getDashboardData', 'getDashboardData');
        Route::post('swm/getAnalyticDashboardData', 'getAnalyticDashboardData');
        Route::get('swm/searchTransaction/{transactionNo}', 'searchTransaction');
        Route::post('swm/transactionDeactivate', 'transactionDeactivate');
        Route::post('swm/postRenterForn', 'RenterForm');
        
        // Geo Tagging
        Route::post('swm/postGeoTagging', 'AddGeoTagging');
        Route::post('swm/getGeoLocation', 'GetGeoLocation');
        
        Route::post('swm/getAllTransaction', 'GetAllTransaction');
        Route::post('swm/getCollectionSummary', 'AllCollectionSummary');
        Route::post('swm/postEditConsumerDetail', 'UpdateConsumerDetails');
        Route::post('swm/transactionModeChange', 'transactionModeChange');
        Route::post('swm/postReminder', 'addConsumerReminder');
        Route::post('swm/getReminder', 'getConsumerReminder');
        Route::post('swm/apartmentPayment', 'ApartmentPayment');
        Route::post('swm/apartmentDeactivate', 'ApartmentDeactivate');
        Route::post('swm/getCashVerificationList', 'getCashVerificationList');
        Route::post('swm/getCashVerificationFullDetails', 'getCashVerificationFullDetails');
        Route::post('swm/postCashVerification', 'CashVerification');

        Route::post('swm/postClearanceForm', 'ClearanceForm');                           // Route for make bank reconciliation
        Route::post('swm/getBankReconciliationList', 'GetBankReconciliationList');        // Route for get bank reconciliation

        Route::post('swm/ApartmentDetailsById', 'GetApartmentDetailsById');
        Route::post('swm/getConsumerListByCategory', 'ConsumerListByCategory');
        Route::post('swm/postPaymentDeny', 'PaymentDeny');
        Route::post('swm/getPaymentDenyList', 'PaymentDenyList');

        Route::post('swm/getReprintData', 'getReprintData');
        Route::post('swm/getDemandReceipt', 'GetDemandReceipt');
        Route::post('swm/getdenialNotification', 'DenialNotificationList');

        // Payment adjustments
        Route::post('swm/paymentAdjustment', 'PaymentAdjustment');
        Route::get('swm/getPaymentAdjustmentList', 'PaymentAdjustmentList');
        

        Route::post('swm/consumerListByWardNo', 'ConsumerOrApartmentList');
        Route::post('swm/getReminderList', 'GetReminderList');
        Route::post('swm/getConsumerPastTransactions', 'ConsumerPastTransactions');
       
        // For Complain
        Route::post('swm/postTcComplain', 'TcComplain');
        Route::post('swm/getComplainList', 'getComplainList');
        
        // For Routes
        Route::post('swm/postNewRoute', 'addRoute');
        Route::post('swm/getRouteList', 'RouteList');
        Route::post('swm/getRouteDataById', 'RouteDataById');
        Route::post('swm/updateRoute', 'updateRoute');
        Route::post('swm/deleteRoute', 'DeleteRoute');

        Route::post('swm/createDefaultConsumerApartment', 'DefaultConsumerApartment');
    });

    Route::controller(MasterController::class)->group(function () {
        Route::get('swm/getConsumerAddFormData', 'GetConsumerAddFormData');
        Route::get('swm/getApartmentListByWardNo/{wardNo}', 'GetApartmentListData');
        Route::get('swm/getConsumerTypeByCategory/{id}', 'GetConsumerTypeByCategoryId');

        Route::post('swm/updateApartment', 'updateApartment');
        Route::post('swm/addApartment', 'addApartment');
        Route::get('swm/getApartList', 'GetApartmentListData');
        Route::get('swm/getApartmentById', 'getApartmentById');

        Route::get('swm/getConsumerCategoryList', 'getConsumerCategoryList');
        Route::post('swm/postConsumerCategoryAdd', 'ConsumerCategoryAdd');
        Route::put('swm/postConsumerCategoryUpdate', 'ConsumerCategoryUpdate');
        Route::post('swm/getConsumerCategoryById', 'ConsumerCategoryById');

        Route::post('swm/getConsumerTypeList', 'ConsumerTypeList');
        Route::post('swm/postConsumerTypeAdd', 'ConsumerTypeAdd');
        Route::put('swm/postConsumerTypeUpdate', 'ConsumerTypeUpdate');
        Route::post('swm/getConsumerTypeById', 'ConsumerTypeById');

        Route::post('swm/getUlbList', 'UlbList');
        Route::post('swm/postUlbAdd', 'UlbAdd');
        Route::put('swm/postUlbUpdate', 'UlbUpdate');
        Route::post('swm/deactivateToggleUlb', 'UlbActiveDeactive');
        Route::post('swm/getUlbById', 'UlbById');

        Route::post('swm/getWardList', 'WardList');
        Route::post('swm/postWardAdd', 'WardAdd');
        Route::put('swm/postWardUpdate', 'WardUpdate');
        Route::put('swm/getWardListById', 'WardById');
    });


    Route::controller(ReportController::class)->group(function () {
        Route::post('swm/getReportData', 'GetReportData');              // Route for get all type of report
        Route::post('swm/getDemandReceiptData', 'GetDemandReceiptData'); // Route for get all demand receipt report
    });
});






// Route::controller(ApartmentController::class)->group(function () {
//     Route::get('getApartmentList', 'GetApartmentList');
// });