<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BusinessScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (session()->has('current_business_id')) {
            $builder->where(
                $model->getTable().'.business_id',
                session('current_business_id')
            );
        }
    }
}
