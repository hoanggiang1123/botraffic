<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected $table = 'transactions';

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

        $amount = $createdBy = '';

        switch ($id) {
            case '':
                $amount = "bail|required";
                // $createdBy = "bail|required|integer";
                break;
            default:
                break;

        }

        return [
            'amount' => $amount,
            'created_by' => $createdBy,
        ];
    }

    protected function failedValidation(Validator $validator) {

        throw new HttpResponseException(
            response()->json(['message' => $validator->messages()->first()], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
