<?php

/**
 * Configure App specific behavior for 
 * Maestrano SSO
 */
class MnoSsoUser extends MnoSsoBaseUser
{
  /**
   * Database connection
   * @var PDO
   */
  public $connection = null;
  
  /**
   * Company ID
   * @var integer
   */
  public $company_id = null;
  
  /**
   * Company ID
   * @var integer
   */
  public $user_table = null;
  
  /**
   * Extend constructor to inialize app specific objects
   *
   * @param OneLogin_Saml_Response $saml_response
   *   A SamlResponse object from Maestrano containing details
   *   about the user being authenticated
   */
  public function __construct(OneLogin_Saml_Response $saml_response, &$session = array(), $opts = array())
  {
    // Call Parent
    parent::__construct($saml_response,$session);
    
    // Assign new attributes
    $this->connection = $opts['db_connection'];
    
    # Below is 0 if $opts['company_id'] is null
    # which is the default company
    $this->company_id = intval($opts['company_id']); 
    
    // Set the user table
    global $db_connections;
    $this->user_table = $db_connections[$this->company_id]['tbpref'] . 'users';
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  protected function setInSession()
  {
    // Set language if not set already
    get_text_init();
    if (!isset($this->session["language"])) {
      $this->session["language"] = new language('English','C','iso-8859-1','ltr');
    }
    $this->session["wa_current_user"] = new current_user();
    $this->session["wa_current_user"]->simpleLoginWithoutPassword($this->company_id,$this->local_id);
    $this->session["wa_current_user"]->ui_mode = 0;
    $this->session["wa_current_user"]->last_act = time();
    $this->session["wa_current_user"]->timeout = null;
    $this->session['IPaddress'] = $_SERVER['REMOTE_ADDR'];
		$this->session['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
    
    return true;
  }
  
  
  /**
   * Used by createLocalUserOrDenyAccess to create a local user 
   * based on the sso user.
   * If the method returns null then access is denied
   *
   * @return the ID of the user created, null otherwise
   */
  protected function createLocalUser()
  {
    $lid = null;
    
    if ($this->accessScope() == 'private') {
      // Prepare variables (must pass references to bind_param)
      $full_name = "$this->name $this->surname";
      $password = $this->generatePassword();
      $phone = "";
      $roleId = $this->getRole();
      $language = "en_US";
      $pos = 1;
      $profile = "1";
      $rep = 1;
      $theme = 'dynamic';
      
      // Prepare query
      $sql = "INSERT INTO {$this->user_table} (user_id, real_name, password, phone, email, role_id, language, pos, print_profile, rep_popup, theme)
    		VALUES (?,?,?,?,?,?,?,?,?,?,?)";
    	$stmt = $this->connection->prepare($sql);
    	$stmt->bind_param("sssssisisis", 
    	  $this->uid,
    	  $full_name,
    	  $password,
    	  $phone,
    	  $this->email,
    	  $roleId,
    	  $language,
    	  $pos,
    	  $profile,
    	  $rep,
          $theme);
    	
    	// Execute statement and get id
    	$stmt->execute();
    	$lid = $this->connection->insert_id;
    	$stmt->close();
    }
    
    return $lid;
  }
  
  /**
   * Get the role to give to the user based on context
   *
   * @return the ID of the role
   */
  public function getRole() {
    $role_id = 2; // User
    
    if ($this->app_owner) {
      $role_id = 2; // Admin
    } else {
      foreach ($this->organizations as $organization) {
        if ($organization['role'] == 'Admin' || $organization['role'] == 'Super Admin') {
          $role_id = 2; // accountant
        } else {
          $role_id = 9;
        }
      }
    }
    
    return $role_id;
  }
  
  /**
   * Get the ID of a local user via Maestrano UID lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByUid()
  {
    $param = $this->connection->real_escape_string($this->uid);
    $result = $this->connection->query("SELECT id FROM {$this->user_table} WHERE mno_uid = '$param' LIMIT 1")->fetch_array();
    
    if ($result && $result['id']) {
      return $result['id'];
    }
    
    return null;
  }
  
  /**
   * Get the ID of a local user via email lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function getLocalIdByEmail()
  {
    $param = $this->connection->real_escape_string($this->email);
    $result = $this->connection->query("SELECT id FROM {$this->user_table} WHERE email = '$param' LIMIT 1")->fetch_array();
    
    if ($result && $result['id']) {
      return $result['id'];
    }
    
    return null;
  }
  
  /**
   * Set all 'soft' details on the user (like name, surname, email)
   * Implementing this method is optional.
   *
   * @return boolean whether the user was synced or not
   */
   protected function syncLocalDetails()
   {
     if($this->local_id) {
      // Prepare variables (must pass references to bind_param)
      $fullname = "$this->name $this->surname";
      
      // Prepare query
      $sql = "UPDATE {$this->user_table} SET real_name = ?, email = ? WHERE id = ?";
     	$stmt = $this->connection->prepare($sql);
     	$stmt->bind_param("ssi", 
     	  $fullname,
     	  $this->email,
     	  $this->local_id);

     	// Execute statement
     	$stmt->execute();
     	$stmt->close();
     	
     	return $stmt;
     }
     
     return false;
   }
  
  /**
   * Set the Maestrano UID on a local user via id lookup
   *
   * @return a user ID if found, null otherwise
   */
  protected function setLocalUid()
  {
    if($this->local_id) {
      // Prepare query
      $sql = "UPDATE {$this->user_table} SET mno_uid = ? WHERE id = ?";
     	$stmt = $this->connection->prepare($sql);
     	$stmt->bind_param("si", 
     	  $this->uid,
     	  $this->local_id);

     	// Execute statement
     	$stmt->execute();
     	$stmt->close();
     	
     	return $stmt;
     }
     
     return false;
  }
}