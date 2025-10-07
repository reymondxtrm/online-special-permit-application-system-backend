<?php

namespace App\Rules\InitialReceive;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class CheckBusinessPermitRule implements Rule
{
    public $message;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        //
        $check_business_code = DB::table('businesses')->where('business_permit', $value)
        ->get();

        if (count($check_business_code)  != 0 ) {
            $this->message = 'Business Permit Already Exists';
            return false;
        }
        return true;
        

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
