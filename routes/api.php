<?php

use Illuminate\Http\Request;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckType;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\PublicUserController;
use App\Http\Controllers\SpecialPermitController;
use App\Http\Controllers\AuthenticatedUserController;
use App\Http\Controllers\SpecialPermitAdminController;
use App\Http\Controllers\SpecialPermitClientController;
use App\Http\Middleware\Authenticate;
use Illuminate\Auth\Middleware\Authenticate as MiddlewareAuthenticate;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// For API that needs authenticated middleware - auth:sanctum
Route::group(['middleware' => ['auth:sanctum']], function () {


    Route::post('/truncate-tables', [AdminUserController::class, 'truncateTables'])
        ->middleware([
            CheckType::class . ':admin'
        ]);



    Route::get('/gender', [AuthenticatedUserController::class, 'getGender']);

    Route::post('/logout', [AdminUserController::class, 'logout']);

    // Authenticated User Routes
    Route::get('/get-roles', [AuthenticatedUserController::class, 'getRoles']);
    Route::get('/permit-types', [AuthenticatedUserController::class, 'permitTypes']);
    Route::get('/gender-types', [AuthenticatedUserController::class, 'genderTypes']);
    Route::get('/stages', [AuthenticatedUserController::class, 'stages']);
    Route::get('/analytics', [AuthenticatedUserController::class, 'analytics'])
        ->middleware([
            CheckType::class . ':admin',
            CheckRole::class . ':admin,initial_receiver,assessment_receiver,assessment_releaser,complete_receipphver,final_releaser'
        ]);
    Route::get('/summary', [AuthenticatedUserController::class, 'summary']);
    Route::get('/permit-status', [AuthenticatedUserController::class, 'permitStatus']);
    Route::post('/edit', [AuthenticatedUserController::class, 'editDocument']);
    Route::get('/releasing-data', [AuthenticatedUserController::class, 'releasingRecords']);
    Route::get('/receiver-images', [AuthenticatedUserController::class, 'getReceiverImages']);
    Route::get('/get-primary-id-type', [AuthenticatedUserController::class, 'getIdType']);

    // Special Permit Get Requests
    Route::get('/get-purpose', [AuthenticatedUserController::class, 'getPurposes']);

    Route::post('/email/verify/{id}/{hash}', [AuthenticatedUserController::class, 'emailVerification'])->name('verification.verify');
    // Admin Routes
    Route::group(['middleware' => ['CheckType:admin', 'CheckRole:super_admin,admin'], 'prefix' => 'admin'], function () {
        // Get Requests
        Route::get('/get-users', [AdminUserController::class, 'getUsers']);
        Route::get('/get-users', [AdminUserController::class, 'getUsers']);

        // Post Requests
        Route::post('/add-user', [AdminUserController::class, 'addUser']);
        Route::post('/add-roles', [AdminUserController::class, 'addRoles']);
        Route::post('/edit-stage-timestamp', [AdminUserController::class, 'editStageTimeStamp']);
        Route::post('/delete-user/{id}', [AdminUserController::class, 'deleteUser']);
    });

    // Initial Receiver Routes
    Route::group(['middleware' => ['CheckType:admin', 'CheckRole:super_admin,admin,initial_receiver', 'CheckType:admin'], 'prefix' => 'initial-receiver'], function () {
        // Get Requests
        Route::get('/dashboard', [AdminUserController::class, 'initialDashboard']);

        // Post Requests
        Route::post('/receive', [AdminUserController::class, 'initialReceive']);
        // Get list of existing permit
        Route::get('/existing-permit', [AuthenticatedUserController::class, 'getExistingPermit']);
    });

    // Assessment Receiver Routes
    Route::group(['middleware' => ['CheckType:admin', 'CheckRole:super_admin,admin,assessment_receiver'], 'prefix' => 'assessment-receiver'], function () {
        // Get Requests
        Route::get('/dashboard', [AdminUserController::class, 'assessmentReceiveDashboard']);

        // Post Requests
        Route::post('/receive', [AdminUserController::class, 'assessmentReceive']);
    });

    // Assessment Releaser Routes
    Route::group(['middleware' => ['CheckType:admin', 'CheckRole:super_admin,admin,assessment_releaser'], 'prefix' => 'assessment-releaser'], function () {
        // Get Requests
        Route::get('/dashboard', [AdminUserController::class, 'assessmentReleaseDashboard']);

        // Post Requests
        Route::post('/release', [AdminUserController::class, 'assessmentRelease']);
    });

    // Complete Receiver Routes
    Route::group(['middleware' => ['CheckType:admin', 'CheckRole:super_admin,admin,complete_receiver'], 'prefix' => 'complete-receiver'], function () {
        // Get Requests
        Route::get('/dashboard', [AdminUserController::class, 'completeReceiverDashboard']);

        // Post Requests
        Route::post('/receive', [AdminUserController::class, 'completeReceive']);
        Route::post('/receive-businessCode', [AdminUserController::class, 'completeReceive']);
    });

    // Final Releaser Routes
    Route::group(['middleware' => ['CheckType:admin', 'CheckRole:super_admin,admin,final_releaser'], 'prefix' => 'final-releaser'], function () {
        // Get Requests
        Route::get('/dashboard', [AdminUserController::class, 'finalReleaseDashboard']);

        // Post Requests
        Route::post('/release', [AdminUserController::class, 'finalRelease']);
        Route::post("/print", [AdminUserController::class, 'finalReleasePrinting']);
    });

    // Special Permit Routes
    Route::group(['middleware' => ['CheckType:admin', 'CheckRole:super_admin,admin,final_releaser'], 'prefix' => 'special-permit'], function () {
        // Get Requests
        Route::get('/guest', [AdminUserController::class, 'finalReleaseDashboard']);
        Route::get('/admin', [AdminUserController::class, 'finalReleaseDashboard']);

        // Post Requests
        // Route::post('/release', [AdminUserController::class, 'finalRelease']);
    });

    Route::group(['middleware' => ['CheckType:client', 'verified', 'auth'], 'prefix' => 'client', 'verified'], function () {

        // Get Requests
        Route::get('/user-details', [SpecialPermitClientController::class, 'getUserDetails']);
        Route::get('/special-permit/applications', [SpecialPermitClientController::class, 'getSpecialPermitApplications']);
        Route::get('/download-permit', [SpecialPermitClientController::class, 'downloadPermit']);
        Route::get('/get/discounted-cases', [SpecialPermitClientController::class, 'getDiscountedCases']);
        Route::get('/get/exempted-cases', [SpecialPermitClientController::class, 'getExemptedCases']);

        // Post Requests
        Route::post('/special-permit/mayors-permit', [SpecialPermitController::class, 'mayorsPermit']);
        Route::post('/special-permit/good-moral', [SpecialPermitController::class, 'goodMoral']);
        Route::post('/special-permit/event', [SpecialPermitController::class, 'event']);
        Route::post('/special-permit/motorcade', [SpecialPermitController::class, 'motorcade']);
        Route::post('/special-permit/parade', [SpecialPermitController::class, 'parade']);
        Route::post('/special-permit/recorrida', [SpecialPermitController::class, 'recorrida']);
        Route::post('/special-permit/use-of-government-property', [SpecialPermitController::class, 'useOfGovernmentProperty']);
        Route::post('/special-permit/occupational-permit', [SpecialPermitController::class, 'occupationalPermit']);
        Route::post('/edit/occupation-details', [SpecialPermitClientController::class, 'editOccupationDetails']);
        Route::post('/pay-permit', [SpecialPermitClientController::class, 'payPermit']);
        Route::post('/reupload-payment', [SpecialPermitClientController::class, 'reuploadPayment']);
        Route::post('/forgot-password', [SpecialPermitClientController::class, 'sendResetLinkEmail']);
        Route::post('/reset-password', [SpecialPermitClientController::class, 'reset']);
        Route::post('/change-password', [SpecialPermitClientController::class, 'changePassword']);
    });

    Route::group(['middleware' => ['CheckType:admin', 'CheckRole:special_permit_admin'], 'prefix' => 'admin'], function () {

        // Get Requests
        Route::get('/special-permit/applications', [SpecialPermitAdminController::class, 'getSpecialPermitApplications']);
        Route::get('/get/discounted-cases', [SpecialPermitAdminController::class, 'getDiscountedCases']);
        Route::get('/get/exempted-cases', [SpecialPermitAdminController::class, 'getExemptedCases']);
        Route::get('/get/purposes', [SpecialPermitAdminController::class, 'getPermitPurpose']);
        Route::get('/get/permit-types', [SpecialPermitAdminController::class, 'getPermitTypes']);
        Route::get('/special-permit/all-counts', [SpecialPermitAdminController::class, 'getCount']);
        // for Certificate data
        Route::get('/get/data/certificate', [SpecialPermitAdminController::class, 'getCertificateData']);

        // Post Requests
        Route::post('/approve-payment', [SpecialPermitAdminController::class, 'approvePayment']);
        Route::post('/return-payment', [SpecialPermitAdminController::class, 'returnPayment']);
        Route::post('/check-attachments', [SpecialPermitAdminController::class, 'checkAttachments']);  //from pending to for_payment 
        Route::post('/decline-application', [SpecialPermitAdminController::class, 'declineApplication']); //pending to decline
        Route::post('/upload-permit', [SpecialPermitAdminController::class, 'uploadPermit']);
        Route::post('/create/discount-case', [SpecialPermitAdminController::class, 'createDiscountCase']);
        Route::post('/create/exempted-case', [SpecialPermitAdminController::class, 'createExemptedCase']);
        Route::post('/edit/discount-case', [SpecialPermitAdminController::class, 'editDiscountCase']);
        Route::post('/add/list-purpose', [SpecialPermitAdminController::class, 'addToListPurpose']);
        Route::post('/change/purpose', [SpecialPermitAdminController::class, 'changePurpose']);
        Route::post('/approve/discount', [SpecialPermitAdminController::class, 'approveDiscount']);
        Route::post('/approve/exemption', [SpecialPermitAdminController::class, 'approveExemption']); //from pending to for_payment 
        Route::post('/decline/exemption', [SpecialPermitAdminController::class, 'declineExemption']); // decline from pending
        Route::post('/decline/discount', [SpecialPermitAdminController::class, 'declineDiscount']);
        Route::post('/update-tab-notification', [SpecialPermitController::class, 'updateTabNotification']);
    });
});

// Public Route
Route::post('/login', [PublicUserController::class, 'login']);
Route::post('/registration', [PublicUserController::class, 'specialPermitUserRegistration']);

Route::get('/geolocation/caraga', [PublicUserController::class, 'getCaragaGeolocations']);
Route::get('/get-civil-status', [PublicUserController::class, 'getCivilStatuses']);


// Special Permit Routes
Route::get('/uploaded-file', [SpecialPermitController::class, 'viewUploadedFile']);
Route::get('/files/{path}', function ($path) {
    return response()->file(storage_path('app/public/' . $path));
})->where('path', '.*');

Route::post('/email/resend', [PublicUserController::class, 'resendVerification'])->middleware(['auth:sanctum', 'throttle:6,1'])->name('verification.send');
Route::post('/forget-password', [PublicUserController::class, "forgetPassword"])->middleware('throttle:3,1');
