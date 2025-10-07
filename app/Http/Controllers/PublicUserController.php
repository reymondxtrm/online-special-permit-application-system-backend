<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Ldap\LDAPUser;
use App\Models\UserRole;
use LdapRecord\Container;
use App\Models\UserDetail;
use App\Models\CivilStatus;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use App\Models\UserPhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\SpecialPermitRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use PhpParser\Node\Stmt\TryCatch;

class PublicUserController extends Controller
{
    //
    public function getCivilStatuses()
    {
        return CivilStatus::all();
    }

    public function getCaragaGeolocations()
    {
        // Step 1: Retrieve and sort the data
        $data = DB::table('caraga_geolocations')
            ->orderBy('prov_id')
            ->orderBy('mun_city_id')
            ->orderBy('barangay_id')
            ->get();

        $provinces = $data->where('geographic_level', 'Prov')
            ->map(function ($province) use ($data) {
                $cities = $data
                    ->filter(function ($item) use ($province) {
                        // Include both 'Mun' and 'City'
                        return $item->prov_id == $province->prov_id && in_array($item->geographic_level, ['Mun', 'City']);
                    })
                    ->map(function ($city) use ($data) {
                        $barangays = $data
                            ->filter(function ($barangay) use ($city) {
                                return $barangay->mun_city_id == $city->mun_city_id && $barangay->geographic_level == 'Bgy';
                            })
                            ->map(function ($barangay) {
                                return [
                                    'barangay_id' => $barangay->barangay_id,
                                    'psgc_id' => rtrim(rtrim((string)$barangay->psgc_id, '0'), '.'),
                                    'name' => $barangay->location_desc,
                                ];
                            })
                            ->values();

                        return [
                            'mun_city_id' => $city->mun_city_id,
                            'prov_id' => $city->prov_id,
                            'psgc_id' => rtrim(rtrim((string)$city->psgc_id, '0'), '.'),
                            'name' => $city->location_desc,
                            'barangays' => $barangays,
                        ];
                    })
                    ->values();

                return [
                    'prov_id' => $province->prov_id,
                    'psgc_id' => rtrim(rtrim((string)$province->psgc_id, '0'), '.'),
                    'name' => $province->location_desc,
                    'cities' => $cities,
                ];
            })
            ->values();

        $region = [
            'region_id' => 16,
            'psgc_id' => "160000000",
            'name' => "Region XIII (Caraga)",
            'provinces' => $provinces,
        ];

        return response()->json(['region' => $region]);
    }


    public function login(Request $rq)
    {
        request()->validate([
            'username' => 'required',
            'password' => 'required',
        ]);


        $user = User::where('username', $rq->username)->first();

        if (!$user || $user->is_deleted == 1) {
            return response([
                'message' => 'Invalid Credentials'
            ], 400);
        } else {

            if (!Hash::check($rq->password, $user->password)) {
                return response([
                    'message' => 'Invalid Credentials'
                ], 400);
            } else {
                $roles = UserRole::where('user_id', $user->id)
                    ->join('roles', 'roles.id', '=', 'role_id')
                    ->select(
                        'roles.id',
                        'roles.name',
                    )
                    ->get();
                $roles_array = [];
                for ($i = 0; $i < count($roles); $i++) {
                    array_push($roles_array, $roles[$i]->name);
                }
                return response([
                    'user' => $user,
                    'roles' => $roles_array,
                    'token' => $user->createToken($user->id)->plainTextToken,
                    'status' => 200
                ], 200);
            }
        }
    }


    public function specialPermitUserRegistration(Request $rq)
    {

        request()->validate([
            'surname' => 'required',
            'first_name' => 'required',
            'middle_name' => 'sometimes',
            'suffix' => 'sometimes',
            'sex' => 'required',
            'email' => 'required',
            'contact_no' => 'required|unique:user_phone_numbers,phone_number',
            'province' => 'required',
            'city' => 'required',
            'barangay' => 'required',
            'additional_address' => 'required',
            'date_of_birth' => 'required',
            'place_of_birth' => 'required',
            'educational_attainment' => 'required',
            'civil_status' => 'required',
            'password' => 'required',
        ], ['contact_no.unique' => 'This contact number has already been taken.',]);

        DB::beginTransaction();
        try {
            $user = User::where('lname', $rq->surname)
                ->where('fname', $rq->first_name)
                ->where('mname', $rq->middle_name)
                ->where('suffix', $rq->suffix)
                ->first();

            $check_email = User::where('email', $rq->email)
                ->first();

            if ($user) {
                return response([
                    'message' => 'User already exists'
                ], 401);
            }
            if ($check_email) {
                return response([
                    'message' => 'Email already taken'
                ], 401);
            } else {

                // $new_user = new User();
                // $new_user->fname = $rq->first_name;
                // $new_user->mname = $rq->middle_name;
                // $new_user->lname = $rq->surname;

                // $new_user->sex = $rq->sex;

                // $new_user->username = $rq->username;
                // $new_user->email = $rq->email;
                // $new_user->password = Hash::make($rq->password);
                // $new_user->user_type = "client";
                // $new_user->save();
                // return $new_user;

                $user = User::create([
                    'fname' => $rq->first_name,
                    'mname' => $rq->mname,
                    'lname' => $rq->surname,
                    'sex' => $rq->sex,
                    'username' => $rq->username,
                    'email' => $rq->email,
                    'password' => Hash::make($rq->password),
                    'user_type' => "client"
                ]);
                event(new Registered($user));

                $user_details = new UserDetail();
                $user_details->user_id = $user->id;
                $user_details->civil_status_id = $rq->civil_status;
                $user_details->birthdate = $rq->date_of_birth;
                $user_details->birthplace = $rq->place_of_birth;
                $user_details->educational_attainment = $rq->educational_attainment;
                $user_details->save();


                $user_address = new UserAddress();
                $user_address->user_id = $user->id;
                $user_address->province = $rq->province;
                $user_address->city = $rq->city;
                $user_address->barangay = $rq->barangay;
                $user_address->address_line = $rq->additional_address;
                $user_address->address_type = 'permanent';
                $user_address->save();

                $user_phone = new UserPhoneNumber();
                $user_phone->user_id = $user->id;
                $user_phone->phone_number = $rq->contact_no;
                $user_phone->save();
                DB::commit();
                return response([
                    'message' => "success",
                    'status' => 200
                ], 200);
            }
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function ldapLogin(Request $rq)
    {

        request()->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $ldapuser = LDAPUser::where('samaccountname', $rq->username)->first();
        // return $ldapuser;
        if (!$ldapuser) {
            return response([
                'message' => 'Invalid Credentials'
            ], 401);
        } else {
            $credentials = [
                'samaccountname' => $rq->username,
                'password' => $rq->password
            ];
            if (!Auth::validate($credentials)) {
                return response([
                    'message' => 'Invalid Credentials'
                ], 401);
            } else {
                // return $ldapuser->mail[0];
                $user = User::firstOrCreate([
                    'email' => $ldapuser->userprincipalname[0], // You can use other LDAP attributes as needed
                ], [
                    'fname' => $ldapuser->givenname[0] . ' ' . $ldapuser->sn[0], // Example: Use first and last name from LDAP
                    'username' =>  $ldapuser->samaccountname[0], // Example: Use first and last name from LDAP
                ]);
                $roles = UserRole::where('user_id', $user->id)
                    ->join('roles', 'roles.id', '=', 'role_id')
                    ->select(
                        'roles.id',
                        'roles.name',
                    )
                    ->get();
                $roles_array = [];
                for ($i = 0; $i < count($roles); $i++) {
                    array_push($roles_array, $roles[$i]->name);
                }
                return response([
                    'user' => $user,
                    'roles' => $roles_array,
                    'token' => $user->createToken($user->id)->plainTextToken,
                    'status' => 200
                ], 200);
            }
        }
    }

    public function register(Request $rq)
    {
        return $rq;
    }

    // Using blade as verification route
    // public function verifyEmail(Request $request, $id, $hash)
    // {
    //     $user = User::findOrFail($id);

    //     if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
    //         abort(403, 'Invalid verification link');
    //     }

    //     if (! $user->hasVerifiedEmail()) {
    //         $user->markEmailAsVerified();
    //     }
    //     return view('verification-success');
    // }
    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification email resent']);
    }
    public function forgetPassword(Request $rq)
    {
        $rq->validate([
            'email' => 'required|email|exists:users,email',
        ]);
        DB::beginTransaction();
        try {

            $randomPassword = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 6);
            $user = User::where('email', $rq->email)->first();
            $new_password = Hash::make($randomPassword);
            $user->password = $new_password;
            $user->save();
            // Mail::to($user->email)->send(new \App\Mail\SendNewPassword($user, $new_password));
            $user->sendNewPassword($randomPassword);

            DB::commit();
            return response()->json(['message' => 'A new password has been sent successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
        return response()->json(['message' => "A new password has been sent to your email.", 'status' => 200], 200);
    }


    //     public static function sign($file, $user_id, $qcp_id, $status, $set_status)
    //    {

    //       DB::beginTransaction();
    //       try {

    //          $check_qcp = DB::table('quality_control_documents')->where('id', $qcp_id)
    //          ->get();

    //          if (count($check_qcp) <= 0) {
    //             return response([
    //                'message' => 'Qcp document does not exist',
    //                'status' => 500
    //             ], 500);
    //          } elseif ($check_qcp[0]->status != $status) {
    //             return response([
    //                'message' => 'Qcp already signed',
    //                'status' => 500
    //             ], 500);
    //          } else {

    //             $action = "";
    //             $date_signed = "";

    //             if ($set_status == 2) {

    //                $action = 'Quality Control Document Signed by Implementing Section and routed to Project Engineer ';
    //                $date_signed = 'date_signed_me';
    //                $from = 11;
    //                $to = 12;

    //             }elseif ($set_status == 6) {

    //                $action = 'Quality Control Document Signed by Project Engineer and routed to Division Chief ';
    //                $date_signed = 'date_signed_pe';
    //                $from = 12;
    //                $to = 8;

    //             }elseif ($set_status == 3) {

    //                $action = 'Quality Control Document Signed by Division Chief and routed to Assistant Regional Director ';
    //                $date_signed = 'date_signed_dc';
    //                $from = 8;
    //                $to = 9;

    //             }elseif ($set_status == 4) {

    //                $action = 'Quality Control Document Signed by Assistant Regional Director and routed to Regional Director ';
    //                $date_signed = 'date_signed_ard';
    //                $from = 9;
    //                $to = 10;

    //             }else{

    //                $action = 'Quality Control Document Signed by Regional Director';
    //                $date_signed = 'date_signed_rd';
    //                $from = null;
    //                $to = null;

    //             }

    //             DB::table('quality_control_documents')->where('id', $qcp_id)

    //             ->update(
    //                [
    //                   'status' => $set_status,
    //                   $date_signed => Carbon::now()
    //                ]
    //                );

    //             // DB::table('quality_control_documents')->where('id', $qcp_id)
    //             //    ->when($set_status == 5, function ($query) use ($set_status) {
    //             //       $query->update(
    //             //          [
    //             //             'status' => $set_status,
    //             //             'date_signed' => Carbon::now()
    //             //          ]
    //             //       );
    //             //    }, function ($query) use ($set_status) {
    //             //       $query->update(
    //             //          [
    //             //             'status' => $set_status,
    //             //          ]
    //             //       );
    //             //    });

    //             $uploaded_files = DB::table('uploaded_files')->where('qcp_id', $qcp_id)
    //             ->first();

    //             $uuid = Str::uuid();


    //             $explode = explode("-",$uploaded_files->file_name);
    //             // return $explode[1];

    //             $number_to_iterate = end($explode);
    //             $iteration =  (int)$number_to_iterate + 1;

    //             $filename = date('YmdHi').'-'.$explode[1].'-'.$iteration.'.pdf';


    //             $filepath = $uploaded_files->file_path;
    //             Storage::put($filepath . '/' . $filename, file_get_contents($file));

    //             DB::table('uploaded_files')->where('qcp_id', $qcp_id)->update([
    //                'file_path' => $filepath,
    //                'file_name' => $filename
    //             ]);
    //             $history = self::createQcpHistory($user_id, $qcp_id, $action, $from, $to, 'forward');
    //             $history->save();

    //             DB::commit();

    //             return response([
    //                'message' => 'success',
    //                'status' => 200
    //             ]);
    //          }


    //      } catch (\Exception $e) {
    //          DB::rollback();
    //          return response(['message' => $e->getMessage()], 500);
    //      }


    //    }

}
