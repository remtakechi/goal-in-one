<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    /**
     * リクエストの認可判定
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * バリデーションルールを取得
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:simple,recurring,deadline',
            'recurrence_type' => 'nullable|required_if:type,recurring|in:daily,weekly,monthly',
            'due_date' => 'nullable|required_if:type,deadline|date',
            'status' => 'sometimes|required|in:pending,completed',
        ];
    }

    /**
     * カスタムエラーメッセージを取得
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'type.required' => 'タスクタイプは必須です。',
            'type.in' => 'タスクタイプは有効な値を選択してください。',
            'recurrence_type.required_if' => '繰り返しタスクの場合は、繰り返しタイプを選択してください。',
            'recurrence_type.in' => '繰り返しタイプは有効な値を選択してください。',
            'due_date.required_if' => '期限付きタスクの場合は、期限日を設定してください。',
            'due_date.date' => '期限日は有効な日付形式で入力してください。',
            'status.required' => 'ステータスは必須です。',
            'status.in' => 'ステータスは有効な値を選択してください。',
        ];
    }
}


