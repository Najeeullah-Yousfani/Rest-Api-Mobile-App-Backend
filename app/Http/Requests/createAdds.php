<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class createAdds extends FormRequest
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
            'countries'        =>  'required|array',
            'gender'            =>  [
                'required',
                'array',
                Rule::in(['male', 'female','non-binary','prefer not to say'])
            ],
            'topic_ids'         =>  'required|array',
            'action'            =>   [
                "required",
                "string",
                Rule::in(['clickable', 'swapable'])
            ],
            'type'              =>  [
                'required',
                'string',
                Rule::in(['video', 'image'])
            ],
            'save_as'              =>  [
                'required',
                'string',
                Rule::in(['publish', 'draft'])
            ],
            'advertisement_category' =>  [
                'required',
                'string',
                Rule::in(['basic', 'premium'])
            ],
            'primary_file'              =>  'required|file|max:50000|mimes:jpg,png,jpeg,mp4,mov',
            'primary_url'               =>  'nullable|url',
            'secondary_file'            =>  'nullable|file|max:50000|mimes:jpg,png,jpeg,mp4,mov',
            'secondary_file_url'        =>  'nullable|url',
            //|mimetypes:video/x-ms-asf,video/x-flv,video/mp4,application/x-mpegURL,video/MP2T,video/3gpp,video/quicktime,video/x-msvideo,video/x-ms-wmv,video/avi',
            'current_time'      => 'required|date_format:Y-m-d H:i:s'
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
            'type.Rule'     =>  'Please enter a valid type'
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
