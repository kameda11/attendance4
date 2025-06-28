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
            'clock_in_time' => 'nullable|date_format:H:i',
            'clock_out_time' => 'nullable|date_format:H:i',
            'break_start_time' => 'nullable|date_format:H:i',
            'break_end_time' => 'nullable|date_format:H:i',
            'break2_start_time' => 'nullable|date_format:H:i',
            'break2_end_time' => 'nullable|date_format:H:i',
            'status' => 'required|in:working,break,completed,not_working',
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
            'clock_in_time.date_format' => '出勤時間は HH:MM 形式で入力してください。',
            'clock_out_time.date_format' => '退勤時間は HH:MM 形式で入力してください。',
            'break_start_time.date_format' => '休憩開始時間は HH:MM 形式で入力してください。',
            'break_end_time.date_format' => '休憩終了時間は HH:MM 形式で入力してください。',
            'break2_start_time.date_format' => '休憩2開始時間は HH:MM 形式で入力してください。',
            'break2_end_time.date_format' => '休憩2終了時間は HH:MM 形式で入力してください。',
            'status.required' => 'ステータスは必須です。',
            'status.in' => 'ステータスは working, break, completed, not_working のいずれかを選択してください。',
            'date.required' => '日付は必須です。',
            'date.date' => '有効な日付を入力してください。',
            'notes.max' => '申請理由・備考は500文字以内で入力してください。',
        ];
    }
}
