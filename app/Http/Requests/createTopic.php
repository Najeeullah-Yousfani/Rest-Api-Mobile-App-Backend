<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class createTopic extends FormRequest
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
            'name' => 'required|string|max:25|unique:default_topics',
            'is_age_limit'      => [
                'required',
                'string',
                Rule::in(['true', 'false']),
            ],
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
            '*.required'            => 'Required fields cannot be left empty',
            'is_age_limit.rule'     => 'Please enter a valid option'
        ];
    }

    /**
     * Get the custom attributes for validator errors
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'Topic Name'
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
