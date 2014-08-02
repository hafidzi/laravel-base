<?php

use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;
use Zizaco\Entrust\HasRole;
use Zizaco\Confide\ConfideUser;

class User extends ConfideUser implements UserInterface, RemindableInterface {

	use UserTrait, RemindableTrait, HasRole, UploadableTrait;

  /**
   * $show_authorize_flag
   * 0 => all
   * 1 => show mine only
   * 2 => if i'm a head of ou, show all under my ou
   * 3 => if i'm a head of ou, show all under my ou and other entries under his ou's children
   */
  static $show_authorize_flag = 0;

  /**
   * $update_authorize_flag
   * 0 => all
   * 1 => show mine only
   * 2 => if i'm a head of ou, show all under my ou
   * 3 => if i'm a head of ou, show all under my ou and other entries under his ou's children
   */
  static $update_authorize_flag = 0;

  /**
   * $delete_authorize_flag
   * 0 => all
   * 1 => show mine only
   * 2 => if i'm a head of ou, show all under my ou
   * 3 => if i'm a head of ou, show all under my ou and other entries under his ou's children
   */
  static $delete_authorize_flag = 0;

	protected $table = 'users';

  protected $fillable = [
    'username',
    'email',
    'password',
    'password_confirmation',
    'confirmation_code',
    'confirmed'
  ];

  public static $rules = array(
      'username' => 'required|alpha_dash|unique:users,username',
      'email' => 'required|email|unique:users,email',
      'password' => 'required|min:4|confirmed',
      'password_confirmation' => 'min:4',
  );

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password', 'remember_token');

  public function organizationunit()
  {
    return $this->belongsTo('OrganizationUnit', 'organizationunit_id');
  }

  public function get_authorized_userids($authorization_flag)
  {
    if($authorization_flag === 0)
      return [];
    if($authorization_flag === 1)
      return [$this->id];

    $key = implode('.', ['User', 'get_authorized_userids', $this->id, $authorization_flag]);
    $user = $this;
    return Cache::tags(['User', 'OrganizationUnit'])->rememberForever($key, function() use ($authorization_flag, $user) {
      $result = [$user->id];
      if($user->organizationunit->user_id === $user->id) { 
        if($authorization_flag == 2) {
          $result = $user->organizationunit->users->lists('id');
        }
        if($authorization_flag == 3) {
          $result = User::whereIn('organizationunit_id', $user->organizationunit->descendantsAndSelf()->get()->lists('id'))->lists('id');
        }
      }
      return $result;
    });
  }

  public function is_authorized($authorization_flag, $user_id)
  {
    if($authorization_flag == 0) {
      return true;
    }
    $users = get_authorized_userids($authorization_flag);
    return in_array($user_id, $users);
  }

  /**
   * ACL
   */

  public static function canList() {
    return (Auth::user() && Auth::user()->ability(['Admin', 'User Admin'], ['User:list']));
  }

  public static function canCreate() {
    return (Auth::user() && Auth::user()->ability(['Admin', 'User Admin'], ['User:create']));
  }

  public function canShow()
  {
    return (Auth::user() && Auth::user()->ability(['Admin', 'User Admin'], ['User:show']));
  }

  public function canUpdate() {
    return (Auth::user() && Auth::user()->ability(['Admin', 'User Admin'], ['User:edit']));
  }

  public function canDelete() {
    return (Auth::user() && Auth::user()->ability(['Admin', 'User Admin'], ['User:delete']));
  }

  public function canSetPassword() {
    return (Auth::user() && Auth::user()->ability(['Admin', 'User Admin'], ['User:set_password']));
  }

  public function canSetConfirmation() {
    return (Auth::user() && Auth::user()->ability(['Admin', 'User Admin'], ['User:set_confirmation']));
  }

  /**
   * Decorators
   */
  
  public function getConfirmedAttribute($value)
  {
    return ($value) ? 'Active' : 'Not Confirmed';
  }

  /**
   * Boot
   */

  public static function boot()
  {
    parent::boot();

    static::created(function(){
      Cache::tags('User')->flush();
    });

    static::updated(function(){
      Cache::tags('User')->flush();
    });

    static::deleted(function(){
      Cache::tags('User')->flush();
    });
  }

}
