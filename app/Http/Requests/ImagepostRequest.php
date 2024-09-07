<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class ImagepostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        if(request()->isMethod('post')) {
            return [
              //  'name' => 'required|string|max:5',
               // 'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                //'description' => 'required|string'

                'curiculum_id' => 'required|exists:strandcuriculum,id',
                'strand_id' => 'required|exists:tblstrand,id',
                'section_id' => 'required|exists:tblsection,id',
                'subject_id' => 'required|exists:tblsubject,id',
                'year_id' => 'required|exists:tblyear,id',
                'semester' => 'required|string|max:255',
                'class_desc' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'gen_code' => 'required|string|max:255',
            ];
        } else {
            return [
                //'name' => 'required|string|max:258',
               // 'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
              //  'description' => 'required|string'
              'curiculum_id' => 'required|exists:strandcuriculum,id',
              'strand_id' => 'required|exists:tblstrand,id',
              'section_id' => 'required|exists:tblsection,id',
              'subject_id' => 'required|exists:tblsubject,id',
              'year_id' => 'required|exists:tblyear,id',
              'semester' => 'required|string|max:255',
              'class_desc' => 'nullable|string',
              'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
              'gen_code' => 'required|string|max:255',
            ];
        }
    }
 
    /**
     * Custom message for validation
     *
     * @return array
     */
    public function messages()
    {
        if(request()->isMethod('post')) {
            return [
                'name.required' => 'Name is required!',
                'image.required' => 'Image is required!',
                'description.required' => 'Descritpion is required!'
            ];
        } else {
            return [
                'name.required' => 'Name is required!',
                'description.required' => 'Descritpion is required!'
            ];   
        }
    }
}
