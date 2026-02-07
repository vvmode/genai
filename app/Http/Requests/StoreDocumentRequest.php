<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Add authorization logic here if needed
        // For now, allow all authenticated users with sanctum
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
            // File validation
            'document' => 'required|file|mimes:pdf|max:10240', // Max 10MB

            // Document metadata
            'holder_name' => 'required|string|max:255',
            'holder_email' => 'required|email|max:255',
            'title' => 'required|string|max:255',
            'document_type' => 'required|string|in:certificate,experience_letter,transcript,legal_document,other',
            
            // Optional fields
            'expiry_date' => 'nullable|date|after:today',
            'metadata' => 'nullable|array',
            'metadata.institution_name' => 'nullable|string|max:255',
            'metadata.issue_date' => 'nullable|date',
            'metadata.certificate_number' => 'nullable|string|max:100',
            'metadata.course_name' => 'nullable|string|max:255',
            'metadata.grade' => 'nullable|string|max:50',
            'metadata.additional_info' => 'nullable|string|max:1000',
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
            'document.required' => 'Please upload a document file.',
            'document.mimes' => 'Document must be a PDF file.',
            'document.max' => 'Document size must not exceed 10MB.',
            'document_type.in' => 'Invalid document type. Must be one of: certificate, experience_letter, transcript, legal_document, other.',
        ];
    }
}
