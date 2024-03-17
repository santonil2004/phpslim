<?php
namespace App\Application\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected function finishSave(array $options):void
    {
        parent::finishSave($options);

        //dd($this);
    }
}