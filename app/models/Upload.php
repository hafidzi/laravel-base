<?php

class Upload extends Ardent {

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

  /**
   * Fillable columns
   */
  protected $fillable = [
    'name',
    'size',
    'url',
    'path',
    'type',

  ];

  /**
   * These attributes excluded from the model's JSON form.
   * @var array
   */
  protected $hidden = [
    // 'password'
  ];

  /**
   * Validation Rules
   */
  private static $_rules = [
    'store' => [
      'name' => 'required',
      'size' => 'required',
      'url' => 'required',
      'path' => 'required',
      'type' => 'required',

    ],
    'update' => [
      'name' => 'required',
      'size' => 'required',
      'url' => 'required',
      'path' => 'required',
      'type' => 'required',

    ]
  ];

  public static $rules = [];

  public static function setRules($name)
  {
    self::$rules = self::$_rules[$name];
  }

  /**
   * ACL
   */

  public static function canList() {
    return (Auth::user() && Auth::user()->ability(['Admin', 'Upload Admin'], ['Upload:list']));
  }

  public static function canCreate() {
    return (Auth::user() && Auth::user()->ability(['Admin', 'Upload Admin'], ['Upload:create']));
  }

  public function canShow()
  {
    $user = Auth::user();
    if($user->hasRole('Admin', 'Upload Admin'))
      return true;
    if(isset($this->user_id) && $user->can('Upload:show')) {
      if($this->user_id === $user->id) {
        return true;
      }
      if($user->is_authorized(static::$show_authorize_flag, $this->user_id)) {
        return true;
      }
      return false;
    }
    return true;
  }

  public function canUpdate() {
    $user = Auth::user();
    if($user->hasRole('Admin', 'Upload Admin'))
      return true;
    if(isset($this->user_id) && $user->can('Upload:edit')) {
      if($this->user_id === $user->id) {
        return true;
      }
      if($user->is_authorized(static::$update_authorize_flag, $this->user_id)) {
        return true;
      }
      return false;
    }
    return true;
  }

  public function canDelete() {
    $user = Auth::user();
    if($user->hasRole('Admin', 'Upload Admin'))
      return true;
    if(isset($this->user_id) && $user->can('Upload:update')) {
      if($this->user_id === $user->id) {
        return true;
      }
      if($user->is_authorized(static::$delete_authorize_flag, $this->user_id)) {
        return true;
      }
      return false;
    }
    return true;
  }

  /**
   * Decorators
   */
  
  public function getNameAttribute($value)
  {
    return $value;
  }

  /**
   * Relationships
   */
  
  public function uploadable()
  {
      return $this->morphTo();
  }

  /**
   * Boot Method
   */

  public static function boot()
  {
    parent::boot();

    static::created(function(){
      Cache::tags('Upload')->flush();
    });

    static::updated(function(){
      Cache::tags('Upload')->flush();
    });

    static::deleted(function(){
      Cache::tags('Upload')->flush();
    });
  }

}