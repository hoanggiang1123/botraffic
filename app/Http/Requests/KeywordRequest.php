<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class KeywordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected $table = 'keywords';

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

        $name = $slug = $picutre = '';

        switch ($id) {
            case '':
                $name = "bail|required";
                $url = "bail|required|url";
                $picutre = "bail|required";
                break;
            default:
                $url = "bail|url";
                break;

        }

        return [
            'name' => $name,
            'url' => $url,
            'picutre' => $picutre
        ];
    }

    protected function failedValidation(Validator $validator) {

        throw new HttpResponseException(
            response()->json(['message' => $validator->messages()->first()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
