<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Stage;
use App\Models\Business;
use App\Models\PermitReceiver;
use App\Models\UserRole;
use App\Models\GenderType;
use App\Models\PermitType;
use App\Models\CivilStatus;
use Illuminate\Http\Request;
use App\Models\CaragaGeolocation;
use App\Models\ApplicationPurpose;
use App\Models\Gender;
use App\Models\IdType;
use App\Models\IdTypeOther;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class AuthenticatedUserController extends Controller
{
    //

    public function getGender()
    {
        return Gender::all();
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

    public function editDocument(Request $rq)
    {
        request()->validate([
            'business_code' => 'sometimes',
            'business_permit' => 'sometimes',
            'plate_no' => 'sometimes',
            'business_name' => 'sometimes',
            'business_owner' => 'sometimes',
            'business_id' => 'required',
            'stage' => 'required'
        ]);

        DB::beginTransaction();
        try {

            $rq->business_code ?  $business_code = strtoupper($rq->business_code) : $business_code = null;

            if ($rq->stage == 'initial_receiving') {
                DB::table('businesses')->where('id', $rq->business_id)
                    ->update([
                        'business_code' => $business_code,
                        'name' => strtoupper($rq->business_name),
                        'owner' => strtoupper($rq->business_owner)
                    ]);
            }

            if ($rq->stage == 'complete_receiving') {
                DB::table('businesses')->where('id', $rq->business_id)
                    ->update([
                        'business_code' => strtoupper($rq->business_code),
                    ]);
            }

            if ($rq->stage == 'final_releasing') {
                DB::table('businesses')->where('id', $rq->business_id)
                    ->update([
                        'business_permit' => strtoupper($rq->business_permit),
                        'plate_no' => strtoupper($rq->plate_no)
                    ]);
            }
            DB::commit();
            return response([
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response(['message' => $th->getMessage()], 500);
        }
    }
    public function getGenderAnalytics()
    {
        return DB::table('businesses')
            ->join('gender_types', 'gender_types.id', '=', 'businesses.gender_type_id')
            ->leftJoin('types', 'types.id', '=', 'gender_types.type_id')
            // ->whereIn('businesses.gender_type_id', [])
            ->leftJoin('genders', 'genders.id', '=', 'gender_types.gender_id')
            ->select(DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.name) as gender_type'), DB::raw('COUNT(gender_types.id) AS count'))
            ->groupBy('gender_type')
            ->get();
    }

    public function maleAnalytics(Request $rq)
    {
        // return GenderType::leftJoin('types', 'types.id', '=', 'type_id')
        // ->join('genders', 'genders.id', '=', 'gender_id')
        // ->whereIn('businesses.gender_type_id', [1,3,4])
        // ->join('businesses', 'businesses.gender_type_id', '=', 'gender_types.id')
        // ->select(DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type'), DB::raw('COUNT(businesses.id) AS count'))
        // ->groupBy('gender_type')
        // ->get();
        return GenderType::leftJoin('types', 'types.id', '=', 'type_id')
            ->join('genders', 'genders.id', '=', 'gender_id')
            ->leftJoin('businesses', function ($join) {
                $join->on('businesses.gender_type_id', '=', 'gender_types.id')
                    ->whereIn('businesses.gender_type_id', [1, 3, 4]);
            })
            ->when($rq->date_from && $rq->date_to, function ($query) use ($rq) {
                $query->whereBetween('businesses.created_at', [$rq->date_from, DB::raw("DATE_ADD('$rq->date_to', INTERVAL 1 DAY)")]);
            })
            ->where('genders.name', 'MALE') // Add this line to filter only female genders
            ->select(DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type'), DB::raw('COUNT(businesses.id) AS count'))
            ->groupBy('gender_type')
            ->get();
    }
    public function femaleAnalytics(Request $rq)
    {
        // return GenderType::leftJoin('types', 'types.id', '=', 'type_id')
        // ->join('genders', 'genders.id', '=', 'gender_id')
        // ->whereIn('businesses.gender_type_id', [2,5,6,7])
        // ->join('businesses', 'businesses.gender_type_id', '=', 'gender_types.id')
        // ->select(DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type'), DB::raw('COUNT(businesses.id) AS count'))
        // ->groupBy('gender_type')
        // ->get();

        return GenderType::leftJoin('types', 'types.id', '=', 'type_id')
            ->join('genders', 'genders.id', '=', 'gender_id')
            ->leftJoin('businesses', function ($join) {
                $join->on('businesses.gender_type_id', '=', 'gender_types.id')
                    ->whereIn('businesses.gender_type_id', [2, 5, 6, 7]);
            })
            ->when($rq->date_from && $rq->date_to, function ($query) use ($rq) {
                $query->whereBetween('businesses.created_at', [$rq->date_from, DB::raw("DATE_ADD('$rq->date_to', INTERVAL 1 DAY)")]);
            })
            ->where('genders.name', 'FEMALE') // Add this line to filter only female genders
            ->select(DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type'), DB::raw('COUNT(businesses.id) AS count'))
            ->groupBy('gender_type')
            ->get();
    }

    public function analytics(Request $rq)
    {

        // return $rq;
        return response([
            'gender' => [
                'female' => $this->femaleAnalytics($rq),
                'male' => $this->maleAnalytics($rq),
            ]
        ]);
    }

    public function getRoles()
    {
        return Role::get(['id', 'name', 'description']);
    }

    public function permitTypes()
    {
        //  return PermitType::get(['id', DB::raw('UCASE(name) as name')]);
        return PermitType::get(['id', 'name']);
    }

    public function genderTypes()
    {
        return GenderType::join('genders', 'genders.id', '=', 'gender_id')
            ->leftJoin('types', 'types.id', '=', 'type_id')
            ->select(
                'gender_types.id',
                DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type')
            )
            ->orderBy('id')
            ->get();
    }

    public function stages()
    {
        return Stage::get(['id', 'name', 'description']);
    }

    public function summary(Request $rq)
    {


        $business = Business::select(
            'businesses.id',
            'businesses.name as business_name',
            'business_code',
            'owner',
            'business_permit',
            'status',
            'permit_types.name as type',
            'stage4to6.min_created_at',
            'stage4to6.max_created_at',
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to2.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to2.min_created_at, stage1to2.max_created_at) ELSE 0 END)) as durationStage1to2'),
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage2to3.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage2to3.min_created_at, stage2to3.max_created_at) ELSE 0 END)) as durationStage2to3'),
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to3.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to3.min_created_at, stage1to3.max_created_at) ELSE 0 END)) as durationStage1to3'),
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage4to5.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage4to5.min_created_at, stage4to5.max_created_at) ELSE 0 END)) as durationStage4to5'),
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage4to6.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage4to6.min_created_at, stage4to6.max_created_at) ELSE 0 END)) as durationStage4to6'),
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to5.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to5.min_created_at, stage1to5.max_created_at) ELSE 0 END)) as durationStage1to5'),
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage3to4.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage3to4.min_created_at, stage3to4.max_created_at) ELSE 0 END)) as durationStage3to4'),
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to5.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to5.min_created_at, stage1to5.max_created_at) ELSE 0 END) / 4) as avgDurationStage1to5')
        )
            ->when($rq->date_from && $rq->date_to, function ($query) use ($rq) {
                $query->whereBetween('businesses.created_at', [Carbon::parse($rq->date_from)->startOfDay(), Carbon::parse($rq->date_to)->endOfDay()]);
            })
            ->when($rq->keyword, function ($query) use ($rq) {
                // $query->join('permit_types', 'permit_types.id', '=', 'businesses.permit_type_id');
                $query->where(function ($innerQuery) use ($rq) {
                    $keyword = '%' . $rq->keyword . '%';
                    $innerQuery->where('businesses.name', 'like', $keyword)
                        ->orWhere('businesses.business_code', 'like', $keyword)
                        // ->orWhere('businesses.business_permit', 'like', $keyword)
                        // ->orWhere('businesses.status', 'like', $keyword)
                        // ->orWhere('permit_types.name', 'like', $keyword)
                        ->orWhere('businesses.owner', 'like', $keyword)
                        ->orWhere('permit_types.name', $rq->keyword);
                });
            })
            ->when($rq->status, function ($query1) use ($rq) {
                $query1->where('status', $rq->status);
            })
            ->with('businessStages')
            ->join('permit_types', 'permit_types.id', '=', 'businesses.permit_type_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 1 AND 2
                            GROUP BY business_id) as stage1to2'), 'businesses.id', '=', 'stage1to2.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 2 AND 3
                            GROUP BY business_id) as stage2to3'), 'businesses.id', '=', 'stage2to3.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 1 AND 3
                            GROUP BY business_id) as stage1to3'), 'businesses.id', '=', 'stage1to3.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages 
                            WHERE stage_id BETWEEN 4 AND 5
                            GROUP BY business_id) as stage4to5'), 'businesses.id', '=', 'stage4to5.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id IN (4,6)
                            GROUP BY business_id) as stage4to6'), 'businesses.id', '=', 'stage4to6.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id IN (1,2,3,4,6)
                            GROUP BY business_id) as stage1to5'), 'businesses.id', '=', 'stage1to5.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 3 AND 4
                            GROUP BY business_id) as stage3to4'), 'businesses.id', '=', 'stage3to4.business_id')

            ->groupBy(
                'id',
                'business_name',
                'business_code',
                'owner',
                'business_permit',
                'status',
                'type',
                'stage4to5.min_created_at',
                'stage4to5.max_created_at',
                'stage4to6.min_created_at',
                'stage4to6.max_created_at',
            );


        if ($rq->export) {
            return $business->get();
        } else {
            return $business->paginate(10);
        }
        return Business::select(
            'id',
            'name as business_name',
            'business_code',
            'owner',
            'business_permit',
            'status',
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to3.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to3.min_created_at, stage1to3.max_created_at) ELSE 0 END)) as durationStage1to3'),
            DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage4to5.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage4to5.min_created_at, stage4to5.max_created_at) ELSE 0 END)) as durationStage4to5')
        )
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 1 AND 3
                            GROUP BY business_id) as stage1to3'), 'businesses.id', '=', 'stage1to3.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 4 AND 5
                            GROUP BY business_id) as stage4to5'), 'businesses.id', '=', 'stage4to5.business_id')
            ->groupBy('id', 'business_name', 'business_code', 'owner', 'business_permit', 'status')
            ->orderBy('id')
            ->get();
    }

    public function releasingRecords(Request $rq)
    {

        $finalStage = Stage::where('name', 'final_released')->first();
        $finalStageId = $finalStage ? $finalStage->id : null;
        $permitReceiver = PermitReceiver::select(
            "permit_receivers.id as receiver_id",
            DB::raw('CONCAT_WS(" ", UPPER(users.fname), UPPER(users.mname), UPPER(users.lname)) as releaser_name'),
            "permit_receivers.receiver_name",
            "permit_receivers.receiver_relationship_to_owner",
            "permit_receivers.receiver_signature",
            "permit_receivers.receiver_photo",
            'permit_receivers.receiver_phone_no as receiver_phone_number',
            'permit_receivers.receiver_email',
            DB::raw("CASE WHEN permit_receivers.id_type_id = 22 THEN id_type_others.name ELSE id_types.name END as receiver_id_type"),
            'permit_receivers.receiver_id_no as receiver_id_no',
            "businesses.id as businesses_id",
            "businesses.name as business_name",
            "businesses.business_code",
            "businesses.owner",
            "permit_receivers.created_at as released_at"
        )
            ->where('permit_receivers.business_id', $rq->business_id)
            ->join('businesses', 'permit_receivers.business_id', '=', 'businesses.id')
            ->leftJoin('business_stages', function ($query) use ($finalStageId) {
                $query->on('businesses.id', '=', 'business_stages.business_id')
                    ->where('business_stages.stage_id', '=', $finalStageId);
            })
            ->leftJoin('users', 'users.id', '=', 'business_stages.user_id')
            ->leftjoin('id_types', 'id_types.id', '=', 'permit_receivers.id_type_id')
            ->leftjoin('id_type_others', 'id_type_others.permit_receiver_id', '=', 'permit_receivers.id')
            ->get();
        // so when the the id_type === 22 i want to join the others table and if not just join the id_types 


        return response(["data" => $permitReceiver], 200);
    }

    public function getReceiverImages(Request $id)
    {
        // i expect this to have receiver id and find the path and decrypt it
        $receiver_photos = PermitReceiver::find($id);

        $signaturePath = 'private/' . $receiver_photos[0]->receiver_signature;
        $photoPath = 'private/' . $receiver_photos[0]->receiver_photo;

        if (
            Storage::exists($signaturePath) &&
            Storage::exists($photoPath)
        ) {
            try {
                $signature = Storage::disk('local')->get($signaturePath);
                $photo = Storage::disk('local')->get($photoPath);

                $decryptedSignature = base64_encode(Crypt::decrypt($signature));
                $decryptedPhoto = base64_encode(Crypt::decrypt($photo));

                return response([
                    "signature" => "data:image/png;base64," . $decryptedSignature,
                    "photo" => "data:image/png;base64," . $decryptedPhoto
                ], 200);
            } catch (\Exception $e) {
                return response([
                    'message' => 'Failed to decrypt image(s)',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response([
            'message' => "No photo and signature found",
        ], 404);
    }

    public function permitStatus(Request $rq)
    {
        request()->validate([
            'business_id' => 'required'
        ]);

        $business = DB::table('business_stages')->where('business_id', $rq->business_id)
            ->join('stages', 'stages.id', '=', 'business_stages.stage_id')
            ->join('users', 'users.id', '=', 'business_stages.user_id')
            ->select(
                'business_stages.created_at as date',
                'business_stages.id as id',
                DB::raw('CONCAT(UPPER(LEFT(REPLACE(stages.name, "_", " "), 1)), LOWER(SUBSTRING(REPLACE(stages.name, "_", " "), 2, LOCATE(" ", REPLACE(stages.name, "_", " ")) - 1)), " ", UPPER(LEFT(SUBSTRING_INDEX(REPLACE(stages.name, "_", " "), " ", -1), 1)), LOWER(SUBSTRING(SUBSTRING_INDEX(REPLACE(stages.name, "_", " "), " ", -1), 2))) as stage'),
                DB::raw('CONCAT_WS(" ", UCASE(users.fname), UCASE(users.mname), UCASE(users.lname)) as user_name')
            )
            ->get();

        return $business;
    }

    public function getCivilStatuses()
    {
        return CivilStatus::all();
    }

    public function getPurposes(Request $rq)
    {
        request()->validate([
            'permit_type' => 'required' //good_moral, mayors_certificate
        ]);

        if ($rq->permit_type == 'mayors_certificate') {
            return ApplicationPurpose::where('special_permit_type_id', 1)
                ->where('type', 'permanent')
                ->get();
        }

        if ($rq->permit_type == 'good_moral') {
            return ApplicationPurpose::where('special_permit_type_id', 2)
                ->where('type', 'permanent')
                ->get();
        }
    }
    public function getExistingPermit(Request $rq)
    {
        $like = '%' . $rq->business_code . '%';
        $business_permit = Business::select(
            DB::raw('CONCAT_WS(", ", UCASE(business_code), UCASE(name), UCASE(owner)) as name'),
            'business_code',
            'name as business_name',
            'owner'
        )
            ->where('business_code', 'like', $like)
            ->orderBy('year', 'DESC')
            // ->distinct('business_name') // conflict with the branches business
            ->limit(100)
            ->get();
        return $business_permit;
    }
    public function getIdType()
    {
        $primaryType = IdType::all();
        return $primaryType;
    }
    public function emailVerification($id, $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response(['message' => "Invalid verification link"], 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->sendSuccessRegistrationNotification();
            return response(['message' => "Email Verified Successfully"]);
        }
    }
}
