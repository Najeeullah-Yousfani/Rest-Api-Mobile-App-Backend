<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class resetPassword extends FormRequest
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
            'email' => 'required|email',
            'current_time' => 'required|date_format:Y-m-d H:i:s',
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
