<?php

namespace App\Http\Requests\Tasks;

use App\Http\Requests\CoreRequest;
use App\Setting;
use App\Task;
use Illuminate\Foundation\Http\FormRequest;

class StoreTask extends CoreRequest
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
     * @return array
     */
    public function rules()
    {
        $setting = global_setting();
        $user = auth()->user();
        $rules = [
            'heading' => 'required',
            'start_date' => 'required',
            'due_date' => 'required|date_format:"'.$setting->date_format.'"|after:start_date',
            'priority' => 'required'
        ];

        if($this->has('dependent') && $this->dependent == 'yes' && $this->dependent_task_id != '')
        {
            $dependentTask = Task::find($this->dependent_task_id);

            $rules['start_date'] = 'required|date_format:"'.$setting->date_format.'"|after:"'.$dependentTask->due_date->format($setting->date_format).'"';
        }

        if ($user->can('add_tasks') || $user->hasRole('admin')) {
            $rules['user_id'] = 'required';
        }

        if($this->has('repeat') && $this->repeat == 'yes')
        {
            $rules['repeat_cycles'] = 'required|numeric';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'project_id.required' => __('messages.chooseProject'),
            'user_id.required' => 'Choose an assignee'
        ];
    }
}
