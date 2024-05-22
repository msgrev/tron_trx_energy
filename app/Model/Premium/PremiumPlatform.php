<?php

declare (strict_types=1);
namespace App\Model\Premium;
use Hyperf\DbConnection\Model\Model;

/**
 */
class PremiumPlatform extends Model
{


    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'premium_platform';

    public $primaryKey  = 'rid';

    public $keyType  = 'int';

    public $timestamps  = false;

    public $incrementing  = true;



    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

}