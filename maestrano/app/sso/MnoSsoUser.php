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
  }
  
  
  /**
   * Sign the user in the application. 
   * Parent method deals with putting the mno_uid, 
   * mno_session and mno_session_recheck in session.
   *
   * @return boolean whether the user was successfully set in session or not
   */
  // protected function setInSession()
  // {
  //   // First set $conn variable (need global variable?)
  //   $conn = $this->connection;
  //   
  //   $sel1 = $conn->query("SELECT ID,name,lastlogin FROM user WHERE ID = $this->local_id");
  //   $chk = $sel1->fetch();
  //   if ($chk["ID"] != "") {
  //       $now = time();
  //       
  //       // Set session
  //       $this->session['userid'] = $chk['ID'];
  //       $this->session['username'] = stripslashes($chk['name']);
  //       $this->session['lastlogin'] = $now;
  //       
  //       // Update last login timestamp
  //       $upd1 = $conn->query("UPDATE user SET lastlogin = '$now' WHERE ID = $this->local_id");
  //       
  //       return true;
  //   } else {
  //       return false;
  //   }
  // }
  
  
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
      
      // Prepare query
      $sql = "INSERT INTO users (user_id, real_name, password, phone, email, role_id, language, pos, print_profile, rep_popup)
    		VALUES (?,?,?,?,?,?,?,?,?,?)";
    	$stmt = $this->connection->prepare($sql);
    	$stmt->bind_param("sssssisisi", 
    	  $this->uid,
    	  $full_name,
    	  $password,
    	  $phone,
    	  $this->email,
    	  $roleId,
    	  $language,
    	  $pos,
    	  $profile,
    	  $rep);
    	
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
    $result = $this->connection->query("SELECT id FROM users WHERE mno_uid = '$param' LIMIT 1")->fetch_array();
    
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
    $result = $this->connection->query("SELECT id FROM users WHERE email = '$param' LIMIT 1")->fetch_array();
    
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
      $sql = "UPDATE users SET real_name = ?, email = ? WHERE id = ?";
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
      $sql = "UPDATE users SET mno_uid = ? WHERE id = ?";
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