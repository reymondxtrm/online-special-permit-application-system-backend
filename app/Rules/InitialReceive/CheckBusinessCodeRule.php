<?php

namespace App\Rules\InitialReceive;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\Rule;

class CheckBusinessCodeRule implements Rule
{
    public $message;

    protected $permitTypeId;
    /**
     * Create a new rule instance.
     *
     * @return void
     */

    
    public function __construct($permitTypeId = null)
    {
        $this->permitTypeId = $permitTypeId;
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
        // $check_business_code = DB::table('businesses')->where('business_code', $value)
        // ->first();

        // if ($check_business_code) {
        //     if ($check_business_code->business_code != null) {
        //         $this->message = 'Business Code Already Exists';
        //         return false;
        //     }
        // }

        // return true;
            $currentYear = date('Y');
        if ($this->permitTypeId == 1) {
              $check_business_code = DB::table('businesses')
            ->where('business_code', $value)
            ->where('year', $currentYear)
            ->first();

            if ($check_business_code) {
                if ($check_business_code->business_code != null) {
                     $this->message = 'The business already has an application this year.';
               
                    return false;
                }
            }
        }else{
              // If permit_type_id is 2, check for duplicate business codes
            $check_business_code = DB::table('businesses')
                        ->where('business_code', $value)
                        ->first();

                    if ($check_business_code) {
                        if ($check_business_code->business_code != null) {
                             $this->message = 'Business Code Already Exists';                                           
                            return false;
                        }
        }

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
