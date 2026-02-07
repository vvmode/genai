<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public verification endpoint - no authentication required
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'document' => 'required_without_all:document_id,verification_code|file|mimes:pdf|max:10240',
            'document_id' => 'required_without_all:document,verification_code|string|regex:/^0x[a-fA-F0-9]{64}$/',
            'verification_code' => 'required_without_all:document,document_id|string|uuid',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'document.required_without_all' => 'Please provide a document file, document ID, or verification code.',
            'document_id.regex' => 'Invalid document ID format.',
            'verification_code.uuid' => 'Invalid verification code format.',
        ];
    }
}
