<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class changeUserPassword extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'old_password'  => 'required',
            'new_password'  => 'required|min:6|regex:/^(?=.{6,}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&+=]).*$/',
            'current_time'  => 'required|date_format:Y-m-d H:i:s',
        ];
    }

    /**
     * Get the validation messages that apply to request
     * @return array
     */
    public function messages()
    {
        return [
            '*.required'               =>  'Required fields cannot be left empty',
            'password.regex'    => 'Password length must be atleast 6 char and must include an upper case,lowercase,special character and number'
        ];
    }

    /**
     * Get the custom attributes for validator errors
     * @return array
     */
    public function attributes()
    {
        return [
            'email' => 'Email Address',
            'platform'  => 'Social Platform'
        ];
    }

    /**
     *
     * @return json
     */
    public function failedValidation(Validator $validator)
    {
        $error = [
            "status" => 400,
            "success" => false,
            "error" =>  $validator->errors()->first()
        ];

        throw new HttpResponseException(response()->json($error, 400));
    }
}
