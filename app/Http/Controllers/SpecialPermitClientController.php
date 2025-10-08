<?php

namespace App\Http\Controllers;

use App\Events\DocumentStageMoved;
use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\StatusHistory;
use App\Models\SpecialPermitType;
use Illuminate\Support\Facades\DB;
use App\Models\SpecialPermitStatus;
use App\Models\UserOccupationDetail;
use Illuminate\Support\Facades\Auth;
use App\Models\CompletedSpecialPermit;
use App\Models\OrderOfPayment;
use App\Models\PaymentDetail;
use App\Models\PermitType;
use Illuminate\Support\Facades\Storage;
use App\Models\SpecialPermitApplication;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class SpecialPermitClientController extends Controller
{
    //
    public function count($doc_type_id, $status_id)
    {
        $count = SpecialPermitApplication::where('special_permit_type_id', $doc_type_id)->where('special_permit_status_id', $status_id)
            ->whereNull('mark_as_read')
            ->count();
        return $count;
    }

    public function getExemptedCases(Request $rq)
    {
        request()->validate([
            'permit_type' => 'required',
        ]);


        $permit_type = SpecialPermitType::where('code', $rq->permit_type)->first();

        $exempted_case = DB::table('exempted_cases')
            ->where('exempted_cases.special_permit_type_id', $permit_type->id)
            ->join('special_permit_types', 'special_permit_types.id', '=', 'exempted_cases.special_permit_type_id')
            ->select('exempted_cases.*', 'special_permit_types.name as permit_type')
            ->get();

        return $exempted_case;
    }

    public function getDiscountedCases(Request $rq)
    {
        request()->validate([
            'permit_type' => 'required',
        ]);


        $permit_type = SpecialPermitType::where('code', $rq->permit_type)->first();

        $discounted_cases = DB::table('discounted_cases')
            ->where('discounted_cases.special_permit_type_id', $permit_type->id)
            ->join('special_permit_types', 'special_permit_types.id', '=', 'discounted_cases.special_permit_type_id')
            ->select('discounted_cases.*', 'special_permit_types.name as permit_type')
            ->get();

        return $discounted_cases;
    }

    public function getUserDetails(Request $rq)
    {
        $user = Auth::user();

        $user_details = User::where('id', $user->id)
            ->select(
                'id',
                DB::raw('CONCAT_WS(" ",UCASE(users.fname),UCASE(users.mname), UCASE(users.lname)) as full_name')
            )
            ->with(['userDetails' => function ($query) {
                $query->with('civilStatus');
            }])
            ->with('userPhoneNumbers')
            ->with('userAddresses')
            ->with('userOccupationDetails')
            ->first();


        return $user_details;
    }

    public function addOccupationDetials(Request $rq)
    {

        DB::beginTransaction();
        try {

            request()->validate([
                'position' => 'required',
                'company_name' => 'required',
                'date_hired' => 'required',
                'province' => 'required',
                'city' => 'required',
                'barangay' => 'required',
                'adress_line' => 'sometimes',
            ]);

            $user = Auth::user();

            $user_occupation_details = new UserOccupationDetail();
            $user_occupation_details->user_id = $user->id;
            $user_occupation_details->position = $rq->position;
            $user_occupation_details->company_name = $rq->company_name;
            $user_occupation_details->date_hired = $rq->date_hired;
            $user_occupation_details->province = $rq->province;
            $user_occupation_details->city = $rq->city;
            $user_occupation_details->barangay = $rq->barangay;
            $user_occupation_details->address_line = $rq->address_line;
            $user_occupation_details->save();

            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function editOccupationDetails(Request $rq)
    {
        DB::beginTransaction();
        try {
            // Validate the request
            $rq->validate([
                'position' => 'required',
                'companyName' => 'required',
                'dateHired' => 'required',
                'province' => 'required',
                'city' => 'required',
                'barangay' => 'required',
                'addressLine' => 'sometimes',
            ]);

            // Get the authenticated user
            $user = Auth::user();

            // Use updateOrCreate to handle create or update
            UserOccupationDetail::updateOrCreate(
                [
                    // Define the unique constraint for updating
                    'user_id' => $user->id,
                ],
                [
                    // Data to be inserted or updated
                    'position' => $rq->position,
                    'company_name' => $rq->companyName,
                    'date_hired' => $rq->dateHired,
                    'province' => $rq->province,
                    'city' => $rq->city,
                    'barangay' => $rq->barangay,
                    'address_line' => $rq->addressLine,
                ]
            );

            DB::commit();
            return response(['message' => "success"]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }


    public function getSpecialPermitApplications(Request $rq)
    {
        request()->validate([
            'permit_type' => 'sometimes', //mayors_permit, good_moral, event, motorcade, parade, recorrida, use_of_government_property, occupational_permit
            'status' => 'required' //pendng, for_payment, paid, accomplished
        ]);

        $user = Auth::user();
        $permit_type = SpecialPermitType::where('code', $rq->permit_type)->first();
        $status = SpecialPermitStatus::where('code', $rq->status)->first();


        // $applications = SpecialPermitApplication::where('user_id', $user->id)
        // ->when($rq->permit_type, function ($query) use ($permit_type) {
        //     $query->where('special_permit_type_id', $permit_type->id);
        // })
        // ->where('special_permit_status_id', $status->id)
        // ->with('uploadedFile')
        // ->get();

        $applications = SpecialPermitApplication::where('special_permit_status_id', $status->id)
            ->where('special_permit_applications.user_id', $user->id)
            ->when($rq->permit_type, function ($query) use ($permit_type) {
                $query->where('special_permit_type_id', $permit_type->id);
            })
            ->with(['applicationPurpose' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['applicationPurpose' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with([
                'user' => function ($query) {
                    $query->select(
                        'id',
                        'email',
                        DB::raw("CONCAT(fname, ' ', COALESCE(mname, ''), ' ', lname, ' ', COALESCE(suffix, '')) as fullname"),
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
            ->when($rq->status == 'declined', function ($query) {
                $query->with(['statusHistories' => function ($query) {
                    $query->join('special_permit_statuses', 'special_permit_statuses.id', '=', 'status_histories.special_permit_status_id')
                        ->where('special_permit_statuses.code', 'declined')
                        ->select('status_histories.*');
                }]);
            })
            ->when($rq->status == 'returned', function ($query) {
                $query->with(['statusHistories' => function ($query) {
                    $query->leftJoin('special_permit_statuses', 'special_permit_statuses.id', '=', 'status_histories.special_permit_status_id')
                        ->where('special_permit_statuses.code', 'returned')
                        ->select('status_histories.*')
                        ->latest('status_histories.created_at'); // Ensure the latest record is fetched
                }]);
            })

            ->when($rq->status != 'pending', function ($query) use ($rq) {
                $query->with(['orderOfPayment' => function ($query) use ($rq) {
                    $query->join('users', 'users.id', '=', 'order_of_payments.admin_id')
                        ->select('order_of_payments.*', DB::raw("CONCAT(COALESCE(users.fname, ''), ' ', COALESCE(users.mname, ''), ' ', COALESCE(users.lname, ''), ' ', COALESCE(users.suffix, '')) as fullname"));

                    // Add paymentDetails when status is 'returned'
                    if ($rq->status == 'returned') {
                        $query->with('paymentDetail'); // Assuming you have a relationship defined for paymentDetails
                    }
                }]);
            })
            ->get();

        return $applications;
    }

    public function payPermit(Request $rq)
    {

        DB::beginTransaction();
        try {

            request()->validate([
                'special_permit_application_id' => 'required',
                'or_no' => 'required',
                'paid_amount' => 'required',
                'date_of_payment' => 'required',
                'attachment' => 'required|image|mimes:jpeg,png,jpg',
            ]);

            $user = Auth::user();

            $status = SpecialPermitStatus::where('code', 'for_payment')->first();

            $check_permit = SpecialPermitApplication::where('id', $rq->special_permit_application_id)
                ->where('special_permit_status_id', $status->id)
                ->first();

            if ($check_permit) {

                $order_of_payment = OrderOfPayment::where('special_permit_application_id', $rq->special_permit_application_id)
                    ->first();

                $new_status = SpecialPermitStatus::where('code', 'for_payment_approval')->first();

                DB::table('special_permit_applications')
                    ->where('id', $rq->special_permit_application_id)
                    ->update([
                        'special_permit_status_id' => $new_status->id,
                    ]);

                $payment_details = new PaymentDetail();
                $payment_details->order_of_payment_id = $order_of_payment->id;
                $payment_details->special_permit_application_id = $order_of_payment->special_permit_application_id;
                $payment_details->paid_amount = $rq->paid_amount;
                $payment_details->or_no = $rq->or_no;
                $payment_details->attachment = $rq->file('attachment')->storeAs('or_no/attachment/' . $order_of_payment->id, date('YmdHi') . '-' . 'OR' . '.' . $rq->file('attachment')->getClientOriginalExtension(), 'public');
                $payment_details->applicant_id = $user->id;
                $payment_details->date_of_payment = $rq->date_of_payment;
                $payment_details->payment_type = 'over_the_counter';
                $payment_details->status = 'pending';
                $payment_details->save();

                $status_history = new StatusHistory();
                $status_history->user_id = $user->id;
                $status_history->special_permit_application_id = $rq->special_permit_application_id;
                $status_history->special_permit_status_id = $new_status->id;
                $status_history->save();
                $check_permit->mark_as_read = null;
                $check_permit->save();
                $permit_type = SpecialPermitType::where('id', $check_permit->special_permit_type_id)->first();
                $count = $this->count($permit_type->id, $new_status->id);
                broadcast(new DocumentStageMoved($permit_type->code, 'for_payment_approval', $count));
                DB::commit();
                return response([
                    'message' => "success"
                ]);
            } else {

                return response([
                    'message' => "Permit is for signature already"
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function reuploadPayment(Request $rq)
    {

        DB::beginTransaction();
        try {

            request()->validate([
                'special_permit_application_id' => 'required',
                'attachment' => 'required|image|mimes:jpeg,png,jpg',
            ]);

            $user = Auth::user();

            $status = SpecialPermitStatus::where('code', 'returned')->first();

            $check_permit = SpecialPermitApplication::where('id', $rq->special_permit_application_id)
                ->where('special_permit_status_id', $status->id)
                ->first();

            if ($check_permit) {

                $order_of_payment = OrderOfPayment::where('special_permit_application_id', $rq->special_permit_application_id)
                    ->first();

                $new_status = SpecialPermitStatus::where('code', 'for_payment_approval')->first();

                DB::table('special_permit_applications')
                    ->where('id', $rq->special_permit_application_id)
                    ->update([
                        'special_permit_status_id' => $new_status->id,
                    ]);

                DB::table('payment_details')
                    ->where('special_permit_application_id', $rq->special_permit_application_id)
                    ->update([
                        'attachment' => $rq->file('attachment')->storeAs('or_no/attachment/' . $order_of_payment->id, date('YmdHi') . '-' . 'OR' . '.' . $rq->file('attachment')->getClientOriginalExtension(), 'public'),
                    ]);

                $status_history = new StatusHistory();
                $status_history->user_id = $user->id;
                $status_history->special_permit_application_id = $rq->special_permit_application_id;
                $status_history->special_permit_status_id = $new_status->id;
                $status_history->save();
                $check_permit->mark_as_read = null;
                $check_permit->save();
                $permit_type = SpecialPermitType::where('id', $check_permit->special_permit_type_id);
                $count = $this->count($permit_type->id, $new_status->id);
                broadcast(new DocumentStageMoved($permit_type->code, 'for_payment', $count));
                DB::commit();
                return response([
                    'message' => "success"
                ]);
            } else {

                return response([
                    'message' => "Permit is for signature already"
                ], 500);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    // public function downloadPermit(Request $rq)
    // {

    //     request()->validate([
    //         'special_permit_id' => 'required'
    //     ]);

    //     $completed = CompletedSpecialPermit::where('special_permit_application_id', $rq->special_permit_id)->first();
    //     $filePath = $completed->file;
    //     return response()->download(storage_path($filePath));
    // }
    public function downloadPermit(Request $request)
    {
        $request->validate([
            'special_permit_id' => 'required',
        ]);

        $completed = CompletedSpecialPermit::where('special_permit_application_id', $request->special_permit_id)->first();

        if (!$completed || !$completed->file) {
            return response()->json(['error' => 'File not found'], 404);
        }

        // Full path to the file
        $filePath = $completed->file;

        // Use Storage facade to get the absolute path
        if (Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->download($filePath);
        }

        return response()->json(['error' => 'File does not exist'], 404);
    }
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 400);
    }
    public function reset(Request $request)
    {

        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)], 200)
            : response()->json(['message' => __($status)], 400);
    }
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 400);
        }

        /** @var \App\Models\User $user */
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully', 'status' => 200], 200);
    }
}
