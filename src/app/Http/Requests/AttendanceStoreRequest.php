<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceStoreRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'clock_in_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'clock_out_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break1_start_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break1_end_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break2_start_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'break2_end_time' => 'nullable|regex:/^[0-9]{1,2}:[0-9]{2}$/',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'clock_in_time.regex' => '出勤時間は HH:MM 形式で入力してください。',
            'clock_out_time.regex' => '退勤時間は HH:MM 形式で入力してください。',
            'break1_start_time.regex' => '休憩1開始時間は HH:MM 形式で入力してください。',
            'break1_end_time.regex' => '休憩1終了時間は HH:MM 形式で入力してください。',
            'break2_start_time.regex' => '休憩2開始時間は HH:MM 形式で入力してください。',
            'break2_end_time.regex' => '休憩2終了時間は HH:MM 形式で入力してください。',
            'date.required' => '日付は必須です。',
            'date.date' => '有効な日付を入力してください。',
            'notes.max' => '申請理由・備考は500文字以内で入力してください。',
        ];
    }
}
