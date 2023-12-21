<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class userDetails extends FormRequest
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
            'user_id'       => 'required|integer',
            'profile_image' => 'nullable|image|max:10000|mimes:jpg,png,jpeg',
            'username'      => 'required|unique:users|max:30|string',
            'country_id'    => 'required|integer',
            'city'          => 'nullable|string',
            'gender'        => [
                'required',
                'string',
                Rule::in(['male', 'female','non-binary','prefer not to say']),
            ],
            'dob'           => 'required|date_format:Y-m-d',
            'bio_details'   => 'nullable|max:300|string',
            'current_time'  => 'required|date_format:Y-m-d H:i:s'
        ];
    }

    /**
     * Get the validation messages that apply to request
     * @return array
     */
    public function messages()
    {
        return [
            '*.required'    => 'Required fields cannot be left empty',
            'gender.rule'   => 'Please enter a valid gender'
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
