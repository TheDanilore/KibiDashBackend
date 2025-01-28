<?php

namespace App\Traits;

use App\Models\Audit;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            Audit::create([
                'user_id_created' => auth()->id(),
                'model_type' => get_class($model),
                'model_id' => $model->id,
            ]);
        });

        static::updated(function ($model) {
            Audit::where('model_type', get_class($model))->where('model_id', $model->id)->update([
                'user_id_updated' => auth()->id(),
                'updated_at' => now(),
            ]);
        });
    }

    public function audit()
    {
        return $this->hasOne(Audit::class, 'model_id')->where('model_type', get_class($this));
    }
}
