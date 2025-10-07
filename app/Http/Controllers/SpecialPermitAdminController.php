<?php

namespace App\Http\Controllers;

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

class SpecialPermitAdminController extends Controller
{
    protected $sms;

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
                // return $permit_type;
                $reference_code = "";
                $year = Carbon::now()->year;
                $client = User::where('id', $permit_application->user_id)->first(['id', 'fname', 'mname', 'lname', 'suffix', 'email']);

                if ($permit_type->code == "good_moral") {
                    $current_code = ReferenceCode::where('permit_type', "good_moral")->first(['current_reference_code', 'id']);
                    $reference_code = $year . '-' . 'CGM' . '-' . 'ON' . '-' . str_pad(($current_code->current_reference_code + 1), 4, '0', STR_PAD_LEFT);
                } else if ($permit_type->code == 'mayors_permit') {
                    $current_code = ReferenceCode::where('permit_type', "mayors_permit")->first(['current_reference_code', 'id']);
                    $reference_code = $year . '-' . 'C' . '-' . 'ON' . '-' . str_pad(($current_code->current_reference_code + 1), 5, '0', STR_PAD_LEFT);
                } else if ($permit_type->code == 'event') {
                    $current_code = ReferenceCode::where('permit_type', "event")->first(['current_reference_code', 'id']);
                    $reference_code = $year . '-' . 'EVT' . '-' . 'ON' . '-' . str_pad(($current_code->current_reference_code + 1), 3, '0', STR_PAD_LEFT);
                } else if ($permit_type->code == 'motorcade') {
                    $current_code = ReferenceCode::where('permit_type', "motorcade")->first(['current_reference_code', 'id']);
                    $reference_code = $year . '-' . 'MOT' . '-' . 'ON' . '-' . str_pad(($current_code->current_reference_code + 1), 3, '0', STR_PAD_LEFT);
                } else if ($permit_type->code == "parade") {
                    $current_code = ReferenceCode::where('permit_type', "parade")->first(['current_reference_code', 'id']);
                    $reference_code = $year . '-' . 'PAR' . '-' . 'ON' . '-' . str_pad(($current_code->current_reference_code + 1), 3, '0', STR_PAD_LEFT);
                } else if ($permit_type->code == "recorrida") {
                    $current_code = ReferenceCode::where('permit_type', "recorrida")->first(['current_reference_code', 'id']);
                    $reference_code = $year . '-' . 'REC' . '-' . 'ON' . '-' . str_pad(($current_code->current_reference_code + 1), 3, '0', STR_PAD_LEFT);
                } else if ($permit_type->code == "use_of_government_property") {
                    $current_code = ReferenceCode::where('permit_type', "use_of_government_property")->first(['current_reference_code', 'id']);
                    $reference_code = $year . '-' . 'UGP' . '-' . 'ON' . '-' . str_pad(($current_code->current_reference_code + 1), 5, '0', STR_PAD_LEFT);
                } else if ($permit_type->code == "occupational__permit") {
                    $current_code = ReferenceCode::where('permit_type', "occupational_permit")->first(['current_reference_code', 'id']);
                    $reference_code = $year . '-' . 'COP' . '-' . 'ON' . '-' . str_pad(($current_code->current_reference_code + 1), 3, '0', STR_PAD_LEFT);
                }
                $new_status = SpecialPermitStatus::where('code', 'for_signature')->first();
                ReferenceCode::where('id', $current_code->id)->update([
                    'current_reference_code' => $current_code->current_reference_code + 1
                ]);

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

            if ($order_of_payment) {

                $new_status = SpecialPermitStatus::where('code', 'returned')->first();

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
        request()->validate([
            'permit_application_exemption_id' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $user = Auth::user();

            $permit_exemption = PermitApplicationExemption::where('id', $rq->permit_application_exemption_id)
                ->first();

            $permit_application = SpecialPermitApplication::where('id', $permit_exemption->special_permit_application_id)
                ->first();

            if ($permit_exemption) {

                $order_of_payment = new OrderOfPayment();
                $order_of_payment->special_permit_application_id = $permit_application->id;
                $order_of_payment->permit_application_exemption_id = $permit_exemption->id;
                $order_of_payment->exempted_case_id = $permit_exemption->exempted_case_id;
                $order_of_payment->applicant_id = $permit_application->user_id;
                $order_of_payment->admin_id = $user->id;
                $order_of_payment->billed_amount = 0;
                // $order_of_payment->exemption_amount = null;
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

                DB::table('special_permit_applications')
                    ->where('id', $permit_application->id)
                    ->update([
                        'special_permit_status_id' => $new_status->id,
                    ]);

                $status_history = new StatusHistory();
                $status_history->user_id = $user->id;
                $status_history->special_permit_application_id = $rq->special_permit_application_id;
                $status_history->special_permit_status_id = $new_status->id;
                $status_history->save();




                PermitApplicationExemption::where('id', $rq->permit_application_exemption_id)
                    ->update(
                        [
                            'status' => 'approved',
                            'user_id' => $user->id
                        ]
                    );

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

            if ($permit_exemption) {

                PermitApplicationExemption::where('id', $rq->permit_application_exemption_id)
                    ->update(
                        [
                            'status' => 'declined',
                            'user_id' => $user->id
                        ]
                    );

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

            // $check_permit = DB::table('special_permit_applications')->where('id', $rq->special_permit_application_id)
            //     ->where('special_permit_status_id', $status->id)
            //     ->first();

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
        $counts = SpecialPermitApplication::selectRaw('special_permit_type_id, COUNT(*) as total')
            ->where('special_permit_status_id', $rq->permit_type_id)
            ->whereNull('mark_as_read')
            ->groupBy('special_permit_type_id')
            ->get();

        return response()->json(['counts' => $counts], 200);
    }
}
