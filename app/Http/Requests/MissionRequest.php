<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class MissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected $table = 'missions';

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
        $id = $this->id ? $this->id : '';

        $ip = $keyword_id = '';

        switch ($id) {
            case '':
                $ip = "bail|required";
                $keyword_id = "bail|required";
                break;
            default:
                break;

        }

        return [
            'ip' => $ip,
            'keyword_id' => $keyword_id
        ];
    }

    protected function failedValidation(Validator $validator) {

        throw new HttpResponseException(
            response()->json(['message' => $validator->messages()->first()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
