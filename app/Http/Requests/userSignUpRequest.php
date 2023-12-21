<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class userSignUpRequest extends FormRequest
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
            'email' => 'required|max:50|email|unique:users',
            'password'  => 'required|min:6|regex:/^(?=.{6,}$)(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&+=]).*$/',
            'current_time' => 'required|date_format:Y-m-d H:i:s',
            'platform'      => [
                'required',
                'string',
                Rule::in(['android', 'ios']),
            ],
        ];
    }

    /**
     * Get the validation messages that apply to request
     * @return array
     */
    public function messages()
    {
        return [
            'email.required'    => 'Required fields cannot be left empty',
            'email.email'       => 'Please enter a valid email',
            'password.required' => 'Required fields cannot be left empty',
            'password.regex'    => 'Password length must be atleast 6 char and must include an upper case,lowercase,special character and number',
            'current_time.required' => 'Required fields cannot be left empty',
            'platform'          => 'Required fields cannot be left empty',
            'platform.rule'     => 'Please enter a valid platform'
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
