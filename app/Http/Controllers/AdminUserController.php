<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Stage;
use App\Models\Business;
use App\Models\UserRole;
use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use App\Models\BusinessStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\AddUserRequest;
use App\Http\Requests\InitialReceiveRequest;
use App\Models\IdTypeOther;
use App\Models\PermitReceiver;
use App\Services\AdminUserControllerService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use App\Rules\InitialReceive\CheckBusinessCodeRule;
use PhpParser\Node\Stmt\TryCatch;

class AdminUserController extends Controller
{
    //

    private $current_year = 0;
    public function __construct()
    {

        $this->current_year = (int) date('Y');
    }

    public function truncateTables()
    {
        try {
            // List of tables to truncate
            $tables = [
                'special_permit_applications',
                'order_of_payments',
                'payment_details',
                'uploaded_files',
                'completed_special_permits',
                'permit_application_exemptions',
                'status_histories'
            ];

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Truncate each table
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            return response()->json([
                'success' => true,
                'message' => 'Tables truncated successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error truncating tables: ' . $e->getMessage()
            ], 500);
        }
    }

    public function initialDashboard(Request $rq)
    {

        $business = Business::query()->where('year', $this->current_year)
            ->leftJoin('permit_types', 'permit_types.id', '=', 'businesses.permit_type_id')
            ->leftJoin('gender_types', 'gender_types.id', '=', 'businesses.gender_type_id')
            ->leftJoin('genders', 'genders.id', '=', 'gender_types.gender_id')
            ->leftJoin('types', 'types.id', '=', 'gender_types.type_id')
            // ->leftJoin(DB::raw('(
            //     SELECT bs1.*
            //     FROM business_stages bs1
            //     INNER JOIN (
            //         SELECT business_id, MAX(created_at) as max_date
            //         FROM business_stages
            //         GROUP BY business_id
            //     ) bs2 ON bs1.business_id = bs2.business_id AND bs1.created_at = bs2.max_date
            //     JOIN stages ON stages.id = bs1.stage_id
            //     JOIN users ON users.id = bs1.user_id
            // ) as bs'), 'businesses.id', '=', 'bs.business_id')
            ->when($rq->date_from && $rq->date_to, function ($query) use ($rq) {
                $query->whereBetween('businesses.created_at', [
                    Carbon::parse($rq->date_from)->startOfDay(),
                    Carbon::parse($rq->date_to)->endOfDay()
                ]);
            })
            ->when($rq->keyword, function ($query) use ($rq) {
                $keyword = '%' . $rq->keyword . '%';
                $query->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('businesses.control_no', 'like', $keyword)
                        ->orWhere('businesses.business_code', 'like', $keyword)
                        ->orWhere('businesses.business_permit', 'like', $keyword)
                        ->orWhere('businesses.name', 'like', $keyword)
                        ->orWhere('businesses.plate_no', 'like', $keyword)
                        ->orWhere('businesses.owner', 'like', $keyword);
                });
            })
            ->select(
                'businesses.id',
                'businesses.control_no',
                'businesses.business_code',
                'businesses.business_permit',
                'businesses.name',
                'businesses.plate_no',
                'businesses.owner',
                'businesses.status',
                'businesses.created_at',
                'businesses.year',
                'permit_types.name as permit_type_name',
                'gender_types.id as gender_type_id',
                'genders.name as gender_name',
                'types.description as type_name',
                DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type'),
                // 'bs.business_stage_id',
                // 'bs.stage_id',
                // 'bs.stage_name',
                // 'bs.stage_date',
                // 'bs.user_id',
                // 'bs.user_name'
            )
            ->orderBy('businesses.id', 'DESC')
            ->paginate(10);
        return $business;
    }

    public function assessmentReceiveDashboard(Request $rq)
    {
        request()->validate([
            'for_action' => 'sometimes|boolean'
        ]);

        $business = Business::where('year', $this->current_year)->with(['permitType' => function ($query) {
            $query->select('id', 'name');
        }])
            // ->with(['businessStages' => function ($query){
            //     $query->whereIn('stage_id', [1,2]);
            //     // $query->select(
            //     //      DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage_id = 1 THEN TIMESTAMPDIFF(SECOND, stage1to2.min_created_at, stage1to2.max_created_at) ELSE 0 END)) as durationStage1to2'),
            //     // );
            // }])
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 1 AND 2
                            GROUP BY business_id) as stage1to2'), 'businesses.id', '=', 'stage1to2.business_id')
            ->with(['genderType' => function ($query) {
                $query->join('genders', 'genders.id', '=', 'gender_id');
                $query->leftJoin('types', 'types.id', '=', 'type_id');
                $query->select(
                    'gender_types.id',
                    'gender_id',
                    'type_id',
                    'genders.name as gender_name',
                    'types.description as type_name',
                    DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type')
                );
            }])
            ->when($rq->for_action, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('businesses.status', 'initial_received');
                });
            }, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereIn('businesses.status', ['assessment_received', 'assessment_released', 'complete_received', 'final_released', 'final_release_printed']);
                    // $subQuery->where('businesses.status', 'assessment_received');
                    // $subQuery->orWhere('businesses.status', 'assessment_released');
                    // $subQuery->orWhere('businesses.status', 'complete_received');
                    // $subQuery->orWhere('businesses.status', 'final_released');
                });
            })
            ->when($rq->date_from && $rq->date_to, function ($query) use ($rq) {
                $query->whereBetween('businesses.created_at', [Carbon::parse($rq->date_from)->startOfDay(), Carbon::parse($rq->date_to)->endOfDay()]);
            })
            ->when($rq->keyword, function ($query) use ($rq) {
                // $query->join('permit_types', 'permit_types.id', '=', 'businesses.permit_type_id');
                $query->where(function ($innerQuery) use ($rq) {
                    $keyword = '%' . $rq->keyword . '%';
                    $innerQuery->where('businesses.control_no', 'like', $keyword)
                        ->orWhere('businesses.business_code', 'like', $keyword)
                        ->orWhere('businesses.business_permit', 'like', $keyword)
                        ->orWhere('businesses.name', 'like', $keyword)
                        ->orWhere('businesses.plate_no', 'like', $keyword)
                        ->orWhere('businesses.owner', 'like', $keyword);
                    // ->orWhere('permit_types.name', 'like', $keyword);
                });
            })
            ->select(
                'businesses.*',
                DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to2.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to2.min_created_at, stage1to2.max_created_at) ELSE 0 END)) as aging_from_initial_receiving'),
            )
            ->groupBy(
                'businesses.id',
                'permit_type_id',
                'gender_type_id',
                'business_code',
                'control_no',
                'business_permit',
                'plate_no',
                'with_sticker',
                'name',
                'owner',
                'status',
                'created_at',
                'updated_at',
                'year'
            )
            ->orderBy('businesses.id', "DESC")
            ->paginate(10);


        $business->map(function ($business) {
            list($hours, $minutes, $seconds) = explode(':', $business->aging_from_initial_receiving);
            $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;
            $thresholdSeconds = 15 * 60;
            if ($totalSeconds > $thresholdSeconds) {
                $business->color = 'danger';
            } else {
                $business->color = 'success';
            }
            return $business;
        });



        // return paginateArray($business, 10);
        return $business;
    }

    public function assessmentReleaseDashboard(Request $rq)
    {
        request()->validate([
            'for_action' => 'sometimes|boolean'
        ]);

        $business = Business::where('year', $this->current_year)->with(['permitType' => function ($query) {
            $query->select('id', 'name');
        }])
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 1 AND 3
                            GROUP BY business_id) as stage1to3'), 'businesses.id', '=', 'stage1to3.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 2 AND 3
                            GROUP BY business_id) as stage2to3'), 'businesses.id', '=', 'stage2to3.business_id')
            ->with(['genderType' => function ($query) {
                $query->join('genders', 'genders.id', '=', 'gender_id');
                $query->leftJoin('types', 'types.id', '=', 'type_id');
                $query->select(
                    'gender_types.id',
                    'gender_id',
                    'type_id',
                    'genders.name as gender_name',
                    'types.description as type_name',
                    DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type')
                );
            }])
            ->when($rq->for_action, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('businesses.status', 'assessment_received');
                });
            }, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereIn('businesses.status', ['assessment_released', 'complete_received', 'final_released']);
                    // $subQuery->orWhere('businesses.status', 'assessment_released');
                    // $subQuery->orWhere('businesses.status', 'complete_received');
                    // $subQuery->orWhere('businesses.status', 'final_released');
                });
            })
            ->when($rq->date_from && $rq->date_to, function ($query) use ($rq) {
                $query->whereBetween('businesses.created_at', [Carbon::parse($rq->date_from)->startOfDay(), Carbon::parse($rq->date_to)->endOfDay()]);
            })
            ->when($rq->keyword, function ($query) use ($rq) {
                // $query->join('permit_types', 'permit_types.id', '=', 'businesses.permit_type_id');
                $query->where(function ($innerQuery) use ($rq) {
                    $keyword = '%' . $rq->keyword . '%';
                    $innerQuery->where('businesses.control_no', 'like', $keyword)
                        ->orWhere('businesses.business_code', 'like', $keyword)
                        ->orWhere('businesses.business_permit', 'like', $keyword)
                        ->orWhere('businesses.name', 'like', $keyword)
                        ->orWhere('businesses.plate_no', 'like', $keyword)
                        ->orWhere('businesses.owner', 'like', $keyword);
                    // ->orWhere('permit_types.name', 'like', $keyword);
                });
            })
            ->select(
                'businesses.*',
                DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to3.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to3.min_created_at, stage1to3.max_created_at) ELSE 0 END)) as aging_from_initial_receiving'),
                DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage2to3.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage2to3.min_created_at, stage2to3.max_created_at) ELSE 0 END)) as aging_from_assessment_receiving'),
            )
            ->groupBy(
                'businesses.id',
                'permit_type_id',
                'gender_type_id',
                'business_code',
                'control_no',
                'business_permit',
                'plate_no',
                'with_sticker',
                'name',
                'owner',
                'status',
                'created_at',
                'updated_at',
                'year'
            )
            ->orderBy('businesses.id', "DESC")
            ->paginate(10);
        $business->map(function ($business) {
            list($hours, $minutes, $seconds) = explode(':', $business->aging_from_initial_receiving);
            $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

            // Define 15 minutes in seconds (15 * 60)
            $thresholdSeconds = 37 * 60;

            // Compare the total seconds
            if ($totalSeconds > $thresholdSeconds) {
                // Time is more than 15 minutes
                $business->color = 'danger';
            } else {
                // Time is 15 minutes or less
                $business->color = 'success';
            }
            return $business;
        });
        // foreach ($business as $businesses) {

        // }

        // return paginateArray($business, 10);
        return $business;
    }

    public function completeReceiverDashboard(Request $rq)
    {
        request()->validate([
            'for_action' => 'sometimes|boolean'
        ]);

        $business = Business::where('year', $this->current_year)->with(['permitType' => function ($query) {
            $query->select('id', 'name');
        }])
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 1 AND 4
                            GROUP BY business_id) as stage1to4'), 'businesses.id', '=', 'stage1to4.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 3 AND 4
                            GROUP BY business_id) as stage3to4'), 'businesses.id', '=', 'stage3to4.business_id')
            ->with(['genderType' => function ($query) {
                $query->join('genders', 'genders.id', '=', 'gender_id');
                $query->leftJoin('types', 'types.id', '=', 'type_id');
                $query->select(
                    'gender_types.id',
                    'gender_id',
                    'type_id',
                    'genders.name as gender_name',
                    'types.description as type_name',
                    DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type')
                );
            }])
            ->when($rq->for_action, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('businesses.status', 'assessment_released');
                });
            }, function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereIn('businesses.status', ['complete_received', 'final_released']);
                    // $subQuery->orWhere('businesses.status', 'assessment_released');
                    // $subQuery->orWhere('businesses.status', 'complete_received');
                    // $subQuery->orWhere('businesses.status', 'final_released');
                });
            })
            ->when($rq->date_from && $rq->date_to, function ($query) use ($rq) {
                $query->whereBetween('businesses.created_at', [Carbon::parse($rq->date_from)->startOfDay(), Carbon::parse($rq->date_to)->endOfDay()]);
            })
            ->when($rq->keyword, function ($query) use ($rq) {
                // $query->join('permit_types', 'permit_types.id', '=', 'businesses.permit_type_id');
                $query->where(function ($innerQuery) use ($rq) {
                    $keyword = '%' . $rq->keyword . '%';
                    $innerQuery->where('businesses.control_no', 'like', $keyword)
                        ->orWhere('businesses.business_code', 'like', $keyword)
                        ->orWhere('businesses.business_permit', 'like', $keyword)
                        ->orWhere('businesses.name', 'like', $keyword)
                        ->orWhere('businesses.plate_no', 'like', $keyword)
                        ->orWhere('businesses.owner', 'like', $keyword);
                    // ->orWhere('permit_types.name', 'like', $keyword);
                });
            })
            ->select(
                'businesses.*',
                DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to4.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to4.min_created_at, stage1to4.max_created_at) ELSE 0 END)) as aging_from_initial_receiving'),
                DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage3to4.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage3to4.min_created_at, stage3to4.max_created_at) ELSE 0 END)) as aging_from_assessment_releasing'),
            )
            ->groupBy(
                'businesses.id',
                'permit_type_id',
                'gender_type_id',
                'business_code',
                'control_no',
                'business_permit',
                'plate_no',
                'with_sticker',
                'name',
                'owner',
                'status',
                'created_at',
                'updated_at',
                'year'
            )
            ->orderBy('businesses.id', "DESC")
            ->paginate(10);

        $business->map(function ($business) {
            list($hours, $minutes, $seconds) = explode(':', $business->aging_from_initial_receiving);
            $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;


            $thresholdSeconds = 37 * 60;


            if ($totalSeconds > $thresholdSeconds) {

                $business->color = 'danger';
            } else {

                $business->color = 'success';
            }
            return $business;
        });



        return $business;
    }

    public function finalReleaseDashboard(Request $rq)
    {


        request()->validate([
            'for_action' => 'sometimes',
            'control_no' => 'nullable|string',
            'business_name' => 'nullable|string',
            'type' => 'nullable|integer',
            'gender_type' => 'nullable|integer',
            'business_code' => 'nullable|string',
            'business_permit' => 'nullable|string',
            'plate_no' => 'nullable|string',
            'owner' => 'nullable|string',
            'status' => 'nullable|string',
            'year' => 'nullable',
        ]);

        $business = Business::where('year', $this->current_year)->with(['permitType' => function ($query) {
            $query->select('id', 'name');
        }])
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 1 AND 5
                            GROUP BY business_id) as stage1to5'), 'businesses.id', '=', 'stage1to5.business_id')
            ->leftJoin(DB::raw('(SELECT business_id, MIN(created_at) as min_created_at, MAX(created_at) as max_created_at
                            FROM business_stages
                            WHERE stage_id BETWEEN 4 AND 5
                            GROUP BY business_id) as stage4to5'), 'businesses.id', '=', 'stage4to5.business_id')
            ->with(['genderType' => function ($query) {
                $query->join('genders', 'genders.id', '=', 'gender_id');
                $query->leftJoin('types', 'types.id', '=', 'type_id');
                $query->select(
                    'gender_types.id',
                    'gender_id',
                    'type_id',
                    'genders.name as gender_name',
                    'types.description as type_name',
                    DB::raw('CONCAT_WS(" - ", UCASE(genders.name), types.description) as gender_type')
                );
            }])->when(!$rq->for_action && $rq->filled('status'), function ($query) use ($rq) {
                $query->where('businesses.status', $rq->status);
            })->when($rq->for_action == 1, function ($query) {
                $query->where('businesses.status', 'final_release_printed');
            })->when($rq->for_action == 2, function ($query) {
                $query->where('businesses.status', 'complete_received');
            })
            ->when($rq->date_from && $rq->date_to, function ($query) use ($rq) {
                $query->whereBetween('businesses.created_at', [Carbon::parse($rq->date_from)->startOfDay(), Carbon::parse($rq->date_to)->endOfDay()]);
            })
            ->when($rq->keyword, function ($query) use ($rq) {
                $keyword = '%' . $rq->keyword . '%';
                $query->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('businesses.control_no', 'like', $keyword)
                        ->orWhere('businesses.business_code', 'like', $keyword)
                        ->orWhere('businesses.business_permit', 'like', $keyword)
                        ->orWhere('businesses.name', 'like', $keyword)
                        ->orWhere('businesses.plate_no', 'like', $keyword)
                        ->orWhere('businesses.owner', 'like', $keyword);
                    // ->orWhere('permit_types.name', 'like', $keyword); 
                });
            })->when($rq->filled('control_no'), fn($q) => $q->where('businesses.control_no', 'like', "%{$rq->control_no}%"))
            ->when($rq->filled('business_name'), fn($q) => $q->where('businesses.name', 'like', "%{$rq->business_name}%"))
            ->when($rq->filled('type'), fn($q) => $q->where('businesses.permit_type_id', $rq->type))
            ->when($rq->filled('gender_type'), fn($q) => $q->whereHas('genderType', fn($q2) => $q2->where('gender_id', $rq->gender_type)))
            ->when($rq->filled('business_code'), fn($q) => $q->where('businesses.business_code', 'like', "%{$rq->business_code}%"))
            ->when($rq->filled('business_permit'), fn($q) => $q->where('businesses.business_permit', 'like', "%{$rq->business_permit}%"))
            ->when($rq->filled('plate_no'), fn($q) => $q->where('businesses.plate_no', 'like', "%{$rq->plate_no}%"))
            ->when($rq->filled('owner'), fn($q) => $q->where('businesses.owner', 'like', "%{$rq->owner}%"))
            ->when($rq->filled('year'), fn($q) => $q->where('businesses.year', $rq->year))
            ->when($rq->filled('date'), fn($q) => $q->whereDate('businesses.created_at', $rq->date))
            ->select(
                'businesses.*',
                DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage1to5.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage1to5.min_created_at, stage1to5.max_created_at) ELSE 0 END)) as aging_from_initial_receiving'),
                DB::raw('SEC_TO_TIME(SUM(CASE WHEN stage4to5.min_created_at IS NOT NULL THEN TIMESTAMPDIFF(SECOND, stage4to5.min_created_at, stage4to5.max_created_at) ELSE 0 END)) as aging_from_complete_receiving'),
            )
            ->groupBy(
                'businesses.id',
                'permit_type_id',
                'gender_type_id',
                'business_code',
                'control_no',
                'business_permit',
                'plate_no',
                'with_sticker',
                'name',
                'owner',
                'status',
                'created_at',
                'updated_at',
                'year'
            )
            ->orderBy('businesses.id', "DESC")
            ->paginate(10);


        $business->map(function ($business) {
            list($hours, $minutes, $seconds) = explode(':', $business->aging_from_complete_receiving);
            $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

            $thresholdSeconds = 14 * 60;

            $business->totalSeconds = $totalSeconds;

            if ($totalSeconds > $thresholdSeconds) {
                $business->color = 'danger';
            } else {
                $business->color = 'success';
            }
            return $business;
        });


        return $business;
    }
    public function finalReleasePrinting(Request $rq)
    {
        DB::beginTransaction();
        try {

            $rq->validate([
                'business_id' => "required"
            ]);
            $business = Business::find($rq->business_id);
            if ($business->status != 'complete_received') {
                return response(["message" => "Unathorized Request"], 401);
            }

            $user = Auth::user();
            $stage = Stage::where('name', 'final_release_printed')->first(['id', 'name']);

            $business_stage = new BusinessStage();
            $business_stage->user_id = $user->id;
            $business_stage->business_id = $rq->business_id;
            $business_stage->stage_id = $stage->id;
            $business_stage->save();

            DB::table('businesses')->where('id', $rq->business_id)
                ->update([
                    'status' => 'final_release_printed'
                ]);

            DB::commit();
            return response([
                'message' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function addUser(AddUserRequest $rq)
    {
        DB::beginTransaction();
        try {

            $user = new User;
            $user->fname = $rq->fname;
            $user->mname = $rq->mname;
            $user->lname = $rq->lname;
            $user->username = $rq->fname . '.' . $rq->lname;
            $user->password = Hash::make('password');
            $user->email_verified_at = Carbon::now();
            $user->save();

            if (!empty($rq->role_id)) {
                foreach ($rq->role_id as $role) {
                    $user_role = new UserRole;
                    $user_role->user_id = $user->id;
                    $user_role->role_id = $role;
                    $user_role->save();
                }
            }

            DB::commit();

            return response([
                'message' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function getUsers()
    {
        return User::where('is_deleted', 0)->with(['userRoles' => function ($query) {
            $query->join('roles', 'roles.id', '=', 'role_id');
            $query->select(
                'user_roles.id',
                'user_roles.user_id',
                'roles.name as role_name',
                'roles.description as role_description'
            )
                ->whereIn('roles.name', [
                    'admin',
                    'initial_receiver',
                    'assessment_receiver',
                    'assessment_releaser',
                    'complete_receiver',
                    'final_releaser'
                ]);
        }])
            ->whereHas('userRoles', function ($query) {
                $query->join('roles', 'roles.id', '=', 'role_id')
                    ->whereIn('roles.name', [
                        'admin',
                        'initial_receiver',
                        'assessment_receiver',
                        'assessment_releaser',
                        'complete_receiver',
                        'final_releaser'
                    ]);
            })
            ->select([
                'id',
                DB::raw('UCASE(users.fname) as first_name'),
                DB::raw('UCASE(users.mname) as middle_name'),
                DB::raw('UCASE(users.lname) as last_name')
                // DB::raw('CONCAT_WS(" ", UCASE(users.fname), UCASE(users.mname), UCASE(users.lname)) as user_name')
            ])
            ->paginate(10);
    }

    public function addRoles(Request $rq)
    {
        request()->validate([
            'user_id' => 'required',
            'role_id' => 'array|required'
        ]);

        DB::beginTransaction();
        try {

            for ($i = 0; $i < count($rq->role_id); $i++) {
                $user_role = new UserRole;
                $user_role->user_id = $rq->user_id;
                $user_role->role_id = $rq->role_id;
                $user_role->save();
            }

            DB::commit();

            return response([
                'message' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function initialReceive(InitialReceiveRequest $rq)
    {

        DB::beginTransaction();
        try {


            $current_year = Carbon::now()->year;
            $current_month = Carbon::now()->month;
            $current_day = Carbon::now()->day;
            $get_count = Business::count();

            if ($rq->business_code) {
                $business_code = strtoupper($rq->business_code);
            } else {
                $business_code = null;
            }

            $user = Auth::user();
            $stage = Stage::where('name', 'initial_received')->first(['id', 'name']);

            $business = new Business();
            $business->permit_type_id = $rq->permit_type_id;
            $business->gender_type_id = $rq->gender_type_id;
            $business->business_code = $business_code;
            // $business->control_no = $rq->control_no;
            $business->control_no = $current_year . $current_month . $current_day . '-' . str_pad($get_count += 1, 6, 0, STR_PAD_LEFT);
            $business->name = strtoupper($rq->name);
            $business->owner = strtoupper($rq->owner);
            $business->business_permit = strtoupper($rq->business_permit);
            $business->plate_no = strtoupper($rq->plate_no);
            $business->status = 'initial_received';
            $business->year = $current_year;
            $business->save();

            $business_stage = new BusinessStage();
            $business_stage->user_id = $user->id;
            $business_stage->business_id = $business->id;
            $business_stage->stage_id = $stage->id;
            $business_stage->save();

            DB::commit();
            return response([
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response(['message' => $th->getMessage()], 500);
        }
    }

    public function assessmentReceive(Request $rq)
    {
        DB::beginTransaction();
        try {

            request()->validate([
                'business_id' => 'required',

            ]);

            $business = Business::find($rq->business_id);

            if ($business->status != 'initial_received') {
                return response([
                    'message' => 'Invalid Business'
                ], 422);
            }

            $user = Auth::user();
            $stage = Stage::where('name', 'assessment_received')->first(['id', 'name']);

            $business_stage = new BusinessStage();
            $business_stage->user_id = $user->id;
            $business_stage->business_id = $rq->business_id;
            $business_stage->stage_id = $stage->id;
            $business_stage->save();

            DB::table('businesses')->where('id', $rq->business_id)
                ->update([
                    'status' => 'assessment_received'
                ]);

            DB::commit();
            return response([
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response(['message' => $th->getMessage()], 500);
        }
    }

    public function assessmentRelease(Request $rq)
    {
        DB::beginTransaction();
        try {

            request()->validate([
                'business_id' => 'required',
                'business_code' => ['sometimes', new CheckBusinessCodeRule(2)]
            ]);
            $business = Business::find($rq->business_id);

            if ($business->status != 'assessment_received') {
                return response([
                    'message' => 'Invalid Business'
                ], 422);
            }
            $user = Auth::user();
            $stage = Stage::where('name', 'assessment_released')->first(['id', 'name']);
            $business_stage = new BusinessStage();
            $business_stage->user_id = $user->id;
            $business_stage->business_id = $rq->business_id;
            $business_stage->stage_id = $stage->id;
            $business_stage->save();
            if ($business->permit_type_id == 2) {
                DB::table('businesses')->where('id', $rq->business_id)->update([
                    'business_code' => $rq->business_code,
                    'status' => 'assessment_released'
                ]);
            } else {
                DB::table('businesses')->where('id', $rq->business_id)
                    ->update([
                        'status' => 'assessment_released'
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

    public function completeReceive(Request $rq)
    {
        DB::beginTransaction();
        try {

            request()->validate([
                'business_id' => 'required',
                'business_code' => 'sometimes'
            ]);

            $business = Business::find($rq->business_id);

            if ($business->status != 'assessment_released') {
                return response([
                    'message' => 'Invalid Business'
                ], 422);
            }

            if ($rq->business_code) {
                DB::table('businesses')->where('id', $rq->business_id)
                    ->update([
                        'business_code' => strtoupper($rq->business_code)
                    ]);
            }

            $user = Auth::user();
            $stage = Stage::where('name', 'complete_received')->first(['id', 'name']);

            $business_stage = new BusinessStage();
            $business_stage->user_id = $user->id;
            $business_stage->business_id = $rq->business_id;
            $business_stage->stage_id = $stage->id;
            $business_stage->save();

            DB::table('businesses')->where('id', $rq->business_id)
                ->update([
                    'status' => 'complete_received'
                ]);

            DB::commit();
            return response([
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response(['message' => $th->getMessage()], 500);
        }
    }

    public function finalRelease(Request $rq)
    {
        DB::beginTransaction();
        try {

            request()->validate([
                'business_id' => 'required',
                'business_code' => 'sometimes',
                'business_permit' => 'sometimes',
                'plate_no' => 'sometimes',
                "receiver_name" => 'required',
                'receiver_signature' => 'required',
                'receiver_picture' => 'required',
                'receiver_relationship_to_owner' => 'sometimes',
                'receiver_phone_no' => 'required',
                'receiver_id_no' => 'required',
                'receiver_id_type' => "required",
                'receiver_other_id_type' => 'sometimes',
                'receiver_email' => 'sometimes'
            ]);

            $business = Business::find($rq->business_id);

            if (
                $business->status != 'final_release_printed'
                // || $business->status!='final_released' this for the re-issue
            ) {
                return response([
                    'message' => 'Invalid Business',
                ], 422);
            }
            $permit_receiver = new PermitReceiver();
            $permit_receiver->business_id = $rq->business_id;
            $permit_receiver->receiver_phone_no = $rq->receiver_phone_no;
            $permit_receiver->id_type_id = $rq->receiver_id_type;
            $permit_receiver->receiver_id_no = $rq->receiver_id_no;
            $permit_receiver->receiver_email = $rq->receiver_email;
            $permit_receiver->receiver_name = $rq->receiver_name;
            $permit_receiver->receiver_relationship_to_owner = $rq->receiver_relationship_to_owner;
            if ($rq->receiver_signature) {
                $signatureData = preg_replace('#^data:image/\w+;base64,#i', '', $rq->receiver_signature);
                $signatureDecoded = base64_decode($signatureData);
                $signatureFileName = uniqid() . '.jpeg';
                //store privately and encrypted image 
                $encrypted = Crypt::encrypt($signatureDecoded);
                Storage::disk('local')->put('private/' . $signatureFileName, $encrypted);
                //public storage
                // Storage::disk('public')->put($signatureFileName, $signatureDecoded); 
                $permit_receiver->receiver_signature = $signatureFileName;
            }

            if ($rq->receiver_picture) {
                $pictureData = preg_replace('#^data:image/\w+;base64,#i', '', $rq->receiver_picture);
                $pictureDecoded = base64_decode($pictureData);
                $pictureFileName = uniqid() . '.jpeg';
                //store privately and encrypted image
                $encrypted = Crypt::encrypt($pictureDecoded);
                Storage::disk('local')->put('private/' . $pictureFileName, $encrypted);
                //public storage
                // Storage::disk('public')->put($pictureFileName, $pictureDecoded); 
                $permit_receiver->receiver_photo = $pictureFileName;
            }
            $permit_receiver->save();

            DB::commit();

            if ($rq->receiver_id_type === 22) {
                $other = new IdTypeOther();
                $other->permit_receiver_id = $permit_receiver->id;
                $other->name = $rq->receiver_other_id_type;
                $other->save();
            }

            $user = Auth::user();
            $stage = Stage::where('name', 'final_released')->first(['id', 'name']);
            $business_stage = new BusinessStage();
            $business_stage->user_id = $user->id;
            $business_stage->business_id = $rq->business_id;
            $business_stage->stage_id = $stage->id;
            $business_stage->save();

            $updateData = [
                'status' => 'final_released',
            ];
            if (!is_null($rq->business_permit) && $rq->business_permit !== '') {
                $updateData['business_permit'] = strtoupper($rq->business_permit);
            }
            if (!is_null($rq->business_code) && $rq->business_code !== '') {
                $updateData['business_code'] = strtoupper($rq->business_code);
            }
            if (!is_null($rq->plate_no) && $rq->plate_no !== '') {
                $updateData['plate_no'] = strtoupper($rq->plate_no);
            }
            DB::table('businesses')->where('id', $rq->business_id)
                ->update($updateData);

            DB::commit();
            return response([
                'message' => 'success'
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response(['message' => $th->getMessage()], 500);
        }
    }


    public function getReceiverImages(Request $rq)
    {

        $receiver = PermitReceiver::find($rq->id);
        if (Storage::disk('local')->exists($receiver->receiver_signature) && Storage::disk("local")->exists($receiver->receiver_photo)) {
            $signature = Storage::get($receiver->receiver_signature);
            $photo = Storage::get($receiver->receiver_photo);
            $decryptedSignature = base64_encode(Crypt::decrypt($signature));
            $decryptedPhoto = base64_encode(Crypt::decrypt($photo));

            return response([
                "signature" => "data:image/png;base64," . $decryptedSignature,
                "signature" => "data:image/png;base64," . $decryptedSignature,
                'photo' => "data:image/png;base64," . $decryptedPhoto
            ], 200);
        }
        return response([
            'message' => "No photo and signature found"
        ], 404);
    }
    public function logout(Request $request)
    {

        $res = request()->user()->currentAccessToken()->delete();
        if ($res == 1) {
            return response([
                'message' => 'Logged Out Successfully',
                'status' => 200
            ]);
        }
    }
    public function editStageTimeStamp(Request $rq)
    {
        DB::beginTransaction();
        try {
            $rq->validate([
                'business_stage_id' => 'required',
                'updated_time' => 'required'
            ]);

            $business_stage = BusinessStage::where('id', $rq->business_stage_id)->first();
            if (!$business_stage) {
                return response(['message' => 'Business stage does not exist', 'status' => 404], 404);
            }
            // $business_stage->update(['created_at', $rq->updated_time]);

            $business_stage->created_at = $rq->updated_time;
            $business_stage->save();

            DB::commit();
            return response(['message' => 'success'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage(), 'status' => 500], 500);
        }
    }
    public function deleteUser($id)
    {
        DB::beginTransaction();
        try {
            $user_to_delete = User::where('id', $id)->first();
            if (!$user_to_delete || $user_to_delete->is_deleted == 1) {
                return response()->json(['message' => "User doesn't exist in the list."], 500);
            }
            $user_to_delete->is_deleted = 1;
            $user_to_delete->save();
            DB::commit();
            return response()->json(['message' => "User deleted successfully"], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message', $e->getMessage()], 500);
        }
    }
}
