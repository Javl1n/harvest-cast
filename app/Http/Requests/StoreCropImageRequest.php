<?php

namespace App\Http\Requests;

use App\Models\CropImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCropImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'schedule_id' => ['required', 'exists:schedules,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'image.required' => 'Please select an image to upload.',
            'image.image' => 'The file must be an image.',
            'image.max' => 'The image must not exceed 10MB.',
            'image.mimes' => 'The image must be a file of type: jpg, jpeg, png, webp.',
            'schedule_id.required' => 'Schedule ID is required.',
            'schedule_id.exists' => 'The selected schedule does not exist.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $scheduleId = $this->input('schedule_id');
            $today = now()->toDateString();

            $existingImage = CropImage::where('schedule_id', $scheduleId)
                ->whereDate('image_date', $today)
                ->exists();

            if ($existingImage) {
                $validator->errors()->add(
                    'image',
                    'An image has already been uploaded for this crop today. Only one image per day is allowed.'
                );
            }
        });
    }
}
