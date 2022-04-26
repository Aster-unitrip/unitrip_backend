<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Boolean implements Rule
{
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
        // 判斷必須是 "true" 或是 "false"
        if($value !== "true" || $value !== "false"){
            return response()->json(['error' => '欄位不是 "true" 或是 "false"']);
        }

       /*  if($attribute !== "string"){
            return response()->json(['error' => '欄位必須是string']);
        } */

    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '欄位不是 "true" 或是 "false" ';
    }
}
