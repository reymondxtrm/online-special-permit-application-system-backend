<?php

namespace App\Http\Requests;

use App\Rules\InitialReceive\CheckBusinessCodeRule;
use App\Rules\InitialReceive\CheckBusinessPermitRule;
use App\Rules\InitialReceive\CheckControlNoRule;
use App\Rules\InitialReceive\CheckPlateNoRule;
use Illuminate\Foundation\Http\FormRequest;

class InitialReceiveRequest extends FormRequest
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

        $permitTypeId = $this->input('permit_type_id'); // Get permit_type_id from the request

        return [
            //
            'permit_type_id' => 'required',
            'gender_type_id' => 'required',
            'business_code' => ['sometimes',  new CheckBusinessCodeRule($permitTypeId)],
            // 'control_no' => ['required', new CheckControlNoRule],
            'name' => 'required',
            'owner' => 'required',
            'control_no' => 'sometimes',
            'business_permit' => 'sometimes',
            'plate_no' => 'sometimes',
            // 'business_permit' => ['required', new CheckBusinessPermitRule],
            // 'plate_no' => ['required', new CheckPlateNoRule],
        ];
    }
}
