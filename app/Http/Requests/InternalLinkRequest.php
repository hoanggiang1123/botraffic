<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class InternalLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected $table = 'internal_links';

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

        $anchor_text = $link = $keyword_id = '';

        switch ($id) {
            case '':
                $anchor_text = "bail|required";
                $link = "bail|required|url";
                $keyword_id = "bail|required";
                break;
            default:
                break;

        }

        return [
            'anchor_text' => $anchor_text,
            'link' => $link,
            'keyword_id' => $keyword_id
        ];
    }

    protected function failedValidation(Validator $validator) {

        throw new HttpResponseException(
            response()->json(['message' => $validator->messages()->first()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
