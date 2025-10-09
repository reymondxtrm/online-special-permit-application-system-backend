<?php

namespace App\Http\Controllers;

use App\Events\DocumentStageMoved;
use Exception;
use App\Models\User;
use App\Models\PermitType;
use App\Models\ExemptedCase;
use Illuminate\Http\Request;
use App\Models\PaymentDetail;
use App\Models\StatusHistory;
use App\Models\DiscountedCase;
use App\Models\OrderOfPayment;
use App\Models\SpecialPermitType;
use App\Models\ApplicationPurpose;
use Illuminate\Support\Facades\DB;
use App\Models\ExemptedCaseHistory;
use App\Models\SpecialPermitStatus;
use Illuminate\Support\Facades\Auth;
use App\Models\DiscountedCaseHistory;
use App\Models\CompletedSpecialPermit;
use App\Models\SpecialPermitApplication;
use App\Models\PermitApplicationDiscount;
use App\Models\PermitApplicationExemption;
use App\Models\ReferenceCode;
use App\Services\SmsService;
use Carbon\Carbon;
use League\CommonMark\Reference\Reference;
use PhpParser\Node\Stmt\TryCatch;

class SpecialPermitAdminController extends Controller
{
    protected $sms;
    public function count($doc_type_id, $status_id)
    {
        $count = SpecialPermitApplication::where('special_permit_type_id', $doc_type_id)->where('special_permit_status_id', $status_id)
            ->whereNull('mark_as_read')
            ->count();
        return $count;
    }

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
    }



    // $message = "Hello {$user->first_name}, your permit {$permit->permit_code} has been approved.";

    // $response = $this->sms->sendSms($user->contact_no, $message);
    // Inject it like thiss


    public function getCertificateData(Request $rq)
    {

        request()->validate([
            'special_permit_id' => 'required',
        ]);

        // $data = SpecialPermitApplication::where('id', $rq->special_permit_id)
        // ->first();
        $data = DB::table('special_permit_applications as application')
            ->where('application.id', $rq->special_permit_id)
            ->join('users', 'users.id', '=', 'application.user_id')
            ->join('order_of_payments', 'order_of_payments.special_permit_application_id', '=', 'application.id')
            ->join('payment_details', 'payment_details.order_of_payment_id', '=', 'order_of_payments.id')
            ->join('user_addresses', 'user_addresses.user_id', '=', 'users.id')
            ->join('users as approved_by', 'approved_by.id', '=', 'order_of_payments.admin_id')
            ->join('uploaded_files', 'uploaded_files.special_permit_application_id', '=', 'application.id')
            ->select(
                DB::raw("CONCAT(approved_by.fname, ' ', CASE WHEN approved_by.mname IS NOT NULL AND approved_by.mname <> '' THEN CONCAT(LEFT(approved_by.mname, 1), '. ') ELSE '' END,COALESCE(approved_by.lname, ''), ' ', COALESCE(approved_by.suffix, '')) as approved_by"),
                'payment_details.paid_amount',
                'payment_details.or_no as or_no',
                'application.reference_no as application_reference',
                'application.*',
                DB::raw("DATE_FORMAT(application.event_date_from, '%M %e, %Y (%W)') as eventFromDate"),
                DB::raw("DATE_FORMAT(application.event_date_to, '%M %e, %Y (%W)') as eventToDate"),
                DB::raw("CONCAT(COALESCE(user_addresses.address_line, ''),IF(user_addresses.address_line IS NOT NULL, ', ', ''),'Barangay ',COALESCE(user_addresses.barangay, ''),', ',COALESCE(user_addresses.city, '')) AS address"),
                DB::raw("UPPER(CONCAT(users.fname, ' ', COALESCE(users.mname, ''), ' ', COALESCE(users.lname, ''), ' ', COALESCE(users.suffix, ''))) as applicant_name"),
                'order_of_payments.*',
                'payment_details.*',
                DB::raw("(SELECT name FROM exempted_cases WHERE exempted_cases.id = order_of_payments.exempted_case_id) as exempted_case_name"),
                DB::raw("(SELECT ordinance FROM exempted_cases WHERE exempted_cases.id = order_of_payments.exempted_case_id) as exempted_case_ordinance"),
                'uploaded_files.route_plan as route_plan',
            )
            ->first();

        return $data;
    }

    public function approvePayment(Request $rq)
    {
        request()->validate([
            'order_of_payment_id' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $order_of_payment = OrderOfPayment::where('id', $rq->order_of_payment_id)
                ->first();

            $user = Auth::user();

            if ($order_of_payment) {

                $permit_application = SpecialPermitApplication::where('id', $order_of_payment->special_permit_application_id)
                    ->first();
                $permit_type = SpecialPermitType::where('id', $permit_application->special_permit_type_id)->first(['id', 'name', 'code']);

                $reference_code = "";
                $year = Carbon::now()->year;
                $typeKey = $permit_type->code;
                $format = null;
                switch ($typeKey) {
                    case 'good_moral':
                        $format = ['prefix' => 'CGM', 'pad' => 4];
                        break;
                    case 'mayors_permit':
                        $format = ['prefix' => 'C', 'pad' => 5];
                        break;
                    case 'event':
                        $format = ['prefix' => 'EVT', 'pad' => 4];
                        break;
                    case 'motorcade':
                        $format = ['prefix' => 'MOT', 'pad' => 4];
                        break;
                    case 'parade':
                        $format = ['prefix' => 'PAR', 'pad' => 4];
                        break;
                    case 'recorrida':
                        $format = ['prefix' => 'REC', 'pad' => 4];
                        break;
                    case 'use_of_government_property':
                        $format = ['prefix' => 'UGP', 'pad' => 5];
                        break;
                    case 'occupational__permit':
                        $format = ['prefix' => 'COP', 'pad' => 4];
                        break;
                    default:
                        $format = ['prefix' => 'GEN', 'pad' => 4];
                }

                $current_code = ReferenceCode::firstOrCreate(
                    ['permit_type' => $typeKey],
                    ['current_reference_code' => 0]
                );


                ReferenceCode::where('id', $current_code->id)->increment('current_reference_code');
                $current_code = ReferenceCode::where('id', $current_code->id)->first(['current_reference_code', 'id']);

                $reference_code = $year . '-' . $format['prefix'] . '-' . 'ON' . '-' . str_pad($current_code->current_reference_code, $format['pad'], '0', STR_PAD_LEFT);
                $new_status = SpecialPermitStatus::where('code', 'for_signature')->first();

                $special = DB::table('special_permit_applications')
                    ->where('id', $order_of_payment->special_permit_application_id)
                    ->update([
                        'special_permit_status_id' => $new_status->id,
                        'reference_no' => $reference_code,
                    ]);
                $payment = DB::table('payment_details')
                    ->where('order_of_payment_id', $rq->order_of_payment_id)
                    ->update([
                        'status' => 'approved',
                        'admin_id' => $user->id
                    ]);

                if ($payment == 0 || $special == 0) {
                    return response([
                        'message' => 'No payment to approve'
                    ], 400);
                }

                $status_history = new StatusHistory();
                $status_history->user_id = $user->id;
                $status_history->special_permit_application_id = $order_of_payment->special_permit_application_id;
                $status_history->special_permit_status_id = $new_status->id;
                $status_history->save();
                // $this->sms->sendSms(
                //     $permit_application->user->userDetails->phone_number,
                //     "Hello " . $permit_application->user->fname . ", your " . $permit_type->name . " application with reference no. " . $reference_code . " is now approved. You may now wait for the release of your certificate. Thank you!"
                // );
                /** @var \App\Models\User $client */
                $stage = "FOR SIGNATURE";
                $type = $permit_type->name;
                // $client->sendPermitNotification($stage, $type, $reference_code);
                $permit_application->mark_as_read = null;
                $permit_application->save();
                $permit_type = SpecialPermitType::where('id', $permit_application->special_permit_type_id)->first();
                $count = $this->count($permit_type->id, $new_status->id);
                broadcast(new DocumentStageMoved($permit_type->code, 'for_signature', $count));
                DB::commit();
                return response([
                    'message' => 'success'
                ], 200);
            } else {
                return response([
                    'message' => 'Permit Application not found'
                ], 404);
            }
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function returnPayment(Request $rq)
    {
        request()->validate([
            'order_of_payment_id' => 'required',
            'remarks' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $order_of_payment = OrderOfPayment::where('id', $rq->order_of_payment_id)
                ->first();

            $user = Auth::user();
            $special_permit_application = SpecialPermitApplication::where('id', $order_of_payment->special_permit_application_id)->first();
            if ($order_of_payment) {

                $new_status = SpecialPermitStatus::where('code', 'returned')->first();
                $client = User::where('id', $special_permit_application->user_id)->first();
                $permit_type = SpecialPermitType::where('id', $special_permit_application->special_permit_type_id)->first();
                DB::table('special_permit_applications')
                    ->where('id', $order_of_payment->special_permit_application_id)
                    ->update([
                        'special_permit_status_id' => $new_status->id,
                    ]);
                $status_history = new StatusHistory();
                $status_history->user_id = $user->id;
                $status_history->special_permit_application_id = $order_of_payment->special_permit_application_id;
                $status_history->special_permit_status_id = $new_status->id;
                $status_history->remarks = $rq->remarks;
                $status_history->save();
                $client->sendDisapprovalNotification($rq->remarks, $permit_type->name);
            } else {
                return response([
                    'message' => 'Permit Application not found'
                ]);
            }


            DB::commit();

            return response([
                'message' => 'success'
            ]);
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }


    public function approveExemption(Request $rq)
    {
        $rq->validate([
            // 'permit_application_exemption_id' => 'required',
        ]);

        DB::beginTransaction();

        try {
            $user = Auth::user();

            // Get related permit application
            $permit_application = SpecialPermitApplication::findOrFail($rq->special_permit_application_id);

            // If admin is adding a new exemption
            if ($rq->admin) {
                $permit_type = SpecialPermitType::find($permit_application->special_permit_type_id);

                $exemption = new PermitApplicationExemption();
                $exemption->special_permit_application_id = $permit_application->id;
                $exemption->exempted_case_id = $rq->exemption_id;

                if ($rq->hasFile('exemption_proof')) {
                    $filePath = $rq->file('exemption_proof')->storeAs(
                        'exemption/applications/' . $permit_application->id,
                        date('YmdHi') . '-' . $permit_type->code . '.' . $rq->file('exemption_proof')->getClientOriginalExtension(),
                        'public'
                    );
                    $exemption->attachment = $filePath;
                }

                $exemption->status = 'approved';
                $exemption->user_id = $user->id;
                $exemption->save();

                // Make sure we use the newly created exemption in the next step
                $permit_exemption = $exemption;
            } else {
                // Otherwise, fetch the existing exemption
                $permit_exemption = PermitApplicationExemption::find($rq->permit_application_exemption_id);
            }

            // If no exemption record found
            if (!$permit_exemption) {
                return response(['message' => 'Permit Exemption not found'], 404);
            }

            // Create Order of Payment
            $order_of_payment = new OrderOfPayment();
            $order_of_payment->special_permit_application_id = $permit_application->id;
            $order_of_payment->permit_application_exemption_id = $permit_exemption->id;
            $order_of_payment->exempted_case_id = $permit_exemption->exempted_case_id;
            $order_of_payment->applicant_id = $permit_application->user_id;
            $order_of_payment->admin_id = $user->id;
            $order_of_payment->billed_amount = 0;
            $order_of_payment->total_amount = 0;
            $order_of_payment->save();

            $payment_details = new PaymentDetail();
            $payment_details->order_of_payment_id = $order_of_payment->id;
            $payment_details->special_permit_application_id = $order_of_payment->special_permit_application_id;
            $payment_details->paid_amount = 0;
            $payment_details->reference_no = null;
            $payment_details->or_no = null;
            $payment_details->attachment = null;
            $payment_details->applicant_id = $permit_application->user_id;
            $payment_details->admin_id = $user->id;
            $payment_details->payment_type = 'waived';
            $payment_details->status = 'waived';
            $payment_details->save();


            $new_status = SpecialPermitStatus::where('code', 'for_signature')->first();

            // $permit_application->update([
            //     'special_permit_status_id' => $new_status->id,
            //     'mark_as_read' => null,
            // ]);
            $permit_application->special_permit_status_id = $new_status->id;
            $permit_application->mark_as_read = null;
            $permit_application->save();

            $history = new StatusHistory();
            $history->user_id = $user->id;
            $history->special_permit_application_id = $permit_application->id;
            $history->Special_permit_status_id = $new_status->id;
            $history->save();

            // Send notifications
            $permit_type = SpecialPermitType::find($permit_application->special_permit_type_id);
            $count = $this->count($permit_application->special_permit_type_id, $new_status->id);
            broadcast(new DocumentStageMoved($permit_type->code, 'for_payment', $count));

            $client = User::find($permit_application->user_id);
            $client->sendPermitNotification($permit_type->name);
            $reference_code = "";
            $year = Carbon::now()->year;
            $typeKey = $permit_type->code;
            $format = null;
            switch ($typeKey) {
                case 'good_moral':
                    $format = ['prefix' => 'CGM', 'pad' => 4];
                    break;
                case 'mayors_permit':
                    $format = ['prefix' => 'C', 'pad' => 5];
                    break;
                case 'event':
                    $format = ['prefix' => 'EVT', 'pad' => 4];
                    break;
                case 'motorcade':
                    $format = ['prefix' => 'MOT', 'pad' => 4];
                    break;
                case 'parade':
                    $format = ['prefix' => 'PAR', 'pad' => 4];
                    break;
                case 'recorrida':
                    $format = ['prefix' => 'REC', 'pad' => 4];
                    break;
                case 'use_of_government_property':
                    $format = ['prefix' => 'UGP', 'pad' => 5];
                    break;
                case 'occupational__permit':
                    $format = ['prefix' => 'COP', 'pad' => 4];
                    break;
                default:
                    $format = ['prefix' => 'GEN', 'pad' => 4];
            }

            $current_code = ReferenceCode::firstOrCreate(
                ['permit_type' => $typeKey],
                ['current_reference_code' => 0]
            );


            ReferenceCode::where('id', $current_code->id)->increment('current_reference_code');
            $current_code = ReferenceCode::where('id', $current_code->id)->first(['current_reference_code', 'id']);

            $reference_code = $year . '-' . $format['prefix'] . '-' . 'ON' . '-' . str_pad($current_code->current_reference_code, $format['pad'], '0', STR_PAD_LEFT);
            $new_status = SpecialPermitStatus::where('code', 'for_signature')->first();

            DB::table('special_permit_applications')
                ->where('id', $order_of_payment->special_permit_application_id)
                ->update([
                    'special_permit_status_id' => $new_status->id,
                    'reference_no' => $reference_code,
                ]);

            DB::commit();

            return response(['message' => 'success'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response(['message' => $e->getMessage()], 500);
        }
    }
    // public function approveExemption(Request $rq)
    // {
    //     request()->validate([
    //         'permit_application_exemption_id' => 'required',
    //     ]);

    //     DB::beginTransaction();
    //     try {

    //         $user = Auth::user();

    //         $permit_application = SpecialPermitApplication::where('id', $permit_exemption->special_permit_application_id)
    //             ->first();

    //         if ($rq->admin) {
    //             $exemption = new PermitApplicationExemption();
    //             $exemption->special_permit_application_id = $permit_application->id;
    //             $exemption->exempted_case_id = $rq->exemption_id;
    //             $exemption->attachment = $rq->file('exemption_proof')->storeAs('exemption/applications/' . $goodMoral->id, date('YmdHi') . '-' . $permit_type->code . '.' . $rq->file('exemption_proof')->getClientOriginalExtension(), 'public');
    //             $exemption->status = 'approved';
    //             $exemption->save();
    //         }
    //         $permit_exemption = PermitApplicationExemption::where('id', $rq->permit_application_exemption_id)
    //             ->first(); {
    //         }

    //         $client = User::where('id', $permit_application->user_id)->first();
    //         if ($permit_exemption) {
    //             $order_of_payment = new OrderOfPayment();
    //             $order_of_payment->special_permit_application_id = $permit_application->id;
    //             $order_of_payment->permit_application_exemption_id = $permit_exemption->id;
    //             $order_of_payment->exempted_case_id = $permit_exemption->exempted_case_id;
    //             $order_of_payment->applicant_id = $permit_application->user_id;
    //             $order_of_payment->admin_id = $user->id;
    //             $order_of_payment->billed_amount = 0;
    //             // $order_of_payment->exemption_amount = null;
    //             $order_of_payment->total_amount = 0;
    //             $order_of_payment->save();

    //             $payment_details = new PaymentDetail();
    //             $payment_details->order_of_payment_id = $order_of_payment->id;
    //             $payment_details->special_permit_application_id = $order_of_payment->special_permit_application_id;
    //             $payment_details->paid_amount = 0;
    //             $payment_details->reference_no = null;
    //             $payment_details->or_no = null;
    //             $payment_details->attachment = null;
    //             $payment_details->applicant_id = $permit_application->user_id;
    //             $payment_details->admin_id = $user->id;
    //             $payment_details->payment_type = 'waived';
    //             $payment_details->status = 'waived';
    //             $payment_details->save();

    //             $new_status = SpecialPermitStatus::where('code', 'for_signature')->first();

    //             DB::table('special_permit_applications')
    //                 ->where('id', $permit_application->id)
    //                 ->update([
    //                     'special_permit_status_id' => $new_status->id,
    //                 ]);

    //             $status_history = new StatusHistory();
    //             $status_history->user_id = $user->id;
    //             $status_history->special_permit_application_id = $rq->special_permit_application_id;
    //             $status_history->special_permit_status_id = $new_status->id;
    //             $status_history->save();


    //             PermitApplicationExemption::where('id', $rq->permit_application_exemption_id)
    //                 ->update(
    //                     [
    //                         'status' => 'approved',
    //                         'user_id' => $user->id
    //                     ]
    //                 );
    //             $permit_application->mark_as_read = null;
    //             $permit_application->save();
    //             $permit_type = SpecialPermitType::where('id', $permit_application->special_permit_type_id)->first();
    //             $count = $this->count($permit_application->special_permit_type_id, $new_status->id);
    //             broadcast(new DocumentStageMoved($permit_type->code, 'for_payment', $count));
    //             $client->sendPermitNotification($permit_type->name);
    //             DB::commit();

    //             return response([
    //                 'message' => 'success'
    //             ]);
    //         } else {
    //             return response([
    //                 'message' => 'Permit Exemption not found'
    //             ]);
    //         }
    //     } catch (Exception $e) {

    //         DB::rollback();
    //         return response(['message' => $e->getMessage()], 500);
    //     }
    // }

    public function declineExemption(Request $rq)
    {
        request()->validate([
            'permit_application_exemption_id' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $user = Auth::user();

            $permit_exemption = PermitApplicationExemption::where('id', $rq->permit_application_exemption_id)
                ->first();
            $client = User::where('id', $rq->user_id)->first();
            if ($permit_exemption) {
                $permit_application =  PermitApplicationExemption::where('id', $rq->permit_application_exemption_id)->first();
                PermitApplicationExemption::where('id', $rq->permit_application_exemption_id)
                    ->update(
                        [
                            'status' => 'declined',
                            'user_id' => $user->id
                        ]
                    );
                $permit_type = SpecialPermitType::where('id', $permit_application->special_permit_type_id)->first();
                $client->sendDisapprovalNotification('Your request for exemption was not granted. Please proceed with the regular requirement', $permit_type->name);
                DB::commit();
                return response([
                    'message' => 'success'
                ]);
            } else {
                return response([
                    'message' => 'Permit Exemption not found'
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }


    public function approveDiscount(Request $rq)
    {
        request()->validate([
            'permit_application_discount_id' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $check_permit_discount = PermitApplicationDiscount::where('id', $rq->permit_application_discount_id)
                ->first();

            if ($check_permit_discount) {

                PermitApplicationDiscount::where('id', $rq->permit_application_discount_id)
                    ->update(
                        [
                            'status' => 'approved'
                        ]
                    );

                DB::commit();

                return response([
                    'message' => 'success'
                ]);
            } else {
                return response([
                    'message' => 'Permit Discount not found'
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function declineDiscount(Request $rq)
    {
        request()->validate([
            'permit_application_discount_id' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $check_permit_discount = PermitApplicationDiscount::where('id', $rq->permit_application_discount_id)
                ->first();

            if ($check_permit_discount) {

                PermitApplicationDiscount::where('id', $rq->permit_application_discount_id)
                    ->update(
                        [
                            'status' => 'declined'
                        ]
                    );

                DB::commit();

                return response([
                    'message' => 'success'
                ]);
            } else {
                return response([
                    'message' => 'Permit Discount not found'
                ]);
            }
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function changePurpose(Request $rq)
    {
        request()->validate([
            'purpose_id' => 'required',
            'application_id' => 'required',
        ]);

        DB::beginTransaction();
        try {

            SpecialPermitApplication::where('id', $rq->application_id)
                ->update(
                    [
                        'application_purpose_id' => $rq->purpose_id
                    ]
                );

            DB::commit();

            return response([
                'message' => 'success'
            ]);
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function addToListPurpose(Request $rq)
    {
        request()->validate([
            'purpose_id' => 'required',
            'purpose_name' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $purpose = ApplicationPurpose::where('id', $rq->purpose_id)
                ->first();

            if (!$purpose) {

                return response([
                    'message' => 'record not found!'
                ], 500);
            } elseif ($purpose->type === 'permanent') {

                return response([
                    'message' => 'purpose already added!'
                ], 500);
            } else {

                ApplicationPurpose::where('id', $rq->purpose_id)
                    ->update(
                        [
                            'name' => $rq->purpose_name,
                            'type' => 'permanent',
                        ]
                    );
            }

            DB::commit();

            return response([
                'message' => 'success'
            ]);
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function getPermitTypes()
    {
        $permit_types = SpecialPermitType::all();

        return $permit_types;
    }

    public function getDiscountedCases()
    {
        // $discount_cases = DiscountedCase::all();

        $discounted_cases = DB::table('discounted_cases')
            ->join('special_permit_types', 'special_permit_types.id', '=', 'discounted_cases.special_permit_type_id')
            ->select('discounted_cases.*', 'special_permit_types.name as permit_type')
            ->get();

        return $discounted_cases;
    }

    public function getExemptedCases()
    {
        // $discount_cases = DiscountedCase::all();

        $exempted_cases = DB::table('exempted_cases')
            ->join('special_permit_types', 'special_permit_types.id', '=', 'exempted_cases.special_permit_type_id')
            ->select('exempted_cases.*', 'special_permit_types.name as permit_type')
            ->get();

        return $exempted_cases;
    }

    public function getPermitPurpose()
    {
        // $discount_cases = DiscountedCase::all();

        $purposes = DB::table('application_purposes')
            ->join('special_permit_types', 'special_permit_types.id', '=', 'application_purposes.special_permit_type_id')
            ->select('application_purposes.*', 'special_permit_types.name as permit_type')
            ->orderBy('permit_type')
            ->get();

        return $purposes;
    }

    public function createDiscountCase(Request $rq)
    {
        request()->validate([
            'permit_type' => 'sometimes',
            'name' => 'required',
            'attachment' => 'sometimes',
            'discount_percent' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $user = Auth::user();

            $permit_type = SpecialPermitType::where('id', $rq->permit_type)->first();

            // return $permit_type;

            $discount_case = new DiscountedCase();
            $discount_case->special_permit_type_id = $rq->permit_type;
            $discount_case->name = strtoupper($rq->name);
            // $discount_case->attachment = $rq->attachment;
            $discount_case->attachment = null;
            $discount_case->discount_percent = $rq->discount_percent;
            $discount_case->save();

            DiscountedCase::where('id', $discount_case->id)
                ->update(
                    [
                        'attachment' => $rq->file('attachment')->storeAs('discount/attachment/' . $discount_case->id, date('YmdHi') . '-' . $permit_type->code . '.' . $rq->file('attachment')->getClientOriginalExtension(), 'public')
                    ]
                );

            $discount_case_history = new DiscountedCaseHistory();
            $discount_case_history->discounted_case_id = $discount_case->id;
            $discount_case_history->user_id = $user->id;
            $discount_case_history->attachment = $discount_case->attachment;
            $discount_case_history->discount_percent = $discount_case->dicount_percent;
            $discount_case_history->save();


            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function createExemptedCase(Request $rq)
    {
        request()->validate([
            'permit_type' => 'sometimes',
            'name' => 'required',
            'ordinance' => "required",
            'attachment' => 'sometimes',
        ]);

        DB::beginTransaction();
        try {

            $user = Auth::user();

            $permit_type = SpecialPermitType::where('id', $rq->permit_type)->first();

            // return $permit_type;

            $exemption_case = new ExemptedCase();
            $exemption_case->special_permit_type_id = $rq->permit_type;
            $exemption_case->name = strtoupper($rq->name);
            $exemption_case->ordinance = strtoupper($rq->ordinance);
            // $exemption_case->attachment = $rq->attachment;
            $exemption_case->save();
            if ($rq->attachment) {
                $filePath = $rq->file('attachment')->storeAs(
                    'discount/attachment/' . $exemption_case->id,
                    date('YmdHi') . '-' . $permit_type->code . '.' . $rq->file('attachment')->getClientOriginalExtension(),
                    'public'
                );
                $exemption_case->attachment = $filePath;
                $exemption_case->save();
            } else {
                $exemption_case->attachment = null;
                $exemption_case->save();
            }


            $exemption_case_history = new ExemptedCaseHistory();
            $exemption_case_history->exempted_case_id = $exemption_case->id;
            $exemption_case_history->user_id = $user->id;
            $exemption_case_history->attachment = $exemption_case->attachment;
            $exemption_case_history->save();


            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function editDiscountCase(Request $rq)
    {

        request()->validate([
            'discounted_case_id' => 'sometimes',
            'name' => 'required',
            'attachment' => 'sometimes',
            'dicount_percent' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $user = Auth::user();

            DiscountedCase::where('id', $rq->discounted_case_id)
                ->update(
                    [
                        'name' => $rq->name,
                        'attachment' => $rq->attachment,
                        'dicount_percent' => $rq->dicount_percent,
                    ]
                );


            $discount_case_history = new DiscountedCaseHistory();
            $discount_case_history->discouted_case_id = $rq->discounted_case_id;
            $discount_case_history->user_id = $user->id;
            $discount_case_history->attachment = $rq->attachment;
            $discount_case_history->dicount_percent = $rq->dicounted_percent;
            $discount_case_history->save();

            DB::commit();

            return response([
                'message' => 'success'
            ]);
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function getSpecialPermitApplications(Request $rq)
    {
        request()->validate([
            'permit_type' => 'sometimes',
            'status' => 'required' //pending, for_payment, paid, accomplished
        ]);

        $permit_type = SpecialPermitType::where('code', $rq->permit_type)->first();
        $status = SpecialPermitStatus::where('code', $rq->status)->first();
        $applications = SpecialPermitApplication::where('special_permit_status_id', $status->id)
            ->when($rq->permit_type, function ($query) use ($permit_type) {
                $query->where('special_permit_type_id', $permit_type->id);
            })
            ->orderByRaw('CASE WHEN mark_as_read IS NULL THEN 0 ELSE 1 END, created_at DESC')
            ->with(['applicationPurpose' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['applicationPurpose' => function ($query) {
                $query->select('id', 'name', 'type');
            }])
            ->with([
                'user' => function ($query) {
                    $query->select(
                        'id',
                        'email',
                        DB::raw("CONCAT(fname, ' ', COALESCE(mname,''), ' ', lname, ' ', COALESCE(suffix, '')) as fullname"),
                        'sex as gender'
                    );
                },
                'user.userDetails' => function ($query) {
                    $query->select(
                        'id',
                        'user_id',
                        'birthdate',
                    );
                },
                'user.userPhoneNumbers' => function ($query) {
                    $query->where('type', 'primary');
                    $query->select('id', 'user_id', 'phone_number');
                },
                'uploadedFile'
            ])
            ->when($rq->permit_type === 'good_moral', function ($query) {
                $query->with(['permitApplicationExemption' => function ($subQuery) {
                    $subQuery->join('exempted_cases', 'permit_application_exemptions.exempted_case_id', '=', 'exempted_cases.id')
                        ->select(
                            'permit_application_exemptions.*',
                            'exempted_cases.name as exempted_case_name',
                        );
                }]);
            })
            ->when($rq->status === 'for_payment', function ($query) {
                $query->with(['orderOfPayment']);
            })
            ->when($rq->status === 'for_payment_approval' || $rq->status === 'returned', function ($query) {
                $query->with(['orderOfPayment.paymentDetail']);
            })
            ->when($rq->status == 'returned', function ($query) {
                $query->with(['statusHistories' => function ($query) {
                    $query->join('special_permit_statuses', 'special_permit_statuses.id', '=', 'status_histories.special_permit_status_id')
                        ->where('special_permit_statuses.code', 'returned')
                        ->select('status_histories.*');
                }]);
            })
            ->get();

        return $applications;
    }

    public function checkAttachments(Request $rq)
    {

        DB::beginTransaction();
        try {
            request()->validate([
                'special_permit_application_id' => 'required',
                'billed_amount' => 'required'
            ]);

            $user = Auth::user();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $check_permit = SpecialPermitApplication::where('id', $rq->special_permit_application_id)->where('special_permit_status_id', $status->id)->first();
            $client = User::where('id', $check_permit->user_id)->first();

            if ($rq->event_type) {
                $check_permit->event_type = $rq->event_type;
                $check_permit->save();
            }
            $applicant = null;
            if ($check_permit && $check_permit->user_id) {
                $applicant = User::where('id', $check_permit->user_id)->first();
            }

            if ($check_permit) {

                $order_of_payment = new OrderOfPayment();
                $order_of_payment->special_permit_application_id = $rq->special_permit_application_id;
                $order_of_payment->applicant_id = $check_permit->user_id;
                $order_of_payment->admin_id = $user->id;
                $order_of_payment->billed_amount = $rq->billed_amount;

                $check_exemption = DB::table('permit_application_exemptions')->where('special_permit_application_id', $rq->special_permit_application_id)
                    ->first();

                $new_status = SpecialPermitStatus::where('code', 'for_payment')->first();

                $order_of_payment->exempted_case_id = null;
                // $order_of_payment->exemption_amount = null;
                $order_of_payment->permit_application_exemption_id = null;
                $order_of_payment->total_amount = $rq->billed_amount;


                $order_of_payment->save();


                DB::table('special_permit_applications')
                    ->where('id', $rq->special_permit_application_id)
                    ->update([
                        'special_permit_status_id' => $new_status->id,
                    ]);

                $status_history = new StatusHistory();
                $status_history->user_id = $user->id;
                $status_history->special_permit_application_id = $rq->special_permit_application_id;
                $status_history->special_permit_status_id = $new_status->id;
                $status_history->save();
                $check_permit->mark_as_read = null;
                $check_permit->save();

                $permit_type = SpecialPermitType::where('id', $check_permit->special_permit_type_id)->first();
                $count = $this->count($permit_type->id, $new_status->id);
                broadcast(new DocumentStageMoved($permit_type->code, 'for_payment', $count));
                $client->sendPermitNotification($permit_type->name);
                DB::commit();
                return response([
                    'message' => "success"
                ]);
            } else {

                return response([
                    'message' => "Permit Already For Payment"
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function declineApplication(Request $rq)
    {
        request()->validate([
            'special_permit_application_id' => 'required',
            'remarks' => 'required'
        ]);


        DB::beginTransaction();
        try {

            $user = Auth::user();

            $status = SpecialPermitStatus::where('code', 'pending')->first();

            $check_permit = DB::table('special_permit_applications')->where('id', $rq->special_permit_application_id)
                ->where('special_permit_status_id', $status->id)
                ->first();

            if ($check_permit) {
                $new_status = SpecialPermitStatus::where('code', 'declined')->first();

                DB::table('special_permit_applications')
                    ->where('id', $rq->special_permit_application_id)
                    ->update([
                        'special_permit_status_id' => $new_status->id,
                    ]);

                $status_history = new StatusHistory();
                $status_history->user_id = $user->id;
                $status_history->special_permit_application_id = $rq->special_permit_application_id;
                $status_history->special_permit_status_id = $new_status->id;
                $status_history->remarks = $rq->remarks;
                $status_history->save();
                $client = User::where('id', $check_permit->user_id)->first();
                $special_permit_type = SpecialPermitType::where('id', $check_permit->special_permit_type_id)->first();
                $client->sendDisapprovalNotification($rq->remarks, $special_permit_type->name);
            }


            DB::commit();

            return response([
                'message' => 'success'
            ]);
        } catch (Exception $e) {

            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function uploadPermit(Request $rq)
    {
        DB::beginTransaction();
        try {
            request()->validate([
                'special_permit' => 'required|file|mimes:pdf',
                'special_permit_application_id' => 'required',
                'permit_type' => 'required'
            ]);

            $user = Auth::user();

            $status = SpecialPermitStatus::where('code', 'for_signature')->first();
            $check_permit = DB::table('special_permit_applications')->where('id', $rq->special_permit_application_id)
                ->where('special_permit_status_id', $status->id)
                ->first();

            if ($check_permit) {

                $new_status = SpecialPermitStatus::where('code', 'completed')->first();

                DB::table('special_permit_applications')
                    ->where('id', $rq->special_permit_application_id)
                    ->update([
                        'special_permit_status_id' => $new_status->id,
                    ]);

                $status_history = new StatusHistory();
                $status_history->user_id = $user->id;
                $status_history->special_permit_application_id = $rq->special_permit_application_id;
                $status_history->special_permit_status_id = $new_status->id;
                $status_history->save();

                $complete_permit = new CompletedSpecialPermit();
                $complete_permit->file = $rq->file('special_permit')->storeAs('completed_permits/' . $rq->permit_type . '/' . $check_permit->user_id, date('YmdHi') . '-' . $rq->permit_type . '.pdf', 'public');
                $complete_permit->special_permit_application_id = $rq->special_permit_application_id;
                $complete_permit->applicant_id = $check_permit->user_id;
                $complete_permit->admin_id = $user->id;
                $complete_permit->save();
                $permit_type = SpecialPermitType::where('id', $check_permit->special_permit_type_id)->first();
                $client = User::where('id', $check_permit->user_id)->first();
                $client->sendIssuancePermitNotifcation($permit_type->name);
                DB::commit();
                return response([
                    'message' => "success"
                ]);
            } else {

                return response([
                    'message' => "Permit Already Completed"
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }
    public function getCount(Request $rq)
    {
        $counts = SpecialPermitApplication::selectRaw('
        special_permit_types.code AS code,
        COUNT(*) AS total
    ')
            ->join('special_permit_types', 'special_permit_applications.special_permit_type_id', '=', 'special_permit_types.id')
            ->where('special_permit_applications.special_permit_status_id', $rq->permit_type_id)
            ->whereNull('special_permit_applications.mark_as_read')
            ->groupBy('special_permit_applications.special_permit_type_id', 'special_permit_types.code')
            ->get();

        return response()->json($counts, 200);
    }
}
