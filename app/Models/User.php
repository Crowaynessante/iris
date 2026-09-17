<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable {
    use Notifiable;
    protected $table='users'; public $timestamps=false;
    protected $fillable=['username','email','password','role']; protected $hidden=['password'];
    public function getAuthPassword(){ return $this->password; }
}
