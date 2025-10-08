<?php

namespace App\Http\Controllers;

use App\Events\DocumentStageMoved;
use App\Models\User;
use App\Models\UploadedFile;
use Illuminate\Http\Request;
use App\Models\SpecialPermitType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\SpecialPermitApplication;
use App\Http\Requests\SpecialPermitRequest;
use App\Models\ApplicationPurpose;
use App\Models\PermitApplicationDiscount;
use App\Models\PermitApplicationExemption;
use App\Models\SpecialPermitStatus;
use App\Models\StatusHistory;
use App\Models\UserAddress;
use Carbon\Carbon;

class SpecialPermitController extends Controller
{
    //

    private function count($doc_type_id, $status_id)
    {
        $count = SpecialPermitApplication::where('special_permit_type_id', $doc_type_id)->where('special_permit_status_id', $status_id)
            ->whereNull('mark_as_read')
            ->count();
        return $count;
    }

    public function addReferenceNo($prefix, $user_id)
    {
        $application_count = SpecialPermitApplication::whereYear('created_at', date('Y'))->count();

        $count = 0;

        if ($application_count == 0) {
            $count += 1;
        } else {
            $last_application_id = SpecialPermitApplication::whereYear('created_at', date('Y'))
                ->orderBy('id', 'DESC')
                ->first()->id;

            $count =  $last_application_id + 1;
        }
        // return $prefix.'-'.$user_id.'-'.date('ymdH').'-'.str_pad($count,6,0,STR_PAD_LEFT);
        return date('Y') . '-' . $prefix . '-' . 'ON' . '-' . str_pad($count, 5, 0, STR_PAD_LEFT);
    }

    public function mayorsPermit(SpecialPermitRequest $rq)
    {

        DB::beginTransaction();
        try {

            $permit_type = SpecialPermitType::where('code', 'mayors_permit')->first();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $user = Auth::user();

            // $reference_no = $this->addReferenceNo("CGM", $user->id);


            $address = UserAddress::where('user_id', $user->id)
                ->where('address_type', 'permanent')
                ->first();

            $purpose_id_object = json_decode($rq->purpose_id, true);
            $purpose_id = $purpose_id_object['value'];

            if ($purpose_id_object['value'] == 0) { // for others purpose
                $purpose = new ApplicationPurpose();
                $purpose->special_permit_type_id = $permit_type->id;
                $purpose->name = ucwords($rq->other_purpose);
                $purpose->type = "temporary";
                $purpose->save();

                $purpose_id = $purpose->id;
            }
            $mayorsPermit = new SpecialPermitApplication();
            $mayorsPermit->user_id = $user->id;
            $mayorsPermit->special_permit_type_id = $permit_type->id;
            $mayorsPermit->application_purpose_id = $purpose_id;
            $mayorsPermit->special_permit_status_id = $status->id;
            $mayorsPermit->user_address_id = $address->id;
            $mayorsPermit->save();

            $status_history = new StatusHistory();
            $status_history->user_id = $user->id;
            $status_history->special_permit_application_id = $mayorsPermit->id;
            $status_history->special_permit_status_id = $status->id;
            $status_history->save();

            $uploaded_file = new UploadedFile();
            $uploaded_file->special_permit_application_id = $mayorsPermit->id;
            $uploaded_file->police_clearance = $rq->file('police_clearance')->storeAs('uploaded_files/mayorsPermit/' . $mayorsPermit->id, date('YmdHi') . '-PoliceClearance-' . $mayorsPermit->id . '.jpg', 'public');
            $uploaded_file->community_tax_certificate = $rq->file('community_tax_certificate')->storeAs('uploaded_files/mayorsPermit/' . $mayorsPermit->id, date('YmdHi') . '-CommunityTax-' . $mayorsPermit->id . '.jpg', 'public');
            $uploaded_file->barangay_clearance = $rq->file('barangay_clearance')->storeAs('uploaded_files/mayorsPermit/' . $mayorsPermit->id, date('YmdHi') . '-BarangayClearance-' . $mayorsPermit->id . '.jpg', 'public');
            // $uploaded_file->official_receipt = $rq->file('official_receipt')->storeAs('uploaded_files/mayorsPermit/'.$mayorsPermit->id,date('YmdHi').'-OfficialReceipt-'.$mayorsPermit->id.'.jpg', 'public');
            $uploaded_file->fiscal_clearance = $rq->file('fiscal_clearance')->storeAs('uploaded_files/mayorsPermit/' . $mayorsPermit->id, date('YmdHi') . '-FiscalClearance-' . $mayorsPermit->id . '.jpg', 'public');
            $uploaded_file->court_clearance = $rq->file('court_clearance')->storeAs('uploaded_files/mayorsPermit/' . $mayorsPermit->id, date('YmdHi') . '-CourtClearance-' . $mayorsPermit->id . '.jpg', 'public');

            $uploaded_file->save();

            // $token = $user->createToken('dsfhjkshd$sdlkfjsdl@#$SADFDLfjsdkfjdsfsdf');

            // return $token->plainTextToken;
            $count = $this->count($permit_type->id, $status->id);
            broadcast(new DocumentStageMoved($permit_type->code, "pending", $count))->toOthers();
            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function goodMoral(SpecialPermitRequest $rq)
    {
        DB::beginTransaction();
        try {


            $permit_type = SpecialPermitType::where('code', 'good_moral')->first();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $user = Auth::user();

            // $reference_no = $this->addReferenceNo("CGM", $user->id);

            $address = UserAddress::where('user_id', $user->id)
                ->where('address_type', 'permanent')
                ->first();

            $purpose_id_object = json_decode($rq->purpose_id, true);
            $purpose_id = $purpose_id_object['value'];


            if ($purpose_id_object['value'] == 0) {
                $purpose = new ApplicationPurpose();
                $purpose->special_permit_type_id = $permit_type->id;
                $purpose->name = ucwords($rq->other_purpose);
                $purpose->type = "temporary";
                $purpose->save();

                $purpose_id = $purpose->id;
            }

            $goodMoral = new SpecialPermitApplication();
            $goodMoral->user_id = $user->id;
            $goodMoral->special_permit_type_id = $permit_type->id;
            $goodMoral->application_purpose_id = $purpose_id;
            $goodMoral->special_permit_status_id = $status->id;
            $goodMoral->user_address_id = $address->id;
            $goodMoral->save();


            if ($rq->exemption_id) {

                $exemption = new PermitApplicationExemption();
                $exemption->special_permit_application_id = $goodMoral->id;
                $exemption->exempted_case_id = $rq->exemption_id;
                $exemption->attachment = $rq->file('exemption_proof')->storeAs('exemption/applications/' . $goodMoral->id, date('YmdHi') . '-' . $permit_type->code . '.' . $rq->file('exemption_proof')->getClientOriginalExtension(), 'public');
                $exemption->status = 'pending';
                $exemption->save();
            }

            $status_history = new StatusHistory();
            $status_history->user_id = $user->id;
            $status_history->special_permit_application_id = $goodMoral->id;
            $status_history->special_permit_status_id = $status->id;
            $status_history->save();

            $uploaded_file = new UploadedFile();
            $uploaded_file->special_permit_application_id = $goodMoral->id;
            $uploaded_file->police_clearance = $rq->file('police_clearance')->storeAs('uploaded_files/goodMoral/' . $goodMoral->id, date('YmdHi') . '-PoliceClearance-' . $goodMoral->id . '.jpg', 'public');
            $uploaded_file->community_tax_certificate = $rq->file('community_tax_certificate')->storeAs('uploaded_files/goodMoral/' . $goodMoral->id, date('YmdHi') . '-CommunityTax-' . $goodMoral->id . '.jpg', 'public');
            $uploaded_file->barangay_clearance = $rq->file('barangay_clearance')->storeAs('uploaded_files/goodMoral/' . $goodMoral->id, date('YmdHi') . '-BarangayClearance-' . $goodMoral->id . '.jpg', 'public');
            // $uploaded_file->official_receipt = $rq->file('official_receipt')->storeAs('uploaded_files/goodMoral/'.$goodMoral->id,date('YmdHi').'-OfficialReceipt-'.$goodMoral->id.'.jpg', 'public');
            $uploaded_file->fiscal_clearance = $rq->file('fiscal_clearance')->storeAs('uploaded_files/goodMoral/' . $goodMoral->id, date('YmdHi') . '-FiscalClearance-' . $goodMoral->id . '.jpg', 'public');
            $uploaded_file->court_clearance = $rq->file('court_clearance')->storeAs('uploaded_files/goodMoral/' . $goodMoral->id, date('YmdHi') . '-CourtClearance-' . $goodMoral->id . '.jpg', 'public');
            $uploaded_file->save();
            $count = $this->count($permit_type->id, $status->id);
            broadcast(new DocumentStageMoved($permit_type->code, "pending", $count))->toOthers();


            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function event(SpecialPermitRequest $rq)
    {
        DB::beginTransaction();
        try {

            $permit_type = SpecialPermitType::where('code', 'event')->first();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $user = Auth::user();
            // $reference_no = $this->addReferenceNo("EV", $user->id);

            $address = UserAddress::where('user_id', $user->id)
                ->where('address_type', 'permanent')
                ->first();
            $permit_type = SpecialPermitType::where('code', 'event')->first();

            $event = new SpecialPermitApplication();
            // $event->special_permit_type_id = $permit_type->id;
            $event->user_id = $user->id;
            $event->special_permit_type_id = $permit_type->id;
            // $event->reference_no = $reference_no;
            $event->requestor_name = $rq->requestor_name;
            $event->event_name = $rq->event_name;
            $event->event_date_from = $rq->event_date_from;
            $event->event_date_to = $rq->event_date_to;
            $event->event_time_from = $rq->event_time_from;
            $event->event_time_to = $rq->event_time_to;
            $event->special_permit_status_id = $status->id;
            $event->user_address_id = $address->id;
            $event->event_type = $rq->event_type;
            $event->save();

            $status_history = new StatusHistory();
            $status_history->user_id = $user->id;
            $status_history->special_permit_application_id = $event->id;
            $status_history->special_permit_status_id = $status->id;
            $status_history->save();

            $uploaded_file = new UploadedFile();
            $uploaded_file->special_permit_application_id = $event->id;
            $uploaded_file->request_letter = $rq->file('request_letter')->storeAs('uploaded_files/event/' . $event->id, date('YmdHi') . '-RequestLetter-' . $event->id . '.jpg', 'public');
            $uploaded_file->sworn_statement = $rq->file('sworn_statement')->storeAs('uploaded_files/event/' . $event->id, date('YmdHi') . '-SwornStatement-' . $event->id . '.jpg', 'public');
            $uploaded_file->save();
            $count = $this->count($permit_type->id, $status->id);
            broadcast(new DocumentStageMoved($permit_type->code, "pending", $count))->toOthers();
            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function motorcade(SpecialPermitRequest $rq)
    {
        DB::beginTransaction();
        try {

            $permit_type = SpecialPermitType::where('code', 'motorcade')->first();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $user = Auth::user();

            // $reference_no = $this->addReferenceNo("MC", $user->id);

            $address = UserAddress::where('user_id', $user->id)
                ->where('address_type', 'permanent')
                ->first();

            $permit_type = SpecialPermitType::where('code', 'motorcade')->first();


            $motorcade = new SpecialPermitApplication();

            // $motorcade->special_permit_type_id = $permit_type->id;
            $motorcade->user_id = $user->id;
            $motorcade->special_permit_type_id = $permit_type->id;
            // $motorcade->reference_no = $reference_no;
            $motorcade->requestor_name = $rq->requestor_name;
            $motorcade->event_name = $rq->event_name;
            $motorcade->number_of_participants = $rq->number_of_participants;
            $motorcade->event_date_from = $rq->event_date_from;
            $motorcade->event_date_to = $rq->event_date_to;
            $motorcade->event_time_from = $rq->event_time_from;
            $motorcade->event_time_to = $rq->event_time_to;
            $motorcade->special_permit_status_id = $status->id;
            $motorcade->user_address_id = $address->id;
            $motorcade->save();

            $status_history = new StatusHistory();
            $status_history->user_id = $user->id;
            $status_history->special_permit_application_id = $motorcade->id;
            $status_history->special_permit_status_id = $status->id;
            $status_history->save();

            $uploaded_file = new UploadedFile();
            $uploaded_file->special_permit_application_id = $motorcade->id;
            $uploaded_file->request_letter = $rq->file('request_letter')->storeAs('uploaded_files/motorcade/' . $motorcade->id, date('YmdHi') . '-RequestLetter-' . $motorcade->id . '.jpg', 'public');
            $uploaded_file->route_plan = $rq->file('route_plan')->storeAs('uploaded_files/motorcade/' . $motorcade->id, date('YmdHi') . '-RoutePlan-' . $motorcade->id . '.jpg', 'public');
            // $uploaded_file->official_receipt = $rq->file('official_receipt')->storeAs('uploaded_files/motorcade/'.$motorcade->id,date('YmdHi').'-OfficialReceipt-'.$motorcade->id.'.jpg', 'public');
            $count = $this->count($permit_type->id, $status->id);
            broadcast(new DocumentStageMoved($permit_type->name, "pending", $count))->toOthers();
            $uploaded_file->save();

            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function parade(SpecialPermitRequest $rq)
    {
        DB::beginTransaction();
        try {

            $permit_type = SpecialPermitType::where('code', 'parade')->first();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $user = Auth::user();

            // $reference_no = $this->addReferenceNo("PR", $user->id);

            $address = UserAddress::where('user_id', $user->id)
                ->where('address_type', 'permanent')
                ->first();

            $permit_type = SpecialPermitType::where('code', 'parade')->first();

            $parade = new SpecialPermitApplication();

            // $parade->special_permit_type_id = $permit_type->id;
            $parade->user_id = $user->id;
            $parade->special_permit_type_id = $permit_type->id;
            // $parade->reference_no = $reference_no;
            $parade->requestor_name = $rq->requestor_name;
            $parade->event_name = $rq->event_name;
            $parade->event_date_from = $rq->event_date_from;
            $parade->event_date_to = $rq->event_date_to;
            $parade->event_time_from = $rq->event_time_from;
            $parade->event_time_to = $rq->event_time_to;
            $parade->special_permit_status_id = $status->id;
            $parade->user_address_id = $address->id;
            $parade->save();


            $status_history = new StatusHistory();
            $status_history->user_id = $user->id;
            $status_history->special_permit_application_id = $parade->id;
            $status_history->special_permit_status_id = $status->id;
            $status_history->save();

            $uploaded_file = new UploadedFile();
            $uploaded_file->special_permit_application_id = $parade->id;
            $uploaded_file->request_letter = $rq->file('request_letter')->storeAs('uploaded_files/parade/' . $parade->id, date('YmdHi') . '-RequestLetter-' . $parade->id . '.jpg', 'public');
            $uploaded_file->route_plan = $rq->file('route_plan')->storeAs('uploaded_files/parade/' . $parade->id, date('YmdHi') . '-RoutePlan-' . $parade->id . '.jpg', 'public');
            // $uploaded_file->official_receipt = $rq->file('official_receipt')->storeAs('uploaded_files/parade/'.$parade->id,date('YmdHi').'-OfficialReceipt-'.$parade->id.'.jpg', 'public');

            $uploaded_file->save();
            $count = $this->count($permit_type->id, $status->id);
            broadcast(new DocumentStageMoved($permit_type->code, "pending", $count))->toOthers();
            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function recorrida(SpecialPermitRequest $rq)
    {
        DB::beginTransaction();
        try {

            $permit_type = SpecialPermitType::where('code', 'recorrida')->first();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $user = Auth::user();

            // $reference_no = $this->addReferenceNo("RR", $user->id);

            $address = UserAddress::where('user_id', $user->id)
                ->where('address_type', 'permanent')
                ->first();

            $permit_type = SpecialPermitType::where('code', 'recorrida')->first();

            $recorrida = new SpecialPermitApplication();

            // $recorrida->special_permit_type_id = $permit_type->id;
            $recorrida->user_id = $user->id;
            $recorrida->special_permit_type_id = $permit_type->id;
            // $recorrida->reference_no = $reference_no;
            $recorrida->requestor_name = $rq->requestor_name;

            $recorrida->event_name = $rq->event_name;
            $recorrida->event_date_from = $rq->event_date_from;
            $recorrida->number_of_participants = $rq->number_of_participants;
            $recorrida->event_date_to = $rq->event_date_to;
            $recorrida->event_time_from = $rq->event_time_from;
            $recorrida->event_time_to = $rq->event_time_to;
            $recorrida->special_permit_status_id = $status->id;
            $recorrida->user_address_id = $address->id;
            $recorrida->save();

            $status_history = new StatusHistory();
            $status_history->user_id = $user->id;
            $status_history->special_permit_application_id = $recorrida->id;
            $status_history->special_permit_status_id = $status->id;
            $status_history->save();

            $uploaded_file = new UploadedFile();
            $uploaded_file->special_permit_application_id = $recorrida->id;
            $uploaded_file->request_letter = $rq->file('request_letter')->storeAs('uploaded_files/recorrida/' . $recorrida->id, date('YmdHi') . '-RequestLetter-' . $recorrida->id . '.jpg', 'public');
            $uploaded_file->route_plan = $rq->file('route_plan')->storeAs('uploaded_files/recorrida/' . $recorrida->id, date('YmdHi') . '-RoutePlan-' . $recorrida->id . '.jpg', 'public');
            // $uploaded_file->official_receipt = $rq->file('official_receipt')->storeAs('uploaded_files/recorrida/'.$recorrida->id,date('YmdHi').'-OfficialReceipt-'.$recorrida->id.'.jpg', 'public');
            $count = $this->count($permit_type->id, $status->id);
            broadcast(new DocumentStageMoved($permit_type->code, "pending", $count))->toOthers();
            $uploaded_file->save();

            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function useOfGovernmentProperty(SpecialPermitRequest $rq)
    {
        DB::beginTransaction();
        try {

            $permit_type = SpecialPermitType::where('code', 'use_of_government_property')->first();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $user = Auth::user();

            // $reference_no = $this->addReferenceNo("GP", $user->id);

            $address = UserAddress::where('user_id', $user->id)
                ->where('address_type', 'permanent')
                ->first();

            $permit_type = SpecialPermitType::where('code', 'use_of_government_property')->first();

            $useOfGovernmentProperty = new SpecialPermitApplication();

            // $useOfGovernmentProperty->special_permit_type_id = $permit_type->id;
            $useOfGovernmentProperty->user_id = $user->id;
            $useOfGovernmentProperty->special_permit_type_id = $permit_type->id;
            // $useOfGovernmentProperty->reference_no = $reference_no;
            $useOfGovernmentProperty->requestor_name = $rq->requestor_name;
            $useOfGovernmentProperty->name_of_property = $rq->name_of_property;
            $useOfGovernmentProperty->event_name = $rq->event_name;
            $useOfGovernmentProperty->event_date_from = $rq->event_date_from;
            $useOfGovernmentProperty->event_date_to = $rq->event_date_to;
            $useOfGovernmentProperty->event_time_from = $rq->event_time_from;
            $useOfGovernmentProperty->event_time_to = $rq->event_time_to;
            $useOfGovernmentProperty->special_permit_status_id = $status->id;
            $useOfGovernmentProperty->user_address_id = $address->id;
            $useOfGovernmentProperty->save();

            $status_history = new StatusHistory();
            $status_history->user_id = $user->id;
            $status_history->special_permit_application_id = $useOfGovernmentProperty->id;
            $status_history->special_permit_status_id = $status->id;
            $status_history->save();

            $uploaded_file = new UploadedFile();
            $uploaded_file->special_permit_application_id = $useOfGovernmentProperty->id;
            $uploaded_file->request_letter = $rq->file('request_letter')->storeAs('uploaded_files/useOfGovernmentProperty/' . $useOfGovernmentProperty->id, date('YmdHi') . '-RequestLetter-' . $useOfGovernmentProperty->id . '.jpg', 'public');
            $uploaded_file->route_plan = $rq->file('route_plan')->storeAs('uploaded_files/useOfGovernmentProperty/' . $useOfGovernmentProperty->id, date('YmdHi') . '-RoutePlan-' . $useOfGovernmentProperty->id . '.jpg', 'public');
            // $uploaded_file->official_receipt = $rq->file('official_receipt')->storeAs('uploaded_files/useOfGovernmentProperty/'.$useOfGovernmentProperty->id,date('YmdHi').'-OfficialReceipt-'.$useOfGovernmentProperty->id.'.jpg', 'public');

            $uploaded_file->save();

            DB::commit();
            return response([
                'message' => "success"
            ]);
            $count = $this->count($permit_type->id, $status->id);
            broadcast(new DocumentStageMoved($permit_type->code, "pending", $count))->toOthers();
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }

    public function occupationalPermit(SpecialPermitRequest $rq)
    {
        DB::beginTransaction();
        try {

            $permit_type = SpecialPermitType::where('code', 'occupational_permit')->first();
            $status = SpecialPermitStatus::where('code', 'pending')->first();
            $user = Auth::user();

            // $reference_no = $this->addReferenceNo("OP", $user->id);

            $address = UserAddress::where('user_id', $user->id)
                ->where('address_type', 'permanent')
                ->first();

            $permit_type = SpecialPermitType::where('code', 'occupational_permit')->first();

            $occupationalPermit = new SpecialPermitApplication();

            $occupationalPermit->user_id = $user->id;
            $occupationalPermit->special_permit_type_id = $permit_type->id;
            // $occupationalPermit->reference_no = $reference_no;
            $occupationalPermit->special_permit_status_id = $status->id;
            $occupationalPermit->user_address_id = $address->id;
            $occupationalPermit->save();

            $status_history = new StatusHistory();
            $status_history->user_id = $user->id;
            $status_history->special_permit_application_id = $occupationalPermit->id;
            $status_history->special_permit_status_id = $status->id;
            $status_history->save();

            $uploaded_file = new UploadedFile();
            $uploaded_file->special_permit_application_id = $occupationalPermit->id;
            $uploaded_file->certificate_of_employment = $rq->file('certificate_of_employment')->storeAs('uploaded_files/occupationalPermit/' . $occupationalPermit->id, date('YmdHi') . '-CertificateOfEmployment-' . $occupationalPermit->id . '.jpg', 'public');
            $uploaded_file->community_tax_certificate = $rq->file('community_tax_certificate')->storeAs('uploaded_files/occupationalPermit/' . $occupationalPermit->id, date('YmdHi') . '-CommunityTax-' . $occupationalPermit->id . '.jpg', 'public');
            $uploaded_file->id_picture = $rq->file('id_picture')->storeAs('uploaded_files/occupationalPermit/' . $occupationalPermit->id, date('YmdHi') . '-IdPicture-' . $occupationalPermit->id . '.jpg', 'public');
            $uploaded_file->health_certificate = $rq->file('health_certificate')->storeAs('uploaded_files/occupationalPermit/' . $occupationalPermit->id, date('YmdHi') . '-HealthCertificate-' . $occupationalPermit->id . '.jpg', 'public');
            $uploaded_file->training_certificate = $rq->file('training_certificate')->storeAs('uploaded_files/occupationalPermit/' . $occupationalPermit->id, date('YmdHi') . '-TrainingCertificate-' . $occupationalPermit->id . '.jpg', 'public');
            // $uploaded_file->official_receipt = $rq->file('official_receipt')->storeAs('uploaded_files/occupationalPermit/'.$occupationalPermit->id,date('YmdHi').'-OfficialReceipt-'.$occupationalPermit->id.'.jpg', 'public');

            $uploaded_file->save();
            $count = $this->count($permit_type->id, $status->id);
            broadcast(new DocumentStageMoved($permit_type->name, "pending", $count))->toOthers();
            DB::commit();
            return response([
                'message' => "success"
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response(['message' => $e->getMessage()], 500);
        }
    }


    public function viewUploadedFile(Request $rq)
    {
        request()->validate([
            'specialPermit_application_id' => 'required'
        ]);

        $uploadFile = UploadedFile::where('special_permit_application_id', $rq->specialPermit_application_id)->first();

        // $fileUrl = Storage::url('public/' . $uploadFile->police_clearance);

        $files = [
            'police_clearance' => storage_path('app/public/' . $uploadFile->police_clearance)
            // 'police_clearance' => storage_path('app/public/' . $uploadFile->police_clearance)
        ];

        // return $uploadFile;

        // return response()->json(['files' => $files]);
        return response()->file(storage_path('app/public/' . $uploadFile->police_clearance));
    }
    public function updateTabNotification(Request $rq)
    {
        $rq->validate([
            'permit_type' => 'required',
            'stage' => 'required',
            'permit_id' => 'required'

        ]);
        DB::beginTransaction();

        try {
            $permit = SpecialPermitApplication::where('id', $rq->permit_id)->first();
            $permit->mark_as_read = Carbon::now();
            $permit->save();
            $permit_type = SpecialPermitType::where('code', $rq->permit_type)->first('id');
            $permit_stage = SpecialPermitStatus::where('code', $rq->stage)->first('id');
            $count = $this->count($permit_type->id, $permit_stage->id);

            broadcast(new DocumentStageMoved($rq->permit_type, $rq->stage, $count))->toOthers();
            DB::commit();
            return response(['message' => "success"], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['message' => $e->getMessage()], 500);
        }
    }
}
