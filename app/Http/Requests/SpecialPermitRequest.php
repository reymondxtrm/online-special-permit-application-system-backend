<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SpecialPermitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'type' => 'required', // mayors_permit, good_moral, event, motorcade, parade, recorrida, use_of_government_prop, occupational_permit

            // 'permit_type_id' => 'required_if:type,mayors_permit,good_moral',
            'purpose_id' => 'required_if:type,mayors_permit,good_moral',
            // 'surname' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'first_name' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'middle_initial' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'suffix' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'sex' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'email' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'contact_no' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'date' => 'required_if:type,mayors_permit,good_moral',
            // 'province' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'city' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'barangay' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'additional_address' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'or_no' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',
            // 'paid_amount' => 'required_if:type,mayors_permit,good_moral,event,motorcade,parade,recorrida,use_of_government_prop',

            // 'name_of_employeer' => 'required_if:type,good_moral',

            'requestor_name' => 'required_unless:type,mayors_permit,good_moral,occupational_permit',
            'event_name' => 'required_unless:type,mayors_permit,good_moral,occupational_permit',
            'event_date_from' => 'required_unless:type,mayors_permit,good_moral,occupational_permit',
            // 'event_date_to' => 'required_unless:type,mayors_permit,good_moral,occupational_permit',
            'event_time_from' => 'required_unless:type,mayors_permit,good_moral,occupational_permit',

            // 'date_of_birth' => 'required_if:type,occupational_permit',
            // 'place_of_birth' => 'required_if:type,occupational_permit',
            // 'civil_status' => 'required_if:type,occupational_permit',
            // 'educational_attainment' => 'required_if:type,occupational_permit',
            // 'occupation' => 'required_if:type,occupational_permit',

            'discount_id' => 'sometimes|nullable', // Allows discount_id to be empty
            'attachment' => 'required_if:discount_id,!null|image|mimes:jpeg,png,jpg', // Requires attachment only if discount_id is not null or empty

            // files / images
            'police_clearance' => 'required_if:type,mayors_permit,good_moral|image|mimes:jpeg,png,jpg',
            'community_tax_certificate' => 'required_if:type,mayors_permit,good_moral|image|mimes:jpeg,png,jpg',
            'barangay_clearance' => 'required_if:type,mayors_permit,good_moral|image|mimes:jpeg,png,jpg',
            // 'official_receipt' => 'required_if:type,mayors_permit,good_moral|image|mimes:jpeg,png,jpg',

            'fiscal_clearance' => 'required_if:type,mayors_permit,good_moral|image|mimes:jpeg,png,jpg',
            'court_clearance' => 'required_if:type,mayors_permit,good_moral|image|mimes:jpeg,png,jpg',

            'request_letter' => 'required_if:type,event,motorcade,parade,recorrida,use_of_government_prop|image|mimes:jpeg,png,jpg',

            'route_plan' => 'required_if:type,motorcade,parade,recorrida,use_of_government_prop|image|mimes:jpeg,png,jpg',

            'certificate_of_employment' => 'required_if:type,occupational_permit|image|mimes:jpeg,png,jpg',
            'id_picture' => 'required_if:type,occupational_permit|image|mimes:jpeg,png,jpg',
            'health_certificate' => 'required_if:type,occupational_permit|image|mimes:jpeg,png,jpg',
            'training_certificate' => 'required_if:type,occupational_permit|image|mimes:jpeg,png,jpg',



            // 'surname' => 'required_unless:type,occupational_permit',
        ];
    }
}
